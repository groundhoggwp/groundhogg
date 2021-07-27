<?php

namespace Groundhogg;

use Groundhogg\Bulk_Jobs\Manager;
use Groundhogg\Bulk_Jobs\Migrate_Notes;
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
	 * Add priority column to events db
	 */
	public function version_2_1_13_6() {

		get_db( 'contacts' )->create_table();
		$events = get_db( 'events' );

		$events->create_table();

		global $wpdb;

		$wpdb->query( "UPDATE {$events->get_table_name()}
		SET `priority` = 10
		WHERE `funnel_id` != 1" );

		$wpdb->query( "UPDATE {$events->get_table_name()}
		SET `priority` = 100
		WHERE `funnel_id` = 1" );
	}

	/**
	 * Add `micro_time` column
	 *
	 * Automatic update!
	 */
	public function version_2_1_13_11() {
		get_db( 'events' )->create_table();
	}


	/**
	 * Add index on `claim`
	 *
	 * Automatic update!
	 */
	public function version_2_1_14_1() {
		get_db( 'events' )->create_table();
	}

	/**
	 * Add index on `claim`
	 * create new event_queue table
	 * migrate waiting events to the new queue
	 * add queued_id column
	 * add optin_status_changed column
	 * add ip_address & views columns to form impressions table
	 *
	 * Automatic update!
	 */
	public function version_2_2() {

		global $wpdb;

		Plugin::$instance->dbs->install_dbs();

		get_db( 'form_impressions' )->update_2_2();
		get_db( 'activity' )->update_2_2();

		// Update the current events table
		get_db( 'events' )->move_events_to_queue( [
			'status' => Event::WAITING
		], true );

		if ( $wpdb->last_error ) {
			wp_die( $wpdb->last_error );
		}
	}

	/**
	 * Refirmat all DBs
	 */
	public function version_2_2_3() {
		Plugin::$instance->dbs->install_dbs();
	}

	/**
	 * Ensure roles/caps are added...
	 */
	public function version_2_2_13() {
		Plugin::instance()->roles->add_roles();
		Plugin::instance()->roles->add_caps();
	}

	/**
	 * Notes db added.
	 * Migrate notes
	 */
	public function version_2_2_14() {
		Plugin::$instance->dbs->install_dbs();
		update_option( 'gh_migrate_notes', 1 );
	}

	public function version_2_2_17() {
		Plugin::$instance->dbs->install_dbs();
	}

	public function version_2_2_18() {
		Plugin::$instance->dbs->install_dbs();
	}

	/**
	 * Added permission keys
	 */
	public function version_2_2_19() {
		// Add the new permissions_keys dbs...
		Plugin::$instance->dbs->install_dbs();
	}

	/**
	 * Flush rewrites
	 */
	public function version_2_2_19_2() {
		install_custom_rewrites();
	}

	/**
	 * Update tag_slug col to support larger tag names
	 */
	public function version_2_2_19_3() {
		Plugin::$instance->dbs->install_dbs();
	}

	/**
	 * Add delete_after_use field to permissions keys
	 * Add the autologin rewrite
	 */
	public function version_2_2_19_4() {
		Plugin::$instance->dbs->install_dbs();
		install_custom_rewrites();
	}

	/**
	 * Add delete_after_use field to permissions keys
	 * Add the autologin rewrite
	 */
	public function version_2_2_22() {
		install_custom_rewrites();
	}

	/**
	 * Verify all the license statuses
	 */
	public function version_2_2_23() {
		$this->remember_version_update( '2.2.23' );

		Plugin::instance()->bulk_jobs->check_licenses->start();
	}

	/**
	 * Re-install the gh-cron.php file with the new constant
	 */
	public function version_2_2_23_3() {
		if ( gh_cron_installed() ) {
			install_gh_cron_file();
		}
	}

	/**
	 * Install new DBs
	 */
	public function version_2_3() {

		// Update the DBs
		Plugin::$instance->dbs->install_dbs();

		// Create the email logging table
		get_db( 'email_log' )->create_table();

		// add new logs caps
		Plugin::instance()->roles->add_caps();

		// Set the WordPress SMTP service to the current Transactional one
		\Groundhogg_Email_Services::set_service( \Groundhogg_Email_Services::WORDPRESS, \Groundhogg_Email_Services::get_transactional_service() );
	}

	/**
	 * Verify all the license statuses
	 */
	public function version_2_3_3() {
		$this->remember_version_update( '2.3.3' );

		Plugin::instance()->bulk_jobs->update_marketing_consent->start();
	}

	/**
	 * Verify all the license statuses
	 */
	public function version_2_4_5_5() {
		recount_tag_contacts_count();

		foreach ( Plugin::instance()->dbs->get_all_object_tables() as $table ) {
			$table->delete_orphaned_meta();
		}
	}

	/**
	 * Refactor notes db
	 */
	public function version_2_4_6() {
		get_db( 'notes' )->update_2_4_6();
	}

	/**
	 * Create the other activity tables
	 */
	public function version_2_4_7() {
		get_db( 'other_activity' )->create_table();
		get_db( 'other_activitymeta' )->create_table();
	}

	/**
	 * Refactor notes db
	 */
	public function version_2_4_7_1() {
		// Create missing table
		get_db( 'activitymeta' )->create_table();

		// For woocommerce, unable to see admin dashboard
		wp_roles()->add_cap( 'sales_rep', 'view_admin_dashboard' );
		wp_roles()->add_cap( 'sales_manager', 'view_admin_dashboard' );
	}

	/**
	 * Verify all the license statuses
	 */
	public function version_2_4_7_3() {
		$this->remember_version_update( '2.4.7.3' );

		Plugin::instance()->bulk_jobs->fix_birthdays->start();
	}

	/**
	 * Refactor notes db
	 */
	public function version_3_0() {
		// For woocommerce, unable to see admin dashboard
		wp_roles()->add_cap( 'marketer', 'manage_campaigns' );
		wp_roles()->add_cap( 'marketer', 'export_funnels' );
		wp_roles()->add_cap( 'administrator', 'manage_campaigns' );
		wp_roles()->add_cap( 'administrator', 'export_funnels' );
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
			'2.1.13.6',
			'2.1.14.1',
			'2.2',
			'2.2.14',
			'2.2.17',
			'2.2.18',
			'2.2.19',
			'2.2.19.2',
			'2.2.19.3',
			'2.2.19.4',
			'2.2.22',
			'2.2.23',
			'2.2.23.3',
			'2.3',
			'2.3.3',
			'2.4.5.5',
			'2.4.6',
			'2.4.7',
			'2.4.7.1',
			'2.4.7.3',
			'3.0',
		];
	}

	/**
	 * Automatic updates
	 *
	 * @return array|string[]
	 */
	protected function get_automatic_updates() {
		return [
			'2.1.13.11',
			'2.1.13.17',
			'2.2.3',
			'2.2.13',
			'2.2.14',
			'2.2.18',
			'2.2.19',
			'2.2.19.2',
			'2.2.19.3',
			'2.2.19.4',
			'2.2.22',
			'2.2.22.3',
			'2.3',
			'2.4.7',
			'2.4.7.1',
			'3.0',
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

	/**
	 * Show any required update descriptions.
	 *
	 * @return array|string[]
	 */
	protected function get_update_descriptions() {
		return [
			'2.1.14'        => __( 'Added notes table.', 'groundhogg' ),
			'2.1.13'        => __( 'Refactor contact optin statuses to meet new format.', 'groundhogg' ),
			'2.1.13.revert' => __( 'Revert update 2.1.13 if rogue updated refactored optin status more than once.', 'groundhogg' ),
			'2.1.13.6'      => __( 'Give funnel events higher priority than broadcast events.', 'groundhogg' ),
			'2.1.13.11'     => __( 'Add micro_time column to events table for better display of events order.', 'groundhogg' ),
			'2.1.14.1'      => __( 'Add missing index on `claim` column.', 'groundhogg' ),
			'2.2'           => __( 'Event queue performance improvements.', 'groundhogg' ),
			'2.2.3'         => __( 'Reformat all tables.', 'groundhogg' ),
			'2.2.14'        => __( 'Migrate notes and update broadcasts table.', 'gorundhogg' ),
			'2.2.17'        => __( 'Added Broadcast meta table.', 'groundhogg' ),
			'2.2.18'        => __( 'Add email_id column to events tables.', 'groundhogg' ),
			'2.2.19.3'      => __( 'Increased tag name size. Tag name can be 191 to 255 characters based on your hosted server.', 'groundhogg' ),
			'2.2.19.4'      => __( 'Update the permission keys table to support new usage.', 'groundhogg' ),
			'2.2.22'        => __( 'Show preferences page if url is just /gh/.', 'groundhogg' ),
			'2.2.22.3'      => __( 'Re-install the gh-cron.php file to include a constant <code>DOING_GH_CRON</code>.', 'groundhogg' ),
			'2.3'           => __( 'Add the new database tables for the new Email Logging feature.', 'groundhogg' ),
			'2.3.3'         => __( 'Separate GDPR consent into Data Processing consent and Marketing consent', 'groundhogg' ),
			'2.4.5.5'       => __( 'Fix tag counts and delete orphaned object meta.', 'groundhogg' ),
			'2.4.6'         => __( 'Refactor notes to abstract data type for support across more objects.', 'groundhogg' ),
			'2.4.7'         => __( 'Add new Other Activity tables for arbitrary historical logs.', 'groundhogg' ),
			'2.4.7.1'       => __( 'Add <code>view_admin_dashboard</code> capability to Sales Representative and Sales Manager', 'groundhogg' ),
			'2.4.7.3'       => __( 'Fix birthday date formatting.', 'groundhogg' ),
		];
	}
}
