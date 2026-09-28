<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Throwable;
use WP_Error;

/**
 * Edits a flow with a list of operations - add, update, move, and delete steps - applied in order.
 * The engine behind groundhogg/edit-flow and the flow editor's saves, see apply_all_or_nothing().
 *
 * Works like the flow editor always has: on an active flow every change is staged (new steps are inactive, edits go
 * into the step's changes, deletes are soft) until the changes are published. On an inactive flow edits are written
 * directly, and deleted steps are removed when it's activated.
 *
 * Steps are referenced by their real id, or by the local `id` of a step added earlier in the same call. Settings
 * that reference other steps take either too, which is why every existing step is declared to the Step_Tree_Builder
 * by its real id.
 *
 * Positions are kept in a model of each branch's ordered step ids while the operations run, written to the steps'
 * branch and step_order, and then Funnel::set_step_levels() derives everything else.
 *
 * The flow editor can also add steps of any registered type, set settings as they're stored, and restore deleted
 * steps, for its undo and redo.
 */
class Flow_Operations {

	/**
	 * @var Funnel
	 */
	protected $funnel;

	/**
	 * Whether the operations come from the flow editor
	 *
	 * @var bool
	 */
	protected $editor;

	/**
	 * @var Step_Tree_Builder
	 */
	protected $builder;

	/**
	 * Each branch's step ids in order, branch => int[]
	 *
	 * @var array
	 */
	protected $branches = [];

	/**
	 * The branch each step is in, step id => branch
	 *
	 * @var array
	 */
	protected $step_branch = [];

	/**
	 * Settings to write after the step order is final, [ [ 'step_id' => int, 'settings' => array ], ... ]
	 *
	 * @var array
	 */
	protected $deferred = [];

	/**
	 * Steps deleted by the operations, step id => Step
	 *
	 * @var Step[]
	 */
	protected $deleted = [];

	/**
	 * @param Funnel $funnel
	 * @param bool   $editor whether the operations come from the flow editor
	 */
	public function __construct( Funnel $funnel, bool $editor = false ) {
		$this->funnel = $funnel;
		$this->editor = $editor;
	}

	/**
	 * Steps deleted by the operations
	 *
	 * @return Step[] step id => Step
	 */
	public function get_deleted(): array {
		return $this->deleted;
	}

	/**
	 * Apply the operations in editing mode, and if any fails put the steps back exactly as they were
	 *
	 * @param array $operations
	 *
	 * @return array|WP_Error the steps each add created
	 */
	public function apply_all_or_nothing( array $operations ) {

		return $this->funnel->while_editing( function () use ( $operations ) {

			$backup = $this->funnel->backup();

			try {
				$result = $this->apply( $operations );
			} catch ( Throwable $e ) {
				$result = new WP_Error( 'groundhogg_flow_edit_failed', $e->getMessage() );
			}

			if ( is_wp_error( $result ) ) {
				$this->funnel->rollback( $backup );
			}

			return $result;
		} );
	}

	/**
	 * Apply the operations, in order. Stops at the first one that fails, the caller rolls back what was done.
	 *
	 * @param array $operations
	 *
	 * @return array|WP_Error the steps each add created
	 */
	public function apply( array $operations ) {

		$funnel = $this->funnel;

		$this->branches    = [];
		$this->step_branch = [];
		$this->deferred    = [];
		$this->deleted     = [];

		$this->builder = new Step_Tree_Builder( $funnel );
		$this->builder->allow_any_type( $this->editor );

		foreach ( $funnel->get_steps() as $step ) {
			$this->place( $step->get_id(), $step->branch );
			$this->builder->declare( (string) $step->get_id(), $step->get_id(), $step->get_type() );
		}

		$added = [];

		foreach ( $operations as $i => $operation ) {

			$operation = (array) $operation;
			$op        = $operation['op'] ?? '';

			switch ( $op ) {
				case 'add':
					$result = $this->add( $operation );
					break;
				case 'update':
					$result = $this->update( $operation );
					break;
				case 'move':
					$result = $this->move( $operation );
					break;
				case 'delete':
					$result = $this->delete( $operation );
					break;
				case 'restore':
					// only the editor restores
					if ( $this->editor ) {
						$result = $this->restore( $operation );
						break;
					}
				default:
					/* translators: %s: the operation */
					$result = new WP_Error( 'groundhogg_invalid_operation', sprintf( __( '"%s" is not an operation.', 'groundhogg' ), $op ) );
			}

			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					$result->get_error_code(),
					/* translators: 1: the operation's index, 2: the operation, 3: the error */
					sprintf( __( 'operations[%1$d] (%2$s): %3$s', 'groundhogg' ), $i, $op, $result->get_error_message() ),
					[ 'operation' => $i ]
				);
			}

