<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Throwable;
use WP_Error;

/**
 * Edits an existing flow (funnel) with a list of operations - add, update, move,
 * and delete steps - applied in order, all or nothing.
 *
 * Works like the flow editor: on an active flow every change is staged (new
 * steps are inactive, edits go into the step's changes, deletes are soft) until
 * the changes are published, so contacts keep going through the live flow in the
 * meantime. On an inactive flow edits are written directly, and deleted steps
 * are removed when it's activated. Either way, what happens to contacts waiting
 * at a deleted step is decided when publishing or activating, not here.
 *
 * Steps are referenced by their real id (from groundhogg/get-flow), or by the
 * local `id` of a step added earlier in the same call. Settings that reference
 * other steps (send_email's reply_in_thread, task_completed's tasks) take either
 * too, which is why every existing step is declared to the Step_Tree_Builder by
 * its real id.
 *
 * Positions are kept in a model of each branch's ordered step ids while the
 * operations run, written to the steps' branch and step_order, and then
 * Funnel::set_step_levels() derives everything else, as after a save in the
 * flow editor.
 *
 * All or nothing: the steps are backed up first (Funnel::backup()), and if any
 * operation fails they're rolled back exactly, which is also how dry_run undoes
 * the edits after describing the result.
 */
class Edit_Flow extends Ability {

	protected const NAME       = 'groundhogg/edit-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'edit_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	/**
	 * @var Funnel
	 */
	protected $funnel;

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

