<?php

namespace Groundhogg;

use Groundhogg\DB\Events;
use Groundhogg\Queue\Email_Notification;
use Groundhogg\queue\Test_Email;
use Groundhogg\Queue\Test_Event_Failure;
use Groundhogg\Queue\Test_Event_Success;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event
 *
 * This is an event from the event queue. it contains info about the step, broadcast, funnel, contact etc... that is necessary for processing the event.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Event extends Base_Object {

	/** @var string Event statuses */
	const COMPLETE = 'complete';
	const CANCELLED = 'cancelled';
	const SKIPPED = 'skipped';
	const WAITING = 'waiting';
	const EXECUTE = 'execute';
	const FAILED = 'failed';
	const IN_PROGRESS = 'in_progress';
	const PAUSED = 'paused';

	/**
	 * Supported Event Types
	 */
	const FUNNEL = 1;
	const BROADCAST = 2;
	const EMAIL_NOTIFICATION = 3;
	const TEST_EMAIL = 97;
	const TEST_SUCCESS = 98;
	const TEST_FAILURE = 99;

	/**
	 * @var Contact
	 */
	protected $contact;

	/**
	 * @var Step
	 */
	protected $step;

	/**
	 * @var Funnel
	 */
	protected $funnel;

	/**
	 * @var string
	 */
	protected $db_name = 'events';

	/**
	 * Event constructor.
	 *
	 * @param int    $identifier_or_args
	 * @param string $db    allow for the passing of the db name, this allows the reference of the event_queue table OR the regular events table.
	 * @param string $field the field to identify when querying the DB
	 */
	public function __construct( $identifier_or_args = 0, $db = 'events', $field = 'ID' ) {

		$this->db_name = $db;

		// Backwards compat for missing 'queued_id'
		if ( $field === 'queued_id' && is_int( $identifier_or_args ) && ! $this->get_db()->exists( [ 'queued_id' => $identifier_or_args ] ) ) {
			$field = 'ID';
		}

		parent::__construct( $identifier_or_args, $field );
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Events
	 */
	protected function get_db() {
		return get_db( $this->db_name );
	}

	public function is_complete() {
		return $this->get_status() === self::COMPLETE;
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'event';
	}

	/**
	 * Get the event ID
	 * May return the queued_id if present.
	 *
	 * @param bool $use_queued Use the queued_id for backwards compatibility
	 *
	 * @return int
	 */
	public function get_id( $use_queued = false ) {

		// Return the queued_id instead for backwards compatibility
		if ( $use_queued && $this->get_queued_id() > 0 ) {
			return $this->get_queued_id();
		}

		return absint( $this->ID );
	}

	/**
	 * @return int
	 */
	public function get_time() {
		return absint( $this->time );
	}

	/**
	 * @return int
	 */
	public function get_micro_time() {
		return $this->micro_time;
	}

	/**
	 * @return int
	 */
	public function get_time_scheduled() {
		return absint( $this->time_scheduled );
	}

	/**
	 * @return int
	 */
	public function get_event_type() {
		return absint( $this->event_type );
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return int
	 */
	public function get_priority() {
		return absint( $this->priority );
	}

	/**
	 * @return string
	 */
	public function get_claim() {
		return $this->claim;
	}

	/**
	 * @return int
	 */
	public function get_funnel_id() {
		return absint( $this->funnel_id );
	}

	/**
	 * returns the email id of the
	 *
	 * @return int
	 */
	public function get_email_id() {
		return absint( $this->email_id );

	}

	/**
	 * @return Funnel
	 */
	public function get_funnel() {
		return $this->funnel;
	}

	/**
	 * @return int
	 */
	public function get_contact_id() {
		return absint( $this->contact_id );
	}

	/**
	 * @return String
	 */
	public function get_failure_reason() {
		return $this->get_error_code() . ': ' . $this->get_error_message();
	}

	/**
	 * @return Contact
	 */
	public function get_contact() {
		return $this->contact;
	}

	/**
	 * @return int
	 */
	public function get_step_id() {
		return absint( $this->step_id );
	}

	/**
	 * @return int
	 */
	public function get_queued_id() {
		return absint( $this->queued_id );
	}

	/**
	 * @return Step|Email_Notification|Broadcast
	 */
	public function get_step() {
		return $this->step;
	}

	/**
	 * Get the email of an event.
	 *
	 * @return bool|Email
	 */
	public function get_email() {

		if ( $this->get_email_id() ) {
			return new Email( $this->get_email_id() );
		}

		switch ( $this->get_event_type() ) {
			case Event::FUNNEL;
				return new Email( absint( $this->get_step()->get_meta( 'email_id' ) ) );
			case Event::EMAIL_NOTIFICATION;
				return new Email( $this->get_step()->get_id() );
			case Event::BROADCAST;
				return new Email( $this->get_step()->get_object_id() );
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function get_error_code() {
		return $this->error_code;
	}

	/**
	 * @return string
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Callbacks the create the Event Process based on the event type
	 *
	 * @var array
	 */
	protected static array $step_setup_callbacks = [];

	public static function register_step_setup_callback( int $type, callable $callback ) {
		self::$step_setup_callbacks[ $type ] = $callback;
	}

	/**
	 * Initially registers step callbacks
	 *
	 * @return void
	 */
	public static function maybe_register_step_setup_callbacks() {

		if ( ! empty( self::$step_setup_callbacks ) ) {
			return;
		}

		self::register_step_setup_callback( self::FUNNEL, function ( Event $event ) {
			return new Step( $event->get_step_id() );
		} );

		self::register_step_setup_callback( self::BROADCAST, function ( Event $event ) {
			return new Broadcast( $event->get_step_id() );
		} );

		self::register_step_setup_callback( self::EMAIL_NOTIFICATION, function ( Event $event ) {
			return new Email_Notification( $event->get_email_id() );
		} );

		self::register_step_setup_callback( self::TEST_SUCCESS, function ( Event $event ) {
			return new Test_Event_Success();
		} );

		self::register_step_setup_callback( self::TEST_FAILURE, function ( Event $event ) {
			return new Test_Event_Failure();
		} );

		self::register_step_setup_callback( self::TEST_EMAIL, function ( Event $event ) {
			return new Test_Email( $event->get_email_id() );
		} );

		do_action( 'groundhogg/event/maybe_register_step_callbacks' );
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {

		$this->args = maybe_unserialize( $this->args );
		$this->contact = new Contact( $this->get_contact_id() );

		self::maybe_register_step_setup_callbacks();

		$callback = get_array_var( self::$step_setup_callbacks, $this->get_event_type(), function ( Event $event ) {
			$class = apply_filters( 'groundhogg/event/post_setup/step_class', false, $this );

			if ( class_exists( $class ) ) {
				return new $class( $this->get_step_id() );
			}

			return null;
		} );

		$this->step = call_user_func( $callback, $this );

		do_action( 'groundhogg/event/post_setup', $this );
	}

	/**
	 * Sanitize columns
	 *
	 * @param $data
	 *
	 * @return array
	 */
	protected function sanitize_columns( $data = [] ) {

		if ( isset_not_empty( $data, 'args' ) ) {
			$data['args'] = maybe_serialize( $data['args'] );
		}

		return parent::sanitize_columns( $data );
	}

	/**
	 * Return whether the event is a funnel (automated) event.
	 *
	 * @since 1.2
	 * @return bool
	 */
	public function is_funnel_event() {
		return $this->get_event_type() === self::FUNNEL;
	}

	/**
	 * Return whether the event is a broadcast event
	 *
	 * @return bool
	 */
	public function is_broadcast_event() {
		return $this->get_event_type() === self::BROADCAST;
	}

	/**
	 * @return string
	 */
	public function get_step_title() {
		if ( $this->get_step() ) {
			return $this->get_step()->get_step_title(); //todo
		}

		return __( 'Unknown', 'groundhogg' );
	}

	/**
	 * @return string
	 */
	public function get_funnel_title() {
		if ( $this->get_step() ) {
			return $this->get_step()->get_funnel_title();
		}

		return __( 'Unknown', 'groundhogg' );
	}

	/**
	 * Run the event
	 *
	 * Wrapper function for the step call in WPGH_Step
	 */
	public function run() {

		// If the claim has not been set by the queue we should quit now
		if ( ! $this->get_claim() ) {
			return new WP_Error( 'invalid_claim', 'This event has not be claimed' );
		}

		if ( ! $this->is_waiting() ) {
			return new WP_Error( 'not_waiting', 'This event\'s status is not waiting' );
		}

		do_action( 'groundhogg/event/run/before', $this );

		$this->in_progress();

		// No step or not contact?
		if ( ! $this->get_step() || ! $this->get_contact() || ! $this->get_contact()->exists() ) {

			$error = new \WP_Error( 'missing', 'Could not locate contact or step record.' );

			$this->add_error( $error );

			$this->fail();

			return apply_filters( 'groundhogg/event/run/failed_result', $error, $this );
		}

		try {
			$result = $this->get_step()->run( $this->get_contact(), $this );
		} catch ( \Exception $e ) {
			$result = new WP_Error( 'exception', $e->getMessage() );
		}

		// Hard fail when WP Error
		if ( is_wp_error( $result ) ) {
			/* handle event failure */
			$this->add_error( $result );

			$this->fail();

			return apply_filters( 'groundhogg/event/run/failed_result', $result, $this );
		}

		// Falsy value from the run() method
		if ( $result === false ) {

			// Update the error code details with the reason this event was skipped
			$this->skip( [
				'error_code'    => 'soft_fail',
				'error_message' => 'Falsy value returned from Step::run() method'
			] );

			/**
			 * We have decided that a "Soft Fail" (Falsy value from the run() method) will allow funnel events to proceed to the next step
			 * instead of stopping the funnel.
			 */
			$result = apply_filters( 'groundhogg/event/run/skipped_result', false, $this );
		}

		$this->complete();

		if ( method_exists( $this->get_step(), 'run_after' ) ) {
			call_user_func( [ $this->get_step(), 'run_after' ], $this->get_contact(), $this );
		}

		do_action( 'groundhogg/event/run/after', $this );

		return $result;
	}

	/**
	 * Due to the nature of WP and cron, let's DOUBLE-check that at the time of running this event has not been run by another instance of the queue.
	 *
	 * @return bool whether the event has run or not
	 */
	public function has_run() {
		return $this->get_status() !== self::WAITING;
	}

	/**
	 * Return whether this event is in the appropriate time range to be executed
	 *
	 * @return bool
	 */
	public function is_time() {
		return $this->get_time() <= time();
	}

	/**
	 * Is the current status 'waiting'
	 *
	 * @return bool;
	 */
	public function is_waiting() {
		return $this->get_status() === self::WAITING;
	}

	/**
	 * Reset the status to waiting so that it may be re-enqueud
	 */
	public function queue() {
		do_action( 'groundhogg/event/queued', $this );

		return $this->update( [
			'status' => self::WAITING
		] );
	}

	/**
	 * Mark the event as cancelled
	 */
	public function cancel() {
		do_action( 'groundhogg/event/cancelled', $this );

		return $this->update( [
			'status' => self::CANCELLED,
			'time'   => time(),
		] );

	}

	/**
	 * @return bool
	 */
	public function fail() {
		$args = [
			'status' => self::FAILED
		];

		/**
		 * Report a failure reason for better debugging.
		 *
		 * @since 1.2
		 */
		if ( $this->has_errors() ) {
			$args['error_code']    = $this->get_last_error()->get_error_code();
			$args['error_message'] = $this->get_last_error()->get_error_message();
		}

		$updated = $this->update( $args );

		do_action( 'groundhogg/event/failed', $this, $this->get_last_error() );

		return $updated;
	}

	/**
	 * Mark the event as skipped
	 */
	public function skip( $args = [] ) {
		do_action( 'groundhogg/event/skipped', $this );

		return $this->update( wp_parse_args( $args, [
			'status' => self::SKIPPED,
			'time'   => time(),
		] ) );
	}

	/**
	 * Mark the event as skipped
	 */
	public function in_progress() {
		do_action( 'groundhogg/event/in_progress', $this );

		return $this->update( [
			'status' => self::IN_PROGRESS
		] );
	}

	/**
	 * Mark the event as paused
	 */
	public function pause() {
		do_action( 'groundhogg/event/pause', $this );

		return $this->update( [
			'status' => self::PAUSED
		] );
	}

	/**
	 * Get an arg from the args array
	 *
	 * @param string $arg
	 * @param        $default
	 *
	 * @return bool|mixed
	 */
	public function get_arg( string $arg, $default = false ) {
		return get_array_var( $this->args, $arg, $default );
	}

	/**
	 * Add new args to the event
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function set_args( array $args ) {
		$args = is_array( $this->args ) ? array_merge( $this->args, $args ) : $args;

		return $this->update( [
			'args' => $args
		] );
	}

	/**
	 * Mark the event as complete
	 */
	public function complete() {

		if ( $this->is_complete() ) {
			return true;
		}

		do_action( 'groundhogg/event/complete', $this );

		return $this->update( [
			'status'        => self::COMPLETE,
			'time'          => time(),
			'micro_time'    => micro_seconds(),
			'error_code'    => '',
			'error_message' => '',
		] );
	}

	public function get_as_array() {
		$array = parent::get_as_array();

		$date = new DateTimeHelper( $this->get_time() );

		if ( $this->is_waiting() && $this->get_time() <= time() ) {
			$diff_time = __( 'Running now...', 'groundhogg' );
		} else {
			$diff_time = sprintf( $this->is_waiting() ? __( 'Runs %s', 'groundhogg' ) : __( 'Ran %s', 'groundhogg' ), $date->i18n() );
		}

		$array['i18n'] = [
			'diff_time' => $diff_time
		];

		switch ( $this->get_event_type() ) {
			case Event::FUNNEL:
				return array_merge( $array, [
					'step' => $this->get_step()
				] );
			case Event::BROADCAST:
				return array_merge( $array, [
					'broadcast' => $this->get_step()
				] );
			case Event::EMAIL_NOTIFICATION:
				return array_merge( $array, [
					'email' => $this->get_step()
				] );
			default:
				return $array;
		}
	}
}
