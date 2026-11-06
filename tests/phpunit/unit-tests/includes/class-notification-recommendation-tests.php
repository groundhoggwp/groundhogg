<?php

use Groundhogg\Admin\Welcome\Welcome_Page;

/**
 * One recommendation on the dashboard for all the email summaries a user can get.
 *
 * @see Welcome_Page::get_notifications_item()
 */
class Notification_Recommendation_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
		remove_all_filters( 'groundhogg/notifications/types' );
		parent::tearDown();
	}

	protected function user_with( string $role, array $meta = [] ): int {
		$user_id = self::factory()->user->create( [ 'role' => $role ] );

		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		wp_set_current_user( $user_id );

		return $user_id;
	}

	public function test_it_recommends_every_summary_the_user_has_not_asked_for() {

		$this->user_with( 'administrator' );

		$item = Welcome_Page::get_notifications_item();

		$this->assertFalse( $item['completed'] );
		$this->assertSame( 'read', $item['cap'] );
		$this->assertStringContainsString( 'daily summaries of your day', $item['description'] );
		$this->assertStringContainsString( 'next-day broadcast results', $item['description'] );
		$this->assertStringContainsString( 'monthly and weekly performance overviews', $item['description'] );
		$this->assertStringContainsString( '#groundhogg-notifications', $item['fix'] );
	}

	public function test_it_only_names_the_summaries_that_are_not_on_yet() {

		$this->user_with( 'administrator', [ 'gh_broadcast_results' => 1, 'gh_weekly_overview' => 1 ] );

		$item = Welcome_Page::get_notifications_item();

		$this->assertFalse( $item['completed'] );
		$this->assertStringContainsString( 'daily summaries of your day', $item['description'] );
		$this->assertStringNotContainsString( 'next-day broadcast results', $item['description'] );
		$this->assertStringNotContainsString( 'performance overviews', $item['description'] );
	}

	public function test_it_is_done_once_the_user_has_asked_for_every_summary() {

		$this->user_with( 'administrator', [ 'gh_broadcast_results' => 1, 'gh_weekly_overview' => 1, 'gh_my_day' => 1 ] );

		$this->assertTrue( Welcome_Page::get_notifications_item()['completed'] );
	}

	public function test_the_team_section_of_my_day_alone_is_not_the_summary() {

		$this->user_with( 'administrator', [ 'gh_broadcast_results' => 1, 'gh_weekly_overview' => 1, 'gh_my_day_team' => 1 ] );

		// the notification counts as on if either of its settings is, as it does for unsubscribing
		$this->assertTrue( Welcome_Page::get_notifications_item()['completed'] );
	}

	public function test_it_is_not_for_a_user_who_can_get_none_of_them() {

		$this->user_with( 'subscriber' );

		$this->assertSame( 'do_not_allow', Welcome_Page::get_notifications_item()['cap'] );
	}

	public function test_it_includes_the_summaries_of_add_ons() {

		add_filter( 'groundhogg/notifications/types', function ( $types ) {
			$types['deal_digest'] = [
				'label' => 'weekly deal digests',
				'meta'  => [ 'gh_deal_digest' ],
				'cap'   => 'read',
			];

			return $types;
		} );

		$user_id = $this->user_with( 'administrator', [ 'gh_broadcast_results' => 1, 'gh_weekly_overview' => 1, 'gh_my_day' => 1 ] );

		$item = Welcome_Page::get_notifications_item();

		$this->assertFalse( $item['completed'] );
		$this->assertStringContainsString( 'weekly deal digests', $item['description'] );

		update_user_meta( $user_id, 'gh_deal_digest', 1 );

		$this->assertTrue( Welcome_Page::get_notifications_item()['completed'] );
	}
}
