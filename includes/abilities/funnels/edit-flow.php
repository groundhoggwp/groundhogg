<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Throwable;
use WP_Error;

/**
 * Edits an existing flow (funnel) with a list of operations - add, update, move,
 * and delete steps - applied in order, all or nothing. The operations are applied
 * by Flow_Operations, which the flow editor saves with too.
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
				'definitions' => [
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
									'description' => __( '"add" needs `at` and `steps`. "update" needs `step` and any of `title`, `settings`, and the benchmark flags. "move" needs `step` and `at`, a step moves with everything in its branches. "delete" needs `step`, and also deletes everything in its branches. A step other steps point at in their settings (referenced_by in groundhogg/get-flow) can\'t be deleted until those steps are changed or deleted too.', 'groundhogg' ),
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
									'description'          => __( 'For "update": only the settings to change, the rest are kept. Same shape as groundhogg/list-step-types describes. For if_else, include_condition replaces the include_filters groundhogg/get-flow returned, and exclude_condition the exclude_filters. A `branches` map (split_path, weighted_distribution) is merged branch by branch: give only the branches to change, the same condition/filters rule applies inside each, and null removes a branch (only if it has no steps).', 'groundhogg' ),
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
				'definitions' => array_merge( $flow_schema['definitions'], [
					'step_node_out' => Step_Tree_Builder::node_out_schema(),
				] ),
				'properties' => [
					'dry_run'       => [
						'type' => 'boolean',
					],
					'flow'          => array_diff_key( $flow_schema, [ 'definitions' => true ] ),
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

		$unlocked = Flow_Changes::check_lock( $funnel );

		if ( is_wp_error( $unlocked ) ) {
			return $unlocked;
		}

		if ( $funnel->get_status() === 'archived' ) {
			return new WP_Error( 'groundhogg_flow_archived', __( 'Archived flows can\'t be edited, restore it first.', 'groundhogg' ) );
		}

		return $funnel->while_editing( function () use ( $funnel, $input ) {

			if ( ! empty( $input['expected_revision'] ) && Get_Flow::revision( $funnel ) !== $input['expected_revision'] ) {
				return new WP_Error( 'groundhogg_flow_changed', __( 'The flow was changed since it was read. Read it again with groundhogg/get-flow and retry.', 'groundhogg' ) );
			}

			$backup     = $funnel->backup();
			$operations = new Flow_Operations( $funnel );

			try {
				$result = $operations->apply( (array) $input['operations'] );
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
				}, $operations->get_deleted() ) ),
			];

			if ( $dry_run ) {
				$funnel->rollback( $backup );
			} else {
				do_action( 'groundhogg/api/funnel/updated', $funnel );
			}

			return $output;
		} );
	}
}
