<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see Message::sanitize_composed_headers() what a composed email can be sent with
 * @see Emails_Api::send_email() and the send-composed-email ability, which send it with them
 */
class Emails_Send_Headers_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and Message-ID is unique to the run
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
		remove_all_filters( 'groundhogg/message/composed/allowed_headers' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function mid( $local ) {
		return '<' . $local . '-' . $this->run_id . '@mail.example.com>';
	}

	protected function create_contact( $local = 'jordan' ) {
		return get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( $local ) ] ) );
	}

	/**
	 * A message that was received, that a reply can be a reply to
	 */
	protected function create_received( $contact, $message_id, $thread_id = '' ) {

		$message = new Message();
		$message->create( [
			'object_type'  => 'contact',
			'object_id'    => $contact->get_id(),
			'direction'    => 'inbound',
			'from_address' => $contact->get_email(),
			'to_address'   => 'me@example.com',
			'subject'      => 'Question',
			'content'      => '<p>Hello</p>',
			'message_id'   => $message_id,
			'thread_id'    => $thread_id ?: $message_id,
			'status'       => 'received',
			'is_read'      => 1,
		] );

		return $message;
	}

	/**
	 * Send a composed email through the API
	 *
	 * @return array [ the custom headers that PHPMailer sent, or null if it wasn't sent, the response ]
	 */
	protected function send( $headers, $to = null ) {

		$sent = null;

		$capture = function ( $phpmailer ) use ( &$sent ) {
			$sent = $phpmailer->getCustomHeaders();
		};

		add_action( 'phpmailer_init', $capture, 999 );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/send' );
		$request->set_param( 'to', [ $to ?: $this->addr( 'jordan' ) ] );
		$request->set_param( 'from_email', 'me@example.com' );
		$request->set_param( 'subject', 'Re: Question' );
		$request->set_param( 'content', '<p>Hi Jordan</p>' );

		if ( $headers !== null ) {
			$request->set_param( 'headers', $headers );
		}

		$response = ( new Emails_Api() )->send_email( $request );

		remove_action( 'phpmailer_init', $capture, 999 );

		return [ $sent, $response ];
	}

	/** @return string the value of a header that PHPMailer sent */
	protected function header( array $sent, $name ) {

		foreach ( $sent as [ $n, $value ] ) {
			if ( strtolower( $n ) === strtolower( $name ) ) {
				return $value;
			}
		}

		return null;
	}

	/* ---------------------------------------------------------------------
	 * what is allowed
	 * ------------------------------------------------------------------- */

	public function test_there_is_nothing_to_sanitize_without_headers() {

		$this->assertSame( [], Message::sanitize_composed_headers( null ) );
		$this->assertSame( [], Message::sanitize_composed_headers( [] ) );
	}

	public function test_the_ids_of_messages_are_kept_and_the_name_is_in_its_usual_case() {

		$this->assertSame(
			[ 'In-Reply-To' => '<a@example.com>', 'References' => '<a@example.com> <b@example.com>' ],
			Message::sanitize_composed_headers( [
				'in-reply-to' => '  <a@example.com> ',
				'REFERENCES'  => "<a@example.com>   <b@example.com>",
			] )
		);

		// a list is the same as a string
		$this->assertSame(
			[ 'References' => '<a@example.com> <b@example.com>' ],
			Message::sanitize_composed_headers( [ 'References' => [ '<a@example.com>', '<b@example.com>' ] ] )
		);
	}

	/**
	 * @dataProvider not_allowed
	 */
	public function test_what_is_not_allowed_is_an_error( $headers ) {

		$result = Message::sanitize_composed_headers( $headers );

		$this->assertWPError( $result );
		// a list is not names and values, and the rest is a name that isn't one that can be set, or a value that isn't
		$this->assertSame( $headers === [ '<a@example.com>' ] ? 'invalid_headers' : 'invalid_header', $result->get_error_code() );
	}

	public function not_allowed() {
		return [
			'a header that is not one that is allowed'    => [ [ 'X-Anything' => 'yes' ] ],
			'who it is to'                                => [ [ 'Bcc' => 'someone@example.com' ] ],
			'who it is from'                              => [ [ 'From' => 'someone@example.com' ] ],
			'where replies go'                            => [ [ 'Reply-To' => 'someone@example.com' ] ],
			'the id of the message, that is ours'         => [ [ 'Message-ID' => '<a@example.com>' ] ],
			'a line break, that is another header'        => [ [ 'In-Reply-To' => "<a@example.com>\r\nBcc: someone@example.com" ] ],
			'a line break in the middle of the id'        => [ [ 'In-Reply-To' => "<a@example.com>\nX-Injected: 1" ] ],
			'something that is not the id of a message'   => [ [ 'In-Reply-To' => 'hello there' ] ],
			'an id that is not in <>'                     => [ [ 'In-Reply-To' => 'a@example.com' ] ],
			'an id with no host'                          => [ [ 'In-Reply-To' => '<abc>' ] ],
			'an id and something else'                    => [ [ 'References' => '<a@example.com> and more' ] ],
			'nothing'                                     => [ [ 'In-Reply-To' => '' ] ],
			'a name that is not a name'                   => [ [ "In-Reply-To\r\nBcc" => '<a@example.com>' ] ],
			'a list, and not names and values'            => [ [ '<a@example.com>' ] ],
		];
	}

	public function test_a_header_that_is_allowed_by_a_filter_is_cleaned_and_the_ones_that_are_never_allowed_still_are_not() {

		add_filter( 'groundhogg/message/composed/allowed_headers', fn( $allowed ) => array_merge( $allowed, [ 'X-Ticket', 'Subject', 'Bcc' ] ) );

		$this->assertSame(
			[ 'X-Ticket' => '1234 open' ],
			Message::sanitize_composed_headers( [ 'x-ticket' => "  1234   open " ] )
		);

		$this->assertWPError( Message::sanitize_composed_headers( [ 'X-Ticket' => "1\r\nBcc: x@example.com" ] ) );
		$this->assertWPError( Message::sanitize_composed_headers( [ 'Subject' => 'Changed' ] ) );
		$this->assertWPError( Message::sanitize_composed_headers( [ 'Bcc' => 'x@example.com' ] ) );
	}

	/* ---------------------------------------------------------------------
	 * sent, and what is stored
	 * ------------------------------------------------------------------- */

	public function test_it_is_sent_with_the_headers_and_it_is_in_the_thread_of_what_it_is_a_reply_to() {

		$contact = $this->create_contact();
		$parent  = $this->create_received( $contact, $this->mid( 'received' ), $this->mid( 'root' ) );

		[ $sent, $response ] = $this->send( [
			'In-Reply-To' => $this->mid( 'received' ),
			'References'  => $this->mid( 'root' ) . ' ' . $this->mid( 'received' ),
		] );

		$this->assertNotNull( $sent, wp_json_encode( $response instanceof WP_Error ? $response->get_error_message() : $response->get_data() ) );
		$this->assertSame( $this->mid( 'received' ), $this->header( $sent, 'In-Reply-To' ) );
		$this->assertSame( $this->mid( 'root' ) . ' ' . $this->mid( 'received' ), $this->header( $sent, 'References' ) );

		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id(), 'direction' => 'outbound' ] );

		$this->assertCount( 1, $stored );
		$this->assertSame( $this->mid( 'received' ), $stored[0]->in_reply_to );
		$this->assertSame( $this->mid( 'root' ), $stored[0]->thread_id, 'it is in the thread that it is a reply to' );
	}

	public function test_it_is_found_by_the_references_when_it_is_the_message_before_that_is_ours() {

		$contact = $this->create_contact();
		$this->create_received( $contact, $this->mid( 'received' ), $this->mid( 'root' ) );

		// the id in In-Reply-To isn't one that we have, but one that was earlier in the thread is
		[ $sent ] = $this->send( [
			'In-Reply-To' => $this->mid( 'unknown' ),
			'References'  => $this->mid( 'received' ),
		] );

		$this->assertNotNull( $sent );

		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id(), 'direction' => 'outbound' ] );

		$this->assertSame( $this->mid( 'root' ), $stored[0]->thread_id );
	}

	public function test_a_reply_to_something_that_is_someone_elses_is_not_put_in_their_thread() {

		$other   = $this->create_contact( 'other' );
		$contact = $this->create_contact( 'jordan' );

		$this->create_received( $other, $this->mid( 'theirs' ), $this->mid( 'their-thread' ) );

		[ $sent ] = $this->send( [ 'In-Reply-To' => $this->mid( 'theirs' ) ] );

		$this->assertNotNull( $sent, 'it is still sent, and with the header' );

		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id(), 'direction' => 'outbound' ] );

		$this->assertCount( 1, $stored );
		$this->assertSame( $this->mid( 'theirs' ), $stored[0]->in_reply_to );
		$this->assertNotSame( $this->mid( 'their-thread' ), $stored[0]->thread_id );
		$this->assertSame( $stored[0]->message_id, $stored[0]->thread_id, 'it starts a thread of its own' );
	}

	public function test_an_email_without_headers_is_sent_as_it_was() {

		$contact = $this->create_contact();

		[ $sent ] = $this->send( null );

		$this->assertNotNull( $sent );
		$this->assertNull( $this->header( $sent, 'In-Reply-To' ) );

		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id(), 'direction' => 'outbound' ] );

		$this->assertSame( '', $stored[0]->in_reply_to );
	}

	public function test_headers_that_are_not_allowed_are_a_bad_request_and_nothing_is_sent() {

		$contact = $this->create_contact();

		foreach ( [
			[ 'Bcc' => 'someone@example.com' ],
			[ 'In-Reply-To' => "<a@example.com>\r\nBcc: someone@example.com" ],
			[ 'In-Reply-To' => 'not an id' ],
		] as $headers ) {

			[ $sent, $response ] = $this->send( $headers );

			$this->assertNull( $sent, 'it was sent' );
			$this->assertWPError( $response );
			$this->assertSame( 'invalid_header', $response->get_error_code() );
			$this->assertSame( 400, $response->get_error_data()['status'] ?? null );
		}

		$this->assertSame( [], \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] ) );
	}

	/* ---------------------------------------------------------------------
	 * the ability
	 * ------------------------------------------------------------------- */

	protected function ability() {

		if ( ! function_exists( 'wp_get_ability' ) || ! ( $ability = wp_get_ability( 'groundhogg/send-composed-email' ) ) ) {
			$this->markTestSkipped( 'The abilities are not registered here.' );
		}

		return $ability;
	}

	public function test_the_ability_sends_the_headers_and_stores_what_it_is_a_reply_to() {

		$ability = $this->ability();
		$contact = $this->create_contact();
		$this->create_received( $contact, $this->mid( 'received' ), $this->mid( 'root' ) );

		$sent    = null;
		$capture = function ( $phpmailer ) use ( &$sent ) {
			$sent = $phpmailer->getCustomHeaders();
		};

		add_action( 'phpmailer_init', $capture, 999 );

		$result = $ability->execute( [
			'to'         => [ $this->addr( 'jordan' ) ],
			'subject'    => 'Re: Question',
			'content'    => '<p>Hi</p>',
			'from_email' => 'me@example.com',
			'headers'    => [ 'In-Reply-To' => $this->mid( 'received' ) ],
		] );

		remove_action( 'phpmailer_init', $capture, 999 );

		$this->assertNotWPError( $result );
		$this->assertNotNull( $sent );
		$this->assertSame( $this->mid( 'received' ), $this->header( $sent, 'In-Reply-To' ) );

		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id(), 'direction' => 'outbound' ] );

		$this->assertSame( $this->mid( 'root' ), $stored[0]->thread_id );
	}

	public function test_the_ability_will_not_send_with_a_header_that_is_not_allowed() {

		$ability = $this->ability();
		$this->create_contact();

		$result = $ability->execute( [
			'to'      => [ $this->addr( 'jordan' ) ],
			'subject' => 'Hello',
			'content' => '<p>Hi</p>',
			'headers' => [ 'Bcc' => 'someone@example.com' ],
		] );

		$this->assertWPError( $result );
	}
}
