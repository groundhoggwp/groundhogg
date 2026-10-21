<?php

use Groundhogg\Mobile\App;
use Groundhogg\Mobile\Promo;
use Groundhogg\Notices;

/**
 * How people find the mobile app from the main admin: a notice on Groundhogg's pages, a command, and the record of
 * who has already used the app so they aren't told about it again.
 */
class Mobile_Promo_Tests extends GH_UnitTestCase {

	protected $user_agent;

	public function setUp(): void {
		parent::setUp();
		$this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
		Notices::$dismissed_notices = [];
	}

	public function tearDown(): void {
		if ( $this->user_agent === null ) {
			unset( $_SERVER['HTTP_USER_AGENT'] );
		} else {
			$_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
		}

		unset( $_GET['page'], $_REQUEST['page'] );
		Notices::$dismissed_notices = [];
		set_current_screen( 'front' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function user_with_role( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	/**
	 * Be this user, looking at one of the pages in the admin.
	 */
	protected function visit( int $user, string $page = 'gh_contacts' ) {
		wp_set_current_user( $user );
		set_current_screen( 'dashboard' );
		$_GET['page'] = $_REQUEST['page'] = $page;
		delete_transient( Notices::TRANSIENT . '_user_' . $user );
	}

	/**
	 * What the notice would say, or null when it isn't shown.
	 */
	protected function notice(): ?array {
		( new Promo() )->maybe_add_notice();

		$notices = get_transient( Notices::TRANSIENT . '_user_' . get_current_user_id() );

		return $notices[ Promo::NOTICE_ID ] ?? null;
	}

	/* ---- who it is for ----------------------------------------------------------------------------------- */

	public function test_it_is_for_people_who_can_see_contacts() {
		$this->assertTrue( Promo::is_available_to( $this->user_with_role( 'administrator' ) ) );
		$this->assertTrue( Promo::is_available_to( $this->user_with_role( 'sales_rep' ) ) );
		$this->assertFalse( Promo::is_available_to( $this->user_with_role( 'subscriber' ) ) );
		$this->assertFalse( Promo::is_available_to( 0 ), 'nobody is signed in' );
	}

	/* ---- the notice -------------------------------------------------------------------------------------- */

	public function test_the_notice_is_shown_on_a_page_in_the_admin() {
		$this->visit( $this->user_with_role( 'administrator' ) );

		$notice = $this->notice();

		$this->assertNotNull( $notice );
		$this->assertSame( 'view_contacts', $notice['cap'] );
		$this->assertFalse( $notice['site_wide'], 'it belongs to the admin pages, not every screen' );
	}

	public function test_on_a_computer_the_notice_leads_to_the_page_that_explains_it() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';
		$this->visit( $this->user_with_role( 'administrator' ) );

		$message = $this->notice()['message'];

		$this->assertStringContainsString( 'href="' . esc_url( Promo::page_url() ) . '"', $message );
		$this->assertStringNotContainsString( App::url(), $message );
	}

	public function test_on_a_phone_the_notice_leads_straight_to_the_app() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
		$this->visit( $this->user_with_role( 'administrator' ) );

		$message = $this->notice()['message'];

		$this->assertStringContainsString( 'href="' . esc_url( App::url() ) . '"', $message );
		$this->assertStringNotContainsString( 'page=' . Promo::PAGE, $message );
	}

	public function test_the_notice_does_not_name_the_product() {
		$this->visit( $this->user_with_role( 'administrator' ) );

		$this->assertStringNotContainsStringIgnoringCase( 'groundhogg', wp_strip_all_tags( $this->notice()['message'] ) );
	}

	public function test_nobody_is_nagged_who_cannot_use_it() {
		$this->visit( $this->user_with_role( 'subscriber' ) );

		$this->assertNull( $this->notice() );
	}

	public function test_the_notice_stays_out_of_pages_that_are_not_groundhoggs() {
		$this->visit( $this->user_with_role( 'administrator' ), 'some_other_plugin' );

		$this->assertNull( $this->notice() );
	}

	public function test_the_notice_is_not_shown_on_the_page_about_the_app_itself() {
		$this->visit( $this->user_with_role( 'administrator' ), Promo::PAGE );

		$this->assertNull( $this->notice() );
	}

	public function test_the_notice_does_not_interrupt_the_guided_setup() {
		$this->visit( $this->user_with_role( 'administrator' ), 'gh_guided_setup' );

		$this->assertNull( $this->notice() );
	}

	public function test_closing_the_notice_keeps_it_away() {
		$user = $this->user_with_role( 'administrator' );
		$this->visit( $user );

		$this->assertNotNull( $this->notice() );

		// what the X does (gh_dismiss_notice)
		\Groundhogg\notices()->dismiss_notice( Promo::NOTICE_ID );

		// and the next page
		Notices::$dismissed_notices = [];
		$this->assertNotEmpty( get_user_meta( $user, Notices::DISMISSED_NOTICES_OPTION, true ) );
		Notices::$dismissed_notices = get_user_meta( $user, Notices::DISMISSED_NOTICES_OPTION, true );
		$this->visit( $user );

		$this->assertNull( $this->notice() );
	}

	public function test_a_person_who_has_used_the_app_is_not_told_about_it() {
		$user = $this->user_with_role( 'sales_rep' );
		$this->visit( $user );

		$this->assertNotNull( $this->notice() );

		Promo::record_use( $user );
		$this->visit( $user );

		$this->assertNull( $this->notice() );
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

	/* ---- the command and the page ------------------------------------------------------------------------ */

	public function test_the_command_palette_gets_a_command_for_those_it_is_for() {
		wp_set_current_user( $this->user_with_role( 'administrator' ) );

		$commands = ( new Promo() )->add_command( [] );

		$this->assertCount( 1, $commands );
		$this->assertSame( Promo::page_url(), $commands[0]['url'] );
		$this->assertSame( 'Mobile app', $commands[0]['label'] );
	}

	public function test_the_command_is_left_out_for_those_it_is_not_for() {
		wp_set_current_user( $this->user_with_role( 'subscriber' ) );

		$this->assertSame( [], ( new Promo() )->add_command( [] ) );
	}

	public function test_the_page_is_in_the_admin_and_open_to_those_who_can_see_contacts() {
		$page = new \Groundhogg\Admin\Mobile\Mobile_Page();

		$this->assertInstanceOf( \Groundhogg\Admin\Mobile\Mobile_Page::class, $page );
		$this->assertSame( Promo::PAGE, $page->get_slug() );
		$this->assertSame( 'view_contacts', $page->get_cap() );
		$this->assertStringContainsString( 'page=' . Promo::PAGE, Promo::page_url() );
	}
}
