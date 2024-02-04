<?php

namespace Groundhogg;

use Groundhogg\background\Add_Contacts_To_Funnel;
use Groundhogg\Background\Complete_Benchmark;
use Groundhogg\background\Delete_Contacts;
use Groundhogg\Background\Schedule_Broadcast;
use Groundhogg\Background\Task;
use Groundhogg\Background\Update_Contacts;
use Groundhogg\Utils\Limits;

class Background_Tasks {

	const HOOK = 'groundhogg/background_tasks';

	protected static array $tasks = [];

	public function __construct() {
		add_action( self::HOOK, [ $this, 'handle_task' ], 10, 1 );
	}

	/**
	 * Do callback for the background task to be completed
	 *
	 * @param Task $task
	 *
	 * @return void
	 */
	public function handle_task( Task $task ){

		if ( ! $task->can_run() ){
			return;
		}

		Limits::start();

		Limits::raise_memory_limit();
		Limits::raise_time_limit( MINUTE_IN_SECONDS );

		$complete = false;

		while ( ! Limits::limits_exceeded() && ! $complete ){
			$complete = $task->process();
			Limits::processed_action();
		}

		$task->stop();

		if ( ! $complete ){
			self::add( $task );
		}

		Limits::stop();
	}

	/**
	 * Schedules the background task wp-cron event
	 *
	 * @param Task $task
	 * @param bool $time
	 *
	 * @return bool|\WP_Error
	 */
	public static function add( Task $task, $time = false ) {

		if ( ! is_int( $time ) ){
			$time = time();
		}

		$when = apply_filters( 'groundhogg/background_tasks/schedule_time', $time + 10, $task );

		return wp_schedule_single_event( $when, self::HOOK, [ $task ] );
	}

	/**
	 * Remove a background task
	 *
	 * @param $hook
	 * @param $args
	 *
	 * @return false|int|\WP_Error
	 */
	public static function remove( $hook, $args = [] ) {
		return wp_clear_scheduled_hook( $hook, $args );
	}

	/**
	 * Update contacts in the background
	 *
	 * @param $query
	 * @param $data
	 * @param $batch
	 *
	 * @return bool|\WP_Error
	 */
	public static function update_contacts( $query, $data ){
		return self::add( new Update_Contacts( $query, $data ) );
	}

	/**
	 * Delete contacts in the background
	 *
	 * @param $query
	 * @param $batch
	 *
	 * @return bool|\WP_Error
	 */
	public static function delete_contacts( $query ){
		return self::add( new Delete_Contacts( $query ) );
	}

	/**
	 * Wrapper function to add contacts to a funnel
	 *
	 * @param     $step_id
	 * @param     $query
	 * @param int $batch
	 *
	 * @return bool|\WP_Error
	 */
	public static function add_contacts_to_funnel( $step_id, $query, $batch = 0 ) {
		return self::add( new Add_Contacts_To_Funnel( $step_id, $query, $batch ) );
	}

	/**
	 * Wrapper function to add contacts to a funnel
	 *
	 * @param     $step_id
	 * @param     $query
	 * @param int $batch
	 *
	 * @return bool|\WP_Error
	 */
	public static function complete_benchmark( $step_id, $query, $batch = 0 ) {
		return self::add( new Complete_Benchmark( $step_id, $query, $batch ) );
	}

	/**
	 * Schedule a pending broadcast
	 *
	 * @param $broadcast_id
	 *
	 * @return bool|\WP_Error
	 */
	public static function schedule_pending_broadcast( $broadcast_id ) {
		return self::add( new Schedule_Broadcast( $broadcast_id ) );
	}
}
