<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Reporting\Email_Reports;
use Groundhogg\Templates\Notifications\Notification_Builder;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Replacer;

class Cleanup_Actions {

	public function __construct() {
		add_action( 'init', [ $this, 'schedule_event' ] );

		add_action( 'groundhogg/cleanup', [ $this, 'fix_unprocessed_events' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'fix_unprocessed_tasks' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'delete_expired_permission_keys' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'purge_email_logs' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'handle_sent_broadcasts' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'notify_of_failed_events' ] );
	}

	/**
	 * Fix broadcasts that might have their status stuck in 'sending'
	 *
	 * @return void
	 */
	public function handle_sent_broadcasts() {
		Broadcast::transition_from_sending_to_sent();
	}

	/**
	 * Schedules the cron event for WordPress
	 *
	 * @return void
	 */
	public function schedule_event() {
		if ( wp_next_scheduled( 'groundhogg/cleanup' ) ) {
			return;
		}

		$date = new DateTimeHelper( 'today' );

		wp_schedule_event( $date->getTimestamp(), 'hourly', 'groundhogg/cleanup' );
	}

	/**
	 * If there are new failed events since the last report, send the report. Will send at most once each hour.
	 *
	 * @return void
	 */
	public function notify_of_failed_events() {

		if ( ! is_option_enabled( 'gh_send_notifications_on_event_failure' ) ) {
			return;
		}

		$last_sent = absint( get_option( 'gh_failed_event_notification_last_sent' ) );
		if ( ! $last_sent ){
			$last_sent = time() - DAY_IN_SECONDS;
		}

		$result = Email_Reports::send_failed_events_report( $last_sent );

		// If the email was sent, update the last sent flag
		if ( $result === true ){
			update_option( 'gh_failed_event_notification_last_sent', time() );
		}
	}

	/**
	 * Automatically fix events that are not processed
	 *
	 * @return void
	 */
	public function fix_unprocessed_events() {

		$query = new Table_Query( 'event_queue' );
		$query->where()
		      ->in( 'status', [ Event::WAITING, Event::IN_PROGRESS ] ) // Event is waiting or in progress
		      ->notEmpty( 'claim' ) // Claim is not empty, it should either be released or not in the queue anymore
		      ->greaterThan( 'time_claimed', 0 )
		      ->lessThanEqualTo( 'time_claimed', time() - ( 5 * MINUTE_IN_SECONDS ) ); // claimed more than 5 minutes ago

		// release stale claim
		$query->update( [
			'status'       => Event::WAITING,
			'claim'        => '',
			'time_claimed' => 0,
		] );
	}

	/**
	 * Automatically fix background tasks that are not processed
	 *
	 * @return void
	 */
	public function fix_unprocessed_tasks() {

		$query = new Table_Query( 'background_tasks' );
		$query->where()
		      ->in( 'status', [ 'pending', 'in_progress' ] ) // Task is pending or in progress
		      ->notEmpty( 'claim' ) // Claim is not empty, it should either be released or not in the queue anymore
		      ->greaterThan( 'time_claimed', 0 )
		      ->lessThanEqualTo( 'time_claimed', time() - ( 5 * MINUTE_IN_SECONDS ) ); // claimed more than 5 minutes ago

		$query->update( [
			'status'       => Event::WAITING,
			'claim'        => '',
			'time_claimed' => 0,
		] );
	}

	/**
	 * Delete any expired permissions keys
	 *
	 * @return void
	 */
	public function delete_expired_permission_keys() {
		$query = new Table_Query( 'permissions_keys' );
		$query->where()->lessThan( 'expiration_date', Ymd_His() );
		$query->delete();
	}

	/**
	 * Purge old email logs
	 *
	 * @return void
	 */
	public function purge_email_logs() {

		$retention_in_days = get_option( 'gh_email_log_retention' ) ?: 14;
		$date              = new DateTimeHelper( strtotime( $retention_in_days . ' days ago' ) );
		$query             = new Table_Query( 'email_log' );
		$query->where()->lessThan( 'date_sent', $date->ymdhis() );
		$query->delete();
	}
}