	protected function get_args(): array {

		$ref_schema = [
			'type'        => [ 'integer', 'string' ],
			'description' => __( 'A step\'s real id, or the local `id` of a step added earlier in this call.', 'groundhogg' ),
		];

		$position_schema = [
			'type'                 => 'object',
			'additionalProperties' => false,
			'description'          => __( 'Where to put the steps. Exactly one of: {"after": step}, {"before": step}, {"branch_of": step, "branch": key, "position": "start"|"end"} for a branch of an if_else ("yes"/"no") or the steps that only run after a benchmark ("then"), or {"branch": "main", "position": "start"|"end"}.', 'groundhogg' ),
			'properties'           => [
				'after'     => $ref_schema,
				'before'    => $ref_schema,
				'branch_of' => $ref_schema,
				'branch'    => [
					'type' => 'string',
				],
				'position'  => [
					'type'    => 'string',
					'enum'    => [ 'start', 'end' ],
					'default' => 'end',
				],
			],
		];

		$flow_schema = Get_Flow::flow_schema();

		return [
			'label'       => __( 'Edit Flow', 'groundhogg' ),
			'description' => __( 'Change an existing Groundhogg flow (funnel): add, update, move, and delete steps with a list of operations applied in order, all or nothing. Read the flow with groundhogg/get-flow first. On an active flow the changes are staged, like in the flow editor, and contacts keep going through the live flow until the changes are published; on an inactive flow they\'re written directly. Deleting a step with waiting contacts doesn\'t move or cancel them yet - that\'s decided when the changes are published or the flow is activated. Returns the resulting flow in groundhogg/get-flow\'s shape. Use dry_run to check the result without saving it.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id', 'operations' ],
				'$defs'                => [
					'step_node' => Step_Tree_Builder::node_schema(),
				],
				'properties'           => [
					'flow_id'           => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'expected_revision' => [
						'type'        => 'string',
						'description' => __( 'The revision groundhogg/get-flow returned. If the flow was changed since, nothing is changed and an error is returned, so re-read it and try again.', 'groundhogg' ),
					],
					'dry_run'           => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Apply the operations, return the result, then undo them.', 'groundhogg' ),
					],
					'operations'        => [
						'type'     => 'array',
						'minItems' => 1,
						'items'    => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'required'             => [ 'op' ],
							'properties'           => [
								'op'            => [
									'type'        => 'string',
									'enum'        => [ 'add', 'update', 'move', 'delete' ],
									'description' => __( '"add" needs `at` and `steps`. "update" needs `step` and any of `title`, `settings`, and the benchmark flags. "move" needs `step` and `at`, a step moves with everything in its branches. "delete" needs `step`, and also deletes everything in its branches.', 'groundhogg' ),
								],
								'step'          => $ref_schema,
								'at'            => $position_schema,
								'steps'         => [
									'type'        => 'array',
									'minItems'    => 1,
									'items'       => Step_Tree_Builder::node_schema(),
									'description' => __( 'For "add": the steps to add in order, the same step nodes groundhogg/create-flow takes, including branches.', 'groundhogg' ),
								],
								'title'         => [
									'type' => 'string',
								],
								'settings'      => [
									'type'                 => 'object',
									'additionalProperties' => true,
									'description'          => __( 'For "update": only the settings to change, the rest are kept. Same shape as groundhogg/list-step-types describes. For if_else, include_condition replaces the include_filters groundhogg/get-flow returned, and exclude_condition the exclude_filters.', 'groundhogg' ),
								],
								'is_entry'      => [
									'type' => 'boolean',
								],
								'is_conversion' => [
									'type' => 'boolean',
								],
								'can_passthru'  => [
									'type' => 'boolean',
								],
							],
						],
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'$defs'      => array_merge( $flow_schema['$defs'], [
					'step_node_out' => Step_Tree_Builder::node_out_schema(),
				] ),
				'properties' => [
					'dry_run'       => [
						'type' => 'boolean',
					],
					'flow'          => array_diff_key( $flow_schema, [ '$defs' => true ] ),
					'added'         => [
						'type'        => 'array',
						'description' => __( 'The steps each "add" created, in the order of the operations.', 'groundhogg' ),
						'items'       => [
							'type'  => 'array',
							'items' => Step_Tree_Builder::node_out_schema(),
						],
					],
					'deleted_steps' => [
						'type'        => 'array',
						'description' => __( 'Steps deleted by this call, with how many contacts are waiting at each. They\'re cancelled when the changes are published or the flow is activated, unless moved to another action then.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'id'               => [ 'type' => 'integer' ],
								'title'            => [ 'type' => 'string' ],
								'type'             => [ 'type' => 'string' ],
								'waiting_contacts' => [ 'type' => 'integer' ],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$funnel = new Funnel( absint( $input['flow_id'] ) );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_flow_not_found', __( 'Flow not found.', 'groundhogg' ) );
		}

		if ( $funnel->get_status() === 'archived' ) {
			return new WP_Error( 'groundhogg_flow_archived', __( 'Archived flows can\'t be edited, restore it first.', 'groundhogg' ) );
		}

		return $funnel->while_editing( function () use ( $funnel, $input ) {

			if ( ! empty( $input['expected_revision'] ) && Get_Flow::revision( $funnel ) !== $input['expected_revision'] ) {
				return new WP_Error( 'groundhogg_flow_changed', __( 'The flow was changed since it was read. Read it again with groundhogg/get-flow and retry.', 'groundhogg' ) );
			}

			$backup = $funnel->backup();

			try {
				$result = $this->edit( $funnel, (array) $input['operations'] );
			} catch ( Throwable $e ) {
				$result = new WP_Error( 'groundhogg_flow_edit_failed', $e->getMessage() );
			}

			if ( is_wp_error( $result ) ) {
				$funnel->rollback( $backup );

				return $result;
			}

			$dry_run = ! empty( $input['dry_run'] );

			$output = [
				'dry_run'       => $dry_run,
				'flow'          => Get_Flow::describe( $funnel ),
				'added'         => $result,
				'deleted_steps' => array_values( array_map( function ( Step $step ) use ( $funnel ) {
					return [
						'id'               => $step->get_id(),
						'title'            => $step->get_title(),
						'type'             => $step->get_type(),
						'waiting_contacts' => $funnel->count_pending_events( $step ),
					];
				}, $this->deleted ) ),
			];

			if ( $dry_run ) {
				$funnel->rollback( $backup );
			} else {
				do_action( 'groundhogg/api/funnel/updated', $funnel );
			}

			return $output;
		} );
	}

	/**
	 * Apply the operations
	 *
	 * @param Funnel $funnel
	 * @param array  $operations
	 *
	 * @return array|WP_Error the steps each add created
	 */
	protected function edit( Funnel $funnel, array $operations ) {

		$this->funnel   = $funnel;
		$this->branches = [];
		$this->deferred = [];
		$this->deleted  = [];

		$this->builder = new Step_Tree_Builder( $funnel );

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

			// a condition replaces the stored filters for that side
			foreach ( [ 'include', 'exclude' ] as $which ) {
				if ( isset( $input[ "{$which}_condition" ] ) ) {
					unset( $current[ "{$which}_filters" ] );
				}
			}

			// resolve the whole settings, so settings that are resolved together (web_form's form) keep what isn't changed
			$resolved = Step_Type_Schema::resolve_settings( $type, array_merge( $current, $input ), $this->builder->get_declared() );

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

		foreach ( $this->branches as $branch => $ids ) {
			foreach ( array_values( $ids ) as $index => $id ) {

				$step = new Step( $id );
				$step->merge_changes();

				if ( $step->branch === (string) $branch && $step->get_order() === $index + 1 ) {
					continue;
				}

				$step->update( [
					'branch'     => (string) $branch,
					'step_order' => $index + 1,
				] );
			}
		}
	}
}
