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
	const CONDITION = 'condition';
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
		return Plugin::$instance->dbs->get_db( 'steps' );
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
		$this->parent_steps = $this->parent_steps ? wp_parse_id_list( maybe_unserialize( $this->parent_steps ) ) : [];
		$this->child_steps  = $this->child_steps ? wp_parse_id_list( maybe_unserialize( $this->child_steps ) ) : [];
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

	/**
	 * @return Step[]
	 */
	public function get_child_steps() {
		return $this->child_steps ? id_list_to_class( $this->child_steps, Step::class ) : [];
	}

	/**
	 * @return Step[]
	 */
	public function get_parent_steps() {
		return $this->parent_steps ? id_list_to_class( $this->parent_steps, Step::class ) : [];
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
		return Plugin::$instance->utils->get_funnel( $this->get_funnel_id() );
	}

	/**
	 * Id of the step
	 *
	 * @param $step Step
	 */
	public function add_parent_step( $step ) {
		if ( ! $step || ! $step->exists() || in_array( $step->get_id(), $this->parent_steps ) ) {
			return;
		}

		$this->parent_steps[] = $step->get_id();

		$this->update( [
			'parent_steps' => $this->parent_steps
		] );
	}

	/**
	 * @param $step Step
	 */
	public function add_child_step( $step ) {
		if ( ! $step || ! $step->exists() || in_array( $step->get_id(), $this->child_steps ) ) {
			return;
		}

		$this->child_steps[] = $step->get_id();

		$this->update( [
			'child_steps' => $this->child_steps
		] );
	}

	/**
	 * @param $step Step
	 */
	public function remove_parent_step( $step ) {
		if ( ! $step || ! $step->exists() || ! in_array( $step->get_id(), $this->parent_steps ) ) {
			return;
		}

		$this->update( [
			'parent_steps' => array_diff( $this->parent_steps, [ $step->get_id() ] )
		] );
	}

	/**
	 * @param $step Step
	 */
	public function remove_child_step( $step ) {
		if ( ! $step || ! $step->exists() || ! in_array( $step->get_id(), $this->child_steps ) ) {
			return;
		}

		$this->update( [
			'child_steps' => array_diff( $this->child_steps, [ $step->get_id() ] )
		] );
	}

	protected function sanitize_columns( $data = [] ) {

		map_func_to_attr( $data, 'parent_steps', 'wp_parse_id_list' );
		map_func_to_attr( $data, 'child_steps', 'wp_parse_id_list' );

		return $data;
	}

	/**
	 * Get an array of contacts which are "waiting'
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
	 * Whether the current step is a condition.
	 *
	 * @return bool
	 */
	public function is_condition() {
		return $this->get_group() === self::CONDITION;
	}

	/**
	 * Get the next step in the funnel.
	 *
	 * @return bool|Step
	 */
	public function get_next_step() {

		if ( $this->is_action() || $this->is_benchmark() ) {

			foreach ( $this->get_child_steps() as $child ) {
				if ( $child->is_action() ) {
					return $child;
				}
			}

		} else if ( $this->is_condition() ) {

			// Todo process the condition.

			$passed = 1 || 2;
			$index  = $passed ? 0 : 1;

			return get_array_var( $this->get_child_steps(), $index );

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
		if ( $this->is_action() || $this->is_condition() || ! $this->is_active() ) {
			$can_complete = false;
		}
		// Check if starting
		else if ( $this->is_starting() ) {
			$can_complete = true;
		// check the path to see if this benchmark is in the same path as the contact.
		} else {
			// The step where the contact currently is in the funnel
			$contact_step = $this->get_current_funnel_step( $contact );

			// traverse the tree upwards to see if the current step is in the same path as this one.
			$queue = [ $this ];
			$can_complete = false;

			// BFS to find if the path is correct.
			while ( ! empty( $queue ) && $can_complete === false ){

				$current = array_shift( $queue );

				// If the contact's current step was found among the parents/grandparents
				// of the $this step.
				if ( $current->get_id() === $contact_step->get_id() ){
					$can_complete = true;
					break;
				}

				// Add the $current steps parents to the queue.
				foreach ( $current->get_parents() as $parent ){
					array_push( $queue, $parent );
				}

			}
		}

		return apply_filters( 'groundhogg/step/can_complete', $can_complete, $this, $contact );
	}

	/**
	 * Get the current step order of the contact in the same funnel
	 * as this step.
	 *
	 * @param $contact Contact
	 *
	 * @return bool|Step
	 */
	public function get_current_funnel_step( $contact ) {

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
				return $event->get_step();
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
				return $event->get_step();
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
	 * Whether the step starts a funnel
	 * Will be starting if there are no parent steps and the step is in fact a benchmark.
	 *
	 * @return bool
	 */
	public function is_starting() {
		return $this->is_benchmark() && empty( $this->parent_steps );
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

	public function validate() {
		if ( has_action( "groundhogg/steps/{$this->get_type()}/validate" ) ) {
			do_action( "groundhogg/steps/{$this->get_type()}/validate", $this );
		}
	}

	public function update( $data = [] ) {

		if ( ! empty( $data ) ) {
			$data['last_edited']    = current_time( 'mysql' );
			$data['last_edited_by'] = is_user_logged_in() ? get_current_user_id() : 'system';
		}

		return parent::update( $data ); // TODO: Change the autogenerated stub
	}

	/**
	 * Oh Boy....
	 *
	 * @return array
	 */
	public function export() {
		return apply_filters( "groundhogg/steps/{$this->get_type()}/export", [], $this );
	}

	public function import( $import_args = [] ) {
		do_action( "groundhogg/steps/{$this->get_type()}/import", $import_args, $this );
	}

	/**
	 * Get the step title...
	 *
	 * @return string
	 */
	public function get_step_title() {
		return $this->get_title();
	}

	/**
	 * @return mixed
	 */
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

	/**
	 * Ensure the step can run.
	 */
	public function can_run() {
		return ( $this->is_action() || $this->is_condition() ) && $this->is_active();
	}

	/**
	 * Needs to handle the moving of contacts to another step...
	 *
	 * @return bool
	 */
	public function delete() {

		// Maybe Move contacts forward...
		$next_step = $this->get_next_step();

		if ( $next_step && $next_step->can_run() ) {
			$contacts = $this->get_waiting_contacts();

			if ( ! empty( $contacts ) ) {
				foreach ( $contacts as $contact ) {
					$next_step->enqueue( $contact );
				}
			}

		}

		return parent::delete();
	}
}