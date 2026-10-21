<?php

use Groundhogg\Classes\Message;
use Groundhogg\Event;
use Groundhogg\Mobile\Activity_Feed;
use Groundhogg\Plugin;
use function Groundhogg\db;
use function Groundhogg\get_managed_page_name;

/**
 * The mobile contact record's activity list: a short newest-first list from a lean endpoint, not the desktop timeline.
 */
class Mobile_Activity_Feed_Tests extends GH_UnitTestCase {

	/**
	 * @var Activity_Feed
	 */
	protected $feed;

	public function setUp(): void {
		parent::setUp();
		$this->feed = Plugin::$instance->mobile->activity_feed;
		$this->reset_tracking_state();
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/mobile/activity_feed' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function contact( array $data = [] ) {
		return self::factory()->contacts->create_and_get( array_merge( [ 'first_name' => 'Casey', 'optin_status' => 2 ], $data ) );
	}

	protected function email( string $subject ): int {
		return (int) db()->emails->add( [ 'title' => $subject, 'subject' => $subject, 'content' => '<p>x</p>', 'status' => 'ready', 'message_type' => 'marketing' ] );
	}

	protected function event( $contact, int $email_id, string $status, int $time ): int {
		return (int) db()->events->add( [
			'contact_id'     => $contact->get_id(),
			'email_id'       => $email_id,
			'event_type'     => Event::EMAIL_NOTIFICATION,
			'step_id'        => $email_id,
			'funnel_id'      => 0,
			'status'         => $status,
			'time'           => $time,
			'time_scheduled' => $time,
		] );
	}

	protected function activity( $contact, string $type, int $time, array $extra = [] ): int {
		return (int) db()->activity->add( array_merge( [
			'contact_id'    => $contact->get_id(),
			'activity_type' => $type,
			'timestamp'     => $time,
		], $extra ) );
	}

	protected function visit( $contact, string $path, int $time ): int {
		return (int) db()->page_visits->add( [ 'contact_id' => $contact->get_id(), 'path' => $path, 'timestamp' => $time ] );
	}

	protected function titles( array $feed ): array {
		return array_column( $feed['items'], 'title' );
	}

	/* ---- what's in it ------------------------------------------------------------------------------------ */

	public function test_it_lists_what_happened_newest_first_with_the_contacts_details_in_subjects() {
		$contact = $this->contact();
		$email   = $this->email( 'Hello {first}' );
		$now     = time();

		$this->event( $contact, $email, Event::COMPLETE, $now - 500 );
		$this->event( $contact, $email, Event::FAILED, $now - 400 );
		$this->activity( $contact, 'email_opened', $now - 300, [ 'email_id' => $email ] );
		$this->activity( $contact, 'email_link_click', $now - 200, [ 'referer' => 'https://www.example.test/pricing/' ] );
		$this->activity( $contact, 'bounce', $now - 100 );
		$this->visit( $contact, '/pricing/', $now - 50 );

		$feed = $this->feed->for_contact( $contact );

		$this->assertSame( [
			'Visited a page',
			'Email bounced',
			'Clicked a link in an email',
			'Opened an email',
			'Email failed to send',
			'Email sent',
		], $this->titles( $feed ) );

		$by_type = array_column( $feed['items'], null, 'type' );

		$this->assertSame( 'Hello Casey', $by_type['email_sent']['detail'] );
		$this->assertSame( 'Hello Casey', $by_type['email_opened']['detail'] );
		$this->assertSame( 'example.test/pricing', $by_type['email_link_click']['detail'] );
		$this->assertSame( '/pricing/', $by_type['page_visit']['detail'] );

		$this->assertSame( 'negative', $by_type['email_failed']['tone'] );
		$this->assertSame( 'negative', $by_type['bounce']['tone'] );
		$this->assertSame( '', $by_type['email_sent']['tone'] );

		$this->assertFalse( $feed['has_more'] );

		foreach ( $feed['items'] as $item ) {
			$this->assertNotEmpty( $item['time_diff'] );
			$this->assertNotEmpty( $item['icon'] );
		}
	}

	public function test_noise_is_left_out() {
		$contact = $this->contact();
		$now     = time();

		// a flow step that isn't an email, logins, page views (those come from visits), and the site's own pages
		db()->events->add( [ 'contact_id' => $contact->get_id(), 'event_type' => Event::FUNNEL, 'step_id' => 999999, 'funnel_id' => 1, 'status' => Event::COMPLETE, 'time' => $now - 10, 'time_scheduled' => $now - 10 ] );
		$this->activity( $contact, 'wp_login', $now - 9 );
		$this->activity( $contact, 'wp_logout', $now - 8 );
		$this->activity( $contact, 'page_view', $now - 7 );
		$this->visit( $contact, '/' . get_managed_page_name() . '/preferences/', $now - 6 );

		$this->assertSame( [], $this->feed->for_contact( $contact )['items'] );
	}

	public function test_only_this_contacts_activity_is_listed() {
		$mine   = $this->contact();
		$theirs = $this->contact();

		$this->visit( $theirs, '/theirs/', time() - 5 );
		$this->visit( $mine, '/mine/', time() - 10 );

		$this->assertSame( [ '/mine/' ], array_column( $this->feed->for_contact( $mine )['items'], 'detail' ) );
	}

