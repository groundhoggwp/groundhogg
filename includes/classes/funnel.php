<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
use Groundhogg\DB\Steps;
use Groundhogg\Utils\DateTimeHelper;

class Funnel extends Base_Object_With_Meta {

	protected $is_template = false;
	protected $steps = [];

	public function __construct( $identifier_or_args = 0, $is_template_data = false ) {

		if ( $is_template_data ) {
			$this->setup_template_data( $identifier_or_args );

			return;
		}

		parent::__construct( $identifier_or_args );
	}

	protected function setup_template_data( $data ) {

		$data = (object) $data;

		$this->is_template = true;
		$this->ID          = $data->ID;
		$this->data        = (array) $data->data;
		$this->meta        = (array) $data->meta;
		$this->steps       = (array) $data->steps;
		$this->campaigns   = (array) $data->campaigns;
	}

	public function step_flow( $echo = true ) {

		$steps = $this->get_steps();

		$steps = array_filter( $steps, function ( Step $step ) {
			return $step->is_main_branch();
		} );

		if ( empty( $steps ) ) {
			return false;
		}

		$html = '';

		foreach ( $steps as $step ) {
			$step->get_step_element()->validate_settings( $step );
			$step_output = $step->sortable_item( $echo );
			if ( ! $echo ) {
				$html .= $step_output;
			}
		}

		if ( $echo ) {
			return true;
		}

		return $html;
	}

	/**
	 * What the flow editor's JS needs to draw each step on the canvas, keyed by step ID
	 *
	 * @return array[]
	 */
	public function get_canvas_data() {

		$canvas = [];

		foreach ( $this->get_steps() as $step ) {
			$element = $step->get_step_element();
			$element->validate_settings( $step );
			$canvas[ $step->get_id() ] = $element->get_canvas_data( $step );
		}

		return $canvas;
	}

	/**
	 * The step types' parts of the settings panels, which the flow editor draws the rest of, keyed by step ID
	 *
	 * @param int[]|null $ids only these steps, or all of them
	 *
	 * @return array[] see Funnel_Step::get_settings_island()
	 */
	public function get_settings_islands( ?array $ids = null ) {

		$islands = [];

		foreach ( $this->get_steps() as $step ) {

			if ( $ids !== null && ! in_array( $step->get_id(), $ids ) ) {
				continue;
			}

			$islands[ $step->get_id() ] = $step->get_step_element()->get_settings_island( $step );
		}

		return $islands;
	}

	public function step_settings( $echo = true ) {

		$steps = $this->get_steps();

		usort( $steps, function ( $a, $b ) {
			return $a->ID - $b->ID; // sort by ID because when using morphdom that won't change dom pos
		} );

		$html = '';

		foreach ( $steps as $step ) {
			$step->get_step_element()->validate_settings( $step );
			$html .= $step->html_v2( $echo );
		}

		return $html;
	}

