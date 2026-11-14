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

	/**
	 * What an agent needs to set up sending, compliance and tracking, through groundhogg/update-settings
	 */
	public function test_setup_settings_are_registered() {

		$settings = Plugin::instance()->settings;

		foreach ( [
			'primary_user',
			'wordpress_email_service',
			'transactional_email_service',
			'marketing_email_service',
			'allow_email_in_staging',
			'log_emails',
			'email_log_retention',
			'unsubscribe_email',
			'enable_one_click_unsubscribe',
			'custom_email_footer_text',
			'email_footer_alignment',
			'disable_open_tracking',
			'disable_click_tracking',
			'url_tracking_exclusions',
			'disable_page_tracking',
			'consent_cookie_name',
			'consent_cookie_value',
			'disable_user_sync',
			'sync_user_meta',
			'send_notifications_on_event_failure',
			'event_failure_notification_email',
			'ignore_event_errors',
		] as $setting ) {
			$this->assertTrue( $settings->is_setting_registered( $setting ), "gh_$setting is not registered" );
		}

		// turning it off without an external cron stops the queue, so only the cron abilities may
		$this->assertFalse( $settings->is_setting_registered( 'disable_wp_cron' ) );
	}

	public function test_email_service_must_be_registered() {

		update_option( 'gh_marketing_email_service', 'log_only' );
		$this->assertSame( 'log_only', get_option( 'gh_marketing_email_service' ) );

		update_option( 'gh_marketing_email_service', 'not_a_service' );
		$this->assertSame( 'wp_mail', get_option( 'gh_marketing_email_service' ) );

		update_option( 'gh_marketing_email_service', 'wp_mail' );
	}

	public function test_primary_user_must_be_an_owner() {

		$owner = wp_list_pluck( \Groundhogg\get_owners(), 'ID' )[0];

		update_option( 'gh_primary_user', (string) $owner );
		$this->assertSame( absint( $owner ), absint( get_option( 'gh_primary_user' ) ) );

		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		update_option( 'gh_primary_user', $subscriber );
		$this->assertNotEquals( $subscriber, absint( get_option( 'gh_primary_user' ) ) );
	}

	/**
	 * The settings page still saves 'on', the abilities save true
	 */
	public function test_tracking_checkboxes_read_either_way() {

		update_option( 'gh_disable_page_tracking', 'on' );
		$this->assertTrue( \Groundhogg\is_option_enabled( 'gh_disable_page_tracking' ) );

		update_option( 'gh_disable_page_tracking', true );
		$this->assertTrue( \Groundhogg\is_option_enabled( 'gh_disable_page_tracking' ) );

		update_option( 'gh_disable_page_tracking', false );
		$this->assertFalse( \Groundhogg\is_option_enabled( 'gh_disable_page_tracking' ) );
	}

	public function test_footer_alignment_is_left_or_center() {

		update_option( 'gh_email_footer_alignment', 'center' );
		$this->assertSame( 'center', get_option( 'gh_email_footer_alignment' ) );

		update_option( 'gh_email_footer_alignment', 'sideways' );
		$this->assertSame( 'left', get_option( 'gh_email_footer_alignment' ) );
	}

	/**
	 * On a new site most settings were never saved. Reading them gives the default as it is, it isn't run through the
	 * setting's sanitize callback, which broke groundhogg/list-settings on custom_profile_fields.
	 */
	public function test_settings_never_saved_read_as_the_default() {

		$settings = Plugin::instance()->settings;

		delete_option( 'gh_custom_profile_fields' );

		$this->assertFalse( $settings->get_option( 'custom_profile_fields' ) );
		$this->assertSame( [], $settings->get_option( 'custom_profile_fields', [] ) );
	}

	public function test_a_form_and_map_that_is_not_one_is_sanitized_to_an_empty_one() {

		foreach ( [ false, null, '', 'nonsense', [], [ 'not a form' ] ] as $value ) {
			$this->assertSame( [ [], [] ], \Groundhogg\Form\Form_Fields::sanitize_form_and_map( $value ) );
		}
	}

	public function test_list_settings_works_on_a_new_site() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		foreach ( [ 'gh_custom_profile_fields', 'gh_preferences_profile_fields' ] as $option ) {
			delete_option( $option );
		}

		$ability = function_exists( 'wp_get_ability' ) ? wp_get_ability( 'groundhogg/list-settings' ) : null;

		if ( ! $ability ) {
			$this->markTestSkipped( 'Needs WordPress 6.9 for the Abilities API.' );
		}

		$result = $ability->execute( [] );

		$this->assertNotWPError( $result );
		$this->assertNotEmpty( $result['settings'] );
	}
}
