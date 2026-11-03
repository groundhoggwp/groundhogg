<?php

use Groundhogg\Admin\Settings\Settings_Page;
use Groundhogg\Admin\User\Admin_User;
use function Groundhogg\is_admin_bar_widget_disabled;
use function Groundhogg\is_user_preference_enabled;

/**
 * Tooltips, the toolbar widget and the contact peek are chosen by each user on their profile. The site settings they
 * replaced are the default for a user who hasn't chosen.
 */
class Interface_Preferences_Tests extends GH_UnitTestCase {

	public function set_up() {
		parent::set_up();

		$this->user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user_id );

		$_POST = $_REQUEST = [];
	}

	public function tear_down() {
		delete_option( 'gh_hide_tooltips' );
		delete_option( 'gh_is_admin_bar_widget_disabled' );
		delete_option( 'gh_disable_email_detection' );
		delete_option( 'gh_default_contact_tab' );
		$_POST = $_REQUEST = [];

		parent::tear_down();
	}

	protected $user_id;

	public function test_nothing_is_hidden_until_somebody_chooses_to() {
		$this->assertFalse( is_user_preference_enabled( 'gh_hide_tooltips' ) );
		$this->assertFalse( is_admin_bar_widget_disabled() );
	}

	public function test_a_site_that_had_the_setting_on_keeps_it_for_users_who_have_not_chosen() {

		update_option( 'gh_hide_tooltips', 'on' );

		$this->assertTrue( is_user_preference_enabled( 'gh_hide_tooltips' ) );
		$this->assertTrue( is_user_preference_enabled( 'gh_hide_tooltips', self::factory()->user->create() ) );
	}

	public function test_what_the_user_chose_wins_over_the_site_setting() {

		update_option( 'gh_is_admin_bar_widget_disabled', 'on' );
		update_user_meta( $this->user_id, 'gh_is_admin_bar_widget_disabled', 'off' );

		$this->assertFalse( is_admin_bar_widget_disabled() );

		delete_option( 'gh_is_admin_bar_widget_disabled' );
		update_user_meta( $this->user_id, 'gh_is_admin_bar_widget_disabled', 'on' );

		$this->assertTrue( is_admin_bar_widget_disabled() );
	}

	public function test_a_choice_is_for_that_user_only() {

		update_user_meta( $this->user_id, 'gh_hide_tooltips', 'on' );

		$this->assertTrue( is_user_preference_enabled( 'gh_hide_tooltips' ) );
		$this->assertFalse( is_user_preference_enabled( 'gh_hide_tooltips', self::factory()->user->create() ) );
	}

	public function test_the_profile_saves_the_checked_boxes_as_on_and_the_rest_as_off() {

		$_POST = $_REQUEST = [
			'gh_interface_preferences_present' => '1',
			'gh_hide_tooltips'                 => 'on',
		];

		Admin_User::instance()->save_fields( $this->user_id );

		$this->assertSame( 'on', get_user_meta( $this->user_id, 'gh_hide_tooltips', true ) );
		$this->assertSame( 'off', get_user_meta( $this->user_id, 'gh_is_admin_bar_widget_disabled', true ) );
	}

	public function test_a_form_without_the_checkboxes_leaves_the_choices_alone() {

		update_user_meta( $this->user_id, 'gh_hide_tooltips', 'on' );

		$_POST = $_REQUEST = [ 'gh_from_name' => 'Someone' ];

		Admin_User::instance()->save_fields( $this->user_id );

		$this->assertSame( 'on', get_user_meta( $this->user_id, 'gh_hide_tooltips', true ) );
	}

	public function test_the_profile_shows_the_site_setting_as_checked_for_a_user_who_has_not_chosen() {

		update_option( 'gh_hide_tooltips', 'on' );

		ob_start();
		Admin_User::instance()->fields_table( get_userdata( $this->user_id ) );
		$html = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<input type="checkbox" name="gh_hide_tooltips" value="on"\s+checked=\'checked\'/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="gh_is_admin_bar_widget_disabled" value="on"\s+checked/', $html );
	}

	public function test_the_contact_peek_is_only_offered_to_someone_who_can_see_it() {

		$this->assertArrayHasKey( 'gh_disable_email_detection', Admin_User::get_interface_preferences( get_userdata( $this->user_id ) ) );

		$rep = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->assertArrayNotHasKey( 'gh_disable_email_detection', Admin_User::get_interface_preferences( get_userdata( $rep ) ) );
	}

	public function test_the_update_copies_the_site_settings_that_were_on_to_the_users_who_can_view_contacts() {

		update_option( 'gh_hide_tooltips', 'on' );
		update_option( 'gh_disable_email_detection', 'on' );
		update_option( 'gh_default_contact_tab', 'notes' );

		$rep      = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$chosen   = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$outsider = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		update_user_meta( $chosen, 'gh_hide_tooltips', 'off' );
		update_user_meta( $chosen, 'gh_default_contact_tab', 'files' );

		Groundhogg\Main_Updater::move_interface_settings_to_users();

		$this->assertSame( 'on', get_user_meta( $rep, 'gh_hide_tooltips', true ) );
		$this->assertSame( 'on', get_user_meta( $rep, 'gh_disable_email_detection', true ) );
		$this->assertSame( 'notes', get_user_meta( $rep, 'gh_default_contact_tab', true ) );

		// the setting that was off is not copied
		$this->assertFalse( metadata_exists( 'user', $rep, 'gh_is_admin_bar_widget_disabled' ) );

		// a choice the user already made stays
		$this->assertSame( 'off', get_user_meta( $chosen, 'gh_hide_tooltips', true ) );
		$this->assertSame( 'files', get_user_meta( $chosen, 'gh_default_contact_tab', true ) );

		// someone who can't view contacts isn't a Groundhogg user
		$this->assertFalse( metadata_exists( 'user', $outsider, 'gh_hide_tooltips' ) );
	}

	public function test_the_settings_page_no_longer_has_them() {

		$page = new Settings_Page();
		$page->init_defaults();

		$settings = new ReflectionProperty( Settings_Page::class, 'settings' );
		$settings->setAccessible( true );
		$ids = array_keys( $settings->getValue( $page ) );

		foreach ( [ 'gh_hide_tooltips', 'gh_is_admin_bar_widget_disabled', 'gh_disable_email_detection', 'gh_default_contact_tab' ] as $id ) {
			$this->assertNotContains( $id, $ids );
		}
	}
}
