<?php

namespace Groundhogg\Queue;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\gh_doing_cron;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_event_queue_processing;
use function Groundhogg\track_wp_cron_ping;

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
	 * @var Event_Store_V2
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
		add_action( 'init', [ $this, 'setup_cron_jobs' ] );
		add_action( 'init', [ $this, 'init' ] );

		add_action( 'heartbeat_tick', [ $this, 'heartbeat' ], 99 );
		add_action( 'heartbeat_nopriv_tick', [ $this, 'heartbeat' ], 99 );

	}

	/**
	 * If the cron job file is not installed also execute events during the heartbeat.
	 */
	public function heartbeat() {

		// If the cron file is installed and the queue is processing do not do heartbeat
		if ( is_event_queue_processing() && gh_cron_installed() ) {
			return;
		}

		// 10 second cap on heartbeat
		Limits::set_max_execution_time( 10 );

		do_action( self::WP_CRON_HOOK );
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

		// cron job already exists
		if ( wp_next_scheduled( self::WP_CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time(), apply_filters( 'groundhogg/event_queue/queue_interval', self::WP_CRON_INTERVAL ), self::WP_CRON_HOOK );
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

		// 5-minute window.
		$time = time() - ( MINUTE_IN_SECONDS * 5 );

		$wpdb->query( "
UPDATE {$events->get_table_name()} 
SET status = 'waiting', claim = '' 
WHERE status IN ( 'in_progress', 'waiting' ) AND claim != '' AND `time` < {$time}
ORDER BY ID" );

		$events->cache_set_last_changed();
	}

	/**
	 * Run any scheduled events.
	 *
	 * @return int
	 */
	public function run_queue() {

		// If for some reason the queue is not enabled
		if ( ! $this->is_enabled() ) {
			return 0;
		}

		Limits::start();

		Limits::raise_memory_limit();
		Limits::raise_time_limit( apply_filters( 'groundhogg/event_queue/max_time_limit', MINUTE_IN_SECONDS ) );

		$this->store = new Event_Store_V2();

		do_action( 'groundhogg/event_queue/before_process' );

		$this->process();

		$process_time = round( Limits::time_elapsed(), 2 );

		Limits::stop();

		$times_executed         = intval( get_option( 'gh_queue_times_executed', 0 ) );
		$average_execution_time = floatval( get_option( 'gh_average_execution_time', 0.0 ) );

		$average = $times_executed * $average_execution_time;
		$average += $process_time;
		$times_executed ++;
		$average_execution_time = $average / $times_executed;

		update_option( 'gh_queue_last_execution_time', $process_time );
		update_option( 'gh_queue_times_executed', $times_executed );
		update_option( 'gh_average_execution_time', $average_execution_time );

		return Limits::get_actions_processed();
	}

	/**
	 * Iterate through the list of events and process them via the EVENTS api
	 *
	 * @return void
	 */
	protected function process() {

		self::set_is_processing( true );

		while ( ! Limits::limits_exceeded() ){

			$events = $this->store->get_events( $this->max_events );

			if ( empty( $events ) ){
				break;
			}

			foreach ( $events as $event ){

				if ( Limits::limits_exceeded() ){
					break;
				}

				$this->set_current_event( $event );

				$contact = $event->get_contact();

				if ( ! is_a_contact( $contact ) ) {

					// Delete the event
//					$event->delete();

					continue;
				}

				$this->set_current_contact( $contact );

				// maybe switch the locale if multilingual?
				if ( $contact->get_locale() !== get_locale() ) {
					switch_to_locale( $contact->get_locale() );
				}

				$event->run();

				if ( $event->has_errors() ) {
					$this->add_error( $event->get_last_error() );
				}

				if ( is_locale_switched() ) {
					restore_current_locale();
				}

				Limits::processed_action();

			}

			$this->store->release_events();
		}

		/**
		 * When the queue is finished processing events, but before processed events are moved to the history table
		 */
		do_action( 'groundhogg/queue/processed_events' );

		// Move all processed events to the history table
		get_db( 'event_queue' )->move_events_to_history( [
			'status' => [
				Event::SKIPPED,
				Event::COMPLETE,
				Event::FAILED,
			]
		] );

		self::set_is_processing( false );
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
	protected function set_current_contact( $contact ) {
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
