<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Upgrade
 *
 * @since       File available since Release 1.0.16
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Old_Updater extends Updater {

	/**
	 * Refactor notes db
	 */
	public function version_2_5() {

		Plugin::instance()->dbs->install_dbs();

		get_db( 'activity' )->update( [
			'activity_type' => 'login'
		], [
			'activity_type' => 'wp_login'
		] );

		get_db( 'activity' )->update( [
			'activity_type' => 'logout'
		], [
			'activity_type' => 'wp_logout'
		] );

		// For woocommerce, unable to see admin dashboard
		wp_roles()->add_cap( 'marketer', 'manage_campaigns' );
		wp_roles()->add_cap( 'marketer', 'export_funnels' );
		wp_roles()->add_cap( 'administrator', 'manage_campaigns' );
		wp_roles()->add_cap( 'administrator', 'export_funnels' );
	}

	public function version_2_5_1_3() {
		get_db( 'steps' )->create_table();
	}

	/**
	 * New page tracking stuff!
	 */
	public function version_2_5_4() {
		Plugin::instance()->dbs->install_dbs();

		update_option( 'gh_purge_page_visits', 'on' );
		update_option( 'gh_page_visits_log_retention', 90 );
	}

	/**
	 * Notes
	 */
	public function version_2_5_5() {
		Plugin::$instance->roles->install_roles_and_caps();
		get_role( 'sales_rep' )->remove_cap( 'view_events' );
		get_db( 'notes' )->create_table();
		get_db( 'notes' )->update_2_5_5();
	}

	/**
	 * Activity Caps
	 */
	public function version_2_5_7_4() {

		// Contacts with this birthday only got it probably because of a bug
		get_db( 'contactmeta' )->delete( [
			'meta_key'   => 'birthday',
			'meta_value' => '1970-01-01'
		] );

		// Contacts with this birthday only got it probably because of a bug
		get_db( 'contactmeta' )->delete( [
			'meta_key'   => 'birthday',
			'meta_value' => '1999-11-30'
		] );
	}

	/**
	 * Install the logs table
	 */
	public function version_2_5_7_5() {
		get_db( 'logs' )->create_table();
	}

	/**
	 * Migrate custom fields
	 *
	 * @return void
	 */
	public function version_2_6() {
		migrate_custom_fields_groundhogg_2_6();
	}

	/**
	 * Add hostname column for page visits table
	 *
	 * @return void
	 */
	public function version_2_6_2_2() {
		get_db( 'page_visits' )->create_table();
	}

	public function version_2_7_2() {
		get_db( 'steps' )->create_table();
		install_custom_rewrites();
	}

	/**
	 * Set conversion step IDs to preserve legacy behaviour
	 *
	 * @return void
	 */
	public function version_2_7_4() {

		$funnels = get_db( 'funnels' )->query();

		foreach ( $funnels as $funnel ) {
			$funnel = new Funnel( $funnel );

			$step_id = $funnel->legacy_conversion_step_id();

			if ( ! $step_id ) {
				continue;
			}

			$step = new Step( $step_id );

			if ( ! $step->exists() ) {
				continue;
			}

			$step->update( [
				'is_conversion' => true
			] );

		}

	}

	/**
	 * Drop the claim columns from the history table
	 * Unneeded data
	 *
	 * @return void
	 */
	public function version_2_7_4_3() {
		get_db( 'events' )->drop_column( 'claim' );
	}

	public function version_2_7_5_2() {
		wp_clear_scheduled_hook( 'gh_do_stats_collection' );
		wp_clear_scheduled_hook( 'gh_purge_page_visits' );
		wp_clear_scheduled_hook( 'gh_purge_expired_permissions_keys' );
		wp_clear_scheduled_hook( 'gh_check_bounces' );
	}

	/**
	 * Fix funnel events and step statuses
	 *
	 * @return void
	 */
	public function version_2_7_7_8() {

		$funnels = get_db( 'funnels' )->query();

		foreach ( $funnels as $funnel ) {
			$funnel = new Funnel( $funnel );

			if ( ! $funnel->exists() ) {
				continue;
			}

			$funnel->update_step_status();
			$funnel->update_events_from_status();
		}

	}

	/**
	 * Update the gh-cron file to use direct function instead of do_action
	 */
	public function version_2_7_7_10() {
		if ( gh_cron_installed() ) {
			install_gh_cron_file();
		}
	}

	/**
	 * Add the value column for activity
	 *
	 * @return void
	 */
	public function version_2_7_9_3() {
		get_db( 'activity' )->create_table();
	}

	/**
	 * Update email_logs table to have the is_sensitive flag
	 * Create the new tasks table
	 * Add task related capabilities
	 */
	public function version_2_7_10() {
		get_db( 'email_log' )->create_table();
		get_db( 'tasks' )->create_table();
		Plugin::instance()->roles->add_caps();
	}

	/**
	 * Get a list of updates which are available.
	 *
	 * @return string[]
	 */
	protected function get_available_updates() {
		return [
			'2.5',
			'2.5.1.3',
			'2.5.4',
			'2.5.5',
			'2.5.7.4',
			'2.5.7.5',
			'2.6',
			'2.6.2.2',
			'2.7.2',
			'2.7.4',
			'2.7.4.3',
			'2.7.5.2',
			'2.7.7.8',
			'2.7.7.10',
			'2.7.9.3',
			'2.7.10',
		];
	}


	/**
	 * Automatic updates
	 *
	 * @return array|string[]
	 */
	protected function get_automatic_updates() {
		return [
			'2.5',
			'2.5.1.3',
			'2.5.4',
			'2.5.5',
			'2.5.7.5',
			'2.6',
			'2.6.2.2',
			'2.7.2',
			'2.7.4.3',
			'2.7.5.2',
			'2.7.7.8',
			'2.7.7.10',
			'2.7.9.3',
			'2.7.10',
		];
	}

	/**
	 * Show any required update descriptions.
	 *
	 * @return array|string[]
	 */
	protected function get_update_descriptions() {
		return [
			'2.5'           => __( 'Add additional capabilities for admins and marketers. Update database tables and replace wp_login activity names in the activity table.', 'groundhogg' ),
			'2.5.1.3'       => __( 'Use TINYINT(1) instead of BIT(1)', 'groundhogg' ),
			'2.5.4'         => __( 'Improve the page tracking flow and track page visits for contacts.', 'groundhogg' ),
			'2.5.5'         => __( 'Add new caps and permissions for notes and sales representatives.', 'groundhogg' ),
			'2.5.7.4'       => __( 'Reset birthdays of contacts with dates 1970-01-01 and 1999-11-30 because of import bug.', 'groundhogg' ),
			'2.5.7.5'       => __( 'Install the debug logs table.', 'groundhogg' ),
			'2.6'           => __( 'Refactor custom fields to new format.', 'groundhogg' ),
			'2.6.2.2'       => __( 'Add hostname field to page-visits table.', 'groundhogg' ),
			'2.7.2'         => __( 'Add <code>step_slug</code> column. Add new rewrites for prettier URLs.', 'groundhogg' ),
			'2.7.4'         => __( 'Set the <code>is_conversion</code> for benchmarks based on legacy funnel conversion step.', 'groundhogg' ),
			'2.7.4.3'       => __( 'Drop un-needed <code>claim</code> column from <code>wp_gh_events</code> table.', 'groundhogg' ),
			'2.7.5.2'       => __( 'Clear telemetry cron job.', 'groundhogg' ),
			'2.7.7.8'       => __( 'Fix step statuses for inactive or archived funnels.', 'groundhogg' ),
			'2.7.7.10'      => __( 'Update <code>gh-cron.php</code> to use direction function call instead of <code>do_action()</code>', 'groundhogg' ),
			'2.7.9.3'       => __( 'Add a value column to the the activity table!', 'groundhogg' ),
			'2.7.10'        => __( 'Add an <code>is_sensitive</code> flag to email logs for enhanced security. Create tasks table.', 'groundhogg' ),
		];
	}

	/**
	 * A unique name for the updater to avoid conflicts
	 *
	 * @return string
	 */
	protected function get_updater_name() {
		return 'main';
	}

	/**
	 * Display name for the updater
	 *
	 * @return string
	 */
	public function get_display_name() {
		return white_labeled_name();
	}
}
