<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Events;
use Groundhogg\Queue\Email_Notification;

use Groundhogg\Queue\Test_Event_Failure;
use Groundhogg\Queue\Test_Event_Success;
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
	const FAILED = 'failed';
	const IN_PROGRESS = 'in_progress';
	const PAUSED = 'paused';

	/**
	 * Supported Event Types
	 */
	const FUNNEL = 1;
	const BROADCAST = 2;
	const EMAIL_NOTIFICATION = 3;
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
	 * @param int $identifier_or_args
	 * @param string $db allow for the passing of the db name, this allows the reference of the event_queue table OR the regular events table.
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
		switch ( $this->get_event_type() ) {
			case Event::FUNNEL;
				return new Email( absint( $this->get_step()->get_meta( 'email_id' ) ) );
				break;
			case Event::EMAIL_NOTIFICATION;
				return new Email( $this->get_step()->get_id() );
				break;
			case Event::BROADCAST;
				return new Email( $this->get_step()->get_object_id() );
				break;
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
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {

		$this->contact = get_contactdata( $this->get_contact_id() );

		switch ( $this->get_event_type() ) {
			case self::FUNNEL:
				$this->step = new Step( $this->get_step_id() );
				break;
			case self::EMAIL_NOTIFICATION:
				$this->step = new Email_Notification( $this->get_step_id() );
				break;
			case self::BROADCAST:
				$this->step = new Broadcast( $this->get_step_id() );
				break;
			case self::TEST_SUCCESS:
				$this->step = new Test_Event_Success();
				break;
			case self::TEST_FAILURE:
				$this->step = new Test_Event_Failure();
				break;
			default:
				$class = apply_filters( 'groundhogg/event/post_setup/step_class', false, $this );

				if ( class_exists( $class ) ) {
					$this->step = new $class( $this->get_step_id() );
				}

				break;
		}

		do_action( 'groundhogg/event/post_setup', $this );
	}

	/**
	 * Return whether the event is a funnel (automated) event.
	 *
	 * @return bool
	 * @since 1.2
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
		if ( ! $this->get_claim() || ! $this->is_waiting() ) {
			return false;
		}

		do_action( 'groundhogg/event/run/before', $this );

		$this->in_progress();

		// No step or not contact?
		if ( ! $this->get_step() || ! $this->get_contact() || ! $this->get_contact()->exists() ) {

			$this->add_error( new \WP_Error( 'missing', 'Could not locate contact or step record.' ) );

			$this->fail();

			return apply_filters( 'groundhogg/event/run/failed_result', false, $this );
		}

		$result = $this->get_step()->run( $this->get_contact(), $this );

		// Soft fail when return false
		if ( ! $result ) {

			$this->skip();

			return apply_filters( 'groundhogg/event/run/skipped_result', false, $this );
		}

		// Hard fail when WP Error
		if ( is_wp_error( $result ) ) {
			/* handle event failure */
			$this->add_error( $result );

			$this->fail();

			return apply_filters( 'groundhogg/event/run/failed_result', $result, $this );
		}

		$this->complete();

		do_action( 'groundhogg/event/run/after', $this );

		return true;
	}

	/**
	 * Due to the nature of WP and cron, let's DOUBLE check that at the time of running this event has not been run by another instance of the queue.
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

		$cancel = $this->update( [
			'status' => self::CANCELLED,
			'time'   => time(),
		] );

		return $cancel;

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

		do_action( 'groundhogg/event/failed', $this );

		return $updated;
	}

	/**
	 * Mark the event as skipped
	 */
	public function skip() {
		do_action( 'groundhogg/event/skipped', $this );

		$skip = $this->update( [
			'status' => self::SKIPPED,
			'time'   => time(),
		] );

		return $skip;
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
	 * Mark the event as complete
	 */
	public function complete() {

		do_action( 'groundhogg/event/complete', $this );

		$update = $this->update( [
			'status'        => self::COMPLETE,
			'time'          => time(),
			'micro_time'    => micro_seconds(),
			'error_code'    => '',
			'error_message' => '',
		] );

		return $update;
	}

	public function get_as_array() {
		$array = parent::get_as_array();

		$array['locale'] = [
			'diff_time' => human_time_diff( $this->get_time(), time() )
		];

		switch ( $this->get_event_type() ) {
			case Event::FUNNEL:
				return array_merge( $array, [
					'step' => $this->get_step()
				] );
			case Event::BROADCAST:
			case Event::EMAIL_NOTIFICATION:
				return array_merge( $array, [
					'email' => $this->get_step()
				] );
			default:
				return $array;
		}
	}


}