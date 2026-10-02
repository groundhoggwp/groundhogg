<?php

use Groundhogg\Email;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_sending;

/**
 * is_sending() gates things that must only happen while an email is really going out, like minting
 * an auto-login key. Email::send() used to turn it on and only turn it off after a successful send,
 * so every early return left it on for the rest of the process.
 *
 * @see Email::send()
 */
class Email_Sending_Flag_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		is_sending( false );
	}

	public function tearDown(): void {
		is_sending( false );
		parent::tearDown();
	}

	protected function email( array $args = [] ) {
		return new Email( array_merge( [
			'subject' => 'Hello',
			'content' => '<p>Hello</p>',
			'status'  => 'ready',
		], $args ) );
	}

	protected function contact() {
		return get_contactdata( self::factory()->contacts->create() );
	}

	public function test_the_flag_is_on_while_the_email_is_being_sent() {

		$seen = null;

		add_action( 'phpmailer_init', function () use ( &$seen ) {
			$seen = is_sending();
		} );

		$this->email()->send( $this->contact() );

		$this->assertTrue( $seen, 'is_sending() should be on while PHPMailer is handed the email' );
	}

	public function test_the_flag_is_off_after_a_successful_send() {

		$this->assertTrue( (bool) $this->email()->send( $this->contact() ) );

		$this->assertFalse( is_sending() );
	}

	public function test_the_flag_is_off_when_the_email_is_not_ready() {

		$result = $this->email( [ 'status' => 'draft' ] )->send( $this->contact() );

		$this->assertWPError( $result );
		$this->assertSame( 'email_not_ready', $result->get_error_code() );
		$this->assertFalse( is_sending() );
	}

	public function test_the_flag_is_off_when_the_recipient_is_not_valid() {

		$result = $this->email()->send( 999999999 );

		$this->assertWPError( $result );
		$this->assertSame( 'no_recipient', $result->get_error_code() );
		$this->assertFalse( is_sending() );
	}

	public function test_the_flag_is_off_when_the_contact_is_undeliverable() {

		$contact = $this->contact();
		$contact->update( [ 'optin_status' => \Groundhogg\Preferences::BLOCKED ] );

		$result = $this->email()->send( $contact );

		$this->assertWPError( $result );
		$this->assertFalse( is_sending() );
	}

	public function test_the_flag_is_off_when_something_throws_while_sending() {

		add_action( 'groundhogg/email/before_send', function () {
			throw new RuntimeException( 'boom' );
		} );

		try {
			$this->email()->send( $this->contact() );
			$this->fail( 'the exception should have got out of send()' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'boom', $e->getMessage() );
		}

		$this->assertFalse( is_sending() );
	}
}
