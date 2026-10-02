<?php

use Groundhogg\Classes\Inbound_Signature;
use function Groundhogg\get_contactdata;

/**
 * @see \Groundhogg\Api\V4\Messages_Api::receive() POST gh/v4/messages/inbound, how received messages are delivered
 * @see Inbound_Signature the signing of those requests
 */
class Messages_Inbound_Tests extends GH_UnitTestCase {

	const ROUTE = '/gh/v4/messages/inbound';

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and Message-ID is unique
	 * to the run, otherwise a test would match what an earlier one left behind.
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
		wp_set_current_user( 0 ); // it's not for logged in users
		Inbound_Signature::ensure(); // a site that receives messages has the secret, checking a request doesn't make one
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/message/inbound/authenticated' );
		remove_all_filters( 'groundhogg/message/inbound/secret' );
		delete_option( \Groundhogg\Classes\Inbox::REPLY_ADDRESS_OPTION );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local = 'jordan' ) {
		return get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( $local ) ] ) );
	}

	protected function payload( array $args = [] ) {
		static $n = 0;

		return array_merge( [
			'from'       => $this->addr( 'jordan' ),
			'to'         => 'me@example.com',
			'subject'    => 'Re: Hello there',
			'text'       => 'Thanks!',
			'message_id' => '<inbound-' . $this->run_id . '-' . ( ++ $n ) . '@mail.example.com>',
		], $args );
	}

	/**
	 * Send a request to the endpoint
	 *
	 * @param string|array $body      the payload, or a raw body
	 * @param array        $overrides signature: what to send in the header instead of a good signature, false for none
	 *                                content_type
	 *
	 * @return WP_REST_Response
	 */
	protected function send( $body, array $overrides = [] ) {

		$body = is_string( $body ) ? $body : wp_json_encode( $body );

		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', $overrides['content_type'] ?? 'application/json' );
		$request->set_body( $body );

		$signature = array_key_exists( 'signature', $overrides ) ? $overrides['signature'] : Inbound_Signature::sign( $body );

		if ( $signature !== false ) {
			$request->set_header( Inbound_Signature::HEADER, $signature );
		}

		return rest_do_request( $request );
	}

	/** @return int the number of messages stored against a contact */
	protected function message_count( $contact_id ) {
		return \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact_id ] );
	}

	/* ---------------------------------------------------------------------
	 * the signature
	 * ------------------------------------------------------------------- */

	public function test_an_unsigned_request_is_refused() {

		$this->create_contact();

		$response = $this->send( $this->payload(), [ 'signature' => false ] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'missing_signature', $response->get_data()['code'] );
	}

	public function test_a_request_with_the_wrong_signature_is_refused() {

		$this->create_contact();

		$body     = wp_json_encode( $this->payload() );
		$response = $this->send( $body, [ 'signature' => Inbound_Signature::sign( $body, null, 'not-the-secret' ) ] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->get_data()['code'] );
	}

	public function test_a_request_that_was_changed_after_signing_is_refused() {

		$this->create_contact();

		$signature = Inbound_Signature::sign( wp_json_encode( $this->payload() ) );

		$response = $this->send( $this->payload( [ 'text' => 'something else' ] ), [ 'signature' => $signature ] );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_an_old_request_is_refused() {

		$this->create_contact();

		$body     = wp_json_encode( $this->payload() );
		$response = $this->send( $body, [ 'signature' => Inbound_Signature::sign( $body, time() - Inbound_Signature::TOLERANCE - 10 ) ] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'expired_signature', $response->get_data()['code'] );
	}

	public function test_a_request_from_the_future_is_refused() {

		$body     = wp_json_encode( $this->payload() );
		$response = $this->send( $body, [ 'signature' => Inbound_Signature::sign( $body, time() + Inbound_Signature::TOLERANCE + 10 ) ] );

		$this->assertSame( 'expired_signature', $response->get_data()['code'] );
	}

	public function test_a_garbled_signature_is_refused() {

		foreach ( [ 'nonsense', 't=abc,v1=', 'v1=deadbeef', 't=' . time() ] as $header ) {
			$response = $this->send( $this->payload(), [ 'signature' => $header ] );
			$this->assertSame( 401, $response->get_status(), $header );
			$this->assertSame( 'malformed_signature', $response->get_data()['code'], $header );
		}
	}

	public function test_any_of_several_signatures_will_do_so_the_secret_can_be_rotated() {

		$this->create_contact();

		$body      = wp_json_encode( $this->payload() );
		$timestamp = time();
		$good      = Inbound_Signature::sign( $body, $timestamp );
		$old       = Inbound_Signature::sign( $body, $timestamp, 'the-previous-secret' );

		// the one signed with the previous secret first, and the good one after
		$header = preg_replace( '/^(t=\d+),/', '$1,' . 'v1=' . explode( 'v1=', $old )[1] . ',', $good );

		$this->assertSame( 201, $this->send( $body, [ 'signature' => $header ] )->get_status() );
	}

	public function test_it_is_too_large_to_be_accepted() {

		$body     = wp_json_encode( [ 'text' => str_repeat( 'x', 5 * MB_IN_BYTES ) ] );
		$response = $this->send( $body, [ 'signature' => Inbound_Signature::sign( $body ) ] );

		$this->assertSame( 413, $response->get_status() );
	}

	public function test_the_secret_is_generated_once_and_can_be_replaced() {

		delete_option( Inbound_Signature::OPTION );

		$secret = Inbound_Signature::ensure();

		$this->assertSame( 64, strlen( $secret ) );
		$this->assertSame( $secret, Inbound_Signature::ensure() );
		$this->assertSame( $secret, Inbound_Signature::secret() );

		$new = Inbound_Signature::regenerate();

		$this->assertNotSame( $secret, $new );
		$this->assertSame( $new, Inbound_Signature::secret() );

		// what was signed with the old one no longer works
		$body = wp_json_encode( $this->payload() );
		$this->assertWPError( Inbound_Signature::verify( Inbound_Signature::sign( $body, null, $secret ), $body ) );
		$this->assertTrue( Inbound_Signature::verify( Inbound_Signature::sign( $body ), $body ) );
	}

	public function test_checking_a_signature_does_not_create_a_secret() {

		delete_option( Inbound_Signature::OPTION );
		Inbound_Signature::clear_pending();

		$body = wp_json_encode( $this->payload() );

		// signed with the secret that a request would be signed with by something that has none
		$result = Inbound_Signature::verify( Inbound_Signature::sign( $body, null, 'not-the-secret' ), $body );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_signature', $result->get_error_code() );
		$this->assertSame( '', Inbound_Signature::secret() );
		$this->assertFalse( get_option( Inbound_Signature::OPTION ) );

		// and a request signed with a key that is empty doesn't pass either
		$time = time();
		$this->assertWPError( Inbound_Signature::verify( 't=' . $time . ',v1=' . hash_hmac( 'sha256', $time . '.' . $body, '' ), $body ) );
	}

	/* ---------------------------------------------------------------------
	 * what happens to a message
	 * ------------------------------------------------------------------- */

	public function test_a_signed_message_is_stored_against_the_contact() {

		$contact = $this->create_contact();

		$response = $this->send( $this->payload( [ 'subject' => 'Question', 'text' => 'Can you help?' ] ) );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'received', $response->get_data()['status'] );

		$message = new \Groundhogg\Classes\Message( $response->get_data()['ID'] );

		$this->assertTrue( $message->exists() );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( 'inbound', $message->direction );
		$this->assertSame( 'Question', $message->subject );
	}

	public function test_the_content_type_of_the_request_does_not_matter() {

		$this->create_contact();

		$response = $this->send( $this->payload(), [ 'content_type' => 'text/plain' ] );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_a_message_that_is_never_going_to_be_stored_is_not_an_error_so_it_is_not_retried() {

		$this->create_contact();

		// no one we know
		$response = $this->send( $this->payload( [ 'from' => 'stranger@example.org' ] ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 'no_match', $response->get_data()['reason'] );

		// an automatic reply
		$response = $this->send( $this->payload( [ 'headers' => [ 'Auto-Submitted' => 'auto-replied' ] ] ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 'ignored', $response->get_data()['reason'] );

		// the same message again
		$payload = $this->payload();
		$this->assertSame( 201, $this->send( $payload )->get_status() );

		$again = $this->send( $payload );
		$this->assertSame( 200, $again->get_status() );
		$this->assertSame( 'duplicate', $again->get_data()['reason'] );
	}

	public function test_a_message_without_a_sender_or_body_is_invalid() {

		$this->create_contact();

		$this->assertSame( 422, $this->send( $this->payload( [ 'from' => '' ] ) )->get_status() );
		$this->assertSame( 422, $this->send( $this->payload( [ 'text' => '' ] ) )->get_status() );
	}

	public function test_the_body_must_be_a_json_object() {

		foreach ( [ 'not json', '[1,2,3]', '"a string"', '' ] as $body ) {
			// as plain text, WordPress itself refuses bad JSON that says it is JSON, before we ever see it
			$this->assertSame( 422, $this->send( $body, [ 'content_type' => 'text/plain' ] )->get_status(), $body );
		}
	}

	public function test_unexpected_types_in_the_payload_do_not_break_it() {

		$this->create_contact();

		$response = $this->send( $this->payload( [
			'subject'     => [ 'not', 'a', 'string' ],
			'in_reply_to' => [ 'nested' => [ 'array' ] ],
			'date'        => new stdClass(),
			'headers'     => [ 'X-Ok' => 'yes', 'X-Bad' => [ 'nested' ] ],
			'unknown'     => 'is dropped',
		] ) );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_a_copy_of_an_email_that_one_of_us_sent_is_stored_as_outbound() {

		// the inbox was BCC'd, so what arrives is from the person that wrote to the contact
		$rep     = get_user_by( 'id', self::factory()->user->create( [ 'role' => 'sales_rep', 'user_email' => $this->addr( 'adrian' ) ] ) );
		$contact = $this->create_contact();
		$other   = $this->create_contact( 'other' );

		$response = $this->send( $this->payload( [
			'from' => $rep->user_email,
			'to'   => 'Jordan <' . $this->addr( 'jordan' ) . '>',
			'cc'   => [ $this->addr( 'other' ) ],
		] ) );

		$this->assertSame( 201, $response->get_status() );

		// it was logged, not received
		$this->assertSame( 'logged', $response->get_data()['status'] );
		$this->assertSame( 'outbound', $response->get_data()['direction'] );

		$message = new \Groundhogg\Classes\Message( $response->get_data()['ID'] );

		$this->assertSame( 'outbound', $message->direction );
		$this->assertEquals( $rep->ID, $message->user_id );
		$this->assertSame( 1, $this->message_count( $contact->get_id() ) );
		$this->assertSame( 1, $this->message_count( $other->get_id() ) );
	}

	/* ---------------------------------------------------------------------
	 * the reply address
	 * ------------------------------------------------------------------- */

	/** @return string the address that replies to an email that was sent are sent to */
	protected function reply_address_for_a_sent_email( $contact ) {

		update_option( \Groundhogg\Classes\Inbox::REPLY_ADDRESS_OPTION, 'r' . $this->run_id . '@inbox.example.com' );

		$sent = \Groundhogg\Classes\Message::record_composed_email( $contact, [
			'subject'      => 'Hello there',
			'content'      => '<p>Hi!</p>',
			'from_address' => 'me@example.com',
			'message_id'   => \Groundhogg\Classes\Message::generate_message_id(),
		] );

		return \Groundhogg\Classes\Inbox::reply_to( $sent->message_id );
	}

	public function test_a_reply_to_the_reply_address_is_stored_even_if_the_sender_does_not_pass_authentication() {

		$contact = $this->create_contact();

		// the token is what says who it is a reply to, so who it says it is from does not matter
		$response = $this->send( $this->payload( [
			'from'           => 'jordan.rivera@work.example.org',
			'envelope_to'    => $this->reply_address_for_a_sent_email( $contact ),
			'authentication' => [ 'spf' => 'fail', 'dkim' => 'fail' ],
		] ) );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'received', $response->get_data()['status'] );

		$message = new \Groundhogg\Classes\Message( $response->get_data()['ID'] );

		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	public function test_mail_to_the_reply_address_without_a_valid_token_is_not_stored_and_not_retried() {

		$contact = $this->create_contact();

		update_option( \Groundhogg\Classes\Inbox::REPLY_ADDRESS_OPTION, 'r' . $this->run_id . '@inbox.example.com' );

		$response = $this->send( $this->payload( [ 'envelope_to' => 'r' . $this->run_id . '@inbox.example.com' ] ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 'invalid_token', $response->get_data()['reason'] );
		$this->assertSame( 0, $this->message_count( $contact->get_id() ) );
	}

	public function test_mail_that_fails_authentication_is_still_not_stored_without_a_token() {

		$this->create_contact();

		$response = $this->send( $this->payload( [ 'authentication' => [ 'spf' => 'fail', 'dkim' => 'fail' ] ] ) );

		$this->assertSame( 'unauthenticated', $response->get_data()['reason'] );
	}

	/* ---------------------------------------------------------------------
	 * whether the sender is who they say they are
	 * ------------------------------------------------------------------- */

	public function test_a_sender_that_failed_authentication_is_not_stored() {

		$contact = $this->create_contact();

		$response = $this->send( $this->payload( [ 'authentication' => [ 'spf' => 'fail', 'dkim' => 'none' ] ] ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'ignored', $response->get_data()['status'] );
		$this->assertSame( 'unauthenticated', $response->get_data()['reason'] );

		$this->assertSame( 0, $this->message_count( $contact->get_id() ) );
	}

	public function test_a_sender_that_passed_either_check_is_stored() {

		$this->create_contact();

		$this->assertSame( 201, $this->send( $this->payload( [ 'authentication' => [ 'spf' => 'PASS', 'dkim' => 'fail' ] ] ) )->get_status() );
		$this->assertSame( 201, $this->send( $this->payload( [ 'authentication' => [ 'spf' => 'fail', 'dkim' => 'pass' ] ] ) )->get_status() );
	}

	public function test_authentication_is_not_required_when_the_sender_does_not_report_it() {

		$this->create_contact();

		$this->assertSame( 201, $this->send( $this->payload() )->get_status() );
	}

	public function test_the_authentication_decision_can_be_changed() {

		$this->create_contact();

		add_filter( 'groundhogg/message/inbound/authenticated', '__return_true' );

		$this->assertSame( 201, $this->send( $this->payload( [ 'authentication' => [ 'spf' => 'fail' ] ] ) )->get_status() );
	}
}
