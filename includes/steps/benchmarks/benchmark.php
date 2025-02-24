<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\array_find;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\process_events;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Benchmark extends Funnel_Step {
	const GROUP = 'benchmark';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/';
	}

	/**
	 * List for arbitrary data manipulation
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * List of arguments to be passed later to benchmark enqueue
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * Set any arguments for later
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	protected function set_args( array $args ) {
		$this->args = array_merge( $this->args, $args );
	}

	/**
	 * Add arbitrary data
	 *
	 * @param string $key
	 * @param int    $data
	 */
	protected function add_data( $key = '', $data = 0 ) {
		$this->data[ $key ] = $data;
	}

	/**
	 * Get arbitrary data.
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return bool|mixed
	 */
	protected function get_data( $key = '', $default = false ) {
		if ( isset_not_empty( $this->data, $key ) ) {
			return $this->data[ $key ];
		}

		return $default;
	}

	public function __construct() {
		// Setup the main complete function
		// Accepts no arguments, but requires that child implementations setup the data ahead of time.
		foreach ( $this->get_complete_hooks() as $hook => $args ) {
			if ( is_array( $args ) ) {
				add_action( $args[0], [ $this, 'setup' ], 98, $args[1] );
				add_action( $args[0], [ $this, 'complete' ], 99, 0 );
			} else {
				add_action( $hook, [ $this, 'setup' ], 98, $args );
				add_action( $hook, [ $this, 'complete' ], 99, 0 );
			}
		}

		parent::__construct();
	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return int[]
	 */
	abstract protected function get_complete_hooks();

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	abstract protected function get_the_contact();

	/**
	 * Based on the current step and contact,
	 *
	 * @return bool
	 */
	abstract protected function can_complete_step();

	/**
	 * Whether events should be process after this benchmark is complete
	 *
	 * @var bool
	 */
	protected $process_events_after_complete = false;

	/**
	 * Start completing the thing....
	 */
	public function complete() {

		$steps = $this->get_like_steps( [
			'step_status' => 'active'
		] );

		$contacts_to_process = [];

		foreach ( $steps as $step ) {

			// Skip inactive steps
			if ( ! $step->is_active() ) {
				continue;
			}

			$this->set_current_step( $step );

			$contacts = $this->get_the_contact();

			if ( ! is_array( $contacts ) ) {
				$contacts = [ $contacts ];
			}

			foreach ( $contacts as $contact ) {

				if ( is_wp_error( $contact ) || ! is_a_contact( $contact ) ) {
					continue;
				}

				$this->set_current_contact( $contact );

				if ( $this->can_complete_step() ) {
					$step->benchmark_enqueue( $this->get_current_contact(), $this->args );
				}
			}
		}

		// Only process events if flag to complete is true
		if ( $this->process_events_after_complete && ! empty( $contacts_to_process ) ) {
			process_events( $contacts_to_process );
		}
	}

	/**
	 * @return string
	 */
	final public function get_group() {
		return self::BENCHMARK;
	}

	/**
	 * Process the tag applied step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return true
	 */
	public function run( $contact, $event ) {
		//do nothing...

		return true;
	}

	/**
	 * @param Step $step
	 *
	 * @return void
	 */
	public function sortable_item( $step ) {

		$steps = $step->get_funnel()->get_steps_for_editor();

		$prev = array_find( $steps, function ( Step $maybePrev ) use ( $step ) {
			return $maybePrev->get_order() === $step->get_order() - 1 && $step->is_same_branch( $maybePrev );
		} );

		$next = array_find( $steps, function ( Step $maybePrev ) use ( $step ) {
			return $maybePrev->get_order() === $step->get_order() + 1 && $step->is_same_branch( $maybePrev );
		} );

		// if the previous step was not a benchmark, we should open the horizontal benchmark group
		if ( ! $prev || ! $prev->is_benchmark() ) {
			?>
            <div class="step-branch benchmarks" data-branch="<?php _e( $step->branch ) ?>">
            <?php
		} else {
            ?><span class="benchmark-or">OR</span><?php
		}

		parent::sortable_item( $step );

		// if the next step is not a benchmark, close the benchmark group
		if ( ! $next || ! $next->is_benchmark() ) {
			?></div><?php
		}
	}

}
