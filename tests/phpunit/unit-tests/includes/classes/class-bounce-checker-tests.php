<?php

use Groundhogg\Bounce_Checker;
use Groundhogg\Preferences;
use function Groundhogg\tracking;

/**
 * What the bounce checker makes of a failure report. The IMAP side, which emails it looks at, isn't here, it needs a mailbox.
 */
class Bounce_Checker_Tests extends GH_UnitTestCase {

	protected function checker(): Bounce_Checker {
		return new Bounce_Checker();
	}

	/**
	 * A delivery status notification, what a mail server sends back when it can't deliver
	 */
	protected function dsn( string $recipient, string $action, string $status, string $diagnostic = '' ): string {

		$diagnostic = $diagnostic ?: sprintf( '%s %s mailbox problem', substr( $status, 0, 1 ) . '50', $status );

		return implode( "\r\n", [
			'From: MAILER-DAEMON@mx.example.com',
			'To: bounces@site.example.com',
			'Subject: Undelivered Mail Returned to Sender',
			'MIME-Version: 1.0',
			'Content-Type: multipart/report; report-type=delivery-status; boundary="BOUNDARY"',
			'',
			'--BOUNDARY',
			'Content-Type: text/plain; charset=us-ascii',
			'',
			'This is the mail system at host mx.example.com.',
			'',
			'--BOUNDARY',
			'Content-Type: message/delivery-status',
			'',
			'Reporting-MTA: dns; mx.example.com',
			'',
			'Final-Recipient: rfc822; ' . $recipient,
			'Original-Recipient: rfc822;' . $recipient,
			'Action: ' . $action,
			'Status: ' . $status,
			'Diagnostic-Code: smtp; ' . $diagnostic,
			'',
			'--BOUNDARY',
			'Content-Type: message/rfc822',
			'',
			'From: site@site.example.com',
			'To: ' . $recipient,
			'Subject: Hello',
			'',
			'The email',
			'',
			'--BOUNDARY--',
			'',
		] );
	}

	/**
	 * Run a report through the bounce handler and the checker, like a message from the inbox is
	 *
	 * @return array the rows for every recipient in the report
	 */
	protected function handle( string $dsn ): array {

		if ( ! class_exists( '\BounceHandler' ) ) {
			include_once dirname( __DIR__, 5 ) . '/includes/lib/PHP-Bounce-Handler-master/bounce_driver.class.php';
		}

		$rows = [];

		foreach ( ( new \BounceHandler() )->get_the_facts( $dsn ) as $the ) {
			$rows[] = $this->checker()->handle_recipient( $the );
		}

		return $rows;
	}

	protected function contact( string $email ) {
		return $this->factory()->contacts->create_and_get( [ 'email' => $email ] );
	}

	protected function status_of( $contact ): int {
		return ( new \Groundhogg\Contact( $contact->get_id() ) )->get_optin_status();
	}

	/**
	 * @dataProvider classes
	 */
	public function test_classify( $action, $status, $expected ) {
		$this->assertSame( $expected, Bounce_Checker::classify( [ 'action' => $action, 'status' => $status ] ) );
	}

	public function classes() {
		return [
			'address not found'           => [ 'failed', '5.1.1', 'hard' ],
			'bad destination'             => [ 'failed', '5.1.2', 'hard' ],
			'refused by policy'           => [ 'failed', '5.7.0', 'hard' ],
			'mailbox full is soft'        => [ 'failed', '4.2.2', 'soft' ],
			'any 4 is soft'               => [ 'failed', '4.4.7', 'soft' ],
			'a capital F'                 => [ 'Failed', '5.1.1', 'hard' ],
			'spaces around it'            => [ " failed\r\n", '5.1.1', 'hard' ],
			'failed with no status'       => [ 'failed', '', 'hard' ],
			'a 5 status, whatever it says' => [ 'delivered', '5.1.1', 'hard' ],
			'no action, a 5 status'       => [ '', '5.2.0', 'hard' ],
			'transient'                   => [ 'transient', '', 'soft' ],
			'delayed'                     => [ 'delayed', '4.4.1', 'soft' ],
			'delayed, no status'          => [ 'Delayed', '', 'soft' ],
			'an auto reply'               => [ 'autoresponse', '', 'skip' ],
			'nothing'                     => [ '', '', 'skip' ],
		];
	}

	public function test_a_permanent_failure_marks_the_contact_as_bounced() {

		$contact = $this->contact( 'gone@example.com' );
		$this->assertNotSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );

		$rows = $this->handle( $this->dsn( 'gone@example.com', 'failed', '5.1.1' ) );

