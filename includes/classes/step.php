<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Event_Queue;
use Groundhogg\DB\Events;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Step_Meta;
use Groundhogg\DB\Steps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Step
 *
 * Step is used to provide information about any kind of funnel step, benchmark, or action.
 *
 * @since       File available since Release 0.9
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Step extends Base_Object_With_Meta implements Event_Process {
	const BENCHMARK = 'benchmark';
	const ACTION = 'action';

	/**
	 * This is only used when the step is enqueuing itself...
	 *
	 * @since 1.0.16
	 *
	 * @var Contact
	 */
	public $enqueued_contact;

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Steps
	 */
	protected function get_db() {
		return get_db( 'steps' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Step_Meta
	 */
	protected function get_meta_db() {
		return get_db( 'stepmeta' );
	}

	/**
	 * Get the events DB
	 *
	 * @return Events
	 */
	protected function get_events_db() {
		return get_db( 'events' );
	}

	/**
	 * @return Event_Queue
	 */
	protected function get_event_queue_db() {
		return get_db( 'event_queue' );
	}

	/**
	 * Do any post setup actions.
	 *
	 * Nothing to do here...
	 *
	 * @return void
	 */
	protected function post_setup() {
		$this->step_order = absint( $this->step_order );
		$this->funnel_id  = absint( $this->funnel_id );
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'step';
	}

	public function get_id() {
		return absint( $this->ID );
	}

	public function get_title() {
		return $this->step_title;
	}

	public function get_order() {
		return absint( $this->step_order );
	}

	public function get_type() {
		return $this->step_type;
	}

	public function get_group() {
		return $this->step_group;
	}

	public function get_funnel_id() {
		return absint( $this->funnel_id );
	}

	/**
	 * @return Funnel
	 */
	public function get_funnel() {
		return new Funnel( $this->get_funnel_id() );
	}

	/**
	 * Get an array of contacts which are "waiting'
	 *
	 * @return Contact[] | false
	 */
	public function get_waiting_contacts() {
		$contacts = [];
		$events   = $this->get_waiting_events();

		if ( ! $events ) {
			return [];
		}

		foreach ( $events as $event ) {
			$contacts[] = $event->get_contact();
		}

		return $contacts;
	}


	/**
	 * Get an array of waiting events
	 *
	 * @return Event[]|false
	 */
	public function get_waiting_events() {

		$events = $this->get_event_queue_db()->query( [
			'status'    => Event::WAITING,
			'step_id'   => $this->get_id(),
			'funnel_id' => $this->get_funnel_id(),
		] );

		$prepped = [];

		if ( ! $events ) {
			return false;
		}

		foreach ( $events as $event ) {
			$prepped[] = get_queued_event_by_id( $event->ID );
		}

		return $prepped;
	}

	/**
	 * @return bool whether the step is a benchmark
	 */
	public function is_benchmark() {
		return $this->get_group() === self::BENCHMARK;
	}

	/**
	 * @return bool whether the step is an action
	 */
	public function is_action() {
		return $this->get_group() === self::ACTION;
	}

	/**
	 * Get the next step in the order
	 *
	 * @return Step|false
	 */
	public function get_next_action() {

		/* this will give an array of objects ordered by appearance in the funnel builder */
		$items = $this->get_funnel()->get_steps();

		if ( empty( $items ) ) {
			/* something went wrong or there are no more steps*/
			return false;
		}

		$i = $this->get_order();

		if ( $i >= count( $items ) ) {

			/* This is the last step. */
			return false;
		}

		if ( $items[ $i ]->get_group() === self::ACTION ) {

			/* regardless of whether the current step is an action
			or a benchmark we can run the next step if it's an action */
			return $items[ $i ];

		}

		if ( $this->is_benchmark() ) {

			while ( $i < count( $items ) ) {

				if ( $items[ $i ]->get_group() === self::ACTION ) {

					return $items[ $i ];

				}

				$i ++;

			}

		}

		return false;

	}

	/**
	 * Get the delay time for enqueueing the next action
	 *
	 * @return int
	 */
	public function get_delay_time() {
		$time = apply_filters( "groundhogg/steps/{$this->get_type()}/enqueue", $this );

		if ( ! is_numeric( $time ) ) {
			$time = time();
		}

		return $time;
	}

	/**
	 * Create an event and add it to the queue
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function enqueue( $contact ) {

		$this->enqueued_contact = $contact;

		/**
		 * @param bool $enqueue whether the step can be enqueued or not...
		 * @param Contact Contact
		 * @param Step Step the step being enqueued
		 *
		 * @return bool whether the step can be enqueued or not...
		 */
		$can_enqueue = apply_filters( 'groundhogg/steps/enqueue', true, $contact, $this );

		if ( ! $can_enqueue ) {
			return false;
		}

		// Update any events to skipped...
		$this->get_event_queue_db()->mass_update(
			[
				'status' => Event::SKIPPED
			],
			[
				'funnel_id'  => $this->get_funnel_id(),
				'contact_id' => $contact->get_id(),
				'event_type' => Event::FUNNEL,
				'status'     => Event::WAITING
			]
		);

		// Setup the new event args
		$event = [
			'time'       => $this->get_delay_time(),
			'funnel_id'  => $this->get_funnel_id(),
			'step_id'    => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'contact_id' => $contact->get_id(),
			'priority'   => 10,
		];

		return (bool) $this->get_event_queue_db()->add( $event );
	}

	/**
	 * Whether this step can actually be completed
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function can_complete( $contact = null ) {
		// Actions cannot be completed.
		if ( $this->is_action() || ! $this->is_active() ) {
			return false;
		}

		// Check if starting
		if ( $this->is_starting() ) {
			return true;
		} // If inner step, check if contact is at a step before this one.
		else if ( $this->is_inner() ) {

			// get the current funnel step
			$current_order = $this->get_current_funnel_step_order( $contact );

			// If the step order is < than this one, return true.
			if ( $current_order && $current_order < $this->get_order() ) {
				return true;
			}
		}

		return apply_filters( 'groundhogg/step/can_complete', false, $this, $contact );
	}

	/**
	 * Get the current step order of the contact in the same funnel
	 * as this step.
	 *
	 * @param $contact Contact
	 *
	 * @return bool|int
	 */
	public function get_current_funnel_step_order( $contact ) {

		// Search waiting events, automatically the current event.
		$events = $this->get_event_queue_db()->query( [
			'funnel_id'  => $this->get_funnel_id(),
			'contact_id' => $contact->get_id(),
			'status'     => Event::WAITING
		], null, false );

		if ( ! empty( $events ) ) {
			$event = array_shift( $events );
			$event = new Event( absint( $event->ID ), 'event_queue' );
			// Double check step exists...
			if ( $event->exists() && $event->get_step() && $event->get_step()->exists() ) {
				return $event->get_step()->get_order();
			}
		}

		// The most recent completed event for this funnel and contact.
		$events = $this->get_events_db()->query( [
			'funnel_id'  => $this->get_funnel_id(),
			'contact_id' => $contact->get_id(),
			'status'     => Event::COMPLETE,
			'order'      => 'DESC',
			'orderby'    => 'time',
			'limit'      => 1,
		], null, false );

		if ( ! empty( $events ) ) {
			// get top element.
			$event = array_shift( $events );
			$event = new Event( absint( $event->ID ) );

			// Double check step exists...
			if ( $event->exists() && $event->get_step() && $event->get_step()->exists() ) {
				return $event->get_step()->get_order();
			}
		}

		return false;
	}

	/**
	 * Returns whether the contact is currently in the funnel
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function contact_in_funnel( $contact ) {
		return $this->get_events_db()->count( [
				'funnel_id'  => $this->get_funnel_id(),
				'contact_id' => $contact->get_id()
			] ) > 0 || $this->get_event_queue_db()->count( [
				'funnel_id'  => $this->get_funnel_id(),
				'contact_id' => $contact->get_id()
			] );
	}

	/**
	 * Return whether the step/funnel is active?
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->get_funnel() && $this->get_funnel()->is_active();
	}

	/**
	 * Whether the step is an inner step. Works for both benchmarks and actions.
	 *
	 * @return bool
	 */
	public function is_inner() {
		return ! $this->is_starting();
	}

	/**
	 * Whether this step is an entry point in the funnel
	 *
	 * @return bool
	 */
	public function is_entry(){
		return (bool) $this->is_entry;
	}

	/**
	 * Whether the step starts a funnel
	 *
	 * @return bool
	 */
	public function is_starting() {
		if ( $this->is_action() ) {
			return false;
		}

		if ( $this->get_order() === 1 || $this->is_entry() ) {
			return true;
		}

		$step_order = $this->get_order() - 1;
		$steps      = $this->get_funnel()->get_steps();

		while ( $step_order > 0 ) {

			$step = $steps[ $step_order ];

			if ( $step->is_action() ) {
				return false;
			}

			$step_order -= 1;
		}

		return true;
	}

	/**
	 * Return the name given with the ID prefixed for easy access in the $_POST variable
	 *
	 * @param $name
	 *
	 * @return string
	 * @deprecated since 2.0
	 *
	 */
	public function prefix( $name ) {
		return $this->get_id() . '_' . esc_attr( $name );
	}

	/**
	 * Do the event when being processed from the event queue...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error whether it was successful or not
	 */
	public function run( $contact, $event = null ) {
		if ( ! $this->is_active() ) {
			return false;
		}

		$result = false;

		// Do the step?
		$do_step = apply_filters( 'groundhogg/steps/run/do_step', true, $this, $contact, $event );

		if ( $do_step ) {
			do_action( "groundhogg/steps/{$this->get_type()}/run/before", $this );

			if ( has_filter( "groundhogg/steps/{$this->get_type()}/run" ) ) {
				$result = apply_filters( "groundhogg/steps/{$this->get_type()}/run", $contact, $event, $this );
			} else {
				$result = apply_filters( "groundhogg/steps/error/run", $contact, $event, $this );
			}

			do_action( "groundhogg/steps/{$this->get_type()}/run/after", $this );
		}

		// Modify the result
		$result = apply_filters( 'groundhogg/steps/run/result', $result, $this, $contact, $event );

		return $result;
	}

	/**
	 * Output icon html
	 */
	public function icon() {

		$icon = false;

		if ( has_filter( "groundhogg/steps/{$this->get_type()}/icon" ) ) {
			$icon = apply_filters( "groundhogg/steps/{$this->get_type()}/icon", $this );
		}

		return $icon ?: GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
	}

	/**
	 * Output the HTML of a step.
	 */
	public function html_v2() {
		if ( has_action( "groundhogg/steps/{$this->get_type()}/html_v2" ) ) {
			do_action( "groundhogg/steps/{$this->get_type()}/html_v2", $this );
		} else {
			_e( 'This step has no settings.', 'groundhogg' );
		}
	}

	/**
	 * Save the step
	 */
	public function save() {
		if ( has_action( "groundhogg/steps/{$this->get_type()}/save" ) ) {
			do_action( "groundhogg/steps/{$this->get_type()}/save", $this );
		} else {
			do_action( "groundhogg/steps/error/save", $this );
		}
	}

	/**
	 * Oh Boy....
	 *
	 * @return array
	 */
	public function export() {
		return apply_filters( "groundhogg/steps/{$this->get_type()}/export", [], $this );
	}

	public function get_as_array() {
		return apply_filters( "groundhogg/{$this->get_object_type()}/get_as_array", [
			'ID'     => $this->get_id(),
			'data'   => $this->data,
			'meta'   => $this->meta,
			'export' => $this->export(),
		] );
	}

	/**
	 * Import any contextual args from the given template
	 *
	 * @param array $import_args
	 */
	public function import( $import_args = [] ) {
		do_action( "groundhogg/steps/{$this->get_type()}/import", $import_args, $this );
	}

	/**
	 * @return string
	 */
	public function get_step_title() {
		return $this->get_title();
	}

	public function get_step_notes() {
		return $this->get_meta( 'step_notes' );
	}

	/**
	 * @return string
	 */
	public function get_funnel_title() {
		if ( $this->get_funnel() ) {
			return $this->get_funnel()->get_title();
		}

		return false;
	}

	public function can_run() {
		return true;
	}
}