<?php

use Groundhogg\Plugin;

/**
 * The settings that the guided setup reads and writes are registered, so that whatever they're saved through, the
 * options API, the settings page, an ability, or update_option(), they're sanitized to what the rest of the plugin
 * expects.
 */
class Settings_Registry_Tests extends GH_UnitTestCase {

	/**
	 * Everything the guided setup saves
	 */
	public function test_guided_setup_settings_are_registered() {

		$settings = Plugin::instance()->settings;

		foreach ( [
			'business_name',
			'phone',
			'street_address_1',
			'street_address_2',
			'city',
			'region',
			'zip_or_postal',
			'country',
			'override_from_name',
			'override_from_email',
			'privacy_policy',
			'terms',
			'strict_confirmation',
			'confirmation_grace_period',
			'enable_gdpr',
			'strict_gdpr',
			'disable_unnecessary_cookies',
			'guided_setup_finished',
			'opted_in_stats_collection',
			'telemetry_email',
		] as $setting ) {
			$this->assertTrue( $settings->is_setting_registered( $setting ), "gh_$setting is not registered" );
		}
	}

	public function test_policy_links_keep_only_urls() {

		update_option( 'gh_privacy_policy', 'javascript:alert(1)' );
		$this->assertSame( '', get_option( 'gh_privacy_policy' ) );

		update_option( 'gh_terms', 'https://example.com/terms' );
		$this->assertSame( 'https://example.com/terms', get_option( 'gh_terms' ) );

		// not every site has a policy yet, and a relative link is still a link
		update_option( 'gh_privacy_policy', '#' );
		$this->assertSame( '#', get_option( 'gh_privacy_policy' ) );

		update_option( 'gh_terms', '/terms' );
		$this->assertSame( '/terms', get_option( 'gh_terms' ) );
	}

	/**
	 * Telemetry::optin() saves ['on'], like the settings page's checkbox used to
	 */
	public function test_telemetry_optin_is_a_boolean() {

		update_option( 'gh_opted_in_stats_collection', [ 'on' ] );
		$this->assertTrue( Plugin::instance()->stats_collection->is_enabled() );

		update_option( 'gh_opted_in_stats_collection', '' );
		$this->assertFalse( Plugin::instance()->stats_collection->is_enabled() );
	}

	public function test_telemetry_email_is_an_email() {

		update_option( 'gh_telemetry_email', 'someone@example.com' );
		$this->assertSame( 'someone@example.com', get_option( 'gh_telemetry_email' ) );

		update_option( 'gh_telemetry_email', 'not an email' );
		$this->assertSame( '', get_option( 'gh_telemetry_email' ) );
	}
}
