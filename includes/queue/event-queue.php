<?php

namespace Groundhogg\Queue;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Queue
 *
 * This adds the cron schedule and cron job to process events every 5 minutes.
 * Runs recursively until all consecutive events are completed.
 *
 * @since       File available since Release 1.0.18
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Event_Queue extends Supports_Errors {

	/**
	 * The Cron Hook
	 */
	const WP_CRON_HOOK = 'groundhogg_process_queue';

	/**
	 * The Cron Interval
	 */
	const WP_CRON_INTERVAL = 'every_minute';

	/**
	 * @var Contact the current contact in the event
	 */
	protected $current_contact;

	/**
	 * @var object|Event the current event
	 */
	protected $current_event;

	/**
	 * @var int The time the queue was initialized.
	 */
	protected $started;

	/**
	 * @var bool
	 */
	protected static $is_processing;

	/**
	 * @var Event_Store
	 */
	protected $store;

	/**
	 * @var int[]
	 */
	protected $event_ids = [];

	/**
	 * Setup the cron jobs
	 * Add new short term schedule
	 * setup the action for the cron job
	 */
	public function __construct() {
		add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
		add_action( 'init', [ $this, 'setup_cron_jobs' ] );
		add_action( self::WP_CRON_HOOK, [ $this, 'run_queue' ] );
	}

	/**
	 * Whether the queue is enabled or not.
	 *
	 * @return mixed|void
	 */
	public function is_enabled() {
		return apply_filters( 'groundhogg/event_queue/is_enabled', true, $this );
	}


	/**
	 * Add the schedules
	 *
	 * @param $schedules
	 *
	 * @return array|false
	 */
	public function add_cron_schedules( $schedules = [] ) {
		if ( ! is_array( $schedules ) ) {
			return $schedules;
		}

		$schedules[ self::WP_CRON_INTERVAL ] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => _x( 'Every Minute', 'cron_schedule', 'groundhogg' )
		);

		return $schedules;
	}

	/**
	 * Add the event cron job
	 *
	 * @since 1.0.20.1 Added notice to check if there is something wrong with the cron system.
	 */
	public function setup_cron_jobs() {
		if ( ! wp_next_scheduled( self::WP_CRON_HOOK ) ) {
			wp_schedule_event( time(), apply_filters( 'groundhogg/event_queue/queue_interval', self::WP_CRON_INTERVAL ), self::WP_CRON_HOOK );
		}
	}

	/**
	 * @param $time
	 */
	protected function set_start( $time ) {
		$this->started = $time;
	}

	/**
	 * Run any scheduled events.
	 *
	 * @return int
	 */
	public function run_queue() {

		if ( ! $this->is_enabled() ){
			return 0;
		}

		$this->store = new Event_Store();

		$this->set_start( microtime( true ) );

		$settings = Plugin::$instance->settings;

		Compatibility::raise_memory_limit();
		Compatibility::raise_time_limit( $this->get_time_limit() );

		$result = $this->process();

		$end          = microtime( true );
		$process_time = $end - $this->started;

		$times_executed         = intval( $settings->get_option( 'queue_times_executed', 0 ) );
		$average_execution_time = floatval( $settings->get_option( 'average_execution_time', 0.0 ) );

		$average = $times_executed * $average_execution_time;
		$average += $process_time;
		$times_executed ++;
		$average_execution_time = $average / $times_executed;

		$settings->update_option( 'queue_last_execution_time', $process_time );
		$settings->update_option( 'queue_times_executed', $times_executed );
		$settings->update_option( 'average_execution_time', $average_execution_time );

		return $result;
	}

	/**
	 * Recursive, Iterate through the list of events and process them via the EVENTS api
	 * completes successive events quite since WP-Cron only happens once every 5 or 10 minutes depending on
	 * the amount of traffic.
	 *
	 * @return int the number of events process, 0 if no events.
	 */
	protected function process( $completed_events = 0 ) {

		$max_events = apply_filters( 'groundhogg/event_queue/max_events', 50 );

		$claim     = $this->store->stake_claim( $max_events );
		$event_ids = $this->store->get_events_by_claim( $claim );

		if ( empty( $event_ids ) ) {
			return $completed_events;
		}

		do_action( 'groundhogg/event_queue/process/before', $event_ids );

		self::set_is_processing( true );

		do {
			$event_id = array_pop( $event_ids );

			$event = new Event( $event_id );
			$this->set_current_event( $event );

			$contact = $event->get_contact();
			$this->set_current_contact( $contact );

			if ( $event->run() ) {

				if ( $event->is_funnel_event() ) {

					$next_step = $event->get_step()->get_next_action();

					if ( $next_step instanceof Step && $next_step->is_active() ) {
						$next_step->enqueue( $event->get_contact() );
					}
				}


			} else {
				if ( $event->has_errors() ) {
					$this->add_error( $event->get_last_error() );
				}
			}

			$completed_events += 1;

		} while ( ! empty( $event_ids ) && ! $this->limits_exceeded( $completed_events ) );

		$this->store->release_events( $claim );

		self::set_is_processing( false );

		do_action( 'groundhogg/event_queue/process/after', $this );

		if ( $this->limits_exceeded( $completed_events ) ) {
			return $completed_events;
		}

		return $this->process( $completed_events );
	}

	/**
	 * Run the queue Manually and provide a notice.
	 */
	public function run_queue_manually() {

		if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'process_queue' ) || ! current_user_can( 'schedule_events' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		Plugin::$instance->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $this->run_queue(), $this->get_last_execution_time() ) );

		if ( $this->has_errors() ) {

			Plugin::$instance->notices->add( 'queue-errors', sprintf( "%d events failed to complete. Please see the following errors.", count( $this->get_errors() ) ), 'warning' );

			foreach ( $this->get_errors() as $error ) {
				Plugin::instance()->notices->add( $error );
			}
		}
	}

	/**
	 * Get the number of seconds the process has been running.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_execution_time() {
		$execution_time = microtime( true ) - $this->started;

		// Get the CPU time if the hosting environment uses it rather than wall-clock time to calculate a process's execution time.
		if ( function_exists( 'getrusage' ) && apply_filters( 'action_scheduler_use_cpu_execution_time', defined( 'PANTHEON_ENVIRONMENT' ) ) ) {
			$resource_usages = getrusage();

			if ( isset( $resource_usages['ru_stime.tv_usec'], $resource_usages['ru_stime.tv_usec'] ) ) {
				$execution_time = $resource_usages['ru_stime.tv_sec'] + ( $resource_usages['ru_stime.tv_usec'] / 1000000 );
			}
		}

		return $execution_time;
	}


	/**
	 * Check if the host's max execution time is (likely) to be exceeded if processing more actions.
	 *
	 * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action
	 *
	 * @return bool
	 */
	protected function time_likely_to_be_exceeded( $processed_actions ) {

		$execution_time        = $this->get_execution_time();
		$max_execution_time    = $this->get_time_limit();
		$time_per_action       = $execution_time / $processed_actions;
		$estimated_time        = $execution_time + ( $time_per_action * 3 );
		$likely_to_be_exceeded = $estimated_time > $max_execution_time;

		return apply_filters( 'groundhogg/event_queue/time_likely_to_be_exceeded', $likely_to_be_exceeded, $this, $processed_actions, $execution_time, $max_execution_time );
	}

	/**
	 * Get memory limit
	 *
	 * Based on WP_Background_Process::get_memory_limit()
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
		}

		if ( ! $memory_limit || - 1 === $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return Compatibility::convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90% of the maximum WordPress memory.
	 *
	 * Based on WP_Background_Process::memory_exceeded()
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {

		$memory_limit    = $this->get_memory_limit() * 0.90;
		$current_memory  = memory_get_usage( true );
		$memory_exceeded = $current_memory >= $memory_limit;

		return apply_filters( 'groundhogg/event_queue/memory_exceeded', $memory_exceeded, $this );
	}

	/**
	 * See if the batch limits have been exceeded, which is when memory usage is almost at
	 * the maximum limit, or the time to process more actions will exceed the max time limit.
	 *
	 * Based on WC_Background_Process::batch_limits_exceeded()
	 *
	 * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action
	 *
	 * @return bool
	 */
	protected function limits_exceeded( $processed_actions ) {
		return $this->memory_exceeded() || $this->time_likely_to_be_exceeded( $processed_actions );
	}

	/**
	 * Set the processing state
	 *
	 * @param bool $bool
	 */
	protected static function set_is_processing( $bool = true ) {
		self::$is_processing = (bool) $bool;
	}

	/**
	 * Whether the queue is processing.
	 *
	 * @return bool
	 */
	public static function is_processing() {
		return (bool) static::$is_processing;
	}

	/**
	 * The time we have in seconds to process the queue.
	 *
	 * @return float|int
	 */
	public function get_time_limit() {
		return min( MINUTE_IN_SECONDS, $this->get_real_max_execution_time() );
	}

	/**
	 * @return int
	 */
	public function get_real_max_execution_time() {
		return absint( ini_get( 'max_execution_time' ) );
	}

	/**
	 * @param $event Event
	 */
	protected function set_current_event( &$event ) {
		$this->current_event = $event;
	}

	/**
	 * @return Event
	 */
	public function get_current_event() {
		return $this->current_event;
	}

	/**
	 * @param $contact Contact
	 */
	protected function set_current_contact( &$contact ) {
		$this->current_contact = $contact;
	}

	/**
	 * @return Contact
	 */
	public function get_current_contact() {
		return $this->current_contact;
	}

	/**
	 * @return mixed
	 */
	public function get_queue_execution_time() {
		return Plugin::$instance->settings->get_option( 'average_execution_time' );
	}

	/**
	 * @return mixed
	 */
	public function get_last_execution_time() {
		return Plugin::$instance->settings->get_option( 'queue_last_execution_time' );
	}

	/**
	 * @return mixed
	 */
	public function get_total_executions() {
		return Plugin::$instance->settings->get_option( 'queue_times_executed' );
	}

}