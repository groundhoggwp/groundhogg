<?php

use Groundhogg\Notices;
use function Groundhogg\notices;

/**
 * Notifications added in code (update paths and the like) that show up in the notifications feed next to the
 * broadcast notices, or as a banner, or both.
 */
class Notifications_Feed_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		delete_option( Notices::NOTIFICATIONS_OPTION );
		Notices::$dismissed_notices = [];
		Notices::$read_notices      = [];
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		delete_option( Notices::NOTIFICATIONS_OPTION );
		Notices::$dismissed_notices = [];
		Notices::$read_notices      = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Stand in for the remote notices so nothing goes over the network.
	 */
	protected function notices_with_remote( array $remote ): Notices {
		$mock = $this->getMockBuilder( Notices::class )->onlyMethods( [ 'fetch_remote_notices' ] )->getMock();
		$mock->method( 'fetch_remote_notices' )->willReturn( $remote );

		return $mock;
	}

	public function test_a_notification_goes_to_the_feed_by_default() {
		$this->assertTrue( notices()->add_notification( 'updated-to-test', [
			'title'   => 'Updated',
			'message' => 'Something <strong>changed</strong>',
		] ) );

		$this->assertArrayHasKey( 'updated-to-test', notices()->get_notifications( 'feed' ) );
		$this->assertArrayNotHasKey( 'updated-to-test', notices()->get_notifications( 'banner' ) );
	}

	public function test_location_decides_where_it_belongs() {
		notices()->add_notification( 'a-feed', [ 'title' => 'a', 'location' => 'feed' ] );
		notices()->add_notification( 'a-banner', [ 'title' => 'b', 'location' => 'banner' ] );
		notices()->add_notification( 'a-both', [ 'title' => 'c', 'location' => 'both' ] );
		notices()->add_notification( 'a-bogus', [ 'title' => 'd', 'location' => 'sideways' ] );

		$feed   = array_keys( notices()->get_notifications( 'feed' ) );
		$banner = array_keys( notices()->get_notifications( 'banner' ) );

		sort( $feed );
		sort( $banner );

		$this->assertEquals( [ 'a-bogus', 'a-both', 'a-feed' ], $feed );
		$this->assertEquals( [ 'a-banner', 'a-both' ], $banner );
	}

	public function test_it_needs_something_to_say() {
		$this->assertFalse( notices()->add_notification( 'empty', [] ) );
		$this->assertFalse( notices()->add_notification( '', [ 'title' => 'No id' ] ) );
	}

	public function test_numeric_ids_are_kept_apart_from_the_remote_ones() {
		notices()->add_notification( '42', [ 'title' => 'Mine' ] );

		$this->assertArrayHasKey( 'notice-42', notices()->get_notifications() );
	}

	public function test_adding_it_again_keeps_the_original_date() {
		notices()->add_notification( 'again', [ 'title' => 'First' ] );

		$stored          = get_option( Notices::NOTIFICATIONS_OPTION );
		$stored['again']['added'] = time() - DAY_IN_SECONDS;
		update_option( Notices::NOTIFICATIONS_OPTION, $stored );

		notices()->add_notification( 'again', [ 'title' => 'Second' ] );

		$notification = notices()->get_notifications()['again'];

		$this->assertEquals( 'Second', $notification['title'] );
		$this->assertEquals( $stored['again']['added'], $notification['added'] );
	}

	public function test_capability_hides_it_from_those_without() {
		notices()->add_notification( 'admins-only', [ 'title' => 'Admins', 'cap' => 'manage_options' ] );

		$this->assertArrayHasKey( 'admins-only', notices()->get_notifications() );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertArrayNotHasKey( 'admins-only', notices()->get_notifications() );
	}

	public function test_expired_notifications_are_left_out_and_pruned() {
		notices()->add_notification( 'old', [ 'title' => 'Old', 'expires' => time() + 60 ] );

		$stored                   = get_option( Notices::NOTIFICATIONS_OPTION );
		$stored['old']['expires'] = time() - 60;
		update_option( Notices::NOTIFICATIONS_OPTION, $stored );

		$this->assertArrayNotHasKey( 'old', notices()->get_notifications() );

		notices()->add_notification( 'new', [ 'title' => 'New' ] );

		$this->assertArrayNotHasKey( 'old', get_option( Notices::NOTIFICATIONS_OPTION ) );
	}

	public function test_it_can_be_removed() {
		notices()->add_notification( 'gone', [ 'title' => 'Gone' ] );

		$this->assertTrue( notices()->remove_notification( 'gone' ) );
		$this->assertFalse( notices()->remove_notification( 'gone' ) );
		$this->assertEmpty( notices()->get_notifications() );
	}

	public function test_the_message_is_sanitized() {
		notices()->add_notification( 'xss', [
			'title'   => '<b>Title</b>',
			'message' => '<p>Hi</p><script>alert(1)</script>',
		] );

		$notification = notices()->get_notifications()['xss'];

		$this->assertEquals( 'Title', $notification['title'] );
		$this->assertStringNotContainsString( '<script', $notification['message'] );
	}

	public function test_the_feed_merges_with_remote_notices_newest_first() {
		notices()->add_notification( 'local-one', [ 'title' => 'Local', 'message' => 'Hello' ] );

		$notices = $this->notices_with_remote( [
			[ 'id' => 3, 'title' => 'Newer', 'content' => '<p>n</p>', 'sent' => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) ],
			[ 'id' => 2, 'title' => 'Older', 'content' => '<p>o</p>', 'sent' => gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) ],
		] );

		$feed = $notices->get_feed();

		$this->assertEquals( [ 3, 'local-one', 2 ], wp_list_pluck( $feed, 'id' ) );
		$this->assertStringContainsString( 'Hello', $feed[1]['content'] );
	}

	public function test_local_notifications_still_show_when_the_remote_fetch_fails() {
		notices()->add_notification( 'local-only', [ 'title' => 'Local' ] );

		$mock = $this->getMockBuilder( Notices::class )->onlyMethods( [ 'fetch_remote_notices' ] )->getMock();
		$mock->method( 'fetch_remote_notices' )->willReturn( new WP_Error( 'down', 'Down' ) );

		$this->assertEquals( [ 'local-only' ], wp_list_pluck( $mock->get_feed(), 'id' ) );

		notices()->remove_notification( 'local-only' );

		$this->assertWPError( $mock->get_feed() );
	}

	public function test_unread_counts_local_notifications_until_read_or_dismissed() {
		notices()->add_notification( 'one', [ 'title' => 'One' ] );
		notices()->add_notification( 'two', [ 'title' => 'Two' ] );

		$notices = $this->notices_with_remote( [
			[ 'id' => 9, 'title' => 'Remote', 'content' => '', 'sent' => gmdate( 'Y-m-d H:i:s' ) ],
		] );

		$this->assertEquals( 3, $notices->count_unread() );

		Notices::$read_notices = [ 'one' => 'one' ];
		$this->assertEquals( 2, $notices->count_unread() );

		Notices::$dismissed_notices = [ 'two' => 'two' ];
		$this->assertEquals( 1, $notices->count_unread() );
	}

	public function test_banners_print_until_dismissed() {
		notices()->add_notification( 'banner-me', [ 'title' => 'Heads up', 'message' => 'Read this', 'location' => 'banner', 'site_wide' => true ] );

		ob_start();
		notices()->notification_banners();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Heads up', $html );
		$this->assertStringContainsString( 'Read this', $html );
		$this->assertStringContainsString( 'notice=banner-me', $html );
		$this->assertStringContainsString( '_wpnonce=', $html );

		Notices::$dismissed_notices = [ 'banner-me' => 'banner-me' ];

		ob_start();
		notices()->notification_banners();
		$this->assertEmpty( ob_get_clean() );
	}

	public function test_feed_only_notifications_do_not_print_as_banners() {
		notices()->add_notification( 'feed-only', [ 'title' => 'Quiet', 'site_wide' => true ] );

		ob_start();
		notices()->notification_banners();
		$this->assertEmpty( ob_get_clean() );
	}
}
