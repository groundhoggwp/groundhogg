<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Steps\Actions\Send_Email;

class Main_Updater extends Old_Updater {

	/**
	 * New class for managing the *new* update format
	 *
	 * @return array[]
	 */
	protected function get_updates() {
		return [
			'2.7.11.3' => [
				'automatic'   => true,
				'description' => __( 'Refresh permalinks so that the new email archive feature works.', 'groundhogg' ),
				'callback'    => function () {
					install_custom_rewrites();

					$steps = get_db( 'steps' )->query( [
						'step_type'   => Send_Email::TYPE,
						'step_status' => 'active'
					] );

					array_map_to_step( $steps );

					foreach ( $steps as $step ) {
						$email_id = absint( $step->get_meta( 'email_id' ) );

						get_db( 'events' )->update( [
							'funnel_id'  => $step->get_funnel_id(),
							'step_id'    => $step->get_id(),
							'event_type' => Event::FUNNEL,
							'status'     => Event::COMPLETE,
						], [ 'email_id' => $email_id ] );
					}
				}
			],
			'2.7.11.7' => [
				'automatic'   => true,
				'description' => __( 'Refresh permalinks for shortened tracking URL structure.', 'groundhogg' ),
				'callback'    => function () {
					install_custom_rewrites();
				}
			],
			'3.0'      => [
				'automatic'   => true,
				'description' => __( 'Update the emails table.', 'groundhogg' ),
				'callback'    => function () {
					// Update the emails table to add `plain` and `type` as a column
					get_db( 'emails' )->create_table();
				}
			],
			'3.0.1'    => [
				'automatic'   => true,
				'description' => __( 'Disable the Advanced Email Editor automatically.', 'groundhogg' ),
				'callback'    => function () {
					delete_option( 'gh_use_advanced_email_editor' );
				}
			],
			'3.1'    => [
				'automatic'   => true,
				'description' => __( 'Enable tag mapping.', 'groundhogg' ),
				'callback'    => function () {
					update_option( 'gh_enable_tag_mapping', 'on' );
				}
			],
			'3.2.2'    => [
				'automatic'   => true,
				'description' => __( 'Rename cron job hooks.', 'groundhogg' ),
				'callback'    => function () {
					wp_clear_scheduled_hook( 'gh_purge_old_email_logs' );
					wp_clear_scheduled_hook( 'gh_purge_page_visits' );
				}
			],
			'3.2.3.1'    => [
				'automatic'   => true,
				'description' => __( 'Re-sync funnel step statuses.', 'groundhogg' ),
				'callback'    => function () {

					$query = new Table_Query( 'funnels' );
					$funnels = $query->get_objects( Funnel::class );

					foreach ( $funnels as $funnel ) {
						$funnel->update_step_status();
					}
				}
			],
			'3.3'    => [
				'automatic'   => true,
				'description' => __( 'List-Unsubscribe header is now required.', 'groundhogg' ),
				'callback'    => function () {
					// List-Unsubscribe is now required, delete this options since it has become unused
					delete_option( 'gh_disable_unsubscribe_header' );
				}
			],
			'3.3.1'    => [
				'automatic'   => true,
				'description' => __( 'Add background tasks table for better logging and management of background tasks.', 'groundhogg' ),
				'callback'    => function () {
					get_db( 'background_tasks' )->create_table();
				}
			],
			'3.3.2'    => [
				'automatic'   => true,
				'description' => __( 'Combine cron tasks into a single hook.', 'groundhogg' ),
				'callback'    => function () {
					wp_clear_scheduled_hook( 'groundhogg/purge_email_logs' );
					wp_clear_scheduled_hook( 'groundhogg/purge_expired_permissions_keys' );
				}
			],
			'3.4'    => [
				'automatic'   => true,
				'description' => __( 'Update permalinks, submissions table, and the campaigns table.', 'groundhogg' ),
				'callback'    => function () {
					get_db( 'campaigns' )->create_table();
					get_db( 'submissions' )->create_table();
					install_custom_rewrites();
				}
			],
			'3.4.1'    => [
				'automatic'   => true,
				'description' => __( 'Create new user agents table and add user agent columns to page visits and activity.', 'groundhogg' ),
				'callback'    => function () {
					get_db( 'user_agents' )->create_table();
					get_db( 'activity' )->create_table();
					get_db( 'page_visits' )->create_table();
				}
			],
			'3.4.2'    => [
				'automatic'   => true,
				'description' => __( 'Convert IP Address columns to binary and optimize table indexes.', 'groundhogg' ),
				'callback'    => function () {
					db()->activity->update_3_4_2();
					db()->events->update_3_4_2();
					db()->event_queue->update_3_4_2();
					db()->page_visits->update_3_4_2();
					db()->form_impressions->update_3_4_2();
				}
			],
			'3.4.2.1'    => [
				'automatic'   => true,
				'description' => __( 'Fix IP Address column not renamed correctly.', 'groundhogg' ),
				'callback'    => function () {
					db()->activity->maybe_fix_ip_column();
					db()->page_visits->maybe_fix_ip_column();
					db()->form_impressions->maybe_fix_ip_column();
				}
			],
			'3.5'    => [
				'automatic'   => true,
				'description' => __( 'Subscribe admins that can view reports to the new email performance reports.', 'groundhogg' ),
				'callback'    => function () {
					$owners = filter_by_cap( get_owners(), 'view_reports' );
					foreach ( $owners as $owner ){

						// Ignore super admin and admins that do not have an email the belongs to the current site. For example agency users
						if ( ( is_multisite() && is_super_admin( $owner->ID ) ) || ! email_is_same_domain( $owner->user_email ) ){
							continue;
						}

						update_user_meta( $owner->ID, 'gh_broadcast_results', 1 );
						update_user_meta( $owner->ID, 'gh_weekly_overview', 1 );
					}

					// Clear the purge page visits hook because we're going to use the daily hook instead
					wp_clear_scheduled_hook( 'groundhogg/purge_page_visits' );
				}
			],
			'3.5.1'    => [
				'automatic'   => true,
				'description' => __( 'Unsubscribe super admins, support, and unrelated emails from email reports.', 'groundhogg' ),
				'callback'    => function () {
					$owners = filter_by_cap( get_owners(), 'view_reports' );
					foreach ( $owners as $owner ){
						// Unsubscribe super admin and admins that do not have an email the belongs to the current site. For example agency users
						if ( ( is_multisite() && is_super_admin( $owner->ID ) ) || ! email_is_same_domain( $owner->user_email ) ){
							delete_user_meta( $owner->ID, 'gh_broadcast_results' );
							delete_user_meta( $owner->ID, 'gh_weekly_overview' );
						}
					}
				}
			],
			'3.7'    => [
				'automatic'   => true,
				'description' => __( 'Add time_claimed flag for events and background tasks', 'groundhogg' ),
				'callback'    => function () {
					wp_unschedule_hook( 'groundhogg/cleanup' );
					wp_unschedule_hook( 'groundhogg/check_bounces' );
					db()->background_tasks->create_table();
					db()->event_queue->create_table();
				}
			],
			'3.7.2'    => [
				'automatic'   => true,
				'description' => __( 'Add names for submissions.', 'groundhogg' ),
				'callback'    => function () {
					db()->submissions->create_table(); // add name column
				}
			],
			'3.7.3'    => [
				'automatic'   => true,
				'description' => __( 'Add the <code>altbody</code> column to the logs DB table.', 'groundhogg' ),
				'callback'    => function () {
					db()->email_log->create_table(); // add altbody column
				}
			]
		];
	}

	/**
	 * Wrapper for new format
	 *
	 * @return array[]|string[]
	 */
	protected function get_available_updates() {
		return array_merge( parent::get_available_updates(), $this->get_updates() );
	}

}
