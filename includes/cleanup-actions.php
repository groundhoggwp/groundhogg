<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;

class Cleanup_Actions {

	public function __construct() {
		add_action( 'init', [ $this, 'schedule_event' ] );

		add_action( 'groundhogg/cleanup', [ $this, 'fix_unprocessed_events' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'fix_unprocessed_tasks' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'delete_expired_permission_keys' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'purge_email_logs' ] );
		add_action( 'groundhogg/cleanup', [ $this, 'handle_sent_broadcasts' ] );
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
			'status' => Event::WAITING,
			'claim'  => '',
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
