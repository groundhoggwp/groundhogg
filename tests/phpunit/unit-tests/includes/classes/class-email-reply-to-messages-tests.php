<?php

use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Message;
use Groundhogg\Email;
use function Groundhogg\get_contactdata;

/**
 * @see Email::replies_to_messages() the setting of an email to have replies saved as messages
 * @see Email::get_reply_to_address() and the address that they're sent to, an address of the inbox that's made for the email
 */
class Email_Reply_To_Messages_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address is unique to the run
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();

		$this->run_id = uniqid();

		update_option( Inbox::REPLY_ADDRESS_OPTION, 'r' . $this->run_id . '@inbox.example.com' );
		update_option( Inbox::ADDRESS_OPTION, 's' . $this->run_id . '@inbox.example.com' );
	}

	public function tearDown(): void {
		delete_option( Inbox::REPLY_ADDRESS_OPTION );
		delete_option( Inbox::ADDRESS_OPTION );
		remove_all_filters( 'groundhogg/email/reply_to_messages' );
		remove_all_filters( 'groundhogg/message/reply_to_enabled' );
		parent::tearDown();
	}

	/**
	 * @param array $meta
	 *
	 * @return Email
	 */
	protected function create_email( array $meta = [] ) {

		$email = new Email();
		$email->create( [
			'title'   => 'Reply to ' . $this->run_id,
			'subject' => 'Hello',
			'content' => 'Hi',
			'status'  => 'ready',
		] );

		foreach ( $meta as $key => $value ) {
			$email->update_meta( $key, $value );
		}

		$email->set_contact( get_contactdata( self::factory()->contacts->create( [ 'email' => 'jordan@' . $this->run_id . '.example.com' ] ) ) );

		return $email;
	}

	public function test_an_email_does_not_send_replies_to_messages_unless_it_says_to() {

		$email = $this->create_email();

		$this->assertFalse( $email->replies_to_messages() );
		$this->assertStringNotContainsString( '@inbox.example.com', $email->get_reply_to_address() );
	}

	public function test_replies_go_to_the_address_it_had_when_it_is_not_set() {

		$email = $this->create_email( [ 'reply_to_override' => 'support@example.com' ] );

		$this->assertSame( 'support@example.com', $email->get_reply_to_address() );
		$this->assertContains( 'Reply-To: support@example.com', $email->get_headers() );
	}

	public function test_replies_go_to_an_address_of_the_inbox_that_is_made_for_the_email_when_it_says_to() {

		$email = $this->create_email( [ 'reply_to_messages' => true, 'reply_to_override' => 'support@example.com' ] );

		$this->assertTrue( $email->replies_to_messages() );

		$address = $email->get_reply_to_address();

		$this->assertMatchesRegularExpression( '/^r' . $this->run_id . '\+[a-z2-7]{32}@inbox\.example\.com$/', $address, 'it is used in place of the address that it is set to' );

		// and it's one that a reply to is trusted by, it has a token that is ours
		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );
		$this->assertSame( 'reply', Inbox::route( [ $address ] ) );
	}

	public function test_the_headers_that_are_sent_have_it() {

		$email = $this->create_email( [ 'reply_to_messages' => true ] );

		$reply_to = array_values( array_filter( $email->get_headers(), fn( $header ) => str_starts_with( $header, 'Reply-To: ' ) ) );

		$this->assertCount( 1, $reply_to );
		$this->assertStringStartsWith( 'Reply-To: r' . $this->run_id . '+', $reply_to[0] );
	}

	public function test_it_is_a_new_address_each_time_so_a_reply_is_to_the_one_that_it_is_a_reply_to() {

		$email = $this->create_email( [ 'reply_to_messages' => true ] );

		$this->assertNotSame( $email->get_reply_to_address(), $email->get_reply_to_address() );
	}

	public function test_a_reply_to_it_is_saved_to_the_contact_that_it_is_from_since_there_is_no_message_that_it_is_a_reply_to() {

		$email   = $this->create_email( [ 'reply_to_messages' => true ] );
		$contact = $email->get_contact();
		$address = $email->get_reply_to_address();

		$message = Message::ingest( [
			'from'        => $contact->get_email(),
			'to'          => $address,
			'envelope_to' => $address,
			'subject'     => 'Re: Hello',
			'text'        => 'Thanks!',
			'message_id'  => '<broadcast-reply-' . $this->run_id . '@mail.example.com>',
		] );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	public function test_replies_go_where_they_did_when_there_is_no_inbox_that_is_on() {

		$email = $this->create_email( [ 'reply_to_messages' => true, 'reply_to_override' => 'support@example.com' ] );

		delete_option( Inbox::REPLY_ADDRESS_OPTION );

		$this->assertTrue( $email->replies_to_messages(), 'it is still what it is set to' );
		$this->assertSame( 'support@example.com', $email->get_reply_to_address() );
	}

	public function test_replies_go_where_they_did_when_the_inbox_is_told_not_to_have_them() {

		$email = $this->create_email( [ 'reply_to_messages' => true, 'reply_to_override' => 'support@example.com' ] );

		add_filter( 'groundhogg/message/reply_to_enabled', '__return_false' );

		$this->assertSame( 'support@example.com', $email->get_reply_to_address() );
	}

	public function test_a_filter_can_have_them_for_every_email_or_for_some() {

		$plain = $this->create_email();

		add_filter( 'groundhogg/email/reply_to_messages', '__return_true' );

		$this->assertTrue( $plain->replies_to_messages() );
		$this->assertStringStartsWith( 'r' . $this->run_id . '+', $plain->get_reply_to_address() );

		remove_all_filters( 'groundhogg/email/reply_to_messages' );

		// not for the one that says it does
		$says = $this->create_email( [ 'reply_to_messages' => true ] );
		add_filter( 'groundhogg/email/reply_to_messages', fn( $bool, $email ) => $email->get_id() === $says->get_id() ? false : $bool, 10, 2 );

		$this->assertFalse( $says->replies_to_messages() );
	}

	public function test_the_setting_is_saved_as_true_or_false_whatever_it_is_given() {

		$email = $this->create_email();

		foreach ( [ [ '1', true ], [ 'true', true ], [ 1, true ], [ '', false ], [ 0, false ] ] as [ $given, $expected ] ) {

			$email->update_meta( 'reply_to_messages', $given );

			$this->assertSame( $expected, (bool) ( new Email( $email->get_id() ) )->get_meta( 'reply_to_messages' ), var_export( $given, true ) );
		}
	}
}
