<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\Classes\Inbound_Signature;
use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see Inbox the two addresses of the inbox, and the token that makes the reply address safe to publish
 * @see Message::ingest() what happens to a message that was sent to each of them
 * @see Emails_Api::send_email() the Reply-To that the emails we send get
 */
class Inbox_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and Message-ID is unique
	 * to the run, otherwise a test would match what an earlier one left behind.
	 *
	 * @var string
	 */
	protected $run_id;

	/** the public address that replies go to, tokens are added to it */
	protected $reply_address;

	/** the secret address, to BCC or forward to */
	protected $inbox_address;

	public function setUp(): void {
		parent::setUp();

		$this->run_id        = uniqid();
		$this->reply_address = 'r' . $this->run_id . '@inbox.example.com';
		$this->inbox_address = 's' . $this->run_id . '@inbox.example.com';

		update_option( Inbox::REPLY_ADDRESS_OPTION, $this->reply_address );
		update_option( Inbox::ADDRESS_OPTION, $this->inbox_address );
	}

	public function tearDown(): void {
		delete_option( Inbox::REPLY_ADDRESS_OPTION );
		delete_option( Inbox::ADDRESS_OPTION );
		remove_all_filters( 'groundhogg/message/reply_to_enabled' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local ) {
		return get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( $local ) ] ) );
	}

	/**
	 * A composed email that we sent, with a Message-ID that we made
	 *
	 * @return Message
	 */
	protected function create_sent( $contact, $message_id = null ) {
		return Message::record_composed_email( $contact, [
			'subject'      => 'Hello there',
			'content'      => '<p>Hi!</p>',
			'from_address' => 'me@example.com',
			'user_id'      => 1,
			'message_id'   => $message_id ?: Message::generate_message_id(),
		] );
	}

	/** @return string the address that a reply to the email is sent to */
	protected function reply_to( Message $message ) {
		return Inbox::reply_to( $message->message_id );
	}

	protected function payload( array $args = [] ) {
		static $n = 0;

		return array_merge( [
			'from'       => $this->addr( 'jordan' ),
			'to'         => 'me@example.com',
			'subject'    => 'Re: Hello there',
			'text'       => 'Thanks!',
			'message_id' => '<tok-' . $this->run_id . '-' . ( ++ $n ) . '@mail.example.com>',
		], $args );
	}

	/* ---------------------------------------------------------------------
	 * the reply address
	 * ------------------------------------------------------------------- */

	public function test_the_message_ids_that_we_make_start_with_an_id_that_can_be_signed() {

		$this->assertMatchesRegularExpression( '/^<[a-z2-7]{16}@[^>]+>$/', Message::generate_message_id() );
		$this->assertNotSame( Message::generate_message_id(), Message::generate_message_id() );
	}

	public function test_the_reply_address_has_a_token_for_the_email() {

		$message_id = Message::generate_message_id();
		$address    = Inbox::reply_to( $message_id );

		$this->assertMatchesRegularExpression( '/^r' . $this->run_id . '\+[a-z2-7]{32}@inbox\.example\.com$/', $address );

		// the first half of the token is the id that the Message-ID starts with
		preg_match( '/^<([a-z2-7]{16})@/', $message_id, $matches );
		$this->assertStringContainsString( '+' . $matches[1], $address );
	}

	public function test_there_is_no_reply_to_without_a_reply_address() {

		delete_option( Inbox::REPLY_ADDRESS_OPTION );

		$this->assertSame( '', Inbox::reply_to( Message::generate_message_id() ) );
	}

	public function test_the_reply_to_is_on_by_default_and_a_filter_turns_it_off() {

		$message_id = Message::generate_message_id();

		$this->assertNotSame( '', Inbox::reply_to( $message_id ) );

		add_filter( 'groundhogg/message/reply_to_enabled', '__return_false' );

		$this->assertSame( '', Inbox::reply_to( $message_id ) );
	}

	public function test_there_is_no_reply_address_for_a_message_id_that_we_did_not_make() {

		$this->assertSame( '', Inbox::reply_to( '<9f2c1e0a-1b2c-4d3e-8f90-a1b2c3d4e5f6@mail.example.com>' ) );
		$this->assertSame( '', Inbox::reply_to( '' ) );
	}

	public function test_there_is_no_reply_address_that_is_too_long_to_be_one() {

		// the part before the @ is 64 characters at the most
		update_option( Inbox::REPLY_ADDRESS_OPTION, str_repeat( 'a', 40 ) . '@inbox.example.com' );

		$this->assertSame( '', Inbox::reply_to( Message::generate_message_id() ) );
	}

	/* ---------------------------------------------------------------------
	 * the token
	 * ------------------------------------------------------------------- */

	public function test_a_valid_token_gives_the_id_of_the_email() {

		$message_id = Message::generate_message_id();
		preg_match( '/^<([a-z2-7]{16})@/', $message_id, $matches );

		$this->assertSame( $matches[1], Inbox::verify_reply( [ Inbox::reply_to( $message_id ) ] ) );

		// it's found among other addresses, and addresses are not case sensitive
		$this->assertSame( $matches[1], Inbox::verify_reply( [ 'someone@example.org', strtoupper( Inbox::reply_to( $message_id ) ) ] ) );
	}

	public function test_a_token_that_was_changed_is_not_valid() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		[ $local, $domain ] = explode( '@', $address );
		$flip = fn( $char ) => $char === 'a' ? 'b' : 'a';

		// the id, and the signature
		$in_id  = substr( $local, 0, -31 ) . $flip( $local[ strlen( $local ) - 31 ] ) . substr( $local, -30 );
		$in_mac = substr( $local, 0, -1 ) . $flip( $local[ strlen( $local ) - 1 ] );

		$this->assertSame( '', Inbox::verify_reply( [ $in_id . '@' . $domain ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $in_mac . '@' . $domain ] ) );

		// and the one that wasn't changed is
		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_is_still_valid_after_the_secret_that_the_relay_signs_with_is_replaced() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );

		// the tokens are signed with a key of their own, otherwise replacing the secret would stop every reply
		// address that was already sent out from working
		Inbound_Signature::regenerate();

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_is_not_valid_after_the_key_it_was_signed_with_is_replaced() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );

		Inbox::regenerate_token_key();

		$this->assertSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_only_counts_on_the_reply_address() {

		$address = Inbox::reply_to( Message::generate_message_id() );
		$token   = explode( '+', explode( '@', $address )[0] )[1];

		$this->assertSame( '', Inbox::verify_reply( [ 'other+' . $token . '@inbox.example.com' ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $this->inbox_address ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $this->reply_address ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ 'not an address', '' ] ) );
	}

	public function test_which_address_of_the_inbox_it_was_sent_to() {

		$token = explode( '+', explode( '@', Inbox::reply_to( Message::generate_message_id() ) )[0] )[1];

		$this->assertSame( 'reply', Inbox::route( [ $this->reply_address ] ) );
		$this->assertSame( 'reply', Inbox::route( [ 'r' . $this->run_id . '+' . $token . '@inbox.example.com' ] ) );
		$this->assertSame( 'inbox', Inbox::route( [ $this->inbox_address ] ) );
		$this->assertSame( 'inbox', Inbox::route( [] ) );
	}

	/* ---------------------------------------------------------------------
	 * a message that's sent to the reply address
	 * ------------------------------------------------------------------- */

	public function test_a_reply_is_matched_by_its_token_when_the_message_id_was_rewritten_and_the_sender_is_someone_else() {

		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );

		// what a service that sends the email does to the Message-ID, so In-Reply-To doesn't match, and the reply
		// is from an address that isn't on any contact
		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'envelope_to' => $this->reply_to( $sent ),
			'in_reply_to' => '<0198a7b2-rewritten@smtp.provider.example>',
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_a_reply_by_token_goes_to_the_contact_that_wrote_it_when_the_email_went_to_several() {

		$a = $this->create_contact( 'a' );
		$b = $this->create_contact( 'b' );

		// one email to both is stored once for each of them, and they see the same Reply-To
		$message_id = Message::generate_message_id();
		$this->create_sent( $a, $message_id );
		$sent_b = $this->create_sent( $b, $message_id );

		$message = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'b' ),
			'envelope_to' => $this->reply_to( $sent_b ),
		] ) );

		$this->assertEquals( $b->get_id(), $message->object_id );
	}

	public function test_the_reply_address_without_a_token_is_not_trusted_whoever_it_says_it_is_from() {

		// the address is in the header of every email that was sent, this is what someone that's had one would do
		$contact = $this->create_contact( 'jordan' );

		$result = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'jordan' ),
			'envelope_to' => $this->reply_address,
		] ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_token', $result->get_error_code() );
		$this->assertSame( 0, \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] ) );
	}

	public function test_the_reply_address_with_a_token_that_was_made_up_is_not_trusted() {

		$this->create_contact( 'jordan' );

		$address = Inbox::reply_to( Message::generate_message_id() );
		$at      = strpos( $address, '@' );

		// the last character of the signature, made into a different one
		$forged = substr( $address, 0, $at - 1 ) . ( $address[ $at - 1 ] === 'a' ? 'b' : 'a' ) . substr( $address, $at );

		$result = Message::ingest( $this->payload( [ 'envelope_to' => $forged ] ) );

		$this->assertSame( 'invalid_token', $result->get_error_code() );
	}

	public function test_a_token_for_an_email_we_do_not_have_falls_back_on_who_it_is_from() {

		$contact = $this->create_contact( 'jordan' );

		// a good token, for an email that was sent and since deleted, or that another site sent
		$message = Message::ingest( $this->payload( [ 'envelope_to' => Inbox::reply_to( Message::generate_message_id() ) ] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	/* ---------------------------------------------------------------------
	 * a message that's sent to the inbox address
	 * ------------------------------------------------------------------- */

	public function test_the_inbox_address_is_trusted_and_the_sender_says_who_it_is() {

		$contact = $this->create_contact( 'jordan' );

		$message = Message::ingest( $this->payload( [
			'to'          => 'help@theirdomain.example',  // what it says, the address was forwarded to the inbox
			'envelope_to' => $this->inbox_address,
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( 'help@theirdomain.example', $message->to_address );
	}

	/* ---------------------------------------------------------------------
	 * the emails we send
	 * ------------------------------------------------------------------- */

	/** @var callable|null what captures the email that is about to be sent */
	protected $capture;

	/**
	 * Send a composed email, and get what it was sent with
	 *
	 * @return array [ [ reply_to => the addresses, message_id => the Message-ID PHPMailer used ], the response ]
	 */
	protected function send_composed() {

		$captured = null;

		// the person sending, who has to be able to see who they're sending to
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// after the Message-ID has been set
		$this->capture = function ( $phpmailer ) use ( &$captured ) {
			$captured = [
				// each is [ address, name ]
				'reply_to'   => array_values( array_map( fn( $reply_to ) => $reply_to[0], $phpmailer->getReplyToAddresses() ) ),
				'message_id' => $phpmailer->MessageID,
			];
		};

		add_action( 'phpmailer_init', $this->capture, 999 );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/send' );
		$request->set_param( 'to', [ $this->addr( 'jordan' ) ] );
		$request->set_param( 'from_email', 'me@example.com' );
		$request->set_param( 'from_name', 'Me' );
		$request->set_param( 'subject', 'Hello there' );
		$request->set_param( 'content', '<p>Hi Jordan</p>' );

		$response = ( new Emails_Api() )->send_email( $request );

		remove_action( 'phpmailer_init', $this->capture, 999 );

		return [ $captured, $response ];
	}

	public function test_a_composed_email_sends_replies_to_the_inbox_by_a_token_for_that_email() {

		$contact = $this->create_contact( 'jordan' );

		[ $sent, $response ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertCount( 1, $sent['reply_to'] );

		$address = $sent['reply_to'][0];

		// the token is for the Message-ID that PHPMailer used, and that was stored
		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] );

		$this->assertCount( 1, $stored );
		$this->assertSame( $sent['message_id'], $stored[0]->message_id );
		$this->assertSame( Inbox::reply_to( $sent['message_id'] ), $address );

		// so a reply to it finds the message, whoever it's from
		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'envelope_to' => $address,
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent['message_id'], $message->thread_id );
	}

	public function test_a_composed_email_has_no_reply_to_when_the_filter_turns_it_off() {

		$this->create_contact( 'jordan' );

		add_filter( 'groundhogg/message/reply_to_enabled', '__return_false' );

		[ $sent ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertSame( [], $sent['reply_to'] );
	}

	public function test_a_composed_email_has_no_reply_to_without_an_inbox() {

		$this->create_contact( 'jordan' );

		delete_option( Inbox::REPLY_ADDRESS_OPTION );

		[ $sent ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertSame( [], $sent['reply_to'] );
	}
}
