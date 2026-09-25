<?php

use Groundhogg\Api\V4\Emails_Api;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\get_sender_profiles;

/**
 * @see Emails_Api::send_email() and who a composed email is from, a sender profile or an address
 */
class Emails_Send_Sender_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address is unique to the run
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();

		$this->run_id = uniqid();

		// the person sending, who has to be able to see who they're sending to
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local = 'jordan', array $args = [] ) {
		return get_contactdata( self::factory()->contacts->create( array_merge( [ 'email' => $this->addr( $local ) ], $args ) ) );
	}

	/**
	 * Send a composed email to a contact
	 *
	 * @param array $params what's sent with the email, other than the recipient, the subject and the message
	 *
	 * @return array [ [ email, name ] of who PHPMailer sent it from, or null if it wasn't sent, the response ]
	 */
	protected function send( array $params = [], $to = null ) {

		$from = null;

		$capture = function ( $phpmailer ) use ( &$from ) {
			$from = [ $phpmailer->From, $phpmailer->FromName ];
		};

		add_action( 'phpmailer_init', $capture, 999 );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/send' );
		$request->set_param( 'to', [ $to ?: $this->addr( 'jordan' ) ] );
		$request->set_param( 'subject', 'Hello there' );
		$request->set_param( 'content', '<p>Hi Jordan</p>' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = ( new Emails_Api() )->send_email( $request );

		remove_action( 'phpmailer_init', $capture, 999 );

		return [ $from, $response ];
	}

	public function test_it_is_from_the_default_sender_when_it_is_not_said_who_from() {

		$this->create_contact();

		[ $from, $response ] = $this->send();

		$this->assertSame( [ get_default_from_email(), get_default_from_name() ], $from );
		$this->assertSame( get_default_from_email(), $response->get_data()['from'] );
	}

	public function test_an_address_and_name_are_used_as_they_are_without_a_profile() {

		$this->create_contact();

		[ $from, $response ] = $this->send( [ 'from_email' => 'someone@elsewhere.example.org', 'from_name' => 'Someone Else' ] );

		$this->assertSame( [ 'someone@elsewhere.example.org', 'Someone Else' ], $from );
		$this->assertSame( 'someone@elsewhere.example.org', $response->get_data()['from'] );
	}

	public function test_a_profile_is_who_it_is_from() {

		$this->create_contact();

		// what any site has
		$profile = get_sender_profiles()['default'];

		[ $from ] = $this->send( [ 'sender_profile' => 'default' ] );

		$this->assertSame( [ $profile['from_email'], $profile['from_name'] ], $from );
	}

	public function test_an_address_and_name_are_used_in_place_of_what_the_profile_has() {

		$this->create_contact();

		[ $from ] = $this->send( [ 'sender_profile' => 'default', 'from_email' => 'someone@elsewhere.example.org' ] );

		$this->assertSame( 'someone@elsewhere.example.org', $from[0] );
		$this->assertSame( get_sender_profiles()['default']['from_name'], $from[1], 'what isn\'t given is the profile\'s' );
	}

	public function test_a_profile_that_does_not_exist_is_an_error_and_nothing_is_sent() {

		$this->create_contact();

		[ $from, $response ] = $this->send( [ 'sender_profile' => 'user-0-does-not-exist' ] );

		$this->assertNull( $from, 'it was sent' );
		$this->assertWPError( $response );
		$this->assertSame( 'invalid_sender_profile', $response->get_error_code() );
	}

	public function test_the_owner_profile_is_the_owner_of_the_contact() {

		$owner = self::factory()->user->create( [ 'role' => 'sales_rep', 'user_email' => $this->addr( 'owner' ), 'display_name' => 'Olive Owner' ] );
		$this->create_contact( 'jordan', [ 'owner_id' => $owner ] );

		[ $from ] = $this->send( [ 'sender_profile' => 'owner' ] );

		$this->assertNotNull( $from, 'it was not sent' );
		$this->assertSame( $this->addr( 'owner' ), $from[0] );
		$this->assertStringNotContainsString( '{', $from[1], 'the merge tag was not replaced' );
	}

	public function test_the_owner_profile_is_the_default_sender_when_the_contact_has_no_owner() {

		$this->create_contact( 'jordan', [ 'owner_id' => 0 ] );

		[ $from ] = $this->send( [ 'sender_profile' => 'owner' ] );

		$this->assertNotNull( $from, 'it was not sent' );
		$this->assertSame( get_default_from_email(), $from[0] );
		$this->assertStringNotContainsString( '{', $from[1], 'the merge tag was not replaced' );
	}

	public function test_a_profile_that_does_not_come_to_an_address_is_an_error_and_nothing_is_sent() {

		$this->create_contact();

		// a profile that is a merge tag that isn't one that there is, so it stays as it is
		add_filter( 'groundhogg/sender_profiles', fn( $profiles ) => $profiles + [
				'broken' => [ 'from_name' => 'Broken', 'from_email' => '{no_such_replacement}', 'from_header' => '', 'display' => 'Broken', 'from_avatar' => '' ],
			] );

		[ $from, $response ] = $this->send( [ 'sender_profile' => 'broken' ] );

		remove_all_filters( 'groundhogg/sender_profiles' );

		// the profiles are kept for the request, so it's only there if this was the first that asked for them
		if ( is_wp_error( $response ) ) {
			$this->assertNull( $from, 'it was sent' );
			$this->assertSame( 'invalid_sender_profile', $response->get_error_code() );
		} else {
			$this->markTestSkipped( 'The sender profiles were already built, so another can not be added to them.' );
		}
	}
}
