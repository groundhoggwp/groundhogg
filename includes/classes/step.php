<?php

namespace Groundhogg;

use Groundhogg\DB\Event_Queue;
use Groundhogg\DB\Events;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DB\Step_Meta;
use Groundhogg\DB\Steps;
use Groundhogg\Steps\Actions\Polyfill_Action;
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\steps\benchmarks\Polyfill_Benchmark;
use Groundhogg\Steps\Funnel_Step;
use Groundhogg\Utils\DateTimeHelper;

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

	const MAIN_BRANCH = 'main';
	const BENCHMARK = 'benchmark';
	const ACTION = 'action';
	const LOGIC = 'logic';

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
		$this->step_level = absint( $this->step_level );
		$this->step_order = absint( $this->step_order );
		$this->funnel_id  = absint( $this->funnel_id );
		$this->changes    = maybe_unserialize( $this->changes );
		$this->is_locked  = (bool) $this->is_locked;

		// force to array plz
		if ( ! is_array( $this->changes ) ) {
			$this->changes = [];
		}

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

	/**
	 * Get the step level
	 *
	 * @return int
	 */
	public function get_level() {
		$level = absint( $this->step_level );

		// level has not been initialized yet, maybe the upgrade script didn't run?
		if ( $level < 1 ) {
			$this->get_funnel()->set_step_levels();
			$this->pull();
			$level = absint( $this->step_level );
		}

		return $level;
	}

	public function get_type() {
		return $this->step_type;
	}

	public function get_type_name() {
		return $this->get_step_element()->get_name();
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
		return (bool) $this->is_conversion && $this->is_benchmark();
	}

	public function is_entry() {
		return (bool) $this->is_entry && $this->is_benchmark();
	}

	public function is_last() {
		return $this->get_order() === $this->get_funnel()->get_num_steps();
	}

	public function _is_locked() {
		if ( $this->is_locked ) {
			return true;
		}

		$parent = $this->get_parent_step();

		if ( $parent !== false && $parent->is_locked() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the step is locked.
	 * A step is considered locked if the parent step is also locked
	 *
	 * @return bool
	 */
	public function is_locked() {
		/**
		 * Filter the locking
		 *
		 * @param $locked bool whether the step is locked
		 * @param $step   Step the step
		 */
		return apply_filters( 'groundhogg/step/is_locked', $this->_is_locked(), $this );
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

	public function get_sub_steps() {

		$steps = $this->get_funnel()->get_steps();

		return array_filter( $steps, function ( Step $step ) {
			return str_starts_with( $step->branch, "$this->ID" );
		} );

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

	public function is_logic() {
		return $this->get_group() === self::LOGIC;
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
	 * @param string $type
	 *
	 * @return Step|false
	 * @todo this does not work with branching ATM
	 *
	 */
	public function get_next_of_type( $type = '' ) {

		$steps = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_order', '>', $this->get_order() ],
				[ 'funnel_id', '=', $this->get_funnel_id() ],
				[ 'step_type', '=', $type ],
			],
			'orderby' => 'step_order',
			'order'   => 'ASC',
			'limit'   => 1,
		] );

		if ( empty( $steps ) ) {
			return false;
		}

		return new Step( $steps[0] );
	}

	/**
	 * Get the previous step in the funnel of a specific type
	 *
	 * @param string $type
	 *
	 * @return Step|false
	 */
	public function get_prev_of_type( $type = '' ) {

		$steps = $this->get_db()->query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'step_order', '<', $this->get_order() ],
				[ 'funnel_id', '=', $this->get_funnel_id() ],
				[ 'step_type', '=', $type ],
			],
			'orderby' => 'step_order',
			'order'   => 'DESC',
			'limit'   => 1,
		] );

		if ( empty( $steps ) ) {
			return false;
		}

		return new Step( $steps[0] );
	}

	/**
	 * Returns all the actions that come before this one
	 *
	 * @return Step[]
	 */
	public function get_preceding_steps() {

		$steps = $this->get_funnel()->get_steps();

		return array_filter( $steps, function ( Step $step ) {
			return $step->is_before( $this );
		} );
	}

	/**
	 * Returns all the actions that come before this one
	 *
	 * @return Step[]
	 */
	public function get_preceding_siblings() {
		return array_filter( $this->get_siblings(), function ( Step $step ) {
			return $step->is_before( $this );
		} );
	}


	/**
	 * Returns all the actions that come before this one
	 *
	 * @return Step[]
	 */
	public function get_preceding_actions() {

		$steps = $this->get_funnel()->get_steps();

		return array_filter( $steps, function ( Step $step ) {
			return $step->is_before( $this ) && $step->is_action();
		} );
	}


	public function get_parent_ids() {

		$parents = [];
		$parent  = $this->get_parent_step();

		while ( $parent ) {
			$parents[] = $parent->ID;
			$parent    = $parent->get_parent_step();
		}

		return $parents;
	}

	/**
	 * Returns an ordered array from the current branch to main
	 *
	 * @return array
	 */
	public function get_nested_branches_array() {

		$branches = [ $this->branch ];

		$parent = $this->get_parent_step();

		while ( $parent ) {
			$branches[] = $parent->branch;
			$parent     = $parent->get_parent_step();
		}

		return $branches;

	}

	/**
	 * Returns all the actions that come before this one
	 *
	 * @return Step[]
	 */
	public function get_preceding_actions_of_type( $type = '' ) {

		$steps = $this->get_funnel()->get_steps();

		return array_filter( $steps, function ( Step $step ) use ( $type ) {
			return $step->is_before( $this ) && $step->type_is( $type ) && $step->is_action();
		} );
	}

	/**
	 * Returns all the actions that come after this one
	 *
	 * @return Step[]
	 */
	public function get_proceeding_actions() {

		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'ASC' ] )
		      ->where()
		      ->equals( 'step_group', self::ACTION )
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->greaterThan( 'step_order', $this->get_order() );

		return $query->get_objects( Step::class );
	}

	/**
	 * Returns all the actions that come after this one
	 *
	 * @return Step[]
	 */
	public function get_proceeding_benchmarks() {

		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'ASC' ] )
		      ->where()
		      ->equals( 'step_group', self::BENCHMARK )
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->greaterThan( 'step_order', $this->get_order() );

		return $query->get_objects( Step::class );
	}

	/**
	 * If a benchmark is marked as passthru, it means it will not impede the execution of proceeding steps.
	 *
	 * @return true
	 */
	public function can_passthru() {
		if ( $this->is_benchmark() ) {
			return boolval( $this->can_passthru );
		}

		return true; // anything that's not a benchmark is passthru
	}

	/**
	 * Get the next step in the order
	 *
	 * Not so easy with branching AHAHAHA
	 *
	 * Rules:
	 * - If the current step is a benchmark, next will be the first available action or logic within the same branch
	 * - if logic, do the logic function to get the correct child action, if there is not one available reconnect with main branch
	 * - if the current steps is an action, get the next step of any type and handle accordingly.
	 *
	 * @param Contact $contact
	 *
	 * @return Step|false
	 */
	public function get_next_action( Contact $contact ) {
		$next = $this->_get_next_action( $contact );

		/**
		 * Allow plugins to filter the next step
		 *
		 * @param $next    Step
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/next_action', $next, $this );
	}

	/**
	 * Helper "recursive" function for get_next_action
	 *
	 * @param $contact
	 *
	 * @return false|Base_Object|Step|object
	 */
	protected function _get_next_action( $contact ) {

		if ( $this->is_benchmark() ) { // benchmarks

			// Can return any closest action from the current branch, or any parent branch
			$branches = explode( '|', $this->branch_path );
			// but also the first step of the branch related to the benchmark, if one.
			$branches[] = $this->ID;

			$query = new Table_Query( 'steps' );
			$query->setOrderby( [ 'step_order', 'ASC' ] )
			      ->setLimit( 1 )
			      ->where()
			      ->equals( 'step_status', 'active' )
			      ->equals( 'funnel_id', $this->get_funnel_id() )
			      ->in( 'branch', $branches )
			      ->in( 'step_group', [ self::ACTION, self::LOGIC ] )
			      ->greaterThanEqualTo( 'step_order', $this->get_order() + 1 )
			      ->greaterThanEqualTo( 'step_level', $this->get_level() + 1 );

			$next = $query->get_objects( Step::class );

			$next = ! empty( $next ) ? $next[0] : false; // first proceeding action

		} else if ( $this->is_logic() ) { // logic

			$next = false;

			if ( method_exists( $this->get_step_element(), 'get_logic_action' ) ) {
				// must do logic things to get the next action within a branch
				$next = $this->get_step_element()->get_logic_action( $contact );
			}

			// no steps in the branch (the branch was empty)
			// thus, we continue on in the current branch
			if ( $next === false ) {
				$next = $this->get_next_step();
			}

		} else { // actions

			// just get the next step
			$next = $this->get_next_step();
		}

		// if next is false, then we can't continue. If it's an action then we're good
		if ( $next === null || $next === false || $next->is_action() ) {
			return $next;
		}

		// benchmark but can't pass thru
		if ( ! $next->can_passthru() ) {
			return false;
		}

		// recursive, kind of.
		// if we get here, either a logic or a benchmark with passthru enabled
		return $next->_get_next_action( $contact );
	}

	/**
	 * Get the next step in the order
	 *
	 * Todo, this no longer works correctly with branching
	 *
	 * @return Step|false
	 */
	public function get_prev_action() {

		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'DESC' ] )
		      ->setLimit( 1 )
		      ->where()
		      ->equals( 'step_group', self::ACTION )
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->compare( 'step_order', $this->get_order() - 1, $this->is_action() ? '=' : '<=' );

		$next = $query->get_objects( Step::class );

		$next = ! empty( $next ) ? $next[0] : false;

		/**
		 * Filters the next action
		 *
		 * @param $next    Step|false
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/prev_action', $next, $this );
	}

	/**
	 * If the current step is part of a branch, this will return the logic step from the parent branch
	 *
	 * @return false|Step
	 */
	public function get_parent_step() {

		// main branch, no parents
		if ( $this->is_main_branch() ) {
			return false;
		}

		$parts     = explode( '-', $this->branch );
		$parent_id = absint( $parts[0] );

		if ( ! $parent_id ) {
			return false;
		}

		$step = new Step( $parent_id );

		if ( ! $step->exists() ) {
			return false;
		}

		return $step;
	}

	/**
	 * Get the prev step of the funnel
	 *
	 * Todo, this no longer works correctly with branching
	 *
	 * @return Step|false
	 */
	public function get_prev_step() {
		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'DESC' ] )
		      ->setLimit( 1 )
		      ->where()
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->equals( 'step_order', $this->get_order() - 1 );

		$prev = $query->get_objects( Step::class );

		$prev = ! empty( $prev ) ? $prev[0] : false;

		/**
		 * Filters the prev step
		 *
		 * @param $prev    Step|false
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/prev_step', $prev, $this );
	}

	/**
	 * Get the next step of the funnel
	 *
	 * THIS WILL ONLY RETURN THE NEXT AVAILABLE STEP FROM WITHIN THE CURRENT BRANCH OR PARENT BRANCH
	 * IT WILL NOT RETURN A STEP FROM WITHIN A CHILD/LOGIC BRANCH. IF YOU WANT TO GET THE CORRECT CHILD ACTION
	 * THEN YOU MUST USE Step::get_next_action() AND SUPPLY AN EVENT
	 *
	 * YOU HAVE BEEN WARNED!
	 *
	 * Rules with branching:
	 * - next would only occur within the same branch
	 * - OR, if this is the last step of the branch, the first step within any parent branch (up to main)
	 *
	 * @return Step|false
	 */
	public function get_next_step() {

		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'ASC' ] )
		      ->setLimit( 1 )
		      ->where()
		      ->equals( 'step_status', 'active' )
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->in( 'branch', explode( '|', $this->branch_path ) )
		      ->greaterThanEqualTo( 'step_order', $this->get_order() + 1 )
		      ->greaterThanEqualTo( 'step_level', $this->get_level() + 1 );

		$next = $query->get_objects( Step::class );

		$next = ! empty( $next ) ? $next[0] : false;

		// special handling for passthru, can't be starting from a benchmark
		if ( ! $this->is_benchmark() && $next && $next->is_benchmark() && ! $next->can_passthru() ) {

			$siblings = $next->get_siblings_of_same_level();
			$passthru = array_find( $siblings, function ( Step $step ) {
				return $step->is_benchmark() && $step->can_passthru();
			} );

			if ( $passthru ) {
				$next = $passthru;
			}

		}

		/**
		 * Filters the next step
		 *
		 * @param $next    Step|false
		 * @param $current Step
		 */
		return apply_filters( 'groundhogg/step/next_step', $next, $this );
	}

	/**
	 * Return the next adjacent sibling step
	 *
	 * @return mixed|null
	 */
	public function get_next_sibling() {
		$query = new Table_Query( 'steps' );

		$query->setOrderby( [ 'step_order', 'ASC' ] )
		      ->setLimit( 1 )
		      ->where()
		      ->equals( 'step_status', 'active' )
		      ->equals( 'funnel_id', $this->get_funnel_id() )
		      ->in( 'branch', $this->branch )
		      ->greaterThanEqualTo( 'step_order', $this->get_order() + 1 );

		$next = $query->get_objects( Step::class );

		$next = ! empty( $next ) ? $next[0] : false;

		return apply_filters( 'groundhogg/step/next_sibling', $next, $this );
	}

	public function is_same_parent( Step $prev_step ) {
		$parent  = $this->get_parent_step();
		$parent2 = $prev_step->get_parent_step();

		if ( ! is_a( $parent, Step::class ) || ! is_a( $parent2, Step::class ) ) {
			return $parent === $parent2; // both must be false then
		}

		// Ids must be same
		return $parent->ID === $parent2->ID;
	}

	public function is_same_level( Step $other ) {
		return $this->get_level() === $other->get_level();
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
	 * @param Step $other
	 *
	 * @return bool
	 */
	public function is_before( Step $other ) {
		return $this->is_same_funnel( $other )
		       && $this->get_order() < $other->get_order()
		       && $this->get_level() < $other->get_level()
		       && ! $this->is_parallel( $other );
	}

	/**
	 * Check to see if this step is after the given one
	 *
	 * @param Step $other
	 *
	 * @return bool
	 */
	public function is_after( Step $other ) {
		return $this->is_same_funnel( $other )
		       && $this->get_order() > $other->get_order()
		       && $this->get_level() > $other->get_level()
		       && ! $this->is_parallel( $other );
	}

	/**
	 * Whether this step is a sibling to another in the SAME branch
	 *
	 * @param Step $other
	 *
	 * @return bool
	 */
	public function is_adjacent_sibling( Step $other ) {
		return $this->is_same_funnel( $other ) && $this->is_same_branch( $other ) && $other->get_level() === $this->get_level();
	}

	public function get_siblings() {
		return array_values( array_filter( $this->get_funnel()->get_steps(), function ( Step $step ) {
			return $step->is_same_branch( $this ) && $step->ID !== $this->ID;
		} ) );
	}

	/**
	 * Get siblings that are the same level as this one in the same branch
	 *
	 * @return array
	 */
	public function get_siblings_of_same_level() {
		return array_values( array_filter( $this->get_siblings(), function ( Step $step ) {
			return $step->is_same_level( $this );
		} ) );
	}

	/**
	 * check if a step is between 2 other steps
	 *
	 * @param Step $other1
	 * @param Step $other2
	 *
	 * @return bool
	 */
	public function is_between( Step $other1, Step $other2 ) {

		if ( $other1->is_before( $other2 ) ) {
			return $other1->is_before( $this ) && $this->is_before( $other2 );
		}

		if ( $other1->is_after( $other2 ) ) {
			return $other1->is_after( $this ) && $this->is_after( $other2 );
		}

		return false;
	}

	/**
	 * if this step is a typical timer
	 *
	 * @return bool
	 */
	public function is_timer() {
		/**
		 * Filter whether this step is considered a "timer"
		 *
		 * @param $is_timer bool whether the step is a timer
		 * @param $step     Step the current step
		 */
		return apply_filters( 'groundhogg/step/is_timer', in_array( $this->get_type(), [ 'delay_timer', 'date_timer', 'advanced_timer', 'field_timer' ] ), $this );
	}

	/**
	 * Get the run time for when this step should run
	 *
	 * Previously get_delay_time
	 *
	 * @return int
	 */
	public function get_run_time( $baseTimestamp = 0 ) {

		if ( ! $baseTimestamp ) {
			$baseTimestamp = time();
		}

		return $this->get_step_element()->calc_run_time( $baseTimestamp, $this );
	}

	/**
	 * Use get_run_time instead
	 * @depreacted use Step::get_run_time()
	 * @return int
	 */
	public function get_delay_time() {
		_deprecated_function( __CLASS__ . '::' . __METHOD__, '3.4', __CLASS__ . '::get_run_time' );

		return $this->get_run_time( time() );
	}

	public function branch_is( $branch ) {
		return $this->branch === $branch;
	}

	/**
	 * Whether this step is part of the main branch
	 *
	 * @return bool
	 */
	public function is_main_branch() {
		return $this->branch_is( 'main' );
	}

	/**
	 * Tests to see if a given step is in the same branch to this one
	 *
	 * @param Step $other
	 *
	 * @return bool
	 */
	public function is_same_branch( Step $other ) {
		return $this->branch === $other->branch;
	}

	public function update_branch_path_in_db() {
		$branches = $this->get_nested_branches_array();
		$this->update( [
			'branch_path' => implode( '|', $branches ),
			'ancestors'   => implode( '|', $this->get_parent_ids() )
		] );
	}

	/**
	 * Retrieve the lowest common ancestor of two steps
	 *
	 * @param Step $other
	 *
	 * @return array|string|false
	 */
	public function get_lowest_common_ancestor_id( Step $other ) {

		$a_ancestors = explode( '|', $this->ancestors );
		$b_ancestors = explode( '|', $other->ancestors );

		$common = array_intersect( $a_ancestors, $b_ancestors );

		if ( empty( $common ) ) {
			return false;
		}

		return array_shift( $common );
	}

	/**
	 * Retrieve the lowest common ancestor of two steps
	 *
	 * @param Step $other
	 *
	 * @return string
	 */
	public function get_lowest_common_ancestor_branch( Step $other ) {

		$a_branches = explode( '|', $this->branch_path );
		$b_branches = explode( '|', $other->branch_path );

		$common = array_intersect( $a_branches, $b_branches );

		if ( empty( $common ) ) {
			return false;
		}

		return array_shift( $common );
	}

	/**
	 * Tests to see if a given step is in a parallel branch to this one
	 * Note, adjacent benchmarks will be considered parallel
	 *
	 * @param Step $other
	 *
	 * @return bool
	 */
	public function is_parallel( Step $other ) {

		if ( $this->ID === $other->ID ) {
			return false;
		}

		if ( $this->is_same_branch( $other ) ) {
			return $this->is_same_level( $other );
		}

		// special handling for benchmarks
		if ( $this->_in_parallel_benchmark_branch( $other ) ) {
			return true;
		}

		$common_ancestor_branch = $this->get_lowest_common_ancestor_branch( $other ); // will always be at least main
		$common_ancestor_id     = $this->get_lowest_common_ancestor_id( $other ); // might be false

		// no common ancestor
		if ( ! $common_ancestor_id ) {
			return false;
		}

		// if the common branch is after the common ancestor (meaning the branch belongs to the ancestor), then not parallel
		// but, if the common ancestor is within the common branch, that means parallel
		$branch_parent_id = explode( '-', $common_ancestor_branch )[0];

		return $branch_parent_id !== $common_ancestor_id;
	}

	/**
	 * Checks if the step is in a parallel benchmark branch
	 *
	 * @param Step $other
	 *
	 * @return bool
	 */
	protected function _in_parallel_benchmark_branch( Step $other ) {

		// would start at main and go down
		$a_branches  = array_reverse( explode( '|', $this->branch_path ) ); // first is always main
		$a_ancestors = array_reverse( explode( '|', $this->ancestors ) );
		$b_branches  = array_reverse( explode( '|', $other->branch_path ) ); // first is always main
		$b_ancestors = array_reverse( explode( '|', $other->ancestors ) );

		array_shift( $a_branches ); // get rid of 'main'
		array_shift( $b_branches ); // get rid of 'main'

		// need a parent to be parallel, duh!
		if ( empty( $b_branches ) || empty( $a_branches ) || empty( $a_ancestors ) || empty( $b_ancestors ) ) {
			return false;
		}

		// first would be main
		foreach ( $a_branches as $i => $a_branch ) {

			if ( ! isset( $b_branches[ $i ] ) || ! isset( $b_ancestors[ $i ] ) ) {
				break; // no more comparisons
			}

			$b_branch = $b_branches[ $i ];

			if ( $a_branch === $b_branch ) {
				continue;
			}

			$a_ancestor = $a_ancestors[ $i ];
			$b_ancestor = $b_ancestors[ $i ];

			// we have found adjacent benchmarks, therefor the steps are parallel
			return $a_ancestor === $a_branch && $b_ancestor === $b_branch;
		}

		return false;
	}

	/**
	 * Ensures that the contact can travel through the branch conditions necessary to get to the current step
	 *
	 * @param Step    $from where contact currently is in the funnel
	 * @param Contact $contact
	 *
	 * @return bool
	 */
	public function can_travel( Step $from, $contact = null ) {

		// the step to travel to must be after the step we're coming from
		if ( ! $this->is_after( $from ) ) {
			return false;
		}

		$curr = $this;

		while ( ! $curr->is_same_branch( $from ) ) {

			$curr = $curr->get_parent_step();

			if ( ! $curr ) {
				break; // we reached main, and we can always travel to the main branch from anywhere
			}

			// If the contact does not match the branch conditions to travel to the current step in the branch
			if ( $curr->is_logic() && is_a_contact( $contact ) && ! $curr->get_step_element()->matches_branch_conditions( $curr->branch, $contact ) ) {
				return false;
			}

			// If the benchmark is not passthru
			if ( $curr->is_benchmark() && ! $curr->can_passthru() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether this step can actually be completed
	 *
	 * Rules with branching:
	 * - if in the main branch, can always complete so long as the order is greater
	 * - if in a branch, must match the parent step criteria
	 * - cannot be in an adjacent parallel path (current position must be less that order of parent logic step)
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function can_complete( $contact = null ) {

		// Actions cannot be completed.
		if ( ! $this->is_benchmark() || ! $this->is_active() ) {
			return false;
		}

		// Check if starting
		if ( $this->is_starting() || $this->is_entry() ) {
			return true;
		}

		// If inner step, check if contact is at a step before this one.
		if ( $this->is_inner() ) {

			// get the current funnel step position of the contact in the funnel
			$current_step = $this->get_current_funnel_step( $contact );

			// We must be in the funnel and the current order MUST BE LESS than this one
			// also ensure we're not in a parallel branch
			if ( $current_step && $this->can_travel( $current_step, $contact ) ) {
				return true;
			}
		}

		return apply_filters( 'groundhogg/step/can_complete', false, $this, $contact );
	}

	/**
	 * Enqueue if and only if the step can complete
	 *
	 * @param $contact Contact
	 * @param $args    array optional args for the event
	 *
	 * @return bool
	 */
	public function benchmark_enqueue( $contact, $args = [] ) {

		if ( ! $this->can_complete( $contact ) ) {
			return false;
		}

		// If this is an inner benchmark we want to persist any args from previously enqueued or completed steps
		// we don't need to check if the order is less because that would have been verified in Step::can_complete()
		if ( $this->is_inner() ) {

			// ideally the result from this would have been cached since Step::get_current_funnel_step_order()
			$event = $this->get_previous_event( $contact );

			// Event has args
			if ( ! empty( $event->args ) ) {
				// merge the previous event args with any given
				$args = is_array( $event->args ) ? array_merge( $event->args, $args ) : $event->args;
			}
		}

		$this->enqueue( $contact, true, $args );

		return true;
	}

	/**
	 * Create an event and add it to the queue
	 *
	 * @param Contact $contact
	 * @param bool    $skip_enqueued whether to skip any other enqueued steps
	 * @param array   $args          option arguments to store with the event
	 *
	 * @return bool
	 */
	public function enqueue( $contact, $skip_enqueued = true, $args = [] ) {

		// logic steps can't be enqueued, only their children or what they point to can be enqueued...
		if ( $this->is_logic() ) {
			$next = $this->get_next_action( $contact );
			if ( $next === false ) {
				return false;
			}

			return $next->enqueue( $contact, $skip_enqueued, $args );
		}

		$this->enqueued_contact = $contact;

		/**
		 * @param bool    $enqueue whether the step can be enqueued or not...
		 * @param Contact $contact Contact
		 * @param Step    $step    Step the step being enqueued
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
					'status'         => Event::SKIPPED,
					'error_code'     => 'skipped_by_step',
					'error_message'  => sprintf( 'Step %d [%s] enqueued', $this->get_id(), sanitize_text_field( $this->get_step_title() ) ),
					'time_scheduled' => time(), // time to say when it skipped
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
			'time'       => $this->get_run_time(),
			'funnel_id'  => $this->get_funnel_id(),
			'step_id'    => $this->get_id(),
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
			'priority'   => 10,
		];

		if ( ! empty( $args ) ) {
			$event['args'] = $args;
		}

		/**
		 * Filter the event data before it is enqueued.
		 *
		 * This filter allows modifying the event properties before it is added to the database queue.
		 *
		 * @param array   $event   The event data to be enqueued.
		 * @param Contact $contact The contact associated with the event.
		 * @param Step    $this    The current step object.
		 *
		 * @return array The modified event data.
		 */
		$event = apply_filters( 'groundhogg/step/enqueue/event', $event, $contact, $this );

		// Special handling for email events
		if ( $this->get_type() === Send_Email::TYPE ) {
			$event['email_id'] = absint( $this->get_meta( 'email_id' ) );
		}

		return $this->get_event_queue_db()->add( $event );
	}

	/**
	 * Retrieves the currently enqueued event from the event queue
	 * or if there isn't one, the most recently completed event from the event history table
	 *
	 * @param $contact
	 *
	 * @return false|Base_Object|object|null
	 */
	public function get_previous_event( $contact ) {
		$eventQuery = new Table_Query( 'event_queue' );
		$eventQuery->setLimit( 1 )
		           ->where()
		           ->equals( 'contact_id', $contact->get_id() )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $this->get_funnel_id() )
		           ->equals( 'status', Event::WAITING );

		$events = $eventQuery->get_objects( Event_Queue_Item::class );

		if ( ! empty( $events ) ) {
			$event = array_shift( $events );

			// Double check step exists...
			if ( $event->exists() ) {
				return $event;
			}
		}

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setLimit( 1 )
		           ->setOrderby( [ 'time', 'DESC' ], [ 'micro_time', 'DESC' ] )
		           ->where()
		           ->equals( 'contact_id', $contact->get_id() )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $this->get_funnel_id() )
		           ->equals( 'status', Event::COMPLETE );

		$events = $eventQuery->get_objects( Event::class );

		if ( ! empty( $events ) ) {
			// get top element.
			$event = array_shift( $events );

			// Double check step exists...
			if ( $event->exists() ) {
				return $event;
			}
		}

		return false;
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

		$event = $this->get_previous_event( $contact );

		if ( ! $event || ! $event->get_step() || ! $event->get_step()->exists() ) {
			return false;
		}

		return $event->get_step();
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

		if ( ! $this->is_benchmark() || ! $this->is_main_branch() ) {
			return false;
		}

		if ( $this->get_level() === 1 || $this->get_order() === 1 ) {
			return true;
		}

		return false;
	}

	/**
	 * Return the name given with the ID prefixed for easy access in the $_POST variable
	 *
	 * @deprecated since 2.0
	 *
	 * @param $name
	 *
	 * @return string
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

			$this->get_step_element()->pre_run( $contact, $event );
			$result = $this->get_step_element()->run( $contact, $event );

			do_action( "groundhogg/steps/{$this->get_type()}/run/after", $this );
		}

		// Modify the result
		$result = apply_filters( 'groundhogg/steps/run/result', $result, $this, $contact, $event );

		if ( $result && ! is_wp_error( $result ) ) {

			// track conversion if a benchmark
			if ( $this->is_conversion() ) {
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
	 * @param Contact          $contact
	 * @param Event_Queue_Item $event
	 *
	 * @return void
	 */
	public function run_after( $contact, $event ) {

		$next = $this->get_next_action( $contact );

		if ( $next && is_a( $next, Step::class ) ) {
			// No need to do update to skip previous at this point
			$next->enqueue( $contact, false, $event->args ); // persist args to next event
		}
	}

	/**
	 * Gets the related step element of a step based on the step type
	 * Also sets the elements' current step to this step
	 *
	 * @return Funnel_Step
	 */
	public function get_step_element() {

		$type = $this->get_type();

		if ( ! Plugin::instance()->step_manager->type_is_registered( $this->get_type() ) ) {
			if ( $this->is_benchmark() ) {
				return new Polyfill_Benchmark( $this );
			}

			if ( $this->is_action() ) {
				return new Polyfill_Action( $this );
			}

			$type = 'error';
		}

		$element = Plugin::instance()->step_manager->get_element( $type );
		$element->set_current_step( $this );

		return $element;
	}

	/**
	 * Output the HTML of a step.
	 */
	public function sortable_item( $echo = true ) {

		if ( ! $echo ) {
			ob_start();
		}

		$this->get_step_element()->sortable_item( $this );

		if ( ! $echo ) {
			return ob_get_clean();
		}

		return true;
	}

	/**
	 * Output the HTML of a step.
	 */
	public function html_v2( $echo = true ) {

		if ( ! $echo ) {
			ob_start();
		}

		$this->get_step_element()->html_v2( $this );

		if ( ! $echo ) {
			return ob_get_clean();
		}

		return false;
	}

	/**
	 * Output icon html
	 */
	public function icon() {
		$icon = $this->get_step_element()->get_icon();

		return $icon ?: GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
	}

	/**
	 * Output the HTML of a step.
	 */
	public function html( $echo = true ) {
		return $this->html_v2( $echo );
	}

	/**
	 * Save the step
	 */
	public function save() {

		$this->merge_changes(); // make sure changes are merged first as that will be relevant for some functions...

		$this->get_step_element()->pre_save( $this );
		$this->get_step_element()->save( $this );
		$this->get_step_element()->after_save( $this );
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function sanitize_columns( $data = [] ) {

		return array_apply_callbacks( $data, [
			'funnel_id'     => 'absint',
			'step_title'    => 'sanitize_text_field',
			'step_status'   => function ( $value ) {
				return one_of( $value, [ 'active', 'inactive', 'archived' ] );
			},
			'step_type'     => 'sanitize_key',
			'step_group'    => function ( $value ) {
				return one_of( $value, [ self::ACTION, self::BENCHMARK, self::LOGIC ] );
			},
			'step_slug'     => 'sanitize_text_field',
			'step_order'    => 'absint',
			'is_entry'      => 'boolval',
			'is_conversion' => 'boolval',
			'can_passthru'  => 'boolval',
			'branch'        => 'sanitize_key',
			'changes'       => 'maybe_serialize',
		] );
	}

	/**
	 * Also call the delete method from the step element in the event there is cleanup
	 *
	 * @return bool
	 */
	public function delete() {

		// This will handle any complimentary delete handlers, such as those for branch logic steps
		$this->get_step_element()->delete( $this );

		// If an active step is deleted, what we'll do is add a change that it was deleted,
		// and when we do get_steps() we'll filter out steps that have that flag
		if ( $this->should_add_as_changes() ) {
			return $this->add_changes( [ 'is_deleted' => true ] );
		}

		return parent::delete();
	}

	/**
	 * Whether this step has changes
	 *
	 * @return bool
	 */
	public function has_changes() {
		$changes = $this->changes;

		return ! empty( $changes );
	}

	protected $is_temp_merged = false;

	/**
	 * Merge the changes with the actual data and meta
	 *
	 * @return void
	 */
	public function merge_changes() {

		if ( ! $this->has_changes() ) {
			return;
		}

		$changes      = $this->changes;
		$columns      = $this->get_db()->get_columns();
		$data_changes = array_intersect_key( $changes, $columns ); // stuff that goes into main DB
		$meta_changes = array_diff_key( $changes, $columns ); // stuff that goes into meta

		$this->data = array_merge( $this->data, $data_changes );
		$this->meta = array_merge( $this->meta, $meta_changes );

		$this->is_temp_merged = true;
	}

	public function pull() {
		$this->is_temp_merged = false;

		return parent::pull();
	}

	public function maybe_pull() {
		if ( $this->is_temp_merged ) {
			return $this->pull();
		}

		return false;
	}

	/**
	 * Add changes while waiting for commit()
	 *
	 * @param $changes array the changes to save
	 *
	 * @return bool
	 */
	public function add_changes( $changes ) {

		// Invalid data for update
		if ( ! is_array( $changes ) ) {
			return false;
		}

		$was_merged = false;

		// pull first if changes were previously merged
		if ( $this->is_temp_merged ) {
			$this->pull();
			$was_merged = true;
		}

		$changes = $this->sanitize_columns( $changes );
		$changes = array_merge( $this->changes, $changes );
		unset( $changes['changes'] );

		$changes = keep_the_diff( $changes, array_merge( $this->data, $this->meta ) );

		ksort( $changes );
		// no nested changed plz

		$result = parent::update( [
			'changes' => $changes,
		] );

		if ( $was_merged ) {
			$this->merge_changes();
		}

		return $result;
	}

	public function clear_changes() {
		$this->maybe_pull();

		parent::update( [
			'changes' => [] // clear the changes with an empty array
		] );
	}

	public function should_add_as_changes() {
		return $this->is_active() && ! $this->is_committing;
	}

	protected $is_committing = false;

	/**
	 * Pushes the changes to the actual meta and data and resets the changes
	 *
	 * @return bool
	 */
	public function commit() {

		$this->maybe_pull();

		$this->is_committing = true;

		$data_changes = [];
		$meta_changes = [];

		if ( $this->has_changes() ) {
			$changes = $this->changes;

			// delete the step if it was "deleted"
			if ( isset_not_empty( $changes, 'is_deleted' ) ) {
				// using parent avoids having to work around is_active()
				return parent::delete(); // todo maybe we do special handling with $this->delete() instead?
			}

			$columns      = $this->get_db()->get_columns();
			$data_changes = array_intersect_key( $changes, $columns ); // stuff that goes into main DB
			$meta_changes = array_diff_key( $changes, $columns ); // stuff that goes into meta
		}

		$data_changes['changes']        = []; // clear the changes
		$data_changes['date_committed'] = ( new DateTimeHelper() )->ymdhis();

		if ( ! empty( $meta_changes ) ) {
			$this->update_meta( $meta_changes );
		}

		$result = $this->update( $data_changes );

		$this->is_committing = false;

		return $result;
	}

	/**
	 *
	 * Instead of actually updating the step, we might be adding the data to the changes
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		// if the step is currently active, all changes should be saved as changes
		// the commit method will instead be used to push the changes live...
		if ( $this->should_add_as_changes() ) {
			return $this->add_changes( $data );
		}

		return parent::update( $data );
	}

	public function sanitize_meta( $key, $value ) {

		if ( $this->get_step_element()->in_settings_schema( $key ) ) {
			return $this->get_step_element()->sanitize_setting( $key, $value );
		}

		switch ( $key ) {
			case 'step_notes':
				return sanitize_textarea_field( $value );
		}

		return $value;
	}

	/**
	 * We are going to pass any added meta through the step element's settings schema validation first
	 *
	 * @param string|array $key
	 * @param mixed        $value
	 *
	 * @return bool|mixed
	 */
	public function add_meta( $key, $value = false ) {

		// single value provided and it's in the step element schema
		if ( is_string( $key ) ) {
			// we need to sanitize it based on the schema settings
			$value = $this->sanitize_meta( $key, $value );
		}

		// maybe add to changes instead?
		if ( is_string( $key ) && $this->should_add_as_changes() ) {
			return $this->add_changes( [
				$key => $value
			] );
		}

		return parent::add_meta( $key, $value );
	}

	/**
	 * We are going to pass any added meta through the step element's settings schema validation first
	 *
	 * @param string|array $key
	 * @param mixed        $value
	 *
	 * @return bool|mixed
	 */
	public function update_meta( $key, $value = false ) {

		// single value provided and it's in the step element schema
		if ( is_string( $key ) ) {

			// we need to sanitize it based on the schema settings
			$value = $this->sanitize_meta( $key, $value );
		}

		// maybe add to changes instead?
		if ( is_string( $key ) && $this->should_add_as_changes() ) {
			return $this->add_changes( [
				$key => $value
			] );
		}

		return parent::update_meta( $key, $value );
	}

	/**
	 * Oh Boy....
	 *
	 * @return array
	 */
	public function export() {
		return $this->get_step_element()->export( [], $this );
	}

	/**
	 * Import any contextual args from the given template
	 *
	 * @param array $import_args
	 */
	public function import( $import_args = [] ) {
		$this->get_step_element()->import( $import_args, $this );
	}

	/**
	 * Post import cleanup actions any contextual args from the given template
	 */
	public function post_import() {
		$this->get_step_element()->post_import( $this );
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

	public function get_as_array() {

		// coming from funnel editor
		if ( $this->get_funnel()->is_editing() ) {
			$this->merge_changes();
		}

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
	 * Get the HTML of the step and return it.
	 *
	 * @return false|string
	 */
	public function __toString() {
		ob_start();

		$this->html_v2();

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

	public function admin_link() {
		return admin_page_url( 'gh_funnels', [
			'action' => 'edit',
			'funnel' => $this->get_funnel_id()
		], $this->get_id() );
	}
}
