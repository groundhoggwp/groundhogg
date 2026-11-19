<?php

use function Groundhogg\has_accepted_cookies;
use function Groundhogg\is_wp_consent_api_active;

/*
 * Stand-ins for the WP Consent API's functions, which the test site doesn't have. They do what the real ones do when
 * no consent plugin is active, which is allow everything, until a test says otherwise.
 */
if ( ! function_exists( 'wp_get_consent_type' ) ) {
	function wp_get_consent_type() {
		return $GLOBALS['gh_test_consent_type'] ?? false;
	}
}

if ( ! function_exists( 'wp_has_consent' ) ) {
	function wp_has_consent( $category ) {
		if ( ! wp_get_consent_type() ) {
			return true;
		}

		return in_array( $category, $GLOBALS['gh_test_consented'] ?? [], true );
	}
}

/**
 * Reading consent from a consent plugin through the WP Consent API, in addition to the consent cookie setting
 */
class Consent_API_Tests extends GH_UnitTestCase {

	public function set_up() {
		parent::set_up();

		update_option( 'gh_enable_gdpr', 'on' );
		update_option( 'gh_consent_cookie_name', 'my_consent' );
		update_option( 'gh_consent_cookie_value', 'yes' );

		unset( $_COOKIE['my_consent'] );
	}

	public function tear_down() {
		unset( $GLOBALS['gh_test_consent_type'], $GLOBALS['gh_test_consented'], $_COOKIE['my_consent'] );
		remove_all_filters( 'groundhogg/consent_category' );
		remove_all_filters( 'groundhogg/has_accepted_cookies' );
		delete_option( 'gh_enable_gdpr' );
		delete_option( 'gh_consent_cookie_name' );
		delete_option( 'gh_consent_cookie_value' );

		parent::tear_down();
	}

	protected function consent_plugin( array $consented, string $type = 'optin' ) {
		$GLOBALS['gh_test_consent_type'] = $type;
		$GLOBALS['gh_test_consented']    = $consented;
	}

	public function test_the_consent_cookie_setting_decides_when_no_consent_plugin_has_set_a_consent_type() {

		// The API itself says yes to everything here, which isn't a visitor's consent
		$this->assertTrue( wp_has_consent( 'marketing' ) );
		$this->assertFalse( is_wp_consent_api_active() );

		$this->assertFalse( has_accepted_cookies() );

		$_COOKIE['my_consent'] = 'yes';

		$this->assertTrue( has_accepted_cookies() );
	}

	public function test_a_visitor_who_allows_marketing_has_accepted_cookies() {

		$this->consent_plugin( [ 'marketing' ] );

		$this->assertTrue( is_wp_consent_api_active() );
		$this->assertTrue( has_accepted_cookies() );
	}

	public function test_a_visitor_who_has_not_allowed_marketing_has_not_accepted_cookies() {

		$this->consent_plugin( [ 'statistics' ] );

		$this->assertFalse( has_accepted_cookies() );
	}

	public function test_the_consent_plugin_wins_over_the_consent_cookie_setting() {

		$_COOKIE['my_consent'] = 'yes';

		$this->consent_plugin( [] );

		$this->assertFalse( has_accepted_cookies() );
	}

	public function test_the_category_can_be_changed() {

		$this->consent_plugin( [ 'statistics' ] );

		add_filter( 'groundhogg/consent_category', function () {
			return 'statistics';
		} );

		$this->assertTrue( has_accepted_cookies() );
	}

	public function test_consent_is_implicit_when_gdpr_features_are_off() {

		delete_option( 'gh_enable_gdpr' );

		$this->consent_plugin( [] );

		$this->assertTrue( has_accepted_cookies() );
	}

	public function test_the_has_accepted_cookies_filter_still_has_the_last_word() {

		$this->consent_plugin( [] );

		add_filter( 'groundhogg/has_accepted_cookies', '__return_true' );

		$this->assertTrue( has_accepted_cookies() );
	}
}
