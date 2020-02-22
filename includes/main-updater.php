<?php

namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Manager;
use Groundhogg\DB\Activity;

/**
 * Upgrade
 *
 * @since       File available since Release 1.0.16
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Main_Updater extends Updater {

	/**
	 * Update to 2.0
	 *
	 * 1. Convert any options.
	 * 2. Add new rewrite rules for iframe, forms, email preferences, unsubscribe, email confirmed.
	 * 3. Move from impressions to new another DB.
	 */
	public function version_2_0() {
		$privacy_policy_id = get_option( 'gh_privacy_policy' );

		if ( $privacy_policy_id ) {
			$privacy_policy_link = get_permalink( absint( $privacy_policy_id ) );
			update_option( 'gh_privacy_policy', $privacy_policy_link );
		}

		$terms_id = get_option( 'gh_terms' );

		if ( $terms_id ) {
			$terms_link = get_permalink( absint( $terms_id ) );
			update_option( 'gh_terms', $terms_link );
		}

		// Give the DBS a quick update...
		Plugin::$instance->dbs->install_dbs();

		wp_clear_scheduled_hook( 'wpgh_process_queue' );

		update_option( 'gh_migrate_form_impressions', 1 );

		Plugin::$instance->utils->files->add_htaccess();

		install_custom_rewrites();

		set_transient( 'groundhogg_upgrade_notice_request_active', 1, WEEK_IN_SECONDS );
	}

	/**
	 * Update the rewrites to support new file access links.
	 */
	public function version_2_0_7() {
		install_custom_rewrites();
	}

	/**
	 * Fix add .htaccess issue.
	 */
	public function version_2_0_7_1() {
		Plugin::$instance->utils->files->mk_dir();
	}

	/**
	 * Index the date_created column
	 */
	public function version_2_0_8() {
		global $wpdb;
		$db = get_db( 'contacts' );
		$wpdb->query( "CREATE INDEX date_created ON {$db->get_table_name()}(date_created)" );
	}

	/**
	 * Attempt more compatibility with the rewrites.
	 */
	public function version_2_0_9_6() {
		install_custom_rewrites();
		Plugin::$instance->roles->add_caps();
	}

	public function get_display_name() {
		return white_labeled_name();
	}

	/**
	 * Update the tags table to support custom preference options.
	 * Fix typo in the cancelled status
	 */
	public function version_2_0_10() {
		get_db( 'tags' )->create_table();
		get_db( 'events' )->mass_update( [ 'status' => Event::CANCELLED ], [ 'status' => 'canceled' ] );
	}

	/**
	 * Update tracking link options
	 */
	public function version_2_0_11() {
		install_custom_rewrites();
	}

	/**
	 * Re-install caps for roles.
	 */
	public function version_2_0_11_5() {
		Plugin::instance()->roles->add_caps();
	}

	/**
	 * Set the update notice.
	 */
	public function version_2_1() {
		update_option( 'gh_updating_to_2_1', true );
	}

	/**
	 * Reset the stats collection hook to ping the site weekly, not daily.
	 */
	public function version_2_1_6() {
		wp_clear_scheduled_hook( 'gh_do_stats_collection' );
	}

	/**
	 * Re-install all the rewrites.
	 */
	public function version_2_1_6_2() {
		install_custom_rewrites();
	}

	/**
	 * Update missing author in emails table
	 */
	public function version_2_1_7_1() {
		if ( is_user_logged_in() ) {
			get_db( 'emails' )->mass_update( [ 'author' => get_current_user_id() ], [ 'author' => 0 ] );
		}
	}

	/**
	 * Update the table to support the new "time_scheduled" column
	 */
	public function version_2_1_11_1() {
		get_db( 'events' )->create_table();
	}

	/**
	 * Refactor contact optin statuses
	 */
	public function version_2_1_13() {

		$contacts = get_db( 'contacts' );

		$changes = [
			7 => Preferences::COMPLAINED,
			6 => Preferences::SPAM,
			5 => Preferences::HARD_BOUNCE,
			4 => Preferences::MONTHLY,
			3 => Preferences::WEEKLY,
			2 => Preferences::UNSUBSCRIBED,
			1 => Preferences::CONFIRMED,
			0 => Preferences::UNCONFIRMED
		];

		foreach ( $changes as $old_status => $new_status ) {
			$contacts->mass_update( [
				'optin_status' => $new_status
			], [
				'optin_status' => $old_status
			] );
		}
	}

	/**
	 * Revert version 2.1.13
	 */
	public function version_2_1_13_revert() {
		$contacts = get_db( 'contacts' );

		$changes = [
			2 => 1,
			3 => 2,
			4 => 3,
			5 => 4,
			6 => 5,
			7 => 6,
			8 => 7,
		];

		foreach ( $changes as $old_status => $new_status ) {
			$contacts->mass_update( [
				'optin_status' => $new_status
			], [
				'optin_status' => $old_status
			] );
		}
	}

	/**
	 * Test update to run the updater and ensure it works.
	 */
	public function version_2_1_13_1() {
		set_transient( 'hi', 'there' );
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
	 * Get a list of updates which are available.
	 *
	 * @return string[]
	 */
	protected function get_available_updates() {
		return [
			'2.0',
			'2.0.7',
			'2.0.7.1',
			'2.0.8',
			'2.0.9.6',
			'2.0.10',
			'2.0.11',
			'2.0.11.5',
			'2.1',
			'2.1.6',
			'2.1.6.2',
			'2.1.7.1',
			'2.1.11.1',
			'2.1.13',
		];
	}

	protected function get_update_descriptions() {
		return [
			'2.1.13'        => __( 'Refactor contact optin statuses to meet new format.', 'groundhogg' ),
			'2.1.13.revert' => __( 'Revert update 2.1.13 if rogue updated refactored optin status moe than once.' ),
		];
	}

	/**
	 * Updates that will allow you to revert.
	 *
	 * @return array|string[]
	 */
	protected function get_optional_updates() {
		return [
			'2.1.13.revert'
		];
	}
}