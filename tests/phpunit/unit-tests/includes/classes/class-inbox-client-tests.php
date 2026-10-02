<?php

use Groundhogg\Admin\Settings\Incoming_Messages;
use Groundhogg\Classes\Inbound_Signature;
use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Inbox_Client;
use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see Inbox_Client setting up, changing and turning off the inbox at the relay
 * @see Inbox what the site keeps of it
 *
 * The relay is played by a filter on the HTTP requests. It keeps its own copy of the secret and checks signatures
 * with it, and it does what the real one does when it's asked to set up an inbox or change one, it calls
 * the endpoint of the site with a challenge, which is the real endpoint of the site.
 */
class Inbox_Client_Tests extends GH_UnitTestCase {

	const RELAY = 'https://relay.example.test';

	protected $run_id;

	/** @var array[] every request that the relay got: method, path, headers, body, data */
	protected $requests = [];

	/** what the relay has for the site */
	protected $relay = [];

	/** how the relay behaves: license, handshake, and what it answers to what */
	protected $behaviour = [];

	/** what the site had while the relay was calling it, to show that it's pending that it was accepted with */
	protected $during_handshake = [];

	public function setUp(): void {
		parent::setUp();

		$this->run_id           = uniqid();
		$this->requests         = [];
		$this->relay            = [];
		$this->during_handshake = [];
		$this->behaviour        = [ 'license' => 'valid', 'handshake' => 'ok', 'unreachable' => false, 'error' => null ];

		Inbox::clear();
		Inbound_Signature::ensure(); // a site that has the inbox has the secret
		delete_option( Inbox::TOKEN_KEY_OPTION );

		add_filter( 'groundhogg/inbox/api_url', fn() => self::RELAY );
		add_filter( 'groundhogg/inbox/license_key', fn( $key ) => $key ?: 'test-valid-license' );
		add_filter( 'pre_http_request', [ $this, 'relay' ], 10, 3 );
	}

	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'groundhogg/inbox/api_url' );
		remove_all_filters( 'groundhogg/inbox/license_key' );
		remove_all_filters( 'groundhogg/inbox/endpoint' );
		remove_all_filters( 'home_url' );
		remove_all_filters( 'groundhogg/message/reply_to_enabled' );
		Inbox::clear();
		delete_option( Inbox::TOKEN_KEY_OPTION );
		delete_option( Inbox::LAST_RECEIVED_OPTION );
		delete_option( Inbox::TERMS_OPTION );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	/* ---------------------------------------------------------------------
	 * the relay
	 * ------------------------------------------------------------------- */

	protected function respond( int $status, array $body = [], array $headers = [] ) {
		return [
			'headers'  => $headers,
			'body'     => wp_json_encode( $body ),
			'response' => [ 'code' => $status, 'message' => '' ],
			'cookies'  => [],
			'filename' => null,
		];
	}

	/**
	 * Whether a request was signed with a secret, the way the relay checks
	 *
	 * @return bool
	 */
	protected function signed_with( $header, string $body, string $secret ) {

		if ( ! preg_match( '/^t=(\d+),v1=([0-9a-f]{64})$/', (string) $header, $m ) || abs( time() - (int) $m[1] ) > 300 ) {
			return false;
		}

		return hash_equals( hash_hmac( 'sha256', $m[1] . '.' . $body, $secret ), $m[2] );
	}

	/**
	 * Call the endpoint of the site, the way that the relay does to check that it's the site
	 *
	 * @return WP_REST_Response
	 */
	protected function call_the_site( string $secret, array $payload ) {

		$body    = wp_json_encode( $payload );
		$request = new WP_REST_Request( 'POST', '/gh/v4/messages/inbound' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( Inbound_Signature::HEADER, Inbound_Signature::sign( $body, null, $secret ) );
		$request->set_body( $body );

		return rest_do_request( $request );
	}

	/**
	 * The handshake
	 *
	 * @return string|null the reason it failed, null if it went well
	 */
	protected function handshake( string $secret ) {

		$challenge = bin2hex( random_bytes( 16 ) );

		$this->during_handshake = [ 'secret' => Inbound_Signature::secret(), 'pending' => Inbound_Signature::pending() ];

		$response = $this->call_the_site( $secret, [ 'type' => 'verify', 'challenge' => $challenge ] );
		$data     = $response->get_data();

		if ( $response->get_status() !== 200 ) {
			return 'bad_status';
		}

		return ( $data['status'] ?? '' ) === 'verified' && ( $data['challenge'] ?? '' ) === $challenge ? null : 'challenge_mismatch';
	}

	/**
	 * What the relay does with a request
	 */
	public function relay( $preempt, $args, $url ) {

		if ( strpos( $url, self::RELAY ) !== 0 ) {
			return $preempt;
		}

		$path = substr( $url, strlen( self::RELAY ) );
		$body = (string) ( $args['body'] ?? '' );
		$data = json_decode( $body, true ) ?: [];

		$this->requests[] = [
			'method'  => $args['method'],
			'path'    => $path,
			'headers' => $args['headers'],
			'body'    => $body,
			'data'    => $data,
			'timeout' => $args['timeout'],
		];

		if ( $this->behaviour['unreachable'] ) {
			return new WP_Error( 'http_request_failed', 'cURL error 6: Could not resolve host' );
		}

		if ( $this->behaviour['error'] ) {
			return $this->respond( ...$this->behaviour['error'] );
		}

		$signature = $args['headers'][ Inbound_Signature::HEADER ] ?? '';

		if ( $args['method'] === 'POST' && $path === '/v1/inboxes' ) {

			if ( $this->behaviour['license'] !== 'valid' ) {
				return $this->respond( 403, [ 'code' => 'invalid_license' ] );
			}

			if ( $this->behaviour['handshake'] !== 'ok' ) {
				return $this->respond( 422, [ 'code' => 'handshake_failed', 'reason' => $this->behaviour['handshake'] ] );
			}

			if ( $reason = $this->handshake( $data['secret'] ) ) {
				return $this->respond( 422, [ 'code' => 'handshake_failed', 'reason' => $reason ] );
			}

			$existing       = ! empty( $this->relay );
			$this->relay    = [
				'inbox_id'      => $this->relay['inbox_id'] ?? 'inbox' . $this->run_id,
				'inbox_address' => $this->relay['inbox_address'] ?? 'i' . $this->run_id . '@groundhogg.email',
				'reply_address' => $this->relay['reply_address'] ?? 'r' . $this->run_id . '@groundhogg.email',
				'secret'        => $data['secret'],
				'endpoint'      => $data['endpoint'],
			];

			return $this->respond( $existing ? 200 : 201, array_intersect_key( $this->relay, array_flip( [ 'inbox_id', 'inbox_address', 'reply_address' ] ) ) );
		}

		if ( ! empty( $this->relay ) && $path === '/v1/inboxes/' . $this->relay['inbox_id'] ) {

			// changing or turning off an inbox is signed with the secret that the relay has
			if ( ! $this->signed_with( $signature, $body, $this->relay['secret'] ) ) {
				return $this->respond( 401, [ 'code' => 'invalid_signature' ] );
			}

			if ( $args['method'] === 'PATCH' ) {

				$secret = $data['secret'] ?? $this->relay['secret'];

				if ( $reason = $this->handshake( $secret ) ) {
					return $this->respond( 422, [ 'code' => 'handshake_failed', 'reason' => $reason ] );
				}

				$this->relay = array_merge( $this->relay, [ 'secret' => $secret, 'endpoint' => $data['endpoint'] ?? $this->relay['endpoint'] ] );

				return $this->respond( 200, [ 'inbox_id' => $this->relay['inbox_id'], 'status' => 'active' ] );
			}

			if ( $args['method'] === 'DELETE' ) {
				$id          = $this->relay['inbox_id'];
				$this->relay = [ 'disabled' => $id ];

				return $this->respond( 200, [ 'inbox_id' => $id, 'status' => 'disabled' ] );
			}
		}

		return $this->respond( 404, [ 'code' => 'not_found' ] );
	}

	/* ---------------------------------------------------------------------
	 * setting it up
	 * ------------------------------------------------------------------- */

	public function test_it_sets_up_the_inbox_and_keeps_what_the_relay_gives() {

		$this->assertTrue( Inbox_Client::enable() );

		$this->assertTrue( Inbox::is_provisioned() );
		$this->assertSame( 'inbox' . $this->run_id, Inbox::id() );
		$this->assertSame( 'i' . $this->run_id . '@groundhogg.email', Inbox::address() );
		$this->assertSame( 'r' . $this->run_id . '@groundhogg.email', Inbox::reply_address() );
		$this->assertTrue( Inbox::is_active() );
		$this->assertTrue( Inbox::endpoint_is_current() );
	}

	public function test_it_gives_the_relay_the_license_the_site_the_endpoint_and_a_secret() {

		Inbox_Client::enable();

		$this->assertCount( 1, $this->requests );

		[ 'method' => $method, 'path' => $path, 'data' => $data, 'timeout' => $timeout ] = $this->requests[0];

		$this->assertSame( 'POST', $method );
		$this->assertSame( '/v1/inboxes', $path );
		$this->assertSame( 'test-valid-license', $data['license_key'] );
		$this->assertSame( home_url(), $data['site_url'] ); // the one that the license is activated with
		$this->assertSame( rest_url( 'gh/v4/messages/inbound' ), $data['endpoint'] );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $data['secret'] );
		$this->assertGreaterThanOrEqual( 30, $timeout, 'the relay checks the license and then calls the site back' );
	}

	public function test_the_secret_is_the_one_that_was_given_to_the_relay_and_was_pending_while_the_relay_checked_it() {

		Inbox_Client::enable();

		$given = $this->requests[0]['data']['secret'];

		// while the relay called the site, the secret was pending, and that's what the request was accepted with
		$this->assertSame( $given, $this->during_handshake['pending'] );
		$this->assertNotSame( $given, $this->during_handshake['secret'] );

		// and now it's the secret
		$this->assertSame( $given, Inbound_Signature::secret() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	public function test_a_license_that_is_not_valid_sets_nothing_up_and_keeps_the_secret_that_the_site_had() {

		$before = Inbound_Signature::secret();

		$this->behaviour['license'] = 'invalid';

		$result = Inbox_Client::enable();

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_license', $result->get_error_code() );
		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertSame( $before, Inbound_Signature::secret() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	public function test_a_site_the_relay_can_not_get_an_answer_from_is_told_why() {

		$this->behaviour['handshake'] = 'redirect';

		$result = Inbox_Client::enable();

		$this->assertSame( 'handshake_failed', $result->get_error_code() );
		$this->assertSame( 'redirect', $result->get_error_data()['reason'] );
		$this->assertStringContainsString( 'redirected', $result->get_error_message() );
		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	/**
	 * The most common, actionable causes of a failed handshake - a local site that isn't publicly reachable, and
	 * password protection or a firewall standing in front of the site - are each named plainly and specifically,
	 * not left as one generic "could not reach the site" message.
	 */
	public function test_the_handshake_failure_message_names_the_likely_cause() {

		$cases = [
			'network'            => [ 'reached at all', 'local development site' ],
			'timeout'            => [ 'took too long', 'local development site' ],
			'bad_status'         => [ 'did not answer normally', 'password protection', 'firewall' ],
			'redirect'           => [ 'redirected the request' ],
			'challenge_mismatch' => [ 'not with what was expected' ],
			'invalid_response'   => [ 'not in the way that was expected' ],
			'response_too_large' => [ 'far more than expected' ],
		];

		foreach ( $cases as $reason => $expectedPhrases ) {

			$this->behaviour['handshake'] = $reason;

			$result = Inbox_Client::enable();

			$this->assertSame( 'handshake_failed', $result->get_error_code(), $reason );
			$this->assertSame( $reason, $result->get_error_data()['reason'], $reason );

			foreach ( $expectedPhrases as $phrase ) {
				$this->assertStringContainsString( $phrase, $result->get_error_message(), "$reason should mention \"$phrase\"" );
			}
		}
	}

	/**
	 * An endpoint that's rejected outright (never reaches the handshake) for being an internal/local address gets
	 * the same "local development site" explanation, since that's the same underlying problem.
	 */
	public function test_a_local_looking_endpoint_is_also_explained_in_terms_of_being_a_local_site() {

		$this->behaviour['error'] = [ 422, [ 'code' => 'invalid_endpoint', 'reason' => 'internal_host' ] ];

		$result = Inbox_Client::enable();

		$this->assertSame( 'invalid_endpoint', $result->get_error_code() );
		$this->assertStringContainsString( 'local development site', $result->get_error_message() );
	}

	public function test_the_errors_the_relay_gives_are_explained() {

		$cases = [
			[ 409, [ 'code' => 'site_url_in_use' ], 'site_url_in_use', 'different license' ],
			[ 422, [ 'code' => 'invalid_endpoint', 'reason' => 'not_https' ], 'invalid_endpoint', 'HTTPS' ],
			[ 429, [ 'code' => 'rate_limited' ], 'rate_limited', 'minute' ],
			[ 503, [ 'code' => 'license_unavailable' ], 'license_unavailable', 'minute' ],
			[ 500, [], 'error', '500' ],
		];

		foreach ( $cases as [ $status, $body, $code, $contains ] ) {
			$this->behaviour['error'] = [ $status, $body, [ 'retry-after' => '60' ] ];

			$result = Inbox_Client::enable();

			$this->assertSame( $code, $result->get_error_code(), $code );
			$this->assertStringContainsString( $contains, $result->get_error_message(), $code );
			$this->assertSame( $status, $result->get_error_data()['status'], $code );
			$this->assertSame( 60, $result->get_error_data()['retry_after'], $code );
			$this->assertSame( '', Inbound_Signature::pending(), $code );
		}
	}

	/**
	 * "The service" and "this site"/"the site" are ambiguous - which service, which site - so every message names
	 * Groundhogg (or its white label) and the site's own hostname explicitly instead.
	 */
	public function test_messages_name_groundhogg_and_the_site_instead_of_saying_the_service_or_this_site() {

		$hostname = \Groundhogg\get_hostname();

		// cases that used to say "this site"/"the site", and are expected to now name the hostname, and cases
		// that used to say "the service", now expected to name Groundhogg. rate_limited is neither - it was never
		// about the site, so there's nothing of its to name.
		$cases = [
			[ [ 'code' => 'invalid_license' ], $hostname, false ],
			[ [ 'code' => 'site_url_in_use' ], $hostname, false ],
			[ [ 'code' => 'invalid_endpoint', 'reason' => 'host_mismatch' ], $hostname, false ],
			[ [ 'code' => 'rate_limited' ], false, true ],
			[ [ 'code' => 'missing_signature' ], $hostname, true ],
			[ [ 'code' => 'expired_signature' ], $hostname, true ],
			[ [ 'code' => 'not_found' ], false, true ],
			[ [], false, true ], // the default, unrecognised code
		];

		foreach ( $cases as [ $body, $names_hostname, $names_groundhogg ] ) {

			$this->behaviour['error'] = [ 422, $body ];

			$message = Inbox_Client::enable()->get_error_message();
			$label   = wp_json_encode( $body );

			if ( $names_hostname ) {
				$this->assertStringContainsString( $hostname, $message, $label );
			}

			$this->assertSame( $names_groundhogg, str_contains( $message, 'Groundhogg' ), $label );
			$this->assertStringNotContainsStringIgnoringCase( 'the service', $message, $label );
			$this->assertStringNotContainsStringIgnoringCase( 'this site', $message, $label );
			// "the site" as a bare phrase - "the site's" is fine, that's naming it via a possessive
			$this->assertDoesNotMatchRegularExpression( '/\bthe site\b(?!\')/i', $message, $label );
		}

		$this->behaviour['error'] = null; // the mock relay checks this first - clear it before testing the handshake reasons

		foreach ( [ 'network', 'timeout', 'bad_status', 'redirect', 'challenge_mismatch', 'invalid_response', 'response_too_large' ] as $reason ) {

			$this->behaviour['handshake'] = $reason;

			$message = Inbox_Client::enable()->get_error_message();

			$this->assertStringContainsString( 'Groundhogg', $message, $reason );
			$this->assertStringContainsString( $hostname, $message, $reason );
			$this->assertStringNotContainsStringIgnoringCase( 'the service', $message, $reason );
			$this->assertStringNotContainsStringIgnoringCase( 'this site', $message, $reason );
			$this->assertDoesNotMatchRegularExpression( '/\bthe site\b(?!\')/i', $message, $reason );
		}
	}

	public function test_a_relay_that_can_not_be_reached_is_an_error_too() {

		$this->behaviour['unreachable'] = true;

		$result = Inbox_Client::enable();

		$this->assertSame( 'unreachable', $result->get_error_code() );
		$this->assertSame( '', Inbound_Signature::pending() );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_it_does_not_ask_the_relay_when_there_is_no_license() {

		remove_all_filters( 'groundhogg/inbox/license_key' );
		add_filter( 'groundhogg/inbox/license_key', '__return_empty_string', 99 );

		$result = Inbox_Client::enable();

		$this->assertSame( 'no_license', $result->get_error_code() );
		$this->assertSame( [], $this->requests );

		// the same wording the settings UI shows when it hides the button rather than letting this be reached
		$this->assertSame( 'A Groundhogg license is needed to receive messages. Activate your license on the Licenses tab.', $result->get_error_message() );
	}

	public function test_a_response_without_the_addresses_is_not_taken() {

		$this->behaviour['error'] = [ 201, [ 'inbox_id' => 'x' ] ];

		$result = Inbox_Client::enable();

		$this->assertSame( 'invalid_response', $result->get_error_code() );
		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	public function test_setting_it_up_again_gives_the_same_addresses_and_a_new_secret() {

		Inbox_Client::enable();

		$first  = [ Inbox::id(), Inbox::address(), Inbox::reply_address() ];
		$secret = Inbound_Signature::secret();

		// a site that lost its secret, or a restored backup
		Inbox_Client::enable();

		$this->assertSame( $first, [ Inbox::id(), Inbox::address(), Inbox::reply_address() ] );
		$this->assertNotSame( $secret, Inbound_Signature::secret() );
		$this->assertSame( $this->relay['secret'], Inbound_Signature::secret() );
	}

	/* ---------------------------------------------------------------------
	 * changing it
	 * ------------------------------------------------------------------- */

	public function test_a_new_secret_is_given_to_the_relay_signed_with_the_one_it_has() {

		Inbox_Client::enable();

		$old = Inbound_Signature::secret();

		$this->assertTrue( Inbox_Client::update( true ) );

		[ 'method' => $method, 'path' => $path, 'headers' => $headers, 'body' => $body, 'data' => $data ] = end( $this->requests );

		$this->assertSame( 'PATCH', $method );
		$this->assertSame( '/v1/inboxes/inbox' . $this->run_id, $path );
		$this->assertTrue( $this->signed_with( $headers[ Inbound_Signature::HEADER ], $body, $old ) );
		$this->assertNotSame( $old, $data['secret'] );

		// the relay called the site with the new one, before it was the secret
		$this->assertSame( $data['secret'], $this->during_handshake['pending'] );
		$this->assertSame( $old, $this->during_handshake['secret'] );
		$this->assertSame( $data['secret'], Inbound_Signature::secret() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	public function test_replacing_the_secret_does_not_stop_the_reply_addresses_that_were_sent_from_working() {

		Inbox_Client::enable();

		$message_id = Message::generate_message_id();
		$address    = Inbox::reply_to( $message_id );

		$this->assertNotSame( '', $address );

		Inbox_Client::update( true );

		// the token is signed with a key of its own
		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );

		// and only replacing that stops them
		Inbox::regenerate_token_key();

		$this->assertSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_new_endpoint_is_given_to_the_relay_without_a_new_secret() {

		Inbox_Client::enable();

		$secret = Inbound_Signature::secret();

		// what changing the permalinks does
		add_filter( 'groundhogg/inbox/endpoint', fn() => 'https://example.org/?rest_route=/gh/v4/messages/inbound' );

		$this->assertFalse( Inbox::endpoint_is_current() );

		$this->assertTrue( Inbox_Client::update() );

		$request = end( $this->requests );

		$this->assertSame( 'https://example.org/?rest_route=/gh/v4/messages/inbound', $request['data']['endpoint'] );
		$this->assertArrayNotHasKey( 'secret', $request['data'] );
		$this->assertSame( $secret, Inbound_Signature::secret() );
		$this->assertTrue( Inbox::endpoint_is_current() );
	}

	public function test_a_change_the_relay_does_not_accept_leaves_the_secret_where_it_was() {

		Inbox_Client::enable();

		$secret = Inbound_Signature::secret();

		$this->behaviour['error'] = [ 401, [ 'code' => 'invalid_signature' ] ];

		$result = Inbox_Client::update( true );

		$this->assertSame( 'invalid_signature', $result->get_error_code() );
		$this->assertStringContainsString( 'again', $result->get_error_message() );
		$this->assertSame( $secret, Inbound_Signature::secret() );
		$this->assertSame( '', Inbound_Signature::pending() );
	}

	public function test_there_is_nothing_to_change_before_the_inbox_is_set_up() {

		$this->assertSame( 'not_provisioned', Inbox_Client::update()->get_error_code() );
		$this->assertSame( 'not_provisioned', Inbox_Client::disable()->get_error_code() );
		$this->assertSame( [], $this->requests );
	}

	/* ---------------------------------------------------------------------
	 * turning it off
	 * ------------------------------------------------------------------- */

	public function test_turning_it_off_is_signed_with_an_empty_body_and_the_site_forgets_it() {

		Inbox_Client::enable();

		$secret = Inbound_Signature::secret();

		// an email that was sent, which is what makes the key
		$address = Inbox::reply_to( Message::generate_message_id() );
		$this->assertNotSame( '', $address );

		$key = get_option( Inbox::TOKEN_KEY_OPTION );
		$this->assertNotEmpty( $key );

		$this->assertTrue( Inbox_Client::disable() );

		[ 'method' => $method, 'path' => $path, 'headers' => $headers, 'body' => $body ] = end( $this->requests );

		$this->assertSame( 'DELETE', $method );
		$this->assertSame( '/v1/inboxes/inbox' . $this->run_id, $path );
		$this->assertSame( '', $body );
		$this->assertTrue( $this->signed_with( $headers[ Inbound_Signature::HEADER ], '', $secret ) );

		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertSame( '', Inbox::id() );
		$this->assertSame( '', Inbox::address() );
		$this->assertNotSame( $secret, Inbound_Signature::secret() );

		// the key is kept, so that emails that were sent are good again if it's set up again
		$this->assertSame( $key, get_option( Inbox::TOKEN_KEY_OPTION ) );
	}

	public function test_an_inbox_that_the_relay_does_not_have_is_already_off() {

		Inbox_Client::enable();

		$this->relay = [];

		$this->assertTrue( Inbox_Client::disable() );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_an_inbox_that_can_not_be_turned_off_is_kept_so_that_it_can_be_tried_again() {

		Inbox_Client::enable();

		$this->behaviour['error'] = [ 500, [] ];

		$this->assertWPError( Inbox_Client::disable() );
		$this->assertTrue( Inbox::is_provisioned() );
	}

	public function test_the_inbox_can_be_forgotten_without_asking_the_relay() {

		Inbox_Client::enable();

		$count = count( $this->requests );

		Inbox_Client::forget();

		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertCount( $count, $this->requests );
	}

	/* ---------------------------------------------------------------------
	 * a copy of the site
	 * ------------------------------------------------------------------- */

	protected function become_a_copy() {
		add_filter( 'home_url', fn() => 'https://staging.example.org' );
	}

	public function test_a_copy_of_the_site_can_not_change_or_turn_off_the_inbox_of_the_site_that_it_was_copied_from() {

		Inbox_Client::enable();

		$count = count( $this->requests );

		$this->become_a_copy();

		$this->assertFalse( Inbox::site_matches() );
		$this->assertSame( 'site_mismatch', Inbox_Client::update( true )->get_error_code() );
		$this->assertSame( 'site_mismatch', Inbox_Client::disable()->get_error_code() );
		$this->assertCount( $count, $this->requests, 'nothing was sent to the relay' );
		$this->assertTrue( Inbox::is_provisioned(), 'and nothing was forgotten' );
	}

	public function test_a_copy_of_the_site_does_not_send_replies_to_the_inbox_of_the_site_that_it_was_copied_from() {

		Inbox_Client::enable();

		$message_id = Message::generate_message_id();

		$this->assertNotSame( '', Inbox::reply_to( $message_id ) );

		$this->become_a_copy();

		$this->assertFalse( Inbox::is_active() );
		$this->assertSame( '', Inbox::reply_to( $message_id ) );
	}

	public function test_a_copy_of_the_site_does_not_take_mail_but_does_answer_the_relay_when_it_is_set_up_for_the_copy() {

		Inbox_Client::enable();

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( 'jordan' ) ] ) );

		$this->become_a_copy();

		$secret = Inbound_Signature::secret();

		$response = $this->call_the_site( $secret, [
			'from'        => $this->addr( 'jordan' ),
			'envelope_to' => [ Inbox::address() ],
			'text'        => 'Hello?',
			'message_id'  => '<copy-' . $this->run_id . '@example.com>',
		] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 0, \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] ) );

		// the answer to the challenge, so that it can be set up again for the copy
		$this->assertSame( 'verified', $this->call_the_site( $secret, [ 'type' => 'verify', 'challenge' => 'abc123' ] )->get_data()['status'] );

		// which it can, and then it takes mail
		$this->assertTrue( Inbox_Client::enable() );
		$this->assertTrue( Inbox::site_matches() );
		$this->assertSame( 'https://staging.example.org', Inbox::normalize_url( get_option( Inbox::SITE_URL_OPTION ) ) );
	}

	/* ---------------------------------------------------------------------
	 * the site's endpoint
	 * ------------------------------------------------------------------- */

	public function test_the_endpoint_answers_the_challenge_when_it_is_signed_with_the_pending_secret() {

		$pending = Inbound_Signature::generate();
		Inbound_Signature::set_pending( $pending );

		$response = $this->call_the_site( $pending, [ 'type' => 'verify', 'challenge' => 'abc123' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'status' => 'verified', 'challenge' => 'abc123' ], $response->get_data() );

		// and the secret that the relay has now, while it's still being replaced
		$this->assertSame( 200, $this->call_the_site( Inbound_Signature::secret(), [ 'type' => 'verify', 'challenge' => 'abc123' ] )->get_status() );

		// but not one it doesn't
		$this->assertSame( 401, $this->call_the_site( Inbound_Signature::generate(), [ 'type' => 'verify', 'challenge' => 'abc123' ] )->get_status() );
	}

	public function test_the_endpoint_wants_a_challenge_to_answer() {

		foreach ( [ [], [ 'challenge' => '' ], [ 'challenge' => [ 'a' ] ], [ 'challenge' => str_repeat( 'a', 129 ) ] ] as $extra ) {
			$response = $this->call_the_site( Inbound_Signature::secret(), array_merge( [ 'type' => 'verify' ], $extra ) );
			$this->assertSame( 422, $response->get_status(), wp_json_encode( $extra ) );
		}
	}

	public function test_a_secret_that_was_not_taken_is_not_accepted_afterwards() {

		$pending = Inbound_Signature::generate();

		Inbound_Signature::set_pending( $pending );
		Inbound_Signature::clear_pending();

		$this->assertSame( 401, $this->call_the_site( $pending, [ 'type' => 'verify', 'challenge' => 'abc123' ] )->get_status() );
	}

	public function test_mail_to_an_address_that_is_not_the_inboxes_is_not_stored_and_not_retried() {

		Inbox_Client::enable();

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( 'jordan' ) ] ) );

		// an address that was replaced, or someone else's
		$response = $this->call_the_site( Inbound_Signature::secret(), [
			'from'        => $this->addr( 'jordan' ),
			'envelope_to' => [ 'someone.else@groundhogg.email' ],
			'text'        => 'Hello?',
			'message_id'  => '<unknown-' . $this->run_id . '@example.com>',
		] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 'no_match', $response->get_data()['reason'] );
		$this->assertSame( 0, \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] ) );
	}

	public function test_mail_to_the_inbox_address_is_stored_and_the_time_it_arrived_is_kept() {

		Inbox_Client::enable();

		$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( 'jordan' ) ] ) );

		$this->assertSame( 0, Inbox::last_received() );

		// not stored, nothing arrived
		$this->call_the_site( Inbound_Signature::secret(), [ 'from' => 'stranger@example.org', 'envelope_to' => [ Inbox::address() ], 'text' => 'Hi', 'message_id' => '<s-' . $this->run_id . '@x>' ] );

		$this->assertSame( 0, Inbox::last_received() );

		$response = $this->call_the_site( Inbound_Signature::secret(), [
			'from'        => $this->addr( 'jordan' ),
			'envelope_to' => [ strtoupper( Inbox::address() ) ],
			'text'        => 'Hello?',
			'message_id'  => '<stored-' . $this->run_id . '@example.com>',
		] );

		$this->assertSame( 201, $response->get_status() );
		$this->assertLessThan( 5, abs( time() - Inbox::last_received() ) );
	}

	/* ---------------------------------------------------------------------
	 * the settings section - just a mount point, the JS (against Inbox_Api) does the rest
	 * ------------------------------------------------------------------- */

	protected function render() {
		ob_start();
		Incoming_Messages::render();

		return ob_get_clean();
	}

	protected function be_an_administrator() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function test_the_section_is_on_the_email_tab() {

		Incoming_Messages::init();

		$sections = apply_filters( 'groundhogg/admin/settings/sections', [] );

		$this->assertSame( 'email', $sections['incoming_messages']['tab'] );
		$this->assertTrue( is_callable( $sections['incoming_messages']['callback'] ) );
	}

	public function test_the_section_goes_ahead_of_outgoing_email_since_thats_what_a_contact_sees_first() {

		$before = [ 'business_info' => [ 'id' => 'business_info' ], 'outgoing_email_config' => [ 'id' => 'outgoing_email_config' ], 'email_logging' => [ 'id' => 'email_logging' ] ];

		$after = Incoming_Messages::add_section( $before );

		$this->assertSame( [ 'business_info', 'incoming_messages', 'outgoing_email_config', 'email_logging' ], array_keys( $after ) );

		// the sections either side keep their own definitions, untouched
		$this->assertSame( $before['business_info'], $after['business_info'] );
		$this->assertSame( $before['outgoing_email_config'], $after['outgoing_email_config'] );
	}

	public function test_the_section_is_still_added_if_outgoing_email_is_not_there_to_go_ahead_of() {

		// an add-on could remove it, or reorder things - this shouldn't lose the section entirely
		$before = [ 'business_info' => [ 'id' => 'business_info' ] ];

		$after = Incoming_Messages::add_section( $before );

		$this->assertArrayHasKey( 'incoming_messages', $after );
		$this->assertSame( 'incoming_messages', array_key_first( $after ) );
	}

	public function test_the_section_is_just_a_mount_point() {

		// nothing about the state of the inbox is rendered server side - not provisioned...
		$html = $this->render();
		$this->assertStringContainsString( 'id="' . Incoming_Messages::MOUNT_ID . '"', $html );
		$this->assertStringContainsString( 'gh-panel', $html );

		// ...and not provisioned, so nothing leaks into page source that the JS should be fetching over REST
		Inbox_Client::enable();
		$html = $this->render();
		$this->assertStringContainsString( 'id="' . Incoming_Messages::MOUNT_ID . '"', $html );
		$this->assertStringNotContainsString( Inbox::address(), $html );
		$this->assertStringNotContainsString( Inbox::reply_address(), $html );
	}

	public function test_the_script_is_only_enqueued_on_the_email_tab_of_the_settings_page() {

		// normally added once, when the Settings_Page is constructed on admin_menu, which doesn't run here
		Incoming_Messages::init();

		$enqueued = fn() => wp_script_is( 'groundhogg-admin-incoming-messages', 'enqueued' );

		// admin_enqueue_scripts is what registers the handle (Scripts::register_admin_scripts(), already
		// hooked from plugin bootstrap) and is what Incoming_Messages::enqueue() is hooked to as well - firing
		// it is how this runs for real, rather than only checking enqueue() in isolation.
		// Other listeners (like WP_Site_Health) expect a screen, as they would in a real admin request.
		set_current_screen( 'groundhogg_page_gh_settings' );
		$_GET = [];
		do_action( 'admin_enqueue_scripts' );
		$this->assertFalse( $enqueued() );

		$_GET = [ 'page' => 'gh_settings', 'tab' => 'general' ];
		do_action( 'admin_enqueue_scripts' );
		$this->assertFalse( $enqueued() );

		$_GET = [ 'page' => 'gh_settings', 'tab' => 'email' ];
		do_action( 'admin_enqueue_scripts' );
		$this->assertTrue( $enqueued() );

		$_GET = [];
		wp_dequeue_script( 'groundhogg-admin-incoming-messages' );
	}

	/* ---------------------------------------------------------------------
	 * the API the settings UI is built on
	 * ------------------------------------------------------------------- */

	/** @return array the decoded response body */
	protected function api( string $method, string $route, array $params = [] ) {

		$request = new WP_REST_Request( $method, '/gh/v4/inbox' . $route );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );

		return [ $response->get_status(), $response->get_data() ];
	}

	public function test_only_someone_that_can_manage_options_can_use_the_api() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'sales_rep' ] ) );

		foreach ( [ [ 'GET', '' ], [ 'POST', '/enable' ], [ 'POST', '/update' ], [ 'POST', '/rotate' ], [ 'POST', '/disable' ], [ 'POST', '/forget' ] ] as [ $method, $route ] ) {
			[ $status ] = $this->api( $method, $route );
			$this->assertNotSame( 200, $status, "$method $route" );
		}

		$this->assertSame( [], $this->requests );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_reading_the_state_before_it_is_set_up() {

		$this->be_an_administrator();

		[ $status, $data ] = $this->api( 'GET', '' );

		$this->assertSame( 200, $status );
		$this->assertFalse( $data['provisioned'] );
		$this->assertFalse( $data['active'] );
		$this->assertSame( '', $data['address'] );
		$this->assertSame( '', $data['reply_address'] );
		$this->assertSame( 0, $data['last_received'] );
		$this->assertTrue( $data['has_license'] );
	}

	public function test_enabling_through_the_api_sets_it_up_and_reports_the_new_state() {

		$this->be_an_administrator();

		[ $status, $data ] = $this->api( 'POST', '/enable', [ 'accept_terms' => true ] );

		$this->assertSame( 200, $status );
		$this->assertTrue( $data['provisioned'] );
		// it's the address with the name of whoever is looking at it, that's still the same address to the relay
		$this->assertSame( Inbox::pretty_address(), $data['address'] );
		$this->assertStringEndsWith( Inbox::address(), $data['address'] );
		$this->assertSame( Inbox::reply_address(), $data['reply_address'] );
		$this->assertTrue( Inbox::is_provisioned() );

		// and reading it again agrees
		[ , $again ] = $this->api( 'GET', '' );
		$this->assertSame( $data['address'], $again['address'] );
	}

	public function test_the_terms_have_to_be_agreed_to_before_the_inbox_is_set_up() {

		$this->be_an_administrator();

		[ $status, $data ] = $this->api( 'GET', '' );
		$this->assertFalse( $data['terms_accepted'] );
		$this->assertStringStartsWith( 'https://', $data['terms_url'] );

		// not sent, or not agreed to
		foreach ( [ [], [ 'accept_terms' => false ], [ 'accept_terms' => 'false' ], [ 'accept_terms' => '0' ] ] as $params ) {

			[ $status, $data ] = $this->api( 'POST', '/enable', $params );

			$this->assertSame( 400, $status, wp_json_encode( $params ) );
			$this->assertSame( 'terms_not_accepted', $data['code'] );
		}

		$this->assertSame( [], $this->requests, 'nothing was sent to the relay' );
		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertFalse( Inbox::terms_accepted() );
	}

	public function test_agreeing_to_the_terms_is_kept_with_who_and_when_and_is_not_asked_again() {

		$this->be_an_administrator();
		$user_id = get_current_user_id();

		[ $status ] = $this->api( 'POST', '/enable', [ 'accept_terms' => true ] );

		$this->assertSame( 200, $status );
		$this->assertTrue( Inbox::terms_accepted() );

		$terms = get_option( Inbox::TERMS_OPTION );
		$this->assertSame( Inbox::TERMS_VERSION, $terms['version'] );
		$this->assertSame( $user_id, $terms['user_id'] );
		$this->assertEqualsWithDelta( time(), $terms['time'], 10 );

		// setting it up again, or turning it off and on, is without saying so again
		[ $status ] = $this->api( 'POST', '/enable' );
		$this->assertSame( 200, $status );

		$this->api( 'POST', '/disable' );
		[ $status ] = $this->api( 'POST', '/enable' );
		$this->assertSame( 200, $status );

		[ , $data ] = $this->api( 'GET', '' );
		$this->assertTrue( $data['terms_accepted'] );
	}

	public function test_terms_of_an_older_version_are_asked_again() {

		$this->be_an_administrator();

		update_option( Inbox::TERMS_OPTION, [ 'version' => Inbox::TERMS_VERSION - 1, 'user_id' => 1, 'time' => time() ] );
		$this->assertFalse( Inbox::terms_accepted() );

		[ $status, $data ] = $this->api( 'POST', '/enable' );
		$this->assertSame( 400, $status );
		$this->assertSame( 'terms_not_accepted', $data['code'] );

		[ $status ] = $this->api( 'POST', '/enable', [ 'accept_terms' => true ] );
		$this->assertSame( 200, $status );
		$this->assertTrue( Inbox::terms_accepted() );
	}

	public function test_agreeing_is_kept_when_the_relay_says_no_since_they_did_agree() {

		$this->be_an_administrator();

		$this->behaviour['error'] = [ 422, [ 'code' => 'handshake_failed', 'reason' => 'timeout' ] ];

		[ $status ] = $this->api( 'POST', '/enable', [ 'accept_terms' => true ] );

		$this->assertNotSame( 200, $status );
		$this->assertTrue( Inbox::terms_accepted(), 'it is not asked for again on the next try' );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_a_failure_to_enable_is_reported_with_its_reason_and_nothing_is_set_up() {

		$this->be_an_administrator();

		$this->behaviour['license'] = 'invalid';

		[ $status, $data ] = $this->api( 'POST', '/enable', [ 'accept_terms' => true ] );

		$this->assertNotSame( 200, $status );
		$this->assertSame( 'invalid_license', $data['code'] );
		$this->assertStringContainsString( 'license is not valid', $data['message'] );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_rotate_update_and_disable_through_the_api() {

		$this->be_an_administrator();
		Inbox_Client::enable();

		$secret = Inbound_Signature::secret();

		[ $status, $data ] = $this->api( 'POST', '/rotate' );
		$this->assertSame( 200, $status );
		$this->assertNotSame( $secret, Inbound_Signature::secret() );
		$this->assertSame( $this->relay['secret'], Inbound_Signature::secret() );
		$this->assertTrue( $data['endpoint_current'], 'nothing has changed the endpoint yet' );

		add_filter( 'groundhogg/inbox/endpoint', fn() => 'https://example.org/?rest_route=/gh/v4/messages/inbound' );
		$this->assertFalse( Inbox::endpoint_is_current() );

		[ $status, $data ] = $this->api( 'POST', '/update' );
		$this->assertSame( 200, $status );
		$this->assertTrue( $data['endpoint_current'] );
		$this->assertTrue( Inbox::endpoint_is_current() );

		[ $status, $data ] = $this->api( 'POST', '/disable' );
		$this->assertSame( 200, $status );
		$this->assertFalse( $data['provisioned'] );
		$this->assertFalse( Inbox::is_provisioned() );
	}

	public function test_forgetting_through_the_api_does_not_ask_the_relay() {

		$this->be_an_administrator();
		Inbox_Client::enable();

		$count = count( $this->requests );

		[ $status, $data ] = $this->api( 'POST', '/forget' );

		$this->assertSame( 200, $status );
		$this->assertFalse( $data['provisioned'] );
		$this->assertFalse( Inbox::is_provisioned() );
		$this->assertCount( $count, $this->requests );
	}
}