	public function test_emails_written_to_them_show_who_sent_them() {
		$contact = $this->contact();
		$rep     = self::factory()->user->create( [ 'role' => 'sales_rep', 'display_name' => 'Riley Rep' ] );

		Message::record_composed_email( $contact, [ 'subject' => 'Following up', 'content' => 'Hi', 'from_address' => 'rep@example.test', 'user_id' => $rep ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$item = $this->feed->for_contact( $contact )['items'][0];

		$this->assertSame( 'message_out', $item['type'] );
		$this->assertSame( 'Email sent by Riley Rep', $item['title'] );
		$this->assertSame( 'Following up', $item['detail'] );
	}

	/* ---- paging ------------------------------------------------------------------------------------------ */

	public function test_it_pages_with_an_inclusive_cursor_and_nothing_is_lost() {
		$contact = $this->contact();
		$now     = time();

		for ( $i = 1; $i <= 7; $i ++ ) {
			$this->visit( $contact, "/page-$i/", $now - ( $i * 100 ) );
		}

		$first = $this->feed->for_contact( $contact, 3 );

		$this->assertSame( [ '/page-1/', '/page-2/', '/page-3/' ], array_column( $first['items'], 'detail' ) );
		$this->assertTrue( $first['has_more'] );
		$this->assertSame( $now - 300, $first['next_before'] );

		$second = $this->feed->for_contact( $contact, 3, $first['next_before'] );

		// inclusive: the page starts with the entry the last one ended on, and the client drops it by id
		$this->assertSame( [ '/page-3/', '/page-4/', '/page-5/' ], array_column( $second['items'], 'detail' ) );
		$this->assertTrue( $second['has_more'] );

		$third = $this->feed->for_contact( $contact, 3, $second['next_before'] );

		$this->assertSame( [ '/page-5/', '/page-6/', '/page-7/' ], array_column( $third['items'], 'detail' ) );
		$this->assertFalse( $third['has_more'] );

		$all = array_unique( array_merge( array_column( $first['items'], 'id' ), array_column( $second['items'], 'id' ), array_column( $third['items'], 'id' ) ) );
		$this->assertCount( 7, $all );
	}

	/**
	 * Entries are dropped after the query (the site's own pages, flow steps that aren't emails), so a page must keep
	 * reading until it has enough or runs out. Otherwise a run of noise newer than the real entries makes a page come
	 * back short, and it looks like the last one.
	 */
	public function test_noise_newer_than_the_real_entries_does_not_hide_that_there_are_more() {
		$contact = $this->contact();
		$now     = time();
		$own     = '/' . get_managed_page_name() . '/archive/';

		// more noise than a page reads, and newer than everything real. Each a different path, since a repeat visit to
		// one page is a count on one row, not a row each
		for ( $i = 1; $i <= 40; $i ++ ) {
			$this->visit( $contact, $own . $i . '/', $now - $i );
		}

		for ( $i = 1; $i <= 5; $i ++ ) {
			$this->visit( $contact, "/real-$i/", $now - 1000 - $i );
		}

		$first = $this->feed->for_contact( $contact, 2 );

		$this->assertSame( [ '/real-1/', '/real-2/' ], array_column( $first['items'], 'detail' ) );
		$this->assertTrue( $first['has_more'] );

		$second = $this->feed->for_contact( $contact, 2, $first['next_before'] );

		$this->assertSame( [ '/real-2/', '/real-3/' ], array_column( $second['items'], 'detail' ) );
		$this->assertTrue( $second['has_more'] );
	}

	public function test_an_empty_feed() {
		$feed = $this->feed->for_contact( $this->contact() );

		$this->assertSame( [], $feed['items'] );
		$this->assertFalse( $feed['has_more'] );
		$this->assertNull( $feed['next_before'] );
	}

	/* ---- extending --------------------------------------------------------------------------------------- */

	public function test_addons_can_add_entries() {
		$contact = $this->contact();
		$now     = time();

		$this->visit( $contact, '/old/', $now - 1000 );

		add_filter( 'groundhogg/mobile/activity_feed', function ( $entries ) use ( $now ) {
			$entries[] = [ 'id' => 'deal-1', 'type' => 'deal_stage', 'icon' => 'deals', 'title' => 'Deal moved to Won', 'detail' => '', 'time' => $now - 10, 'tone' => '' ];

			return $entries;
		} );

		$this->assertSame( [ 'Deal moved to Won', 'Visited a page' ], $this->titles( $this->feed->for_contact( $contact ) ) );
	}

	/* ---- the endpoint ------------------------------------------------------------------------------------ */

	protected function get( int $contact_id, array $params = [] ) {
		$request = new WP_REST_Request( 'GET', "/gh/v4/mobile/contacts/$contact_id/activity" );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}

	public function test_the_endpoint_returns_the_feed() {
		$contact = $this->contact();
		$this->visit( $contact, '/pricing/', time() - 10 );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$response = $this->get( $contact->get_id() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ '/pricing/' ], array_column( $response->get_data()['items'], 'detail' ) );
	}

	public function test_the_endpoint_clamps_the_page_size() {
		$contact = $this->contact();

		for ( $i = 0; $i < 60; $i ++ ) {
			$this->visit( $contact, "/p$i/", time() - $i );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$data = $this->get( $contact->get_id(), [ 'limit' => 5000 ] )->get_data();

		$this->assertCount( Activity_Feed::MAX_LIMIT, $data['items'] );
		$this->assertTrue( $data['has_more'] );
	}

	public function test_a_rep_sees_their_own_contacts_but_not_another_reps() {
		$rep   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		$mine   = $this->contact( [ 'owner_id' => $rep ] );
		$theirs = $this->contact( [ 'owner_id' => $other ] );

		wp_set_current_user( $rep );

		$this->assertSame( 200, $this->get( $mine->get_id() )->get_status() );
		$this->assertContains( $this->get( $theirs->get_id() )->get_status(), [ 401, 403 ] );
	}

	public function test_the_endpoint_needs_a_login_and_a_contact_that_exists() {
		$contact = $this->contact();

		$this->assertContains( $this->get( $contact->get_id() )->get_status(), [ 401, 403 ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 404, $this->get( 99999999 )->get_status() );
	}
}
