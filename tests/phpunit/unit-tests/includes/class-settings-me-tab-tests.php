<?php

use Groundhogg\Admin\Settings\Settings_Page;

/**
 * The Me tab of the settings shows the profile fields of the current user and saves them with the profile's own handler
 */
class Settings_Me_Tab_Tests extends GH_UnitTestCase {

	protected function sign_in_as_admin(): int {

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		get_userdata( $user_id )->add_cap( 'view_contacts', true );
		get_userdata( $user_id )->add_cap( 'send_emails', true );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function settings_page(): Settings_Page {
		$page = new Settings_Page();
		$page->init_defaults();

		return $page;
	}

	protected function tab_ids( Settings_Page $page ): array {
		$tabs = ( new ReflectionProperty( Settings_Page::class, 'tabs' ) );
		$tabs->setAccessible( true );

		return array_keys( $tabs->getValue( $page ) );
	}

	public function test_there_is_a_me_tab() {

		$this->sign_in_as_admin();

		$this->assertContains( 'me', $this->tab_ids( $this->settings_page() ) );
	}

	public function test_the_me_tab_is_only_for_a_user_who_can_view_contacts() {

		$this->sign_in_as_admin();
		$page = $this->settings_page();

		$tab_is_listed = new ReflectionMethod( Settings_Page::class, 'tab_is_listed' );
		$tab_is_listed->setAccessible( true );

		$tab = [ 'id' => 'me', 'title' => 'Me', 'cap' => 'view_contacts' ];

		$this->assertTrue( $tab_is_listed->invoke( $page, $tab ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( $tab_is_listed->invoke( $page, $tab ) );
	}

	protected function sign_in_as_limited_user(): int {

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		get_userdata( $user_id )->add_cap( 'view_contacts', true );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function call( Settings_Page $page, string $method, ...$args ) {
		$method = new ReflectionMethod( Settings_Page::class, $method );
		$method->setAccessible( true );

		return $method->invoke( $page, ...$args );
	}

	public function test_the_page_is_open_to_anyone_who_can_view_contacts() {

		$this->assertSame( 'view_contacts', $this->settings_page()->get_cap() );
	}

	public function test_a_user_without_manage_options_only_gets_the_me_tab() {

		$this->sign_in_as_limited_user();
		$page = $this->settings_page();

		$listed = array_values( array_filter( $this->tab_ids( $page ), function ( $id ) use ( $page ) {
			return $this->call( $page, 'tab_is_listed', [ 'id' => $id, 'title' => $id, 'cap' => $id === 'me' ? 'view_contacts' : null ] );
		} ) );

		$this->assertSame( [ 'me' ], $listed );

		$tabs = new ReflectionProperty( Settings_Page::class, 'tabs' );
		$tabs->setAccessible( true );

		foreach ( array_keys( $tabs->getValue( $page ) ) as $id ) {
			$this->assertSame( $id === 'me', $this->call( $page, 'user_can_access_tab', $id ), "tab $id" );
		}
	}

	public function test_a_tab_without_a_cap_is_for_administrators() {

		$page = $this->settings_page();

		$this->sign_in_as_limited_user();
		$this->assertFalse( $this->call( $page, 'tab_is_listed', [ 'id' => 'api_tab', 'title' => 'API' ] ) );
		$this->assertFalse( $this->call( $page, 'user_can_access_tab', 'a_tab_from_an_addon' ) );

		$this->sign_in_as_admin();
		$this->assertTrue( $this->call( $page, 'tab_is_listed', [ 'id' => 'api_tab', 'title' => 'API' ] ) );
	}

	public function test_the_me_tab_is_the_default_for_a_user_without_manage_options() {

		$_REQUEST = [];

		$this->sign_in_as_limited_user();
		$this->assertSame( 'me', $this->call( $this->settings_page(), 'active_tab' ) );

		$this->sign_in_as_admin();
		$this->assertSame( 'general', $this->call( $this->settings_page(), 'active_tab' ) );
	}

	public function test_a_user_without_manage_options_can_only_run_the_save_action() {

		$this->sign_in_as_limited_user();
		$page = $this->settings_page();

		// would die with no access if it was run, it needs a super admin
		$_REQUEST = $_POST = [ 'action' => 'create_api_key', '_wpnonce' => wp_create_nonce( 'create_api_key' ), 'user_id' => get_current_user_id() ];
		$page->process_action();

		$this->assertEmpty( get_user_meta( get_current_user_id(), 'wpgh_user_secret_key', true ) );
	}

	public function test_the_tab_shows_the_profile_fields_in_a_form_that_saves_to_the_page() {

		$this->sign_in_as_admin();

		ob_start();
		$this->settings_page()->view_me();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="gh_from_name"', $html );
		$this->assertStringContainsString( 'name="gh_default_contact_tab"', $html );
		$this->assertStringContainsString( 'name="gh_admin_theme"', $html );
		$this->assertStringContainsString( 'id="signature"', $html );
		$this->assertMatchesRegularExpression( '/name="action"[^>]*value="save_me"|value="save_me"[^>]*name="action"/', $html );
		$this->assertStringContainsString( 'id="primary-submit"', $html );
	}

	public function test_saving_the_tab_saves_the_current_users_profile_fields() {

		$user_id = $this->sign_in_as_admin();

		$_POST = $_REQUEST = [
			'gh_from_name'           => 'Adrian from Groundhogg',
			'gh_from_email'          => 'adrian@example.com',
			'gh_default_contact_tab' => 'activity',
			'gh_admin_theme'         => 'slate',
			'signature'              => '<p>Cheers</p>',
		];

		$this->assertTrue( $this->settings_page()->process_save_me() );

		$this->assertSame( 'Adrian from Groundhogg', get_user_meta( $user_id, 'gh_from_name', true ) );
		$this->assertSame( 'adrian@example.com', get_user_meta( $user_id, 'gh_from_email', true ) );
		$this->assertSame( 'activity', get_user_meta( $user_id, 'gh_default_contact_tab', true ) );
		$this->assertSame( 'slate', get_user_meta( $user_id, 'gh_admin_theme', true ) );
		$this->assertSame( '<p>Cheers</p>', get_user_meta( $user_id, 'signature', true ) );
	}

	public function test_saving_the_tab_never_touches_another_user() {

		$this->sign_in_as_admin();
		$other_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		update_user_meta( $other_id, 'gh_from_name', 'Someone else' );

		$_POST = $_REQUEST = [ 'gh_from_name' => 'Me', 'user_id' => $other_id ];

		$this->settings_page()->process_save_me();

		$this->assertSame( 'Someone else', get_user_meta( $other_id, 'gh_from_name', true ) );
	}
}
