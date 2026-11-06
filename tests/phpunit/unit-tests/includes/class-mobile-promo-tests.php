<?php

use Groundhogg\Mobile\App;
use Groundhogg\Mobile\Promo;
use Groundhogg\Notices;

/**
 * How people find the mobile app from the main admin: a notification in the feed, a command, and the record of
 * who has already used the app so they aren't told about it again.
 */
class Mobile_Promo_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		delete_option( Notices::NOTIFICATIONS_OPTION );
		Notices::$dismissed_notices = [];
	}

	public function tearDown(): void {
		delete_option( Notices::NOTIFICATIONS_OPTION );
		Notices::$dismissed_notices = [];
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function user_with_role( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	/**
	 * Be this user, as they load the admin, and with what they have already dismissed.
	 */
	protected function visit( int $user ) {
		wp_set_current_user( $user );

		$dismissed = get_user_meta( $user, Notices::DISMISSED_NOTICES_OPTION, true );

		Notices::$dismissed_notices = is_array( $dismissed ) ? $dismissed : [];
	}

	/**
	 * The notification as the current user would get it in the feed, or null when they don't.
	 */
	protected function notification(): ?array {
		( new Promo() )->maybe_add_notification();

		return \Groundhogg\notices()->get_notifications( 'feed' )[ Promo::NOTICE_ID ] ?? null;
	}

	/* ---- who it is for ----------------------------------------------------------------------------------- */

	public function test_it_is_for_people_who_can_see_contacts() {
		$this->assertTrue( Promo::is_available_to( $this->user_with_role( 'administrator' ) ) );
		$this->assertTrue( Promo::is_available_to( $this->user_with_role( 'sales_rep' ) ) );
		$this->assertFalse( Promo::is_available_to( $this->user_with_role( 'subscriber' ) ) );
		$this->assertFalse( Promo::is_available_to( 0 ), 'nobody is signed in' );
	}

	/* ---- the notification -------------------------------------------------------------------------------- */

	public function test_the_notification_is_in_the_feed_and_not_a_banner() {
		$this->visit( $this->user_with_role( 'administrator' ) );

		$notification = $this->notification();

		$this->assertNotNull( $notification );
		$this->assertSame( 'view_contacts', $notification['cap'] );
		$this->assertSame( 'feed', $notification['location'] );
		$this->assertArrayNotHasKey( Promo::NOTICE_ID, \Groundhogg\notices()->get_notifications( 'banner' ) );
	}

	public function test_it_leads_to_the_page_that_explains_the_app() {
		$this->visit( $this->user_with_role( 'administrator' ) );

		$message = $this->notification()['message'];

		// the stored message has been through wp_kses_post, which writes the & in the url as &amp;
		$this->assertStringContainsString( 'href="' . htmlspecialchars( Promo::page_url() ) . '"', $message );
		$this->assertStringNotContainsString( App::url(), $message );
	}

	public function test_the_notification_does_not_name_the_product() {
		$this->visit( $this->user_with_role( 'administrator' ) );

		$notification = $this->notification();

		$this->assertStringNotContainsStringIgnoringCase( 'groundhogg', wp_strip_all_tags( $notification['title'] . ' ' . $notification['message'] ) );
	}

	public function test_nobody_is_nagged_who_cannot_use_it() {
		$this->visit( $this->user_with_role( 'subscriber' ) );

		$this->assertNull( $this->notification() );
	}

	public function test_someone_who_cannot_use_it_does_not_see_it_even_once_it_is_stored() {
		$this->visit( $this->user_with_role( 'administrator' ) );
		$this->assertNotNull( $this->notification() );

		$this->visit( $this->user_with_role( 'subscriber' ) );

		$this->assertArrayNotHasKey( Promo::NOTICE_ID, \Groundhogg\notices()->get_notifications( 'feed' ) );
	}

	public function test_it_is_stored_once_for_the_whole_site() {
		$this->visit( $this->user_with_role( 'administrator' ) );
		$this->notification();

		$added = get_option( Notices::NOTIFICATIONS_OPTION )[ Promo::NOTICE_ID ]['added'];

		$this->visit( $this->user_with_role( 'sales_rep' ) );

		$this->assertNotNull( $this->notification() );
		$this->assertSame( $added, get_option( Notices::NOTIFICATIONS_OPTION )[ Promo::NOTICE_ID ]['added'] );
	}

	public function test_dismissing_it_is_for_that_person_only() {
		$one     = $this->user_with_role( 'sales_rep' );
		$another = $this->user_with_role( 'sales_rep' );

		$this->visit( $one );
		$this->assertNotNull( $this->notification() );

		// what the X in the feed does (gh_dismiss_notice)
		\Groundhogg\notices()->dismiss_notice( Promo::NOTICE_ID );

		$this->assertNotEmpty( get_user_meta( $one, Notices::DISMISSED_NOTICES_OPTION, true ) );

		$this->visit( $another );
		$this->assertFalse( \Groundhogg\notices()->is_dismissed( Promo::NOTICE_ID ) );

		$this->visit( $one );
		$this->assertTrue( \Groundhogg\notices()->is_dismissed( Promo::NOTICE_ID ) );
	}

	public function test_a_person_who_has_used_the_app_is_treated_as_having_dismissed_it() {
		$user = $this->user_with_role( 'sales_rep' );
		$this->visit( $user );

		$this->notification();
		$this->assertFalse( \Groundhogg\notices()->is_dismissed( Promo::NOTICE_ID ) );

		Promo::record_use( $user );
		$this->visit( $user );
		$this->notification();

		$this->assertTrue( \Groundhogg\notices()->is_dismissed( Promo::NOTICE_ID ) );
		$this->assertNotEmpty( get_user_meta( $user, Notices::DISMISSED_NOTICES_OPTION, true ) );
	}

	/* ---- remembering that it was used ------------------------------------------------------------------- */

	public function test_it_is_remembered_per_person() {
		$one     = $this->user_with_role( 'sales_rep' );
		$another = $this->user_with_role( 'sales_rep' );

		$this->assertFalse( Promo::has_used( $one ) );

		Promo::record_use( $one );

		$this->assertTrue( Promo::has_used( $one ) );
		$this->assertFalse( Promo::has_used( $another ) );
	}

	public function test_use_is_written_at_most_once_a_day() {
		$user = $this->user_with_role( 'sales_rep' );

		Promo::record_use( $user );
		$first = (int) get_user_meta( $user, Promo::USED_META, true );
		$this->assertGreaterThan( 0, $first );

		// opened again a moment later: nothing to write
		update_user_meta( $user, Promo::USED_META, $first - 60 );
		Promo::record_use( $user );
		$this->assertSame( $first - 60, (int) get_user_meta( $user, Promo::USED_META, true ) );

		// opened again the next day
		update_user_meta( $user, Promo::USED_META, $first - DAY_IN_SECONDS - 60 );
		Promo::record_use( $user );
		$this->assertGreaterThanOrEqual( $first, (int) get_user_meta( $user, Promo::USED_META, true ) );
	}

	public function test_nothing_is_recorded_when_nobody_is_signed_in() {
		wp_set_current_user( 0 );

		Promo::record_use();

		$this->assertFalse( Promo::has_used() );
	}

	/* ---- the tab in Tools, and the command for it -------------------------------------------------------- */

	public function test_the_tab_is_in_tools_and_the_command_palette_can_find_it() {
		wp_set_current_user( $this->user_with_role( 'sales_rep' ) );

		$tools    = new \Groundhogg\Admin\Tools\Tools_Page();
		$commands = $tools->command_palette_tab_commands( [] );
		$urls     = array_column( $commands, 'url' );

		$this->assertContains( Promo::page_url(), $urls );
		$this->assertStringContainsString( 'page=' . Promo::PAGE, Promo::page_url() );
		$this->assertStringContainsString( 'tab=' . Promo::TAB, Promo::page_url() );
	}
}
