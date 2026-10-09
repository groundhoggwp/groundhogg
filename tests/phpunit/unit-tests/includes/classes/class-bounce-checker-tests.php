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

	/* ---------------------------------------------------------------------
	 * which emails a cron run handles, and where the next one starts
	 * ------------------------------------------------------------------- */

	public function test_where_a_run_starts() {

		$now = 1_800_000_000;

		// the first time, 2 days back
		$this->assertSame( $now - 2 * DAY_IN_SECONDS, Bounce_Checker::start_time( false, $now, 0, false ) );

		// after a run that did all of them, a few hours before where it got to
		$this->assertSame( $now - 100 - Bounce_Checker::CRON_OVERLAP, Bounce_Checker::start_time( false, $now, $now - 100, false ) );

		// after a run that stopped at its limit, just after where it stopped, without looking back
		$this->assertSame( $now - 100 + 1, Bounce_Checker::start_time( false, $now, $now - 100, true ) );

		// a manual run is a set number of days, whatever the cron job did
		$this->assertSame( $now - 7 * DAY_IN_SECONDS, Bounce_Checker::start_time( true, $now, $now - 100, true ) );
		$this->assertSame( $now - 3 * DAY_IN_SECONDS, Bounce_Checker::start_time( true, $now, 0, false, 3 ) );
	}

	public function test_all_of_the_emails_when_there_are_not_more_than_the_limit() {

		[ $chosen, $capped, $last ] = Bounce_Checker::choose_emails( [ 3 => 300, 1 => 100, 2 => 200 ], 3 );

		$this->assertSame( [ 1 => 100, 2 => 200, 3 => 300 ], $chosen, 'the oldest first' );
		$this->assertFalse( $capped );
		$this->assertSame( 300, $last );

		[ $chosen, $capped, $last ] = Bounce_Checker::choose_emails( [], 3 );

		$this->assertSame( [], $chosen );
		$this->assertFalse( $capped );
		$this->assertSame( 0, $last );
	}

	public function test_the_oldest_up_to_the_limit_when_there_are_more() {

		[ $chosen, $capped, $last ] = Bounce_Checker::choose_emails( [ 5 => 500, 1 => 100, 4 => 400, 2 => 200, 3 => 300 ], 3 );

		$this->assertSame( [ 1 => 100, 2 => 200, 3 => 300 ], $chosen );
		$this->assertTrue( $capped );
		$this->assertSame( 300, $last );
	}

	public function test_the_last_second_is_finished_so_the_next_run_can_start_after_it() {

		// the 3rd and 4th are dated the same second, so are both handled, and the next run starts after it
		[ $chosen, $capped, $last ] = Bounce_Checker::choose_emails( [ 1 => 100, 2 => 200, 3 => 300, 4 => 300, 5 => 400 ], 3 );

		$this->assertSame( [ 1 => 100, 2 => 200, 3 => 300, 4 => 300 ], $chosen );
		$this->assertTrue( $capped );
		$this->assertSame( 300, $last );

		// and when it's the end of them, it isn't stopped
		[ $chosen, $capped ] = Bounce_Checker::choose_emails( [ 1 => 100, 2 => 200, 3 => 300, 4 => 300 ], 3 );

		$this->assertSame( [ 1 => 100, 2 => 200, 3 => 300, 4 => 300 ], $chosen );
		$this->assertFalse( $capped, 'there was nothing after it' );
	}

	public function test_no_limit() {
		[ $chosen, $capped ] = Bounce_Checker::choose_emails( [ 1 => 100, 2 => 200 ], 0 );

		$this->assertCount( 2, $chosen );
		$this->assertFalse( $capped );
	}

	/**
	 * Runs of the cron job over a mailbox, the way run() does them: look from where the last one got to, handle up to the
	 * limit, and keep where it got to
	 *
	 * @param array $inbox   message number => dated
	 * @param int   $limit
	 * @param int   $start   when the first run is
	 * @param int   $runs
	 *
	 * @return array[] the message numbers each run handled
	 */
	protected function simulate_cron( array $inbox, int $limit, int $start, int $runs ) {

		$cursor = 0;
		$capped = false;
		$handled = [];

		for ( $i = 0; $i < $runs; $i ++ ) {

			$started = $start + $i * HOUR_IN_SECONDS;
			$since   = Bounce_Checker::start_time( false, $started, $cursor, $capped );

			$emails = array_filter( $inbox, fn( $dated ) => $dated >= $since && $dated <= $started );

			[ $chosen, $hit_limit, $last ] = Bounce_Checker::choose_emails( $emails, $limit );

			$handled[] = array_keys( $chosen );
			$cursor    = $hit_limit ? $last : $started;
			$capped    = $hit_limit;
		}

		return $handled;
	}

	/**
	 * More than the limit in the 6 hours before where a run got to made every run read the same emails
	 */
	public function test_the_cron_job_gets_through_more_emails_than_its_limit_in_6_hours() {

		$start = 1_800_000_000;
		$inbox = [];

		// 450 emails in the hour before the first run, one every 8 seconds
		for ( $n = 1; $n <= 450; $n ++ ) {
			$inbox[ $n ] = $start - 3600 + $n * 8;
		}

		$runs = $this->simulate_cron( $inbox, 200, $start, 4 );

		$this->assertCount( 200, $runs[0] );
		$this->assertCount( 200, $runs[1] );
		$this->assertCount( 50, $runs[2] );

		// each email by itself while it catches up, none twice, none left out
		$caught_up = array_merge( $runs[0], $runs[1], $runs[2] );

		$this->assertSame( range( 1, 450 ), $caught_up );

	}

	public function test_the_cron_job_gets_through_more_emails_than_its_limit_in_one_second() {

		$start = 1_800_000_000;

		// 500 emails all dated the same second
		$inbox = array_fill( 1, 500, $start - 600 );

		$runs = $this->simulate_cron( $inbox, 200, $start, 3 );

		// all of them in the first run, there's no way to stop part way through a second
		$this->assertCount( 500, $runs[0] );

		// so that run wasn't stopped, and the ones after it look back the overlap, like after any run that did them all,
		// they don't get stuck on a limit that's smaller than a second of mail
		$this->assertCount( 500, $runs[1] );
		$this->assertCount( 500, $runs[2] );
	}

	public function test_the_cron_job_does_not_move_on_to_what_came_later_until_it_has_the_ones_before() {

		$start = 1_800_000_000;

		$inbox = [];
		for ( $n = 1; $n <= 250; $n ++ ) {
			$inbox[ $n ] = $start - 7200 + $n * 10;
		}

		// the one that arrives while it's catching up
		$inbox[251] = $start + 30 * MINUTE_IN_SECONDS;

		$runs = $this->simulate_cron( $inbox, 200, $start, 4 );

		$this->assertSame( range( 1, 200 ), $runs[0] );
		$this->assertContains( 251, array_merge( $runs[1], $runs[2], $runs[3] ), 'and it is handled when it gets to it' );
	}
}
