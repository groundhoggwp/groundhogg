<?php

use Groundhogg\Classes\Task;
use Groundhogg\Notifications\Actions;
use Groundhogg\Notifications\Signed_Link;
use Groundhogg\Notifications\Subscriptions;
use Groundhogg\Reporting\Email_Reports;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\base64url_encode;
use function Groundhogg\compute_signature;
use function Groundhogg\get_contactdata;

/**
 * The links in notification emails that work without signing in: unsubscribe, snooze a task and complete a task.
 *
 * @see Signed_Link   the signed token
 * @see Actions       the page behind it, which only asks until the form is sent
 * @see Subscriptions what a user has asked to get
 * @see Email_Reports::mail_report() puts a link of their own in each email
 */
class Notification_Links_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function create_rep(): int {
		return self::factory()->user->create( [ 'role' => 'sales_rep' ] );
	}

	protected function create_task( int $user_id, array $args = [] ): Task {

		$contact = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $user_id ] ) );

		$task = new Task();
		$task->create( array_merge( [
			'object_type'    => 'contact',
			'object_id'      => $contact->get_id(),
			'summary'        => 'Call back ' . uniqid(),
			'user_id'        => $user_id,
			'due_date'       => ( new DateTimeHelper( '+1 day 10:00' ) )->ymdhis(),
			'date_completed' => '0000-00-00 00:00:00',
		], $args ) );

		return $task;
	}

	protected function token( string $action, int $user_id, string $object = '' ): string {
		return Signed_Link::token( $action, $user_id, $object );
	}

	/* ---------------------------------------------------------------------
	 * the signed token
	 * ------------------------------------------------------------------- */

	public function test_a_token_says_what_it_was_made_for() {

		$parsed = Signed_Link::parse( $this->token( 'snooze', 42, '1234' ) );

		$this->assertSame( 'snooze', $parsed['action'] );
		$this->assertSame( 42, $parsed['user_id'] );
		$this->assertSame( '1234', $parsed['object'] );
		$this->assertFalse( $parsed['expired'] );
	}

	public function test_a_token_that_was_changed_is_not_accepted() {

		list( $payload, $signature ) = explode( '.', $this->token( 'unsub', 42, 'my_day' ) );

		// for someone else, with the signature it had
		$other = base64url_encode( str_replace( '|' . dechex( 42 ) . '|', '|' . dechex( 43 ) . '|', \Groundhogg\base64url_decode( $payload ) ) );

		$this->assertFalse( Signed_Link::parse( $other . '.' . $signature ) );
		$this->assertFalse( Signed_Link::parse( $payload . '.' . substr( $signature, 0, -2 ) . 'AA' ) );
		$this->assertFalse( Signed_Link::parse( $payload ) );
		$this->assertFalse( Signed_Link::parse( '' ) );
		$this->assertFalse( Signed_Link::parse( 'not.a.token' ) );
	}

	public function test_a_signature_made_for_something_else_is_not_accepted() {

		// signed with the same secret, the same way, like the open tracking link of an email
		$payload = implode( '|', [ 'o', dechex( 42 ), dechex( 7 ) ] );
		$token   = base64url_encode( $payload ) . '.' . base64url_encode( compute_signature( $payload, 16 ) );

		$this->assertFalse( Signed_Link::parse( $token ) );
	}

	public function test_a_token_expires_and_is_still_recognised() {

		$token = Signed_Link::token( 'unsub', 42, 'my_day', HOUR_IN_SECONDS, time() - DAY_IN_SECONDS );

		$parsed = Signed_Link::parse( $token );

		$this->assertTrue( $parsed['expired'] );
		$this->assertSame( 'my_day', $parsed['object'] );
	}

	public function test_the_url_is_under_the_managed_page() {

		$url = Signed_Link::url( 'unsub', 42, 'my_day' );

		$this->assertStringContainsString( '/' . Signed_Link::ROUTE . '/', $url );
		$this->assertSame( 42, Signed_Link::parse( basename( untrailingslashit( $url ) ) )['user_id'] );
	}

	/* ---------------------------------------------------------------------
	 * unsubscribe
	 * ------------------------------------------------------------------- */

	public function test_opening_the_unsubscribe_link_asks_and_does_not_unsubscribe() {

		$user_id = $this->create_rep();
		update_user_meta( $user_id, 'gh_my_day', 1 );

		$result = ( new Actions() )->handle( $this->token( 'unsub', $user_id, 'my_day' ) );

		$this->assertSame( 200, $result['status'] );
		$this->assertStringContainsString( '<form method="post"', $result['body'] );
		$this->assertTrue( Subscriptions::is_subscribed( $user_id, 'my_day' ) );
	}

	public function test_sending_the_form_unsubscribes_from_all_of_it_without_signing_in() {

		$user_id = $this->create_rep();
		update_user_meta( $user_id, 'gh_my_day', 1 );
		update_user_meta( $user_id, 'gh_my_day_team', 1 );
		update_user_meta( $user_id, 'gh_weekly_overview', 1 );

		$this->assertSame( 0, get_current_user_id() );

		$result = ( new Actions() )->handle( $this->token( 'unsub', $user_id, 'my_day' ), true );

		$this->assertSame( 200, $result['status'] );
		$this->assertFalse( Subscriptions::is_subscribed( $user_id, 'my_day' ) );
		$this->assertEmpty( get_user_meta( $user_id, 'gh_my_day_team', true ) );

		// only the one it was for
		$this->assertNotEmpty( get_user_meta( $user_id, 'gh_weekly_overview', true ) );
	}

	public function test_an_unsubscribe_link_is_for_its_user_only() {

		$user_id  = $this->create_rep();
		$other_id = $this->create_rep();
		update_user_meta( $user_id, 'gh_broadcast_results', 1 );
		update_user_meta( $other_id, 'gh_broadcast_results', 1 );

		( new Actions() )->handle( $this->token( 'unsub', $user_id, 'broadcast_results' ), true );

		$this->assertFalse( Subscriptions::is_subscribed( $user_id, 'broadcast_results' ) );
		$this->assertTrue( Subscriptions::is_subscribed( $other_id, 'broadcast_results' ) );
	}

	public function test_a_link_that_does_not_work_says_so() {

		$actions = new Actions();

		$this->assertSame( 400, $actions->handle( 'nonsense.token' )['status'] );
		$this->assertSame( 410, $actions->handle( Signed_Link::token( 'unsub', 1, 'my_day', 60, time() - DAY_IN_SECONDS ) )['status'] );
		$this->assertSame( 404, $actions->handle( $this->token( 'unsub', $this->create_rep(), 'not_a_notification' ) )['status'] );
		$this->assertSame( 404, $actions->handle( $this->token( 'unsub', 999999999, 'my_day' ) )['status'] );
		$this->assertSame( 400, $actions->handle( $this->token( 'delete_everything', $this->create_rep(), '1' ) )['status'] );
	}

	/* ---------------------------------------------------------------------
	 * tasks
	 * ------------------------------------------------------------------- */

	public function test_opening_the_snooze_link_asks_for_how_long_and_changes_nothing() {

		$rep  = $this->create_rep();
		$task = $this->create_task( $rep );
		$due  = $task->due_date;

		$result = ( new Actions() )->handle( $this->token( 'snooze', $rep, (string) $task->ID ) );

		$this->assertSame( 200, $result['status'] );
		$this->assertStringContainsString( 'name="days" value="1"', $result['body'] );
		$this->assertStringContainsString( 'name="days" value="7"', $result['body'] );
		$this->assertSame( $due, ( new Task( $task->ID ) )->due_date );
	}

	public function test_snoozing_puts_the_task_off_by_the_days_chosen() {

		$rep  = $this->create_rep();
		$task = $this->create_task( $rep, [ 'due_date' => ( new DateTimeHelper( '+1 day 10:00' ) )->ymdhis() ] );

		$result = ( new Actions() )->handle( $this->token( 'snooze', $rep, (string) $task->ID ), true, [ 'days' => '3' ] );

		$this->assertSame( 200, $result['status'] );
		$this->assertSame(
			( new DateTimeHelper( '+4 days 10:00' ) )->ymd(),
			( new DateTimeHelper( ( new Task( $task->ID ) )->due_date ) )->ymd()
		);
		$this->assertSame( 0, get_current_user_id(), 'whoever it was run as is not left signed in' );
	}

	public function test_a_task_can_only_be_snoozed_for_the_lengths_offered() {

		$rep  = $this->create_rep();
		$task = $this->create_task( $rep );
		$due  = $task->due_date;

		foreach ( [ '0', '30', '-1', 'abc', '' ] as $days ) {
			$this->assertSame( 400, ( new Actions() )->handle( $this->token( 'snooze', $rep, (string) $task->ID ), true, [ 'days' => $days ] )['status'] );
		}

		$this->assertSame( $due, ( new Task( $task->ID ) )->due_date );
	}

	public function test_opening_the_complete_link_asks_and_sending_it_completes() {

		$rep  = $this->create_rep();
		$task = $this->create_task( $rep );

		( new Actions() )->handle( $this->token( 'complete', $rep, (string) $task->ID ) );
		$this->assertFalse( ( new Task( $task->ID ) )->is_complete() );

		( new Actions() )->handle( $this->token( 'complete', $rep, (string) $task->ID ), true );
		$this->assertTrue( ( new Task( $task->ID ) )->is_complete() );
	}

	public function test_a_task_that_is_already_complete_is_left_alone() {

		$rep  = $this->create_rep();
		$task = $this->create_task( $rep );
		$task->complete();

		$completed = ( new Task( $task->ID ) )->date_completed;

		$result = ( new Actions() )->handle( $this->token( 'snooze', $rep, (string) $task->ID ), true, [ 'days' => '1' ] );

		$this->assertSame( 200, $result['status'] );
		$this->assertStringNotContainsString( '<form', $result['body'] );
		$this->assertSame( $completed, ( new Task( $task->ID ) )->date_completed );
	}

	public function test_a_link_cannot_change_the_task_of_someone_else() {

		$rep   = $this->create_rep();
		$other = $this->create_rep();
		$task  = $this->create_task( $rep );
		$due   = $task->due_date;

		// signed for the other rep, who has no way to see the contact it's about
		$this->assertSame( 404, ( new Actions() )->handle( $this->token( 'snooze', $other, (string) $task->ID ), true, [ 'days' => '1' ] )['status'] );
		$this->assertSame( 404, ( new Actions() )->handle( $this->token( 'complete', $other, (string) $task->ID ), true )['status'] );

		$this->assertSame( $due, ( new Task( $task->ID ) )->due_date );
		$this->assertFalse( ( new Task( $task->ID ) )->is_complete() );
	}

	public function test_a_task_that_is_gone_is_not_found() {
		$this->assertSame( 404, ( new Actions() )->handle( $this->token( 'complete', $this->create_rep(), '999999999' ) )['status'] );
	}

	/* ---------------------------------------------------------------------
	 * the emails
	 * ------------------------------------------------------------------- */

	/**
	 * Catch the emails instead of sending them
	 *
	 * @return ArrayObject
	 */
	protected function catch_mail(): ArrayObject {

		$sent = new ArrayObject();

		add_filter( 'pre_wp_mail', function ( $return, $atts ) use ( $sent ) {
			$sent->append( $atts );

			return true;
		}, 10, 2 );

		return $sent;
	}

	public function test_every_recipient_of_a_report_gets_their_own_unsubscribe_link() {

		$sent  = $this->catch_mail();
		$one   = $this->create_rep();
		$two   = $this->create_rep();
		$one_u = get_userdata( $one );
		$two_u = get_userdata( $two );

		$result = Email_Reports::mail_report(
			'broadcast-results',
			[ $one_u->user_email, $two_u->user_email, 'someone-else@example.com' ],
			'Subject',
			'<a href="' . Email_Reports::UNSUBSCRIBE_PLACEHOLDER . '">unsubscribe</a>'
		);

		$this->assertTrue( $result );
		$this->assertCount( 3, $sent );

		$by_address = [];

		foreach ( $sent as $atts ) {
			$by_address[ (string) ( is_array( $atts['to'] ) ? $atts['to'][0] : $atts['to'] ) ] = $atts['message'];
		}

		foreach ( [ $one => $one_u, $two => $two_u ] as $user_id => $user ) {

			$this->assertStringNotContainsString( Email_Reports::UNSUBSCRIBE_PLACEHOLDER, $by_address[ $user->user_email ] );
			preg_match( '#/' . Signed_Link::ROUTE . '/([A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+)/#', $by_address[ $user->user_email ], $match );

			$parsed = Signed_Link::parse( $match[1] ?? '' );

			$this->assertSame( 'unsub', $parsed['action'] );
			$this->assertSame( $user_id, $parsed['user_id'] );
			$this->assertSame( 'broadcast_results', $parsed['object'] );
		}

		// an address that isn't a user has nobody to unsubscribe, so it's sent to their profile
		$this->assertStringNotContainsString( '/' . Signed_Link::ROUTE . '/', $by_address['someone-else@example.com'] );
		$this->assertStringContainsString( 'profile.php', $by_address['someone-else@example.com'] );
	}

	public function test_the_footer_of_a_notification_has_the_unsubscribe_link() {

		$html = Email_Reports::get_general_notification_template_html( 'up-to-date', [
			'unsubscribe_url' => 'https://example.com/unsubscribe-here',
		] );

		$this->assertStringContainsString( 'href="https://example.com/unsubscribe-here"', $html );
		$this->assertStringNotContainsString( 'unsubscribe_url', $html );
	}

	public function test_a_notification_without_its_own_link_has_the_profile_to_go_to() {

		$html = Email_Reports::get_general_notification_template_html( 'up-to-date' );

		$this->assertStringContainsString( 'profile.php#groundhogg', $html );
		$this->assertStringNotContainsString( 'unsubscribe_url', $html );
	}
}
