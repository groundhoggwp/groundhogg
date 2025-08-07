<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Main_Installer extends Installer {

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	protected function get_installer_name() {
		return 'main';
	}

	/**
	 * Activate the Groundhogg plugin.
	 */
	protected function activate() {
		set_transient( 'groundhogg_review_request_dismissed', WEEK_IN_SECONDS );

		// Multisite compatibility, re-init as after_setup_theme DB $prefix is wrong.!
		if ( is_multisite() ) {
			Plugin::$instance->dbs->init_dbs();
		}

		// Install our DBS...
		Plugin::$instance->dbs->install_dbs();

		// Add roles and caps...
		Plugin::$instance->roles->install_roles_and_caps();

		Plugin::$instance->utils->files->mk_dir();

		$settings = [
			'gh_override_from_name'               => get_bloginfo( 'name' ),
			'gh_override_from_email'              => get_bloginfo( 'admin_email' ),
			'gh_confirmation_grace_period'        => 14,
			'gh_event_failure_notification_email' => get_bloginfo( 'admin_email' ),
		];

		foreach ( $settings as $setting_name => $value ) {
			if ( ! get_option( $setting_name ) ) {
				update_option( $setting_name, $value );
			}
		}

		// Install any custom rewrites
		install_custom_rewrites();

		// Store previous updates
		Plugin::instance()->updater->save_previous_updates_when_installed();

		// Make sure the current user installing is added to the contacts
		$contact = create_contact_from_user();

		// Make sure their email is auto confirmed.
		if ( is_a_contact( $contact ) ){
			$contact->change_marketing_preference( Preferences::CONFIRMED );
		}

		// Setup no longer redirects after activation from the add plugins screen
		if ( ! is_option_enabled( 'gh_guided_setup_finished' ) && ! is_white_labeled() ){
			update_option( 'gh_force_to_setup', 1 );
		}

		// Auto optin admins to get performance reports
		$owners = filter_by_cap( get_owners(), 'view_reports' );
		foreach ( $owners as $owner ){

			// Ignore super admin and admins that do not have an email the belongs to the current site. For example agency users
			if ( ( is_multisite() && is_super_admin( $owner->ID ) ) || ! email_is_same_domain( $owner->user_email ) ){
				continue;
			}

			update_user_meta( $owner->ID, 'gh_broadcast_results', 1 );
			update_user_meta( $owner->ID, 'gh_weekly_overview', 1 );
		}
	}

	public function get_display_name() {
		return white_labeled_name();
	}

	protected function deactivate() {
		// TODO: Implement deactivate() method.
	}

	/**
	 * The path to the main plugin file
	 *
	 * @return string
	 */
	function get_plugin_file() {
		return GROUNDHOGG__FILE__;
	}

	/**
	 * Get the plugin version
	 *
	 * @return string
	 */
	function get_plugin_version() {
		return GROUNDHOGG_VERSION;
	}

	/**
	 * Drop these tables when uninstalling MU site.
	 *
	 * @return string[]
	 */
	protected function get_table_names() {
		return Plugin::$instance->dbs->get_table_names();
	}

	/**
	 * Fires after the 'activated_plugin' hook.
	 *
	 * @param $plugin
	 */
	public function plugin_activated( $plugin ) {

		// Ignore the redirect if quietly activating.
		if ( doing_rest() || wp_doing_ajax() || wp_doing_cron() || get_url_var( 'action' ) !== 'activate' ){
			return;
		}

		if ( $plugin == plugin_basename( GROUNDHOGG__FILE__ ) && ! is_white_labeled() ) {
			if ( is_option_enabled( 'gh_guided_setup_finished' ) ) {
				exit( wp_redirect( admin_url( 'admin.php?page=groundhogg' ) ) );
			} else {
				exit( wp_redirect( admin_url( 'admin.php?page=gh_guided_setup' ) ) );
			}
		}
	}
}
