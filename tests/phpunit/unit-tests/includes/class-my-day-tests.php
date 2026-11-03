<?php

use Groundhogg\Admin\User\Admin_User;
use Groundhogg\Classes\Message;
use Groundhogg\Classes\Task;
use Groundhogg\Notifications\Signed_Link;
use Groundhogg\Pii;
use Groundhogg\Reporting\Email_Reports;
use Groundhogg\Reporting\My_Day;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_contactdata;

/**
 * The My Day email: who gets it and when, what's in it, and who isn't sent one.
 *
 * @see My_Day
 */
class My_Day_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables aren't rolled back between tests, so what a test looks at is what it made
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
		$_POST        = $_REQUEST = [];
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/email_reports/my_day_sections' );
		remove_all_filters( 'pre_wp_mail' );
		$this->set_limits_exceeded( false );
		wp_set_current_user( 0 );
		$_POST = $_REQUEST = [];
		parent::tearDown();
	}

	protected function create_rep( array $meta = [] ): int {

		$user_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		return $user_id;
	}

	protected function create_contact( string $first, int $owner_id, array $args = [] ) {
		return get_contactdata( self::factory()->contacts->create( array_merge( [
			'first_name' => $first,
			'last_name'  => $this->run_id,
			'email'      => $first . '@' . $this->run_id . '.example.com',
			'owner_id'   => $owner_id,
		], $args ) ) );
	}

	protected function create_task( int $user_id, $contact, string $summary, string $due = '-2 days' ): Task {

		$task = new Task();
		$task->create( [
			'object_type'    => 'contact',
			'object_id'      => $contact->get_id(),
			'summary'        => $summary,
			'user_id'        => $user_id,
			'due_date'       => ( new DateTimeHelper( $due ) )->ymdhis(),
			'date_completed' => '0000-00-00 00:00:00',
		] );

		return $task;
	}

	protected function create_reply( $contact, string $subject = 'Can we talk?' ): Message {

		$message = new Message();
		$message->create( [
			'object_type'  => 'contact',
			'object_id'    => $contact->get_id(),
			'direction'    => 'inbound',
			'from_address' => $contact->get_email(),
			'to_address'   => 'me@example.com',
			'subject'      => $subject,
			'content'      => '<p>Hello</p>',
			'is_read'      => 0,
		] );

		return $message;
	}

	/**
	 * Catch the emails instead of sending them
	 */
	protected function catch_mail(): ArrayObject {

		$sent = new ArrayObject();

		add_filter( 'pre_wp_mail', function ( $return, $atts ) use ( $sent ) {
			$sent->append( $atts );

			return true;
		}, 10, 2 );

		return $sent;
	}

	protected function time_at( string $when, string $timezone ): int {
		return ( new DateTime( $when, new DateTimeZone( $timezone ) ) )->getTimestamp();
	}

	/* ---------------------------------------------------------------------
	 * what a user chose
	 * ------------------------------------------------------------------- */

	public function test_it_goes_at_eight_in_the_site_timezone_until_the_user_chooses() {

		$user_id = $this->create_rep();

		$this->assertSame( 8, My_Day::get_hour( $user_id ) );
		$this->assertSame( wp_timezone()->getName(), My_Day::get_timezone( $user_id )->getName() );

		update_user_meta( $user_id, 'gh_my_day_hour', '6' );
		update_user_meta( $user_id, 'gh_my_day_timezone', 'Asia/Tokyo' );

		$this->assertSame( 6, My_Day::get_hour( $user_id ) );
		$this->assertSame( 'Asia/Tokyo', My_Day::get_timezone( $user_id )->getName() );
	}

	public function test_a_timezone_or_hour_that_is_not_one_is_not_used() {

		$user_id = $this->create_rep();

		update_user_meta( $user_id, 'gh_my_day_timezone', 'Mars/Olympus_Mons' );
		update_user_meta( $user_id, 'gh_my_day_hour', '99' );

		$this->assertSame( wp_timezone()->getName(), My_Day::get_timezone( $user_id )->getName() );
		$this->assertSame( 23, My_Day::get_hour( $user_id ) );
	}

	public function test_saving_what_the_user_chose() {

		$user_id = $this->create_rep();

		My_Day::save_preferences( $user_id, [ 'enabled' => '1', 'team' => '1', 'hour' => '7', 'timezone' => 'Europe/London' ] );

		$this->assertTrue( My_Day::is_subscribed( $user_id ) );
		$this->assertSame( 7, My_Day::get_hour( $user_id ) );
		$this->assertSame( 'Europe/London', My_Day::get_timezone( $user_id )->getName() );
		$this->assertFalse( My_Day::wants_team( get_userdata( $user_id ) ), 'a rep has no team to include' );

		My_Day::save_preferences( $user_id, [ 'timezone' => 'Not/AZone' ] );

		$this->assertFalse( My_Day::is_subscribed( $user_id ) );
		$this->assertEmpty( get_user_meta( $user_id, 'gh_my_day_timezone', true ) );
	}

	public function test_only_a_manager_can_have_their_team() {

		$manager = self::factory()->user->create( [ 'role' => 'sales_manager' ] );

		My_Day::save_preferences( $manager, [ 'enabled' => '1', 'team' => '1' ] );

		$this->assertTrue( My_Day::wants_team( get_userdata( $manager ) ) );
	}

	public function test_turning_it_on_after_its_time_waits_for_tomorrow() {

		$user_id = $this->create_rep();
		$hour    = (int) ( new DateTimeHelper( 'now', My_Day::get_timezone( $user_id ) ) )->format( 'G' );

		// the hour it's in now, so its time today has come
		My_Day::save_preferences( $user_id, [ 'enabled' => '1', 'hour' => (string) $hour ] );

		$this->assertFalse( My_Day::is_due( $user_id ) );
		$this->assertTrue( My_Day::is_due( $user_id, time() + DAY_IN_SECONDS ) );
	}

	public function test_someone_without_access_to_contacts_cannot_have_it() {

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		My_Day::save_preferences( $user_id, [ 'enabled' => '1' ] );

		$this->assertFalse( My_Day::is_subscribed( $user_id ) );
		$this->assertEmpty( get_user_meta( $user_id, 'gh_my_day', true ) );
	}

	/* ---------------------------------------------------------------------
	 * when it's sent
	 * ------------------------------------------------------------------- */

	public function test_it_is_due_in_the_hours_after_the_chosen_time_in_the_users_timezone() {

		$user_id = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );

		$this->assertFalse( My_Day::is_due( $user_id, $this->time_at( '2026-11-03 07:59:00', 'America/New_York' ) ) );
		$this->assertTrue( My_Day::is_due( $user_id, $this->time_at( '2026-11-03 08:00:00', 'America/New_York' ) ) );
		$this->assertTrue( My_Day::is_due( $user_id, $this->time_at( '2026-11-03 13:59:00', 'America/New_York' ) ) );
		$this->assertFalse( My_Day::is_due( $user_id, $this->time_at( '2026-11-03 14:00:00', 'America/New_York' ) ), 'not sent in the evening because it was missed in the morning' );
	}

	public function test_the_same_moment_is_the_morning_for_one_user_and_not_another() {

		$new_york = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );
		$tokyo    = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'Asia/Tokyo' ] );

		$now = $this->time_at( '2026-11-03 08:30:00', 'America/New_York' );

		$this->assertTrue( My_Day::is_due( $new_york, $now ) );
		$this->assertFalse( My_Day::is_due( $tokyo, $now ) );
		$this->assertTrue( My_Day::is_due( $tokyo, $this->time_at( '2026-11-03 08:30:00', 'Asia/Tokyo' ) ) );
	}

	public function test_it_goes_once_a_day() {

		$user_id = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );
		$now     = $this->time_at( '2026-11-03 08:10:00', 'America/New_York' );

		$this->assertTrue( My_Day::is_due( $user_id, $now ) );

		My_Day::mark_sent( $user_id, $now );

		$this->assertFalse( My_Day::is_due( $user_id, $now + HOUR_IN_SECONDS ) );
		$this->assertTrue( My_Day::is_due( $user_id, $this->time_at( '2026-11-04 08:10:00', 'America/New_York' ) ) );
	}

	public function test_the_schedule_sends_to_each_user_whose_time_it_is_once() {

		$sent      = $this->catch_mail();
		$due       = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );
		$not_yet   = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'Asia/Tokyo' ] );
		$opted_out = $this->create_rep( [ 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );

		foreach ( [ $due, $not_yet, $opted_out ] as $user_id ) {
			$this->create_task( $user_id, $this->create_contact( 'ada' . $user_id, $user_id ), 'Overdue ' . $this->run_id, '-3 days' );
		}

		$now = $this->time_at( '2026-11-03 08:30:00', 'America/New_York' );

		$this->assertGreaterThanOrEqual( 1, My_Day::dispatch( $now ) );

		$this->assertNotEmpty( get_user_meta( $due, 'gh_my_day_last_sent', true ) );
		$this->assertEmpty( get_user_meta( $not_yet, 'gh_my_day_last_sent', true ) );
		$this->assertEmpty( get_user_meta( $opted_out, 'gh_my_day_last_sent', true ) );

		$recipients = [];
		foreach ( $sent as $atts ) {
			$recipients = array_merge( $recipients, (array) $atts['to'] );
		}

		$this->assertContains( get_userdata( $due )->user_email, $recipients );
		$this->assertNotContains( get_userdata( $not_yet )->user_email, $recipients );
		$this->assertNotContains( get_userdata( $opted_out )->user_email, $recipients );

		// the next time the schedule runs there's nobody left, and nothing is sent twice
		$count = count( $sent );
		$this->assertSame( 0, My_Day::dispatch( $now + HOUR_IN_SECONDS ) );
		$this->assertCount( $count, $sent );
	}

	/**
	 * Say that the time or memory limits have been reached, or that they haven't
	 */
	protected function set_limits_exceeded( bool $exceeded ) {
		if ( ! $exceeded ) {
			Limits::reset();

			return;
		}

		$property = new ReflectionProperty( Limits::class, 'limits_exceeded' );
		$property->setAccessible( true );
		$property->setValue( null, true );
	}

	public function test_the_schedule_leaves_the_rest_for_the_next_run_when_it_is_out_of_limits() {

		$now = $this->time_at( '2026-11-03 08:30:00', 'America/New_York' );

		// anybody else who is due goes first
		$this->catch_mail();
		My_Day::dispatch( $now );

		$first  = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );
		$second = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );

		foreach ( [ $first, $second ] as $user_id ) {
			$this->create_task( $user_id, $this->create_contact( 'ada' . $user_id, $user_id ), 'Overdue ' . $this->run_id, '-3 days' );
		}

		// the limits are reached as soon as the first email is out
		add_filter( 'pre_wp_mail', function ( $return ) {
			$this->set_limits_exceeded( true );

			return true;
		} );

		try {
			$this->assertSame( 1, My_Day::dispatch( $now ) );
			$this->assertNotEmpty( get_user_meta( $first, 'gh_my_day_last_sent', true ) );
			$this->assertEmpty( get_user_meta( $second, 'gh_my_day_last_sent', true ) );
		} finally {
			$this->set_limits_exceeded( false );
		}

		$this->assertSame( 1, My_Day::dispatch( $now + HOUR_IN_SECONDS ) );
		$this->assertNotEmpty( get_user_meta( $second, 'gh_my_day_last_sent', true ) );
	}

	public function test_a_user_it_fails_for_does_not_stop_the_others_or_go_again() {

		$this->catch_mail();

		$now = $this->time_at( '2026-11-03 08:30:00', 'America/New_York' );

		My_Day::dispatch( $now );

		$first  = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );
		$second = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_hour' => 8, 'gh_my_day_timezone' => 'America/New_York' ] );

		$failed = [];

		add_filter( 'groundhogg/email_reports/my_day_sections', function ( $sections ) {
			$sections['boom'] = [
				'id'       => 'boom',
				'title'    => 'Boom',
				'priority' => 1,
				'render'   => function () {
					throw new \RuntimeException( 'nope' );
				},
			];

			return $sections;
		} );

		add_action( 'groundhogg/my_day/failed', function ( $user_id ) use ( &$failed ) {
			$failed[] = $user_id;
		} );

		$this->assertSame( 2, My_Day::dispatch( $now ) );
		$this->assertContains( $first, $failed );
		$this->assertContains( $second, $failed );
		$this->assertSame( 0, My_Day::dispatch( $now + HOUR_IN_SECONDS ) );
	}

	/* ---------------------------------------------------------------------
	 * what's in it
	 * ------------------------------------------------------------------- */

	public function test_a_day_with_nothing_in_it_sends_nothing() {

		$sent = $this->catch_mail();
		$rep  = $this->create_rep( [ 'gh_my_day' => 1 ] );

		$this->assertFalse( My_Day::build( get_userdata( $rep ) ) );
		$this->assertFalse( My_Day::send_to_user( $rep ) );
		$this->assertCount( 0, $sent );
	}

	public function test_a_task_that_is_due_is_in_it_and_one_that_is_not_yet_is_not() {

		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'ada', $rep );

		$this->create_task( $rep, $contact, 'Overdue ' . $this->run_id, '-3 days' );
		$this->create_task( $rep, $contact, 'Far off ' . $this->run_id, '+30 days' );

		$email = My_Day::build( get_userdata( $rep ) );

		$this->assertStringContainsString( 'Overdue ' . $this->run_id, $email['html'] );
		$this->assertStringNotContainsString( 'Far off ' . $this->run_id, $email['html'] );
		$this->assertStringContainsString( Email_Reports::UNSUBSCRIBE_PLACEHOLDER, $email['html'] );
		$this->assertStringContainsString( '[', $email['subject'] );
	}

	public function test_the_task_links_work_for_that_user_and_that_task() {

		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'ada', $rep );
		$task    = $this->create_task( $rep, $contact, 'Link me ' . $this->run_id );

		$email = My_Day::build( get_userdata( $rep ) );

		preg_match_all( '#/' . Signed_Link::ROUTE . '/([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/#', $email['html'], $matches );

		$actions = [];

		foreach ( $matches[1] as $token ) {
			$link                       = Signed_Link::parse( $token );
			$actions[ $link['action'] ] = $link;
		}

		$this->assertSame( [ $rep, (string) $task->ID ], [ $actions['snooze']['user_id'], $actions['snooze']['object'] ] );
		$this->assertSame( [ $rep, (string) $task->ID ], [ $actions['complete']['user_id'], $actions['complete']['object'] ] );
	}

	public function test_what_a_task_says_is_never_taken_for_something_to_replace() {

		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'ada', $rep );

		$this->create_task( $rep, $contact, 'Fix site_url and the_task_link & more ' . $this->run_id );

		$html = My_Day::build( get_userdata( $rep ) )['html'];

		$this->assertStringContainsString( 'Fix site_url and the_task_link &amp; more ' . $this->run_id, $html );
	}

	public function test_replies_that_have_not_been_read_are_in_it() {

		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'grace', $rep );

		$this->create_reply( $contact, 'About the quote ' . $this->run_id );

		$html = My_Day::build( get_userdata( $rep ) )['html'];

		$this->assertStringContainsStringIgnoringCase( 'grace', $html );
		$this->assertStringContainsString( 'About the quote ' . $this->run_id, $html );
	}

	public function test_replies_on_contacts_of_other_reps_are_not_in_it() {

		$rep   = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$other = $this->create_rep();

		$this->create_reply( $this->create_contact( 'theirs', $other ), 'Not for you ' . $this->run_id );
		$this->create_task( $other, $this->create_contact( 'theirs2', $other ), 'Not yours ' . $this->run_id );

		$this->assertFalse( My_Day::build( get_userdata( $rep ) ) );
	}

	public function test_new_contacts_are_in_it() {

		$rep = $this->create_rep( [ 'gh_my_day' => 1 ] );

		$this->create_contact( 'newlead', $rep );

		$html = My_Day::build( get_userdata( $rep ) )['html'];

		$this->assertStringContainsStringIgnoringCase( 'newlead', $html );
	}

	public function test_a_user_who_cannot_see_who_a_contact_is_does_not_get_their_name() {

		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'secretname', $rep );

		get_userdata( $rep )->add_cap( Pii::CAP, false );

		$this->create_reply( $contact, 'Mentions secretname ' . $this->run_id );

		$html = My_Day::build( get_userdata( $rep ) )['html'];

		$this->assertStringNotContainsString( 'secretname', $html );
		$this->assertStringContainsString( 'Contact #' . $contact->get_id(), $html );
		$this->assertSame( 0, get_current_user_id(), 'built as them, and not left as them' );
	}

	/* ---------------------------------------------------------------------
	 * the team
	 * ------------------------------------------------------------------- */

	protected function create_manager( int ...$team ): int {

		$manager = self::factory()->user->create( [ 'role' => 'sales_manager' ] );
		update_user_meta( $manager, 'gh_sales_team_ids', $team );

		return $manager;
	}

	public function test_a_manager_gets_the_tasks_of_their_team_in_a_section_of_its_own() {

		$rep     = $this->create_rep();
		$manager = $this->create_manager( $rep );

		$this->create_task( $rep, $this->create_contact( 'teamlead', $rep ), 'Team task ' . $this->run_id );

		update_user_meta( $manager, 'gh_my_day', 1 );
		$without = My_Day::build( get_userdata( $manager ) );

		$this->assertFalse( $without, 'the team is only in it for a manager who asked' );

		update_user_meta( $manager, 'gh_my_day_team', 1 );
		$email = My_Day::build( get_userdata( $manager ) );

		$this->assertStringContainsString( 'Team task ' . $this->run_id, $email['html'] );
		$this->assertStringContainsString( get_userdata( $rep )->display_name, $email['html'] );
		$this->assertStringContainsString( 'Your team', $email['html'] );
	}

	public function test_the_team_is_only_the_team() {

		$rep     = $this->create_rep();
		$outside = $this->create_rep();
		$manager = $this->create_manager( $rep );

		$this->create_task( $outside, $this->create_contact( 'outsider', $outside ), 'Outside ' . $this->run_id );

		update_user_meta( $manager, 'gh_my_day_team', 1 );

		$this->assertFalse( My_Day::build( get_userdata( $manager ) ) );
	}

	public function test_a_rep_cannot_get_the_team_section_by_setting_it() {

		$rep   = $this->create_rep( [ 'gh_my_day' => 1, 'gh_my_day_team' => 1 ] );
		$other = $this->create_rep();

		$this->create_task( $other, $this->create_contact( 'someone', $other ), 'Not the rep team ' . $this->run_id );

		$this->assertFalse( My_Day::build( get_userdata( $rep ) ) );
	}

	/* ---------------------------------------------------------------------
	 * sending
	 * ------------------------------------------------------------------- */

	public function test_it_is_sent_to_the_user_with_a_link_that_unsubscribes_them() {

		$sent    = $this->catch_mail();
		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'ada', $rep );

		$this->create_task( $rep, $contact, 'Send it ' . $this->run_id );

		$this->assertTrue( My_Day::send_to_user( $rep ) );
		$this->assertCount( 1, $sent );

		$mail = $sent[0];

		$this->assertSame( get_userdata( $rep )->user_email, (string) ( is_array( $mail['to'] ) ? $mail['to'][0] : $mail['to'] ) );
		$this->assertStringNotContainsString( Email_Reports::UNSUBSCRIBE_PLACEHOLDER, $mail['message'] );

		$found = false;

		preg_match_all( '#/' . Signed_Link::ROUTE . '/([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/#', $mail['message'], $matches );

		foreach ( $matches[1] as $token ) {
			$link = Signed_Link::parse( $token );

			if ( $link['action'] === 'unsub' ) {
				$found = true;
				$this->assertSame( [ $rep, 'my_day' ], [ $link['user_id'], $link['object'] ] );
			}
		}

		$this->assertTrue( $found, 'the email has an unsubscribe link' );
	}

	public function test_it_is_not_sent_to_someone_who_is_no_longer_subscribed() {

		$sent    = $this->catch_mail();
		$rep     = $this->create_rep( [ 'gh_my_day' => 1 ] );
		$contact = $this->create_contact( 'ada', $rep );

		$this->create_task( $rep, $contact, 'Too late ' . $this->run_id );

		delete_user_meta( $rep, 'gh_my_day' );

		$this->assertFalse( My_Day::send_to_user( $rep ) );
		$this->assertCount( 0, $sent );
	}

	/* ---------------------------------------------------------------------
	 * add-ons
	 * ------------------------------------------------------------------- */

	public function test_an_addon_can_add_a_section_and_one_with_nothing_to_say_is_left_out() {

		$rep = $this->create_rep( [ 'gh_my_day' => 1 ] );

		add_filter( 'groundhogg/email_reports/my_day_sections', function ( $sections ) {

			$sections['deals'] = [
				'title'    => 'Deals closing',
				'priority' => 5,
				'render'   => function ( $user, $scope, $context ) {
					return My_Day::note( 'Deal ' . $user->ID . ' ' . $scope );
				},
			];

			$sections['appointments'] = [
				'title'  => 'Appointments',
				'render' => function () {
					return '';
				},
			];

			$sections['broken'] = [ 'title' => 'No callback' ];

			return $sections;
		} );

		$email = My_Day::build( get_userdata( $rep ) );

		$this->assertStringContainsString( 'Deals closing', $email['html'] );
		$this->assertStringContainsString( 'Deal ' . $rep . ' me', $email['html'] );
		$this->assertStringNotContainsString( 'Appointments', $email['html'] );
		$this->assertStringNotContainsString( 'No callback', $email['html'] );
	}

	public function test_sections_show_in_the_order_of_their_priority() {

		add_filter( 'groundhogg/email_reports/my_day_sections', function ( $sections ) {
			$sections['late']  = [ 'priority' => 99, 'render' => '__return_empty_string' ];
			$sections['early'] = [ 'priority' => 1, 'render' => '__return_empty_string' ];

			return $sections;
		} );

		$ids = array_keys( My_Day::get_sections() );

		$this->assertSame( 'early', $ids[0] );
		$this->assertSame( 'late', end( $ids ) );
	}

	public function test_a_section_can_ask_for_what_the_user_needs_to_have_it() {

		$rep = $this->create_rep( [ 'gh_my_day' => 1 ] );

		add_filter( 'groundhogg/email_reports/my_day_sections', function ( $sections ) {
			$sections['admin_only'] = [
				'title'  => 'Admin only',
				'cap'    => 'manage_options',
				'render' => function () {
					return My_Day::note( 'Only admins' );
				},
			];

			return $sections;
		} );

		$this->assertFalse( My_Day::build( get_userdata( $rep ) ) );
	}

	/* ---------------------------------------------------------------------
	 * the profile
	 * ------------------------------------------------------------------- */

	public function test_the_profile_saves_it() {

		$rep = $this->create_rep();
		wp_set_current_user( $rep );

		$_POST = $_REQUEST = [
			'gh_my_day_present'  => '1',
			'gh_my_day'          => '1',
			'gh_my_day_hour'     => '9',
			'gh_my_day_timezone' => 'Europe/Berlin',
		];

		Admin_User::instance()->save_fields( $rep );

		$this->assertTrue( My_Day::is_subscribed( $rep ) );
		$this->assertSame( 9, My_Day::get_hour( $rep ) );
		$this->assertSame( 'Europe/Berlin', My_Day::get_timezone( $rep )->getName() );

		// unchecked boxes aren't posted
		$_POST = $_REQUEST = [ 'gh_my_day_present' => '1', 'gh_my_day_hour' => '9' ];

		Admin_User::instance()->save_fields( $rep );

		$this->assertFalse( My_Day::is_subscribed( $rep ) );
	}

	public function test_a_profile_form_that_does_not_have_it_leaves_it_alone() {

		$rep = $this->create_rep( [ 'gh_my_day' => 1 ] );
		wp_set_current_user( $rep );

		Admin_User::instance()->save_fields( $rep );

		$this->assertTrue( My_Day::is_subscribed( $rep ) );
	}

	public function test_the_profile_shows_it_to_anyone_who_can_see_contacts_and_the_team_to_managers() {

		$rep     = $this->create_rep();
		$manager = $this->create_manager( $rep );

		wp_set_current_user( $rep );
		ob_start();
		Admin_User::instance()->fields_table( get_userdata( $rep ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="gh_my_day"', $html );
		$this->assertStringContainsString( 'name="gh_my_day_hour"', $html );
		$this->assertStringContainsString( 'name="gh_my_day_timezone"', $html );
		$this->assertStringNotContainsString( 'name="gh_my_day_team"', $html );
		$this->assertStringNotContainsString( 'name="gh_broadcast_results"', $html, 'the reports are for people who can see reports' );

		wp_set_current_user( $manager );
		ob_start();
		Admin_User::instance()->fields_table( get_userdata( $manager ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="gh_my_day_team"', $html );
	}
}
