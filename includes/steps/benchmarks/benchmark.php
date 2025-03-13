<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\array_all;
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

	/**
	 * Convert the data to args friendly data, if present
	 *
	 * @return array
	 */
	protected function data_as_args() {

		$args = [];

		foreach ( $this->data as $key => $value ) {

			// ignore these
			if ( in_array( $key, [ 'contact', 'contacts' ] ) ) {
				continue;
			}

			// if it's a simple value we can keep it
			if ( is_numeric( $value ) || is_string( $value ) ) {
				$args[ $key ] = $value;
				continue;
			}

			if ( is_object( $value ) ) {

				if ( property_exists( $value, 'ID' ) ) {
					$args[ $key ] = $value->ID;
					continue;
				}

				if ( property_exists( $value, 'id' ) ) {
					$args[ $key ] = $value->id;
					continue;
				}

				if ( method_exists( $value, 'get_id' ) ) {
					$args[ $key ] = $value->get_id();
					continue;
				}

			}
		}

		return $args;
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

					$this->args = array_merge( $this->data_as_args(), $this->args );

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

		$siblings = $step->get_siblings_of_same_level();

		if ( empty( $siblings ) ) {
			$is_first = true;
			$is_last  = true;
		} else {
			$is_first = array_all( $siblings, function ( Step $sibling ) use ( $step ) {
				return $step->get_order() < $sibling->get_order();
			} );
			$is_last  = array_all( $siblings, function ( Step $sibling ) use ( $step ) {
				return $step->get_order() > $sibling->get_order();
			} );
		}

		// if the previous step was not a benchmark, we should open the horizontal benchmark group
		if ( $is_first ) {

			?>
            <div class="sortable-item benchmarks <?php echo $step->is_starting() ? 'starting' : '' ?>"><?php

			if ( ! $step->is_starting() ) {
				$this->add_step_button( 'before-group-' . $step->ID );
				?>
                <div class="flow-line"></div><?php
			}

			?>
            <div class="step-branch benchmarks" data-branch="<?php _e( $step->branch ) ?>">
			<?php
		} else {
			?><span class="benchmark-or">OR</span><?php
		}

		$sortable_classes = [ 'sortable-item benchmark' ];
		if ( $step->can_passthru() ) {
			$sortable_classes[] = 'passthru';
		}

		?>
        <div class="<?php echo implode( ' ', $sortable_classes ) ?>" data-type="<?php esc_attr_e( $step->get_type() ); ?>" data-group="<?php esc_attr_e( $step->get_group() ); ?>">
			<?php $this->__sortable_item( $step ); ?>
            <div class="step-branch" data-branch="<?php esc_attr_e( $step->ID ); ?>">
				<?php

				$steps = $step->get_funnel()->get_steps();

				$sub_steps = array_filter( $steps, function ( Step $sub ) use ( $step ) {
					return $sub->branch_is( $step->ID );
				} );

				if ( ! empty( $sub_steps ) ) {
					?>
                    <div class="flow-line"></div><?php
				}

				foreach ( $sub_steps as $sub_step ) {
					$sub_step->get_step_element()->validate_settings( $sub_step );
					$sub_step->sortable_item();
				}

				$this->set_current_step( $step );

				if ( empty( $sub_steps ) ) {
					?>
                    <div class="flow-line"></div><?php
				}

				$this->add_step_button( 'end-inside-' . $step->ID );

				?>
            </div>
        </div>
		<?php

		// if the next step is not a benchmark, close the benchmark group
		if ( $is_last ) {
			$this->add_step_button( [ 'id' => 'add-to-group-after-' . $step->ID, 'tooltip' => 'Add benchmark', 'class' => 'add-benchmark' ] );
			?></div></div><?php
		}
	}

	public function delete( Step $step ) {

		$branch_steps = $step->get_sub_steps();

		foreach ( $branch_steps as $branch_step ) {
			$branch_step->delete(); // this might change the current step
		}

		// reset to the current step
		$this->set_current_step( $step );
	}

}