	public function flow_preview( $show = 15 ) {

		$allSteps = ! empty( $this->steps ) ? $this->steps : $this->get_steps();

		?>
        <div class="funnel-preview"><?php

		$steps = array_splice( $allSteps, 0, $show );

		foreach ( $steps as $step ) {

			// from actual funnel
			if ( is_a( $step, Step::class ) ) {
				$step_type = $step->get_step_element();

				// skip unregistered steps, might be polyfill
				if ( ! $step_type->is_registered() ) {
					continue;
				}
			} // from template
			else {

				$step_type = get_array_var( $step->data, 'step_type' );

				if ( ! Plugin::instance()->step_manager->type_is_registered( $step_type ) ) {
					continue;
				}

				$step_type = Plugin::instance()->step_manager->get_element( $step_type );

			}

			?>
            <div class="step-preview">
                <div class="step-icon <?php echo esc_attr( $step_type->get_type() ); ?> <?php echo esc_attr( $step_type->get_group() ) ?>">
					<?php if ( $step_type->icon_is_svg() ): ?>
						<?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated SVG
                        echo $step_type->get_icon_svg(); ?>
					<?php else: ?>
                        <img src="<?php echo esc_url( $step_type->get_icon() ); ?>" alt="<?php echo esc_attr( $step_type->get_name() ); ?>">
					<?php endif; ?>
                </div>
                <div class="gh-tooltip top">
					<?php echo esc_html( sanitize_text_field( get_array_var( $step->data, 'step_title' ) ) ) ?>
                </div>
            </div>
			<?php

		}

		if ( ! empty( $allSteps ) ) : ?>
            <div class="step-preview">
                <div class="step-icon more">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16">
                        <path fill="#000" d="M4 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                    </svg>
                    <div class="gh-tooltip top">
						<?php
                        /* translators: %d: the number of steps remaining */
                        echo esc_html( sprintf( _n( '%d more step...', '%d more steps', count( $allSteps ), 'groundhogg' ), count( $allSteps ) ) ) ?>
                    </div>
                </div>
            </div>
			<?php endif; ?>
        </div><?php

	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Funnels
	 */
	protected function get_db() {
		return get_db( 'funnels' );
	}

	/**
	 * @return Steps
	 */
	protected function get_steps_db() {
		return get_db( 'steps' );
	}

	protected function get_meta_db() {
		return Plugin::instance()->dbs->get_db( 'funnelmeta' );
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'funnel';
	}

	public function get_id() {
		return absint( $this->ID );
	}

	public function get_title() {
		return $this->title;
	}

	public function get_status() {
		return $this->status;
	}

	public function is_active() {
		return $this->get_status() === 'active';
	}

	/**
	 * Pause any events in the queue
	 */
	public function pause_events() {
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status'         => Event::PAUSED,
			'time_scheduled' => time()
		] );
	}

	/**
	 * Unpause any waiting events in the event queue
	 */
	public function unpause_events() {
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		], [
			'status'         => Event::WAITING,
			'time_scheduled' => time()
		] );
	}

	/**
	 * Cancel paused or waiting events
	 */
	public function cancel_events() {

		$time = time();

		// Cancel waiting events
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status'         => Event::CANCELLED,
			'time_scheduled' => $time
		] );

		// Cancel paused events
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		], [
			'status'         => Event::CANCELLED,
			'time_scheduled' => $time
		] );

		// Move to history
		event_queue_db()->move_events_to_history( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::CANCELLED,
		], 'AND' );
	}

	/**
	 * Steps that are deleted but not removed yet, staged for the next commit or, if the funnel is inactive, marked deleted directly
	 *
	 * @return Step[]
	 */
	public function get_deleted_steps() {
		return array_values( array_filter( $this->get_real_steps(), function ( Step $step ) {
			$step->merge_changes();

			return $step->step_status === 'deleted';
		} ) );
	}

	/**
	 * Which steps point at which of this funnel's steps in their settings (Funnel_Step::get_step_references()),
	 * including add to flow steps in other funnels that add contacts at one of them.
	 * Uses the steps as get_steps() sees them, so the draft while editing.
	 *
	 * @return array step ID => IDs of the steps pointing at it
	 */
	public function get_step_references_map(): array {

		$steps    = $this->get_steps();
		$step_ids = array_map( 'absint', wp_list_pluck( $steps, 'ID' ) );
		$map      = [];

		$add = function ( Step $from ) use ( &$map, $step_ids ) {
			foreach ( $from->get_step_element()->get_step_references( $from ) as $id ) {
				if ( in_array( $id, $step_ids, true ) ) {
					$map[ $id ][] = $from->get_id();
				}
			}
		};

		foreach ( $steps as $step ) {
			$add( $step );
		}

		// add to flow steps elsewhere that add contacts at a step in this funnel
		foreach ( $this->get_steps_db()->query( [ 'step_type' => 'add_to_flow', 'step_status' => [ '!=', 'archived' ] ] ) as $row ) {

			$step = new Step( $row );
			$step->merge_changes();

			if ( $step->get_funnel_id() === $this->get_id() || $step->step_status === 'deleted' ) {
				continue;
			}

			$add( $step );
		}

		return array_map( function ( $ids ) {
			return array_values( array_unique( $ids ) );
		}, $map );
	}

	/**
	 * Steps can't be deleted while other steps point at them. Checks the steps about to be deleted,
	 * including the steps in their branches, ignoring steps pointing at them that are being deleted too.
	 *
	 * @param int[] $step_ids the steps being deleted, with the steps in their branches
	 *
	 * @return true|\WP_Error an error naming the steps pointing at them
	 */
	public function can_delete_steps( array $step_ids ) {

		$step_ids = wp_parse_id_list( $step_ids );
		$map      = $this->get_step_references_map();
		$blocked  = [];

		foreach ( $step_ids as $step_id ) {

			$referencing = array_diff( $map[ $step_id ] ?? [], $step_ids );

			if ( ! empty( $referencing ) ) {
				$blocked[ $step_id ] = array_values( $referencing );
			}
		}

		if ( empty( $blocked ) ) {
			return true;
		}

		$lines = [];

		foreach ( $blocked as $step_id => $referencing ) {
			$lines[] = sprintf(
				/* translators: 1: the step being deleted, 2: the steps pointing at it */
				__( '"%1$s" is used by %2$s.', 'groundhogg' ),
				wp_strip_all_tags( ( new Step( $step_id ) )->get_title() ),
				implode( ', ', array_map( function ( $id ) {
					$step = new Step( $id );

					return sprintf( '"%s"', wp_strip_all_tags( $step->get_title() ) ) . ( $step->get_funnel_id() !== $this->get_id() ? sprintf(
						/* translators: %s: the flow the step is in */
							__( ' in the flow "%s"', 'groundhogg' ),
							$step->get_funnel()->get_title()
						) : '' );
				}, $referencing ) )
			);
		}

		return new \WP_Error(
			'step_referenced',
			__( 'Steps other steps point at can\'t be deleted. Change those steps first.', 'groundhogg' ) . ' ' . implode( ' ', $lines ),
			[ 'referenced_by' => $blocked ]
		);
	}

	/**
	 * How many contacts have waiting or paused events at a step
	 *
	 * @param Step $step
	 *
	 * @return int
	 */
	public function count_pending_events( Step $step ) {

		$count = 0;

		foreach ( [ Event::WAITING, Event::PAUSED ] as $status ) {
			$count += event_queue_db()->count( [
				'funnel_id'  => $this->get_id(),
				'step_id'    => $step->get_id(),
				'event_type' => Event::FUNNEL,
				'status'     => $status,
			] );
		}

		return $count;
	}

	/**
	 * Actions that contacts at a deleted step can be moved to
	 *
	 * @return Step[]
	 */
	public function get_move_targets() {
		return array_values( array_filter( $this->get_real_steps(), function ( Step $step ) {
			$step->merge_changes();

			return $step->is_action() && $step->step_status !== 'deleted';
		} ) );
	}

	/**
	 * Decide what happens to contacts waiting at deleted steps, either move them to another action or cancel their events
	 *
	 * @param array $choices step ID => [ 'action' => 'move', 'to' => step ID ] or [ 'action' => 'cancel' ], anything else cancels
	 *
	 * @return void
	 */
	public function resolve_deleted_step_events( array $choices = [] ) {

		$targets = [];

		foreach ( $this->get_move_targets() as $target ) {
			$targets[ $target->get_id() ] = $target;
		}

		$time = time();

		foreach ( $this->get_deleted_steps() as $step ) {

			$choice = get_array_var( $choices, $step->get_id(), [] );
			$to     = absint( get_array_var( $choice, 'to' ) );

			$where = [
				'funnel_id'  => $this->get_id(),
				'step_id'    => $step->get_id(),
				'event_type' => Event::FUNNEL,
			];

			$run_time = false;

			if ( get_array_var( $choice, 'action' ) === 'move' && isset( $targets[ $to ] ) ) {

				try {
					// when they were told when, otherwise when the step they're moved to normally runs, from now
					$run_time = absint( get_array_var( $choice, 'time' ) ) ?: $targets[ $to ]->get_run_time();
				} catch ( \Exception $e ) {
					// a step that has no time to run, like a timer set to run on a day that doesn't exist, isn't moved to, the events are cancelled
				}
			}

			if ( $run_time !== false ) {

				// paused events stay paused, they'll run at the new step when the funnel is activated
				foreach ( [ Event::WAITING, Event::PAUSED ] as $status ) {
					event_queue_db()->update( array_merge( $where, [ 'status' => $status ] ), [
						'step_id' => $to,
						'time'    => $run_time,
					] );
				}

				continue;
			}

			foreach ( [ Event::WAITING, Event::PAUSED ] as $status ) {
				event_queue_db()->update( array_merge( $where, [ 'status' => $status ] ), [
					'status'         => Event::CANCELLED,
					'time_scheduled' => $time,
					'error_code'     => 'step_deleted',
					/* translators: %s: the step title */
					'error_message'  => sprintf( __( 'The step "%s" was deleted.', 'groundhogg' ), $step->get_title() ),
				] );
			}

			event_queue_db()->move_events_to_history( array_merge( $where, [ 'status' => Event::CANCELLED ] ), 'AND' );
		}
	}

	/**
	 * Remove steps that were marked deleted while the funnel was inactive, which can't be committed
	 *
	 * @return void
	 */
	public function remove_deleted_steps() {
		foreach ( $this->get_deleted_steps() as $step ) {
			$step->commit(); // removes steps with the deleted status
		}
	}

	/**
	 * Mass update status of steps related to this funnel
	 *
	 * @return bool
	 */
	public function update_step_status() {

		// update inactive steps to active
		if ( $this->is_active() ) {
			return db()->steps->update( [
				'funnel_id'   => $this->get_id(),
				'step_status' => 'inactive'
			], [
				'step_status'    => 'active',
				'date_activated' => ( new DateTimeHelper() )->ymdhis()
			] );
		}

		// only active steps, archived steps aren't part of the flow anymore and must stay archived
		return get_db( 'steps' )->update( [
			'funnel_id'   => $this->get_id(),
			'step_status' => 'active',
		], [
			'step_status' => 'inactive'
		] );
	}

	/**
	 * Pause, unpause, or cancel events based on the current status of the funnel
	 *
	 * @return void
	 */
	public function update_events_from_status() {
		switch ( $this->get_status() ) {
			case 'active':
				$this->unpause_events();
				break;
			case 'inactive':
				$this->pause_events();
				break;
			case 'archived':
				$this->cancel_events();
				break;
		}
	}

	/**
	 * Initialize the levels for the steps
	 *
	 * @param string $branch
	 * @param int    $level
	 *
	 * @return mixed
	 */
	public function set_step_levels( string $branch = 'main', int $level = 1 ) {

		if ( $branch === 'main' && $level === 1 ) {
			Step::increment_step_order( 0 );
		}

		$steps = $this->get_steps();

		$branch_steps = array_filter( $steps, function ( Step $step ) use ( $branch ) {
			return $step->branch_is( $branch );
		} );

		$prev     = null;
		$maxDepth = $level;

		foreach ( $branch_steps as $step ) {

			$step->update_branch_path_in_db(); // do this while we're here

			if ( $step->is_benchmark() ) {
				$step->update( [
					'step_level' => $level,
					'step_order' => Step::increment_step_order()
				] );
				$maxDepth = max( $maxDepth, $this->set_step_levels( "$step->ID", $level + 1 ) );
				$prev     = $step;
				continue;
			}

			if ( $prev && $prev->is_benchmark() ) {
				$level = $maxDepth;
			}

			$step->update( [
				'step_level' => $level,
				'step_order' => Step::increment_step_order()
			] );

			$level ++;

			if ( $step->is_branch_logic() ) {
				$sub_steps = $step->get_sub_steps();
				$branches  = array_unique( wp_list_pluck( $sub_steps, 'branch' ) );
				$maxDepth  = $level;
				foreach ( $branches as $branch ) {
					$maxDepth = max( $maxDepth, $this->set_step_levels( $branch, $level ) );
				}
				$level = $maxDepth;
			}

			$prev = $step;
		}

		return max( $level, $maxDepth );
	}

	/**
	 * Merge step changes into the real data and meta
	 *
	 * @param array $deleted_step_choices see resolve_deleted_step_events()
	 *
	 * @return bool false if the funnel isn't active, so there was nothing to commit
	 */
	public function commit( array $deleted_step_choices = [] ) {

		// can't commit if not active...
		if ( ! $this->is_active() ) {
			return false;
		}

		// before deleted steps are removed, which also removes their events
		$this->resolve_deleted_step_events( $deleted_step_choices );

		$steps = $this->get_real_steps(); // use instead of ::get_steps() to avoid merged changes

		// commit all the step changes
		foreach ( $steps as $step ) {
			$step->commit();
		}

		$this->update_step_status();

		return true;
	}

	/**
	 * Clear any changes and delete inactive steps that may have been added
	 */
	public function uncommit() {

		// can't uncommit if not active...
		if ( ! $this->is_active() ) {
			return;
		}

		$steps = $this->get_real_steps();

		// commit all the step changes
		foreach ( $steps as $step ) {

			// delete any inactive steps
			if ( ! $step->is_active() ) {
				$step->delete_and_commit();
				continue;
			}

			$step->clear_changes();
		}
	}

	/**
	 * Handler to also delete steps
	 *
	 * @return bool
	 */
	public function delete() {

		$this->update( [ 'status' => 'archived' ] );
		$this->update_step_status();
		$this->cancel_events();

		$steps = $this->get_steps();

		// delete all the steps in the funnel as well
		foreach ( $steps as $step ) {
			$step->delete_and_commit();
		}

		return parent::delete();
	}

	/**
	 * Pause or unpause events depending on the status change of the funnel
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$old_status = $this->get_status();
		$updated    = parent::update( $data );
		$new_status = $this->get_status();

		// When the status of the funnel changes we must handle events and steps accordingly
		if ( $new_status !== $old_status ) {
			$this->update_step_status();
			$this->update_events_from_status();
		}

		return $updated;
	}

	/**
	 * Get all the conversion steps in this funnel
	 *
	 * @return Step[]
	 */
	public function get_conversion_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_conversion();
		} );
	}

	/**
	 * Get the ID of the conversion steps...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int[]
	 */
	public function get_conversion_step_ids() {
		return get_object_ids( $this->get_conversion_steps() );
	}

	/**
	 * Get the ID of the conversion step...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int
	 */
	public function legacy_conversion_step_id() {
		$conversion_step_id = absint( $this->conversion_step );

		if ( ! $conversion_step_id ) {
			$steps = $this->get_steps( [
				'step_group' => Step::BENCHMARK,
			] );

			$last = array_pop( $steps );

			if ( $last ) {
				return $last->get_id();
			}

			return 0;

		}

		return $conversion_step_id;
	}

	public function get_first_action_id() {
		$actions = $this->get_step_ids( [
			'step_group' => Step::ACTION,
		] );

		return array_shift( $actions );
	}

	/**
	 * @return int
	 */
	public function get_first_step_id() {
		$actions = $this->get_step_ids( [
			'step_group' => Step::BENCHMARK,
		] );

		return array_shift( $actions );
	}

	/**
	 * Get entry steps
	 *
	 * @return Step[]
	 */
	public function get_entry_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_starting() || $step->is_entry();
		} );
	}

	/**
	 * Get IDs of entry steps
	 *
	 * @return array
	 */
	public function get_entry_step_ids() {
		return get_object_ids( $this->get_entry_steps() );
	}

	/**
	 * All send_email steps
	 *
	 * @return Step[]
	 */
	public function get_email_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->type_is( 'send_email' );
		} );
	}

	/**
	 * Retrieve all email assets within a funnel
	 *
	 * @return Email[]
	 */
	public function get_emails() {
		return array_filter( array_map( function ( Step $step ) {

			$email_id = $step->get_meta( 'email_id' );
			if ( ! $email_id ) {
				return false;
			}

			return new Email( $email_id );

		}, $this->get_email_steps() ) );
	}

	/**
	 * Get the step IDs associated with this funnel
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function get_step_ids( $query = [] ) {
		return get_object_ids( $this->get_steps( $query ) );
	}

	/**
	 * Get the total number of steps in the funnel
	 *
	 * @return array|bool|int|object|null
	 */
	public function get_num_steps() {

		if ( $this->is_editing() ) {
			return count( $this->get_steps() );
		}

		return db()->steps->count( [
			'funnel_id' => $this->get_id(),
		] );
	}

	public function has_errors() {

		$has_errors = array_any( $this->get_steps(), function ( Step $step ) {
			$step->get_step_element()->validate_settings( $step );

			return $step->has_errors() || $step->get_step_element()->has_errors();
		} );

		if ( $has_errors ) {
			return true;
		}

		return parent::has_errors();
	}

	/**
	 * Get a bunch of steps
	 *
	 * @param array $query
	 *
	 * @return Step[]
	 */
	public function get_steps( $query = [] ) {

		$query = wp_parse_args( $query, [
			'funnel_id'   => $this->get_id(),
			'orderby'     => 'step_order',
			'order'       => 'ASC',
			'step_status' => [ '!=', 'archived' ], // archived steps are only kept for their history
		] );

		// if not editing, only active steps should be included...
		// inactive funnels aren't filtered, so they still include steps soft deleted while inactive until activation removes them
		if ( ! $this->is_editing() && $this->is_active() ) {
			$query['step_status'] = 'active';
		}

//		$last_changed = db()->steps->cache_get_last_changed();
//		$cache_key    = "$this->ID:steps:$last_changed:" . md5serialize( $query );
//		$steps        = wp_cache_get( $cache_key, db()->steps->get_cache_group(), false, $found );
//
//        // make sure all items in array are also steps
//		if ( $found && is_array( $steps ) && array_all( $steps, function ( $step ) {
//				return is_a( $step, Step::class );
//			} ) ) {
//
//			return $steps;
//		}

		$steps = $this->get_steps_db()->query( $query );
		$steps = array_map_to_step( $steps );

		if ( $this->is_editing() ) {

			foreach ( $steps as $step ) {
				$step->merge_changes();
			}

			// filter out "deleted" steps with the status as deleted in their changes, or on the row for inactive funnels
			$steps = array_filter( $steps, function ( Step $step ) {
				return $step->step_status !== 'deleted';
			} );

			// resort because of changes
			usort( $steps, function ( Step $a, Step $b ) {
				return $a->get_order() - $b->get_order();
			} );
		}

//		wp_cache_set( $cache_key, $steps, db()->steps->get_cache_group(), MINUTE_IN_SECONDS );

		return $steps;
	}

	/**
	 * Same as get_steps, but without the is_editing() BS
	 * @return Step[]
	 */
	public function get_real_steps( $query = [] ) {

		$query = wp_parse_args( $query, [
			'funnel_id'   => $this->get_id(),
			'orderby'     => 'step_order',
			'order'       => 'ASC',
			'step_status' => [ '!=', 'archived' ], // archived steps are only kept for their history
		] );

		$steps = $this->get_steps_db()->query( $query );
		$steps = array_map_to_step( $steps );

		return $steps;
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function legacy_export() {
		$export          = [];
		$export['title'] = $this->get_title();
		$export['steps'] = [];

		$steps = $this->get_steps();

		if ( ! $steps ) {
			return false;
		}

		foreach ( $steps as $i => $step ) {

			$export['steps'][ $i ]          = [];
			$export['steps'][ $i ]['title'] = $step->get_title();
			$export['steps'][ $i ]['group'] = $step->get_group();
			$export['steps'][ $i ]['order'] = $step->get_order();
			$export['steps'][ $i ]['type']  = $step->get_type();
			$export['steps'][ $i ]['meta']  = $step->get_meta();
			$export['steps'][ $i ]['args']  = $step->export();

		}

		return apply_filters( 'groundhogg/funnel/export', $export, $this );
	}

	/**
	 * How many times each funnel was put in editing mode by start_editing(), by funnel ID.
	 * Static because steps load their own Funnel instance.
	 *
	 * @var int[]
	 */
	protected static $editing = [];

	/**
	 * Treat this funnel as open in the flow editor, so get_steps() includes inactive steps with their changes merged,
	 * the same as the editor sees them. For editing a funnel from outside the editor, like abilities.
	 * Calls can be nested, so every call must be paired with stop_editing().
	 *
	 * @return void
	 */
	public function start_editing() {
		$id = $this->get_id();

		self::$editing[ $id ] = ( self::$editing[ $id ] ?? 0 ) + 1;
	}

	/**
	 * Undo start_editing()
	 *
	 * @return void
	 */
	public function stop_editing() {
		$id = $this->get_id();

		if ( empty( self::$editing[ $id ] ) ) {
			return;
		}

		self::$editing[ $id ] --;

		if ( ! self::$editing[ $id ] ) {
			unset( self::$editing[ $id ] );
		}
	}

	/**
	 * Run a callback with the funnel in editing mode
	 *
	 * @param callable $callback
	 *
	 * @return mixed whatever the callback returns
	 */
	public function while_editing( callable $callback ) {
		$this->start_editing();

		try {
			return $callback();
		} finally {
			$this->stop_editing();
		}
	}

	/**
	 * Snapshot the steps as the editor sees them, with their changes merged, for restore()
	 * Same shape as the steps the flow editor keeps for undo/redo.
	 *
	 * @return array[] [ [ 'ID' => int, 'data' => array, 'meta' => array ], ... ]
	 */
	public function snapshot() {
		return $this->while_editing( function () {
			return array_values( array_map( function ( Step $step ) {
				return [
					'ID'   => $step->get_id(),
					'data' => $step->get_data(),
					'meta' => $step->get_meta(),
				];
			}, $this->get_steps() ) );
		} );
	}

	/**
	 * Restore the steps to a snapshot from snapshot() or the flow editor's undo/redo.
	 * Steps added since are soft deleted, and deleted steps come back by updating their row,
	 * which is why Step::delete() is a soft delete until the changes are committed.
	 * On an active funnel this is staged in the changes like any other edit.
	 *
	 * @param array[] $snapshot
	 *
	 * @return void
	 */
	public function restore( array $snapshot ) {
		$this->while_editing( function () use ( $snapshot ) {

			$keep_step_ids = wp_parse_id_list( wp_list_pluck( $snapshot, 'ID' ) );

			// delete steps that were added that aren't in the snapshot
			foreach ( $this->get_steps() as $step ) {
				if ( ! in_array( $step->ID, $keep_step_ids ) ) {
					$step->delete();
				}
			}

			// update current steps with data from the snapshot
			foreach ( $snapshot as $step_state ) {

				$step = new Step( absint( $step_state['ID'] ) );
				if ( $step->exists() ) {
					$step->update( $step_state['data'] );
				} else {
					$step->create( $step_state['data'] );
				}

				$step->update_meta( $step_state['meta'] );
			}
		} );
	}

	/**
	 * Back up the steps exactly as they're stored, their rows (including staged changes) and meta, for rollback().
	 * Unlike snapshot(), which is the editor's view of the steps for undo/redo.
	 *
	 * @return array[] step ID => [ 'row' => array, 'meta' => array ]
	 */
	public function backup() {

		$backup = [];

		foreach ( $this->get_steps_db()->query( [ 'funnel_id' => $this->get_id() ] ) as $row ) {
			$backup[ absint( $row->ID ) ] = [
				'row'  => (array) $row,
				'meta' => get_db( 'stepmeta' )->get_meta( $row->ID ) ?: [],
			];
		}

		return $backup;
	}

	/**
	 * Put the steps back exactly as they were in backup(), deleting any steps added since.
	 * Writes to the tables directly, so nothing is staged, cascaded, or hooked into like editing the steps would.
	 *
	 * @param array[] $backup from backup()
	 *
	 * @return void
	 */
	public function rollback( array $backup ) {

		$steps_db = $this->get_steps_db();
		$meta_db  = get_db( 'stepmeta' );

		// steps added since, their meta goes with them
		foreach ( $steps_db->query( [ 'funnel_id' => $this->get_id() ] ) as $row ) {
			if ( ! isset( $backup[ absint( $row->ID ) ] ) ) {
				$steps_db->delete( absint( $row->ID ) );
			}
		}

		foreach ( $backup as $step_id => $step ) {

			// the row's values are already as they're stored
			$steps_db->update( $step_id, $step['row'] );

			$meta = $meta_db->get_meta( $step_id ) ?: [];

			foreach ( array_keys( $meta ) as $key ) {
				$meta_db->delete_meta( $step_id, $key );
			}

			foreach ( $step['meta'] as $key => $values ) {
				foreach ( (array) $values as $value ) {
					$meta_db->add_meta( $step_id, $key, maybe_unserialize( $value ) );
				}
			}
		}
	}

	public function is_editing() {

		if ( ! empty( self::$editing[ $this->get_id() ] ) ) {
			return true;
		}

		if ( wp_doing_ajax() || wp_is_serving_rest_request() ) {
			wp_parse_str( wp_parse_url( wp_get_referer(), PHP_URL_QUERY ), $params );

			if ( get_array_var( $params, 'page' ) === 'gh_funnels'
			     && get_array_var( $params, 'action' ) === 'edit'
			     && isset_not_empty( $params, 'funnel' )
			     && absint( $params['funnel'] ) === $this->get_id()
			) {
				return true;
			}
		}

		return get_url_var( 'page' ) === 'gh_funnels'
		       && get_url_var( 'action' ) === 'edit'
		       && absint( get_url_var( 'funnel' ) ) === $this->get_id();
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'steps'     => $this->get_steps(),
			'campaigns' => $this->get_related_objects( 'campaign' ),
			'links'     => [
				'export' => $this->export_url(),
				'report' => admin_page_url( 'gh_reporting', [
					'tab'         => 'v3',
					'currentPage' => 'funnels',
					'params'      => [ 'funnel' => $this->get_id() ],
				] ),
			]
		] );
	}

	/**
	 * A funnel has changes if any of its steps have changes, the step is inactive or deleted
	 *
	 * @return bool
	 */
	public function has_changes() {
		return array_any( $this->get_steps(), function ( Step $step ) {
			return $step->has_changes() || in_array( $step->step_status, [ 'inactive', 'deleted' ] );
		} ) || ! empty( $this->get_deleted_steps() ); // while editing get_steps() leaves out deleted steps
	}

	protected function sanitize_meta( $key, $value ) {
		switch ( $key ) {
			case 'description':
				$value = sanitize_textarea_field( $value );
				break;
			case 'replacements':
				$value = array_map_keys( $value, 'sanitize_key' );
				$value = array_map( '\Groundhogg\email_kses', $value );
				break;
		}

		return $value;
	}

	/**
	 * Return wrapper function.
	 *
	 * @return array|bool
	 */
	public function export() {
		// only export real steps
		$json = $this->get_as_array();

		return array_merge( $json, [
			'steps' => $this->get_real_steps()
		] );
	}

	/**
	 * The export URL
	 *
	 * @return string
	 */
	public function export_url() {
		return managed_page_url( sprintf( 'funnels/export/%s/', Plugin::$instance->utils->encrypt_decrypt( $this->get_id() ) ) );
	}

	/**
	 * Import a funnel
	 *
	 * @return bool|int|\WP_Error
	 */
	public function import( $data ) {

		// legacy import
		if ( isset_not_empty( $data, 'title' ) ) {
			return $this->legacy_import( json_decode( json_encode( $data ), true ) );
		}

		/**
		 * Relly just here as a flag for importing, but theoretically you can modify the data directly
		 *
		 * @param array $data the import JSON
		 */
		do_action_ref_array( 'groundhogg/funnel/import/before', [ &$data ] );

		$this->setup_template_data( $data );

		$this->create( [
			'title'  => $this->get_title(),
			'author' => get_current_user_id(),
			'status' => 'inactive'
		] );

		if ( ! $this->exists() ) {
			return new \WP_Error( 'error', 'Unable to create funnel.' );
		}

		/**
		 * @var $steps Step[]
		 */
		$steps = [];

		// settings use their import sanitizers while the steps are imported
		Step::start_importing();

		try {

			foreach ( $this->steps as $i => $_step ) {

				$_step = (object) $_step;

				$step_data                = (array) $_step->data;
				$step_data['funnel_id']   = $this->get_id();
				$step_data['step_status'] = 'inactive'; // force status to inactive

				$step = new Step();
				$step->create( $step_data );

				$metadata   = json_decode( json_encode( $_step->meta ), true );
				$importdata = json_decode( json_encode( $_step->export ), true );

				$step->update_meta( $metadata );
				$step->import( $importdata );

				// Save the original ID from the donor funnel
				$step->update_meta( 'imported_step_id', $_step->ID );

				$steps[ $i ] = $step;
			}

			// Re-run through the steps and perform cleanup actions...
			foreach ( $steps as $step ) {
				$step->post_import();
			}

		} finally {
			Step::stop_importing();
		}

		// don't need imported_step_id forever, just get rid of it, only for these steps in case another import is running
		foreach ( $steps as $step ) {
			$step->delete_meta( 'imported_step_id' );
		}

		do_action( 'groundhogg/funnel/import/after', $this, $data );

		return $this->get_id();
	}

	/**
	 * Import a funnel
	 *
	 * @param $template
	 *
	 * @return bool|int|\WP_Error
	 */
	public function legacy_import( $template ) {

		if ( is_string( $template ) ) {
			$template = json_decode( $template, true );
		}

		if ( ! is_array( $template ) || empty( $template ) ) {
			return new \WP_Error( 'invalid_funnel', 'Invalid funnel markup.' );
		}

		$title = $template['title'];

		$args = [
			'title'  => $title,
			'status' => 'inactive',
			'author' => get_current_user_id()
		];

		$funnel_id = $this->create( $args );

		if ( ! $funnel_id ) {
			return new \WP_Error( 'db_error', 'Could not add to the DB.' );
		}

		$steps = $template['steps'];

		foreach ( $steps as $i => $step_args ) {

			$step_title = $step_args['title'];
			$step_group = $step_args['group'];
			$step_type  = $step_args['type'];

			$args = array(
				'funnel_id'  => $funnel_id,
				'step_title' => $step_title,
				'step_group' => $step_group,
				'step_type'  => $step_type,
				'step_order' => $i + 1,
			);

			$step = new Step( $args );

			if ( ! $step->exists() ) {
				continue;
			}

			$step_meta = $step_args['meta'];

			foreach ( $step_meta as $key => $value ) {
				$step->update_meta( $key, $value );
			}

			$import_args = $step_args['args'];

			$step->import( $import_args );

		}

		return $funnel_id;
	}

	/**
	 * @return false|string
	 */
	public function get_as_json() {
		return wp_json_encode( $this->get_as_array() );
	}

	/**
	 * Add a step to the funnel
	 *
	 * @param $args array a list of args for the step
	 *
	 * @return Step|false
	 */
	public function add_step( $args ) {

		$args = wp_parse_args( $args, [
			'funnel_id'  => $this->get_id(),
			'step_order' => count( $this->get_step_ids() ) + 1,
			'meta'       => [],
		] );

		$step = new Step( $args );

		if ( ! $step->exists() ) {
			return false;
		}

		foreach ( $args['meta'] as $key => $value ) {
			$step->update_meta( $key, $value );
		}

		return $step;
	}
}
