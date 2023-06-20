<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Event_Queue;
use Groundhogg\DB\Events;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Step_Meta;
use Groundhogg\DB\Steps;
use Groundhogg\Steps\Actions\Send_Email;

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

	public function __construct( $identifier_or_args = 0, $field = null ) {

		if ( is_string( $identifier_or_args ) && ! is_numeric( $identifier_or_args ) ) {

			$slug_or_id = maybe_url_decrypt_id( $identifier_or_args );

			// We got an ID
			if ( is_numeric( $slug_or_id ) ) {
				return parent::__construct( $slug_or_id, 'ID' );
			}

			// We got a slug
			$parts = explode( '-', $slug_or_id );
			$ID    = absint( $parts[0] );

			return parent::__construct( $ID, 'ID' );
		}

		parent::__construct( $identifier_or_args, $field );
	}

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
	 * @param string $type
	 *
	 * @return bool
	 */
	public function type_is( string $type ) {
		return $this->get_type() === $type;
	}

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

		do_action( 'groundhogg/step/post_setup', $this );
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
		return sanitize_text_field( $this->step_title );
	}

	public function get_title_formatted() {
		return $this->step_title;
	}

	public function get_order() {
		return absint( $this->step_order );
	}

	public function get_type() {
		return $this->step_type;
	}

	public function get_type_name() {
		return Plugin::instance()->step_manager->get_element( $this->get_type() )->get_name();
	}

	public function get_group() {
		return $this->step_group;
	}

	public function get_funnel_id() {
		return absint( $this->funnel_id );
	}

	public function get_slug() {

		if ( ! $this->step_slug ) {
			$this->set_slug();
		}

		return $this->step_slug;
	}

	public function is_conversion() {
		return (bool) $this->is_conversion;
	}

	public function is_entry() {
		return (bool) $this->is_entry;
	}

	/**
	 * @var Funnel
	 */
	protected $funnel;

	/**
	 * @return Funnel
	 */
	public function get_funnel() {

		if ( $this->funnel ) {
			return $this->funnel;
		}

		$this->funnel = new Funnel( $this->get_funnel_id() );

		return $this->funnel;
	}

	public function set_slug() {
		$this->update( [
			'step_slug' => $this->get_id() . '-' . sanitize_title( $this->get_step_title() )
		] );
	}

	public function create( $data = [] ) {
		$step = parent::create( $data );

		// to set the slug
		$this->set_slug();

		return $step;
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
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
			'step_id'    => $this->get_id(),
			'funnel_id'  => $this->get_funnel_id(),
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
	 * Get the next step in the funnel of a specific type
	 *
	 * @param $type
	 *
	 * @return Step
	 */
	public function get_next_of_type( $type = '' ) {

		$items = $this->get_funnel()->get_steps();

		// Order = index + 1, so accessing array at order is actually getting the next step
		for ( $i = $this->get_order(); $i < count( $items ); $i ++ ) {
			$next = $items[ $i ];

			if ( $next->get_type() === $type ) {
				return $next;
			}
		}

		return $this->get_next_action();
	}

	/**
	 * Returns all the actions that come before this one
	 *
	 * @return Step[]
	 */
	public function get_preceding_actions() {
		$steps = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_group', '=', self::ACTION ],
				[ 'step_order', '<', $this->get_order() ],
				[ 'funnel_id', '=', $this->get_funnel_id() ]
			],
			'orderby' => 'step_order',
			'order'   => 'asc',
		] );

		return array_map_to_step( $steps );
	}

	/**
	 * Returns all the actions that come after this one
	 *
	 * @return Step[]
	 */
	public function get_proceeding_actions() {

		$steps = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_group', '=', self::ACTION ],
				[ 'step_order', '>', $this->get_order() ],
				[ 'funnel_id', '=', $this->get_funnel_id() ]
			],
			'orderby' => 'step_order',
			'order'   => 'asc',
		] );

		return array_map_to_step( $steps );
	}

	/**
	 * Get the next step in the order
	 *
	 * @return Step|false
	 */
	public function get_next_action() {

		$next = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_group', '=', self::ACTION ],
				[ 'step_order', $this->is_action() ? '=' : '>=', $this->get_order() + 1 ],
				[ 'funnel_id', '=', $this->get_funnel_id() ]
			],
			'orderby' => 'step_order',
			'order'   => 'asc',
			'limit'   => 1,
		] );

		if ( is_array( $next ) ) {

			$next = array_shift( $next );

			if ( ! empty( $next ) ) {
				$next = new Step( $next );
			}

		}

		/**
		 * Filters the next action
		 *
		 * @param $next    Step|null
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/next_action', $next, $this );
	}

	/**
	 * Get the next step in the order
	 *
	 * @return Step|false
	 */
	public function get_prev_action() {

		$next = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_group', '=', self::ACTION ],
				[ 'step_order', $this->is_action() ? '=' : '<=', $this->get_order() - 1 ],
				[ 'funnel_id', '=', $this->get_funnel_id() ]
			],
			'orderby' => 'step_order',
			'order'   => 'desc',
			'limit'   => 1,
		] );

		if ( is_array( $next ) ) {

			$next = array_shift( $next );

			if ( ! empty( $next ) ) {
				$next = new Step( $next );
			}

		}

		/**
		 * Filters the next action
		 *
		 * @param $next    Step|null
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/prev_action', $next, $this );
	}

	/**
	 * Check if the funnel of this step is the same as the given one
	 *
	 * @param Step $step
	 *
	 * @return bool
	 */
	public function is_same_funnel( Step $step ) {
		return $step->get_funnel_id() === $this->get_funnel_id();
	}

	/**
	 * Check to see if this step is before the given one
	 *
	 * @param Step $step
	 *
	 * @return bool
	 */
	public function is_before( Step $step ) {
		return $this->is_same_funnel( $step ) && $this->get_order() < $step->get_order();
	}

	/**
	 * Check to see if this step is after the given one
	 *
	 * @param Step $step
	 *
	 * @return bool
	 */
	public function is_after( Step $step ) {
		return $this->is_same_funnel( $step ) && $this->get_order() > $step->get_order();
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
		if ( $this->is_starting() || $this->is_entry() ) {
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
	 * Enqueue if and only if the step can complete
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function benchmark_enqueue( $contact ) {

		if ( ! $this->can_complete( $contact ) ) {
			return false;
		}

		$this->enqueue( $contact );

		return true;
	}

	/**
	 * Create an event and add it to the queue
	 *
	 * @param Contact $contact
	 * @param bool    $skip_enqueued whether to skip any other enqueued steps
	 *
	 * @return bool
	 */
	public function enqueue( $contact, $skip_enqueued = true ) {

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

		if ( $skip_enqueued ) {

			// Update any events to skipped...
			$this->get_event_queue_db()->mass_update(
				[
					'status'        => Event::SKIPPED,
					'error_code'    => 'skipped_by_step',
					'error_message' => sprintf( 'Step %d [%s] enqueued', $this->get_id(), sanitize_text_field( $this->get_step_title() ) )
				],
				[
					'funnel_id'  => $this->get_funnel_id(),
					'contact_id' => $contact->get_id(),
					'event_type' => Event::FUNNEL,
					'status'     => Event::WAITING,
				]
			);

		}

		// Set up the new event args
		$event = [
			'time'       => $this->get_delay_time(),
			'funnel_id'  => $this->get_funnel_id(),
			'step_id'    => $this->get_id(),
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
			'priority'   => 10,
		];

		// Special handling for email events
		if ( $this->get_type() === Send_Email::TYPE ) {
			$event['email_id'] = absint( $this->get_meta( 'email_id' ) );
		}

		return $this->get_event_queue_db()->add( $event );
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
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
			'limit'      => 1,
		] );

		if ( ! empty( $events ) ) {
			$event = array_shift( $events );
			$event = new Event( $event, 'event_queue' );

			// Double check step exists...
			if ( $event->exists() && $event->get_step() && $event->get_step()->exists() ) {
				return $event->get_step()->get_order();
			}
		}

		// The most recent completed event for this funnel and contact.
		$events = $this->get_events_db()->query( [
			'funnel_id'  => $this->get_funnel_id(),
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
			'order'      => 'DESC',
			'orderby'    => 'time',
			'limit'      => 1,
		] );

		if ( ! empty( $events ) ) {
			// get top element.
			$event = array_shift( $events );
			$event = new Event( $event );

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
		return $this->get_events_db()->exists( [
				'funnel_id'  => $this->get_funnel_id(),
				'contact_id' => $contact->get_id(),
				'event_type' => Event::FUNNEL,
			] ) || $this->get_event_queue_db()->exists( [
				'funnel_id'  => $this->get_funnel_id(),
				'contact_id' => $contact->get_id(),
				'event_type' => Event::FUNNEL,
			] );
	}

	/**
	 * Return whether the step/funnel is active?
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->step_status === 'active';
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
	 *
	 * @return bool
	 */
	public function is_starting() {
		if ( $this->is_action() ) {
			return false;
		}

		if ( $this->get_order() === 1 ) {
			return true;
		}

		// if has preceding actions, than also not starting
		$preceding = $this->get_preceding_actions();

		if ( empty( $preceding ) ) {
			return true;
		}

		return false;
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
			return new \WP_Error( 'funnel_inactive', 'The funnel is not active.' );
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

		if ( $result && ! is_wp_error( $result ) ) {

			// track conversion if a benchmark
			if ( $this->is_benchmark() && $this->is_conversion() ) {
				track_activity( $contact, 'funnel_conversion', [
					'funnel_id' => $this->get_funnel_id(),
					'step_id'   => $this->get_id(),
					'event_id'  => $event->get_id()
				] );
			}
		}

		return $result;
	}

	/**
	 * Enqueue the next action if one exists
	 *
	 * @param $contact
	 * @param $event
	 *
	 * @return void
	 */
	public function run_after( $contact, $event ) {

		$next = $this->get_next_action();

		if ( $next && is_a( $next, Step::class ) ) {

			// No need to do update to skip previous at this point
			$next->enqueue( $contact, false );
		}

	}

	/**
	 * Output the HTML of a step.
	 */
	public function sortable_item() {
		if ( has_action( "groundhogg/steps/{$this->get_type()}/sortable" ) ) {
			do_action( "groundhogg/steps/{$this->get_type()}/sortable", $this );
		} else {
			do_action( "groundhogg/steps/error/sortable", $this );
		}
	}

	/**
	 * Output the HTML of a step.
	 */
	public function html() {
		if ( has_action( "groundhogg/steps/{$this->get_type()}/html" ) ) {
			do_action( "groundhogg/steps/{$this->get_type()}/html", $this );
		} else {
			do_action( "groundhogg/steps/error/html", $this );
		}
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
			do_action( "groundhogg/steps/error/html_v2", $this );
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

		$data = $this->data;
		// remove HTML formatting
		$data['step_title'] = sanitize_text_field( $this->step_title );

		return apply_filters( "groundhogg/{$this->get_object_type()}/get_as_array", [
			'ID'     => $this->get_id(),
			'data'   => $data,
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
	 * Post import cleanup actions any contextual args from the given template
	 */
	public function post_import() {
		do_action( "groundhogg/steps/{$this->get_type()}/post_import", $this );
		do_action( "groundhogg/steps/post_import", $this );
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

	/**
	 * Get the HTML of the step and return it.
	 *
	 * @return false|string
	 */
	public function __toString() {
		ob_start();

		$this->html();

		return ob_get_clean();
	}

	/**
	 * Return whether or not the current action can run.
	 * This was implement so that WPMU could be effectively implemented with the GLOBAL DB option enabled.
	 *
	 * Always return true if not a multisite or multisite global is not enabled
	 * otherwise compare the current blog ID to the blg ID associated with the step.
	 *
	 * @deprecated
	 */
	public function can_run() {
		return true;
	}
}
