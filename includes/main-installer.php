<?php

namespace Groundhogg;

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

		// Install Default tags for tag mapping.
		Plugin::$instance->tag_mapping->install_default_tags();

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

		create_contact_from_user();
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
			if ( Plugin::$instance->settings->is_option_enabled( 'gh_guided_setup_finished' ) ) {
				exit( wp_redirect( admin_url( 'admin.php?page=groundhogg' ) ) );
			} else {
				exit( wp_redirect( admin_url( 'admin.php?page=gh_guided_setup' ) ) );
			}
		}
	}
}