		$this->assertCount( 1, $rows );
		$this->assertSame( 'hard', $rows[0]['decision'] );
		$this->assertSame( 'gone@example.com', $rows[0]['recipient'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
		$this->assertNotEmpty( ( new \Groundhogg\Contact( $contact->get_id() ) )->get_notes() );
	}

	public function test_a_policy_failure_marks_the_contact_as_bounced() {

		$contact = $this->contact( 'blocked@example.com' );

		$rows = $this->handle( $this->dsn( 'blocked@example.com', 'failed', '5.7.0' ) );

		$this->assertSame( 'hard', $rows[0]['decision'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
	}

	public function test_an_action_in_a_different_case_still_counts() {

		$contact = $this->contact( 'upper@example.com' );

		$rows = $this->handle( $this->dsn( 'upper@example.com', 'Failed', '5.1.1' ) );

		$this->assertSame( 'hard', $rows[0]['decision'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
	}

	public function test_a_full_mailbox_is_not_a_hard_bounce_even_when_the_action_is_failed() {

		$contact = $this->contact( 'full@example.com' );
		$before  = $this->status_of( $contact );

		$rows = $this->handle( $this->dsn( 'full@example.com', 'failed', '4.2.2' ) );

		$this->assertSame( 'soft', $rows[0]['decision'] );
		$this->assertSame( $before, $this->status_of( $contact ) );
	}

	public function test_a_delayed_message_is_left_alone() {

		$contact = $this->contact( 'slow@example.com' );
		$before  = $this->status_of( $contact );

		$rows = $this->handle( $this->dsn( 'slow@example.com', 'delayed', '4.4.1', '451 4.4.1 connection timed out' ) );

		$this->assertSame( 'soft', $rows[0]['decision'] );
		$this->assertSame( $before, $this->status_of( $contact ) );
	}

	public function test_a_recipient_that_is_not_a_contact_is_counted_and_nothing_else_happens() {

		$rows = $this->handle( $this->dsn( 'nobody-' . uniqid() . '@example.com', 'failed', '5.1.1' ) );

		$this->assertCount( 1, $rows );
		$this->assertSame( 'not_found', $rows[0]['decision'] );
	}

	public function test_a_contact_that_is_already_bounced_is_counted_not_noted_again() {

		$contact = $this->contact( 'twice@example.com' );

		$this->assertSame( 'hard', $this->handle( $this->dsn( 'twice@example.com', 'failed', '5.1.1' ) )[0]['decision'] );

		$notes = count( ( new \Groundhogg\Contact( $contact->get_id() ) )->get_notes() );

		$this->assertSame( 'already', $this->handle( $this->dsn( 'twice@example.com', 'failed', '5.1.1' ) )[0]['decision'] );
		$this->assertCount( $notes, ( new \Groundhogg\Contact( $contact->get_id() ) )->get_notes() );
	}

	/**
	 * An empty value given to get_contactdata() means whoever is being tracked
	 */
	public function test_a_report_with_no_recipient_does_not_bounce_the_tracked_contact() {

		$tracked = $this->contact( 'tracked@example.com' );
		tracking()->set_current_contact( $tracked );
		$this->assertSame( $tracked->get_id(), tracking()->get_current_contact()->get_id() );

		$before = $this->status_of( $tracked );

		$row = $this->checker()->handle_recipient( [ 'recipient' => '', 'action' => 'failed', 'status' => '5.1.1' ] );
		$this->assertSame( 'skipped', $row['decision'] );

		$row = $this->checker()->handle_recipient( [ 'recipient' => '<>', 'action' => 'failed', 'status' => '5.1.1' ] );
		$this->assertSame( 'skipped', $row['decision'] );

		$this->assertSame( $before, $this->status_of( $tracked ) );
	}

	public function test_the_address_is_found_with_angle_brackets_and_capitals() {

		$contact = $this->contact( 'brackets@example.com' );

		$row = $this->checker()->handle_recipient( [ 'recipient' => '<Brackets@Example.com>', 'action' => 'failed', 'status' => '5.1.1' ] );

		$this->assertSame( 'hard', $row['decision'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
	}

	public function test_a_failure_with_no_status_still_has_a_note() {

		$contact = $this->contact( 'nostatus@example.com' );

		$row = $this->checker()->handle_recipient( [ 'recipient' => 'nostatus@example.com', 'action' => 'failed', 'status' => '' ] );

		$this->assertSame( 'hard', $row['decision'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
	}

	/**
	 * The bounce handler used to turn a failure into a temporary one when what the mail server said sounded temporary,
	 * even when the status of the report was a permanent one
	 */
	public function test_a_permanent_status_is_not_changed_by_a_diagnostic_that_sounds_temporary() {

		$contact = $this->contact( 'rewritten@example.com' );

		$rows = $this->handle( $this->dsn( 'rewritten@example.com', 'failed', '5.1.1', '451 4.3.0 try again later' ) );

		$this->assertSame( '5.1.1', $rows[0]['status'] );
		$this->assertSame( 'hard', $rows[0]['decision'] );
		$this->assertSame( Preferences::HARD_BOUNCE, $this->status_of( $contact ) );
	}

	public function test_a_failure_with_a_temporary_status_and_a_temporary_diagnostic_is_still_soft() {

		$contact = $this->contact( 'temporary@example.com' );
		$before  = $this->status_of( $contact );

		$rows = $this->handle( $this->dsn( 'temporary@example.com', 'failed', '4.4.1', '451 4.3.0 try again later' ) );

		$this->assertSame( 'soft', $rows[0]['decision'] );
		$this->assertSame( $before, $this->status_of( $contact ) );
	}

	public function test_summary() {
		$this->assertSame(
			'12 emails checked. 3 marked as bounced, 2 soft bounces left alone, 1 already bounced, 4 recipients not found in your contacts, 5 not understood.',
			$this->checker()->summarize( [ 'messages' => 12, 'hard' => 3, 'soft' => 2, 'already' => 1, 'not_found' => 4, 'skipped' => 5 ] )
		);
	}
}
