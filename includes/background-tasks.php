<?php

namespace Groundhogg;

use Groundhogg\background\Add_Contacts_To_Funnel_Last_Id;
use Groundhogg\background\Complete_Benchmark_Last_Id;
use Groundhogg\background\Delete_Contacts;
use Groundhogg\Background\Schedule_Broadcast;
use Groundhogg\Background\Task;
use Groundhogg\background\Update_Contacts_Last_Id;
use Groundhogg\Classes\Background_Task;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Utils\Limits;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Background_Tasks {

	const HOOK = 'groundhogg/background_tasks';

	protected static array $tasks = [];

	/**
	 *
	 */
	public function __construct() {
		add_action( self::HOOK, [ $this, 'do_tasks' ], 10 );
		add_action( 'init', [ $this, 'add_cron' ] );
	}

	/**
	 * Schedule recurring cron job
	 *
	 * @return void
	 */
	public function add_cron() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), Event_Queue::WP_CRON_INTERVAL, self::HOOK );
		}
	}

	/**
	 * Do the tasks
	 *
	 * @return void
	 */
	public function do_tasks() {

		$claim = generate_claim();

		$taskQuery = new Table_Query( 'background_tasks' );
		$taskQuery->where->in( 'status', [ 'pending', 'in_progress' ] )
		                 ->empty( 'claim' )
		                 ->lessThan( 'time', time() );

		// Claim the tasks
		$updated = $taskQuery->update( [
			'claim'        => $claim,
			'status'       => 'in_progress',
			'time_claimed' => time(),
		] );

		// No tasks were claimed
		if ( ! $updated ) {
			return;
		}

		// Fetch the claimed tasks
		$claimQuery = new Table_Query( 'background_tasks' );
		$claimQuery->where->equals( 'claim', $claim );
		$tasks = $claimQuery->get_objects();

		if ( empty( $tasks ) ) {
			return;
		}

		Limits::raise_memory_limit();
		Limits::raise_time_limit( MINUTE_IN_SECONDS );

		while ( ! Limits::limits_exceeded() && ! empty( $tasks ) ) {
			$task = array_shift( $tasks );

			try {
				$task->process();
			} catch ( \Exception $exception ) {
				continue;
			}
		}

		// Release the claim
		$claimQuery->update( [
			'claim'        => '',
			'time'         => time(),
			'time_claimed' => 0
		] );
	}

	/**
	 * @var int
	 */
	protected static int $last_added_task_id = 0;

	/**
	 * @var Background_Task
	 */
	protected static $last_added_task;

	/**
	 * Schedules the background task wp-cron event
	 *
	 * @param Task $task
	 * @param int  $time
	 *
	 * @return bool|\WP_Error
	 */
	public static function add( Task $task, int $time = 0 ) {

		if ( ! $time ) {
			$time = time();
		}

		$time = apply_filters( 'groundhogg/background_tasks/schedule_time', $time, $task );

		$bg_task = new Background_Task();
		$bg_task->create( [
			'task'    => $task,
			'time'    => $time,
			'user_id' => get_current_user_id()
		] );

		if ( ! $bg_task->exists() ) {
			return new \WP_Error( 'oops', 'Unable to add background task.' );
		}

		self::$last_added_task_id = $bg_task->ID;
		self::$last_added_task    = $bg_task;

		return true;
	}

	/**
	 * Return the ID of the most recently added task
	 *
	 * @return int
	 */
	public static function get_last_added_task_id(): int {
		return self::$last_added_task_id;
	}

	/**
	 * Return the ID of the most recently added task
	 *
	 * @return null|Background_Task
	 */
	public static function get_last_added_task() {
		return self::$last_added_task;
	}

	/**
	 * Remove a background task
	 *
	 * @deprecated 3.3.1
	 *
	 * @param $args
	 * @param $hook
	 *
	 * @return false
	 */
	public static function remove( $hook, $args = [] ) {
		_deprecated_function( __METHOD__, '3.3.1' );

		return false;
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
	public static function update_contacts( $query, $data ) {
		return self::add( new Update_Contacts_Last_Id( $query, $data ) );
	}

	/**
	 * Delete contacts in the background
	 *
	 * @param $query
	 * @param $batch
	 *
	 * @return bool|\WP_Error
	 */
	public static function delete_contacts( $query ) {
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
	public static function add_contacts_to_funnel( $step_id, $query, $batch = 0, $args = [] ) {
		return self::add( new Add_Contacts_To_Funnel_Last_Id( $step_id, $query, $batch, $args ) );
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
	public static function complete_benchmark( $step_id, $query, $batch = 0, $args = [] ) {
		return self::add( new Complete_Benchmark_Last_Id( $step_id, $query, $batch, $args ) );
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