			if ( $op === 'add' ) {
				$added[] = $result;
			}
		}

		$this->write_layout();

		$funnel->set_step_levels();

		$this->builder->apply_deferred_settings();

		foreach ( $this->deferred as $entry ) {
			// a fresh instance, for the final step order, see Step_Tree_Builder::apply_deferred_settings()
			( new Step( $entry['step_id'] ) )->update_meta( $entry['settings'] );
		}

		return $added;
	}

	/**
	 * Add steps
	 *
	 * @param array $operation
	 *
	 * @return array|WP_Error the created step nodes
	 */
	protected function add( array $operation ) {

		if ( empty( $operation['steps'] ) || ! is_array( $operation['steps'] ) ) {
			return new WP_Error( 'groundhogg_missing_steps', __( '`steps` is required.', 'groundhogg' ) );
		}

		$at = $this->resolve_position( $operation['at'] ?? null );

		if ( is_wp_error( $at ) ) {
			return $at;
		}

		[ $branch, $index ] = $at;

		$nodes = $this->builder->build( $operation['steps'], $branch );

		if ( is_wp_error( $nodes ) ) {
			return $nodes;
		}

		array_splice( $this->branches[ $branch ], $index, 0, wp_list_pluck( $nodes, 'id' ) );

		foreach ( $nodes as $node ) {
			$this->step_branch[ $node['id'] ] = $branch;
			$this->place_branches( $node );
		}

		return $nodes;
	}

	/**
	 * Add the steps in a created node's branches to the model
	 *
	 * @param array $node
	 */
	protected function place_branches( array $node ) {

		// later operations can refer to it by its real id
		$this->builder->declare( (string) $node['id'], $node['id'], $node['type'] );

		foreach ( $node['branches'] ?? [] as $key => $sub_nodes ) {
			foreach ( $sub_nodes as $sub_node ) {
				$this->place( $sub_node['id'], "{$node['id']}-$key" );
				$this->place_branches( $sub_node );
			}
		}
	}

	/**
	 * Update a step's title, settings, and benchmark flags
	 *
	 * @param array $operation
	 *
	 * @return true|WP_Error
	 */
	protected function update( array $operation ) {

		// the editor sets settings as they're stored, like undoing an edit, and the settings of locked steps can change
		if ( $this->editor && ( isset( $operation['meta'] ) || ( isset( $operation['title'] ) && ! isset( $operation['settings'] ) ) ) ) {
			return $this->set_stored( $operation );
		}

		$step = $this->resolve_step( $operation['step'] ?? null, true );

		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step->merge_changes();

		$patch = [];

		if ( isset( $operation['settings'] ) ) {

			$type  = $step->get_type();
			$input = (array) $operation['settings'];

			$current = Step_Type_Schema::export_settings( $step );

			if ( $current === null ) {
				/* translators: %s: the step type */
				return new WP_Error( 'groundhogg_step_not_editable', sprintf( __( 'The settings of "%s" steps can\'t be changed here, use the flow editor.', 'groundhogg' ), $type ) );
			}

			$merged = $this->merge_settings( $current, $input );

			// a branch that goes away can't take its steps with it
			$removed = $this->get_removed_branches_with_steps( $step, $merged );

			if ( ! empty( $removed ) ) {
				return new WP_Error(
					'groundhogg_branch_has_steps',
					/* translators: %s: the branch keys */
					sprintf( __( 'These branches still have steps, move or delete them first: %s.', 'groundhogg' ), implode( ', ', $removed ) )
				);
			}

			// resolve the whole settings, so settings that are resolved together (web_form's form) keep what isn't changed
			$resolved = Step_Type_Schema::resolve_settings( $type, $merged, $this->builder->get_declared() );

			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}

			$patch = $resolved['settings'];

			if ( ! empty( $resolved['deferred_settings'] ) ) {
				$this->deferred[] = [ 'step_id' => $step->get_id(), 'settings' => $resolved['deferred_settings'] ];
			}
		}

		$title = isset( $operation['title'] ) ? sanitize_text_field( $operation['title'] ) : '';

		if ( $title ) {
			$patch['step_title'] = $title;
		}

		foreach ( [ 'is_entry', 'is_conversion', 'can_passthru' ] as $flag ) {
			if ( isset( $operation[ $flag ] ) ) {

				if ( ! $step->is_benchmark() ) {
					/* translators: %s: the flag */
					return new WP_Error( 'groundhogg_not_a_benchmark', sprintf( __( '`%s` is only for benchmarks.', 'groundhogg' ), $flag ) );
				}

				$patch[ $flag ] = (bool) $operation[ $flag ];
			}
		}

		if ( empty( $patch ) ) {
			return new WP_Error( 'groundhogg_nothing_to_update', __( 'Pass `title`, `settings`, or a benchmark flag to change.', 'groundhogg' ) );
		}

		$title = $title ?: $step->get_title();

		$step->update_settings( $patch );

		// saving can replace the title with one generated from the settings, which some step types (delay_timer)
		// generate from a preview the flow editor posts, so without it they'd be wrong. Titles only change when asked.
		$saved = new Step( $step->get_id() );
		$saved->merge_changes();

		if ( $saved->get_title() !== $title ) {
			$saved->update( [ 'step_title' => $title ] );
		}

		return true;
	}

	/**
	 * Merge the settings to change into a step's current settings.
	 * A `branches` map (split_path, weighted_distribution) is merged branch by branch, so only the branches given
	 * change, and null removes a branch. A condition replaces the stored filters for its side, for if_else and
	 * for each branch.
	 *
	 * @param array $current from Step_Type_Schema::export_settings()
	 * @param array $input   the settings to change
	 *
	 * @return array
	 */
	protected function merge_settings( array $current, array $input ): array {

		foreach ( [ 'include', 'exclude' ] as $which ) {
			if ( isset( $input[ "{$which}_condition" ] ) ) {
				unset( $current[ "{$which}_filters" ] );
			}
		}

		if ( isset( $input['branches'] ) && is_array( $input['branches'] ) && is_array( $current['branches'] ?? null ) ) {

			$branches = $current['branches'];

			foreach ( $input['branches'] as $key => $branch ) {

				if ( $branch === null ) {
					unset( $branches[ $key ] );
					continue;
				}

				$branches[ $key ] = is_array( $branch ) && is_array( $branches[ $key ] ?? null )
					? $this->merge_settings( $branches[ $key ], $branch )
					: $branch;
			}

			$input['branches'] = $branches;
		}

		return array_merge( $current, $input );
	}

	/**
	 * Branches of a step that would go away with new settings, but still have steps
	 *
	 * @param Step  $step
	 * @param array $settings the new settings
	 *
	 * @return string[] the branch keys
	 */
	protected function get_removed_branches_with_steps( Step $step, array $settings ): array {

		if ( ! $step->is_branch_logic() ) {
			return [];
		}

		$keys = Step_Type_Schema::branch_keys( $step->get_type(), $settings );

		// branches of types that aren't described can't be checked
		if ( empty( $keys ) ) {
			return [];
		}

		$removed = [];

		foreach ( $this->branches as $branch => $ids ) {

			$branch = (string) $branch;

			if ( empty( $ids ) || ! str_starts_with( $branch, "$step->ID-" ) ) {
				continue;
			}

			$key = substr( $branch, strlen( "$step->ID-" ) );

			if ( ! in_array( $key, $keys, true ) ) {
				$removed[] = $key;
			}
		}

		return $removed;
	}

	/**
	 * Move a step, and the steps in its branches with it
	 *
	 * @param array $operation
	 *
	 * @return true|WP_Error
	 */
	protected function move( array $operation ) {

		$step = $this->resolve_step( $operation['step'] ?? null, true );

		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$id = $step->get_id();

		// take it out first, so positions relative to its neighbours are where they'll be
		$from       = $this->step_branch[ $id ];
		$from_index = array_search( $id, $this->branches[ $from ], true );
		array_splice( $this->branches[ $from ], $from_index, 1 );

		$at = $this->resolve_position( $operation['at'] ?? null, $id );

		if ( is_wp_error( $at ) ) {
			array_splice( $this->branches[ $from ], $from_index, 0, [ $id ] );

			return $at;
		}

		[ $branch, $index ] = $at;

		array_splice( $this->branches[ $branch ], $index, 0, [ $id ] );
		$this->step_branch[ $id ] = $branch;

		return true;
	}

	/**
	 * Soft delete a step, and the steps in its branches
	 *
	 * @param array $operation
	 *
	 * @return true|WP_Error
	 */
	protected function delete( array $operation ) {

		$step = $this->resolve_step( $operation['step'] ?? null, true );

		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$ids = $this->descendants( $step->get_id() );

		foreach ( $ids as $id ) {
			if ( $id !== $step->get_id() && ( new Step( $id ) )->is_locked() ) {
				return new WP_Error( 'groundhogg_step_locked', __( 'A step in its branches is locked.', 'groundhogg' ) );
			}
		}

		// steps other steps point at can't be deleted, see referenced_by in groundhogg/get-flow
		$can_delete = $this->funnel->can_delete_steps( $ids );

		if ( is_wp_error( $can_delete ) ) {
			return $can_delete;
		}

		// the step types' own delete handlers cascade through the stored branches, so they must be up to date
		$this->write_layout();

		foreach ( array_reverse( $ids ) as $id ) {

			$to_delete = new Step( $id );
			$to_delete->merge_changes();

			if ( $to_delete->step_status !== 'deleted' ) {
				$to_delete->delete();
			}

			$this->deleted[ $id ] = $to_delete;
			$this->unplace( $id );
		}

		return true;
	}

	/**
	 * Set a step's settings and title as they're stored, without the step type's save handlers.
	 * For the editor's undo and redo, which put back exactly what was there.
	 *
	 * @param array $operation `meta` setting => value, null deletes it, and `title`
	 *
	 * @return true|WP_Error
	 */
	protected function set_stored( array $operation ) {

		$step = $this->resolve_step( $operation['step'] ?? null );

		if ( is_wp_error( $step ) ) {
			return $step;
		}

		foreach ( (array) ( $operation['meta'] ?? [] ) as $key => $value ) {

			$key = sanitize_key( $key );

			if ( $value === null ) {
				$step->delete_meta( $key );
				continue;
			}

			$step->update_meta( $key, $value );
		}

		if ( isset( $operation['title'] ) ) {
			// sanitized as a column, which allows the formatting generated titles have
			$step->update( [ 'step_title' => (string) $operation['title'] ] );
		}

		return true;
	}

	/**
	 * Bring back a deleted step, and the steps deleted with it, for the editor's undo.
	 * Deletes are soft until the changes are published, so the steps keep their IDs, settings, and history.
	 *
	 * @param array $operation `step`, `at` where to put it, and `steps` the steps in its branches that were deleted with it
	 *
	 * @return true|WP_Error
	 */
	protected function restore( array $operation ) {

		$ref      = $operation['step'] ?? null;
		$declared = $this->builder->get_declared();
		$key      = sanitize_key( (string) $ref );
		$id       = isset( $declared[ $key ] ) ? absint( $declared[ $key ]['id'] ) : absint( $ref );

		$ids = array_values( array_unique( array_merge( [ $id ], wp_parse_id_list( $operation['steps'] ?? [] ) ) ) );

		$steps = [];

		foreach ( $ids as $step_id ) {

			$step = new Step( $step_id );
			$step->merge_changes();

			if ( ! $step->exists() || $step->get_funnel_id() !== $this->funnel->get_id() || $step->step_status !== 'deleted' || isset( $this->step_branch[ $step_id ] ) ) {
				/* translators: %s: the step reference */
				return new WP_Error( 'groundhogg_step_not_deleted', sprintf( __( 'Step "%s" isn\'t a deleted step in this flow.', 'groundhogg' ), $step_id ) );
			}

			$steps[ $step_id ] = $step;
		}

		$at = $this->resolve_position( $operation['at'] ?? null );

		if ( is_wp_error( $at ) ) {
			return $at;
		}

		foreach ( $steps as $step_id => $step ) {

			// the stored row, without the staged delete
			$stored = new Step( $step_id );

			// inactive steps are deleted on the row, active ones in their staged changes
			$stored->update( [ 'step_status' => $stored->step_status === 'deleted' ? 'inactive' : $stored->step_status ] );

			unset( $this->deleted[ $step_id ] );
			$this->builder->declare( (string) $step_id, $step_id, $step->get_type() );
		}

		[ $branch, $index ] = $at;

		array_splice( $this->branches[ $branch ], $index, 0, [ $id ] );
		$this->step_branch[ $id ] = $branch;

		// the rest go back in their branches, in their order
		$rest = array_diff_key( $steps, [ $id => true ] );

		uasort( $rest, function ( Step $a, Step $b ) {
			return $a->get_order() - $b->get_order();
		} );

		foreach ( $rest as $step_id => $step ) {
			$this->place( $step_id, $step->branch );
		}

		return true;
	}

	/**
	 * The real IDs of the steps added by the operations, by the local `id` they were given
	 *
	 * @param array $added the steps each add created, from apply()
	 *
	 * @return array local id => real id
	 */
	public static function get_added_ids( array $added ): array {

		$ids = [];

		$collect = function ( array $nodes ) use ( &$collect, &$ids ) {
			foreach ( $nodes as $node ) {
				if ( ! empty( $node['local_id'] ) ) {
					$ids[ $node['local_id'] ] = $node['id'];
				}
				foreach ( $node['branches'] ?? [] as $sub_nodes ) {
					$collect( $sub_nodes );
				}
			}
		};

		foreach ( $added as $nodes ) {
			$collect( $nodes );
		}

		return $ids;
	}

	/**
	 * A step and every step in its branches, parents first
	 *
	 * @param int $id
	 *
	 * @return int[]
	 */
	protected function descendants( int $id ) {

		$ids = [ $id ];

		foreach ( $this->branches as $branch => $step_ids ) {
			if ( $this->get_branch_owner( (string) $branch ) === $id ) {
				foreach ( $step_ids as $step_id ) {
					array_push( $ids, ...$this->descendants( $step_id ) );
				}
			}
		}

		return $ids;
	}

	/**
	 * Resolve a reference to a step still in the flow
	 *
	 * @param int|string $ref
	 * @param bool       $not_locked refuse locked steps
	 *
	 * @return Step|WP_Error
	 */
	protected function resolve_step( $ref, bool $not_locked = false ) {

		if ( $ref === null || $ref === '' ) {
			return new WP_Error( 'groundhogg_missing_step', __( '`step` is required.', 'groundhogg' ) );
		}

		$declared = $this->builder->get_declared();
		$key      = sanitize_key( (string) $ref );
		$id       = isset( $declared[ $key ] ) ? absint( $declared[ $key ]['id'] ) : 0;

		if ( ! $id || ! isset( $this->step_branch[ $id ] ) ) {
			/* translators: %s: the step reference */
			return new WP_Error( 'groundhogg_step_not_found', sprintf( __( 'Step "%s" isn\'t in this flow.', 'groundhogg' ), $ref ) );
		}

		$step = new Step( $id );

		if ( $not_locked && $step->is_locked() ) {
			/* translators: %s: the step reference */
			return new WP_Error( 'groundhogg_step_locked', sprintf( __( 'Step "%s" is locked in the flow editor.', 'groundhogg' ), $ref ) );
		}

		return $step;
	}

	/**
	 * Resolve a position to a branch and an index in it
	 *
	 * @param array|null $at
	 * @param int        $moving the step being moved, which can't go into its own branches
	 *
	 * @return array|WP_Error [ branch, index ]
	 */
	protected function resolve_position( $at, int $moving = 0 ) {

		$at = (array) $at;

		$given = array_intersect( [ 'after', 'before', 'branch_of' ], array_keys( $at ) );

		if ( count( $given ) > 1 ) {
			return new WP_Error( 'groundhogg_invalid_position', __( '`at` must have only one of after, before, or branch_of.', 'groundhogg' ) );
		}

		if ( isset( $at['after'] ) || isset( $at['before'] ) ) {

			$step = $this->resolve_step( $at['after'] ?? $at['before'] );

			if ( is_wp_error( $step ) ) {
				return $step;
			}

			$branch = $this->step_branch[ $step->get_id() ];
			$index  = array_search( $step->get_id(), $this->branches[ $branch ], true ) + ( isset( $at['after'] ) ? 1 : 0 );

		} else if ( isset( $at['branch_of'] ) ) {

			$owner = $this->resolve_step( $at['branch_of'] );

			if ( is_wp_error( $owner ) ) {
				return $owner;
			}

			$branch = $this->get_branch( $owner, (string) ( $at['branch'] ?? '' ) );

			if ( is_wp_error( $branch ) ) {
				return $branch;
			}

			$index = ( $at['position'] ?? 'end' ) === 'start' ? 0 : count( $this->branches[ $branch ] ?? [] );

		} else if ( ( $at['branch'] ?? '' ) === 'main' ) {

			$branch = 'main';
			$index  = ( $at['position'] ?? 'end' ) === 'start' ? 0 : count( $this->branches['main'] ?? [] );

		} else {
			return new WP_Error( 'groundhogg_invalid_position', __( '`at` needs after, before, branch_of, or branch "main".', 'groundhogg' ) );
		}

		if ( $moving && $this->is_inside( $branch, $moving ) ) {
			return new WP_Error( 'groundhogg_invalid_position', __( 'A step can\'t be moved into its own branches.', 'groundhogg' ) );
		}

		$this->branches[ $branch ] ??= [];

		return [ $branch, $index ];
	}

	/**
	 * The stored branch string for one of a step's branches
	 *
	 * @param Step   $owner
	 * @param string $key
	 *
	 * @return string|WP_Error
	 */
	protected function get_branch( Step $owner, string $key ) {

		if ( $owner->is_benchmark() ) {

			if ( $key !== Get_Flow::BENCHMARK_BRANCH ) {
				/* translators: %s: the benchmark branch key */
				return new WP_Error( 'groundhogg_invalid_branch_key', sprintf( __( 'A benchmark\'s only branch is "%s".', 'groundhogg' ), Get_Flow::BENCHMARK_BRANCH ) );
			}

			return "$owner->ID";
		}

		if ( ! $owner->is_branch_logic() ) {
			return new WP_Error( 'groundhogg_invalid_branch_key', __( 'This step doesn\'t have branches.', 'groundhogg' ) );
		}

		$owner->merge_changes();

		$keys = Step_Type_Schema::branch_keys( $owner->get_type(), (array) Step_Type_Schema::export_settings( $owner ) );

		// branches that already have steps, for types that aren't described
		foreach ( array_keys( $this->branches ) as $branch ) {
			$branch = (string) $branch;
			if ( str_starts_with( $branch, "$owner->ID-" ) ) {
				$keys[] = substr( $branch, strlen( "$owner->ID-" ) );
			}
		}

		if ( ! in_array( $key, $keys, true ) ) {
			return new WP_Error(
				'groundhogg_invalid_branch_key',
				/* translators: 1: the branch key, 2: the valid branch keys */
				sprintf( __( '"%1$s" isn\'t a branch of this step, its branches are: %2$s.', 'groundhogg' ), $key, implode( ', ', array_unique( $keys ) ) )
			);
		}

		return "$owner->ID-$key";
	}

	/**
	 * The step a branch belongs to
	 *
	 * @param string $branch
	 *
	 * @return int 0 for the main branch
	 */
	protected function get_branch_owner( string $branch ) {
		return $branch === 'main' ? 0 : absint( strtok( $branch, '-' ) );
	}

	/**
	 * Whether a branch is inside a step's branches, or its sub branches
	 *
	 * @param string $branch
	 * @param int    $step_id
	 *
	 * @return bool
	 */
	protected function is_inside( string $branch, int $step_id ) {

		while ( $owner = $this->get_branch_owner( $branch ) ) {

			if ( $owner === $step_id ) {
				return true;
			}

			if ( ! isset( $this->step_branch[ $owner ] ) ) {
				return false;
			}

			$branch = $this->step_branch[ $owner ];
		}

		return false;
	}

	/**
	 * Add a step to the end of a branch in the model
	 *
	 * @param int    $id
	 * @param string $branch
	 */
	protected function place( int $id, string $branch ) {
		$this->branches[ $branch ][] = $id;
		$this->step_branch[ $id ]    = $branch;
	}

	/**
	 * Remove a step from the model
	 *
	 * @param int $id
	 */
	protected function unplace( int $id ) {

		$branch = $this->step_branch[ $id ];

		$this->branches[ $branch ] = array_values( array_diff( $this->branches[ $branch ], [ $id ] ) );

		unset( $this->step_branch[ $id ] );
	}

	/**
	 * Write the model's branches and order to the steps, set_step_levels() does the rest
	 */
	protected function write_layout() {

		// one count across branches, so steps in sibling branches don't tie and set_step_levels() visits the branches
		// in the order they were in, whatever order the database returns tied rows in
		$order = 0;

		foreach ( $this->branches as $branch => $ids ) {
			foreach ( $ids as $id ) {

				$order ++;

				$step = new Step( $id );
				$step->merge_changes();

				if ( $step->branch === (string) $branch && $step->get_order() === $order ) {
					continue;
				}

				$step->update( [
					'branch'     => (string) $branch,
					'step_order' => $order,
				] );
			}
		}
	}
}
