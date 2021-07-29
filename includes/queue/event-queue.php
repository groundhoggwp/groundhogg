<?php

namespace Groundhogg\Queue;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;
use function Groundhogg\gh_cron_installed;

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

	protected $max_events = 50;
	protected $logging_enabled = false;

	/**
	 * Setup the cron jobs
	 * Add new short term schedule
	 * setup the action for the cron job
	 */
	public function __construct() {

		add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );
		add_action( self::WP_CRON_HOOK, [ $this, 'run_queue' ] );

		// no need if gh-cron.php is installed.
		if ( ! gh_cron_installed() ) {
			add_action( 'init', [ $this, 'setup_cron_jobs' ] );
		}

		add_action( 'init', [ $this, 'init' ] );

	}

	public function init() {
		$this->max_events      = apply_filters( 'groundhogg/event_queue/max_events', 50 );
		$this->logging_enabled = apply_filters( 'groundhogg/queue/enable_logging', false );
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
		if ( ! gh_cron_installed() && ! wp_next_scheduled( self::WP_CRON_HOOK ) ) {
			wp_schedule_event( time(), apply_filters( 'groundhogg/event_queue/queue_interval', self::WP_CRON_INTERVAL ), self::WP_CRON_HOOK );
		}

	}

	/**
	 * If the event queue failed for whatever reason, fix events which are still in progress.
	 * Update status back to waiting
	 * Delete the claim
	 * Only for events which were scheduled to be complete but never did.
	 */
	protected function cleanup_unprocessed_events() {
		global $wpdb;

		$events = get_db( 'event_queue' );

		// 5 minute window.
		$time = time() - ( MINUTE_IN_SECONDS * 5 );

		$wpdb->query( "UPDATE {$events->get_table_name()} SET claim = '' WHERE `claim` <> '' AND `time` < {$time}" );
		$wpdb->query( "UPDATE {$events->get_table_name()} SET status = 'waiting' WHERE status = 'in_progress' AND `time` < {$time}" );
	}

	/**
	 * Run any scheduled events.
	 *
	 * @return int
	 */
	public function run_queue() {

		// Let's make sure we are not over doing it.
		if ( ! $this->is_enabled() ) {
			return 0;
		}

		$this->cleanup_unprocessed_events();

		Limits::start();

		Limits::raise_memory_limit();
		Limits::raise_time_limit( MINUTE_IN_SECONDS );

		$this->cleanup_unprocessed_events();

		$this->store = new Event_Store();
		$settings    = Plugin::$instance->settings;

		$thread_id = uniqid();

		$this->log( sprintf( '%s - Starting queue!', $thread_id ) );

		$result = $this->process();

		$process_time = round( Limits::time_elapsed(), 2 );
		Limits::stop();

		if ( $result > 0 ) {
			$this->log( sprintf( "%s - %d events have been completed in %s seconds.", $thread_id, $result, $process_time ) );
		} else {
			$this->log( sprintf( '%s - No events completed.', $thread_id ) );
		}

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
	 * @param int $completed_events
	 *
	 * @return int the number of events process, 0 if no events.
	 */
	protected function process( $completed_events = 0 ) {

		$claim = $this->store->stake_claim( $this->max_events );

		// no events to complete
		if ( ! $claim ) {
			return $completed_events;
		}


		$event_ids           = $this->store->get_events_by_claim( $claim );
		$processed_event_ids = [];

		// If this happens it means we are in a parallel queue processing situation,
		// so let's just try and make another claim.
		if ( empty( $event_ids ) ) {

			$claim = $this->store->stake_claim( $this->max_events );

			// no events to complete
			if ( ! $claim ) {
				return $completed_events;
			}

			$event_ids = $this->store->get_events_by_claim( $claim );

		}

		// Definitely no events, let's bail.
		if ( empty( $event_ids ) ) {
			return $completed_events;
		}

		self::set_is_processing( true );

		do {

			$event_id = array_pop( $event_ids );

			$event = new Event( $event_id, 'event_queue' );
			$this->set_current_event( $event );

			$contact = $event->get_contact();

			$this->set_current_contact( $contact );

			if ( ! is_wp_error( $event->run() ) ) {

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

			$processed_event_ids[] = $event_id;
			$completed_events      += 1;
			Limits::processed_action();

		} while ( ! empty( $event_ids ) && ! Limits::limits_exceeded() );

		$this->store->release_events( $claim );
		get_db( 'event_queue' )->move_events_to_history( [ 'ID' => $processed_event_ids ] );
		get_db( 'event_queue' )->move_events_to_history( [ 'status' => 'skipped' ] );

		self::set_is_processing( false );

		if ( Limits::limits_exceeded( $completed_events ) ) {
			return $completed_events;
		}

		return $this->process( $completed_events );
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

	/**
	 * Log queue messages
	 *
	 * @param string $message
	 */
	public function log( $message = "" ) {

		if ( ! $this->logging_enabled ) {
			return;
		}

		$file    = Plugin::instance()->utils->files->get_uploads_dir( 'logs', 'queue', true );
		$message = sprintf( "\n%s: %s", time(), $message );

		error_log( $message, 3, $file );
	}


}