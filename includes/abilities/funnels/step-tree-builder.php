<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;

/**
 * Creates real steps in a funnel from a tree of step nodes, the shape groundhogg/create-flow takes as `steps`.
 * Shared by the abilities that add steps to a flow.
 *
 * A node's `branches` map (only for branching logic types, see Step_Type_Schema::branch_keys()) nests the
 * nodes for each branch, and they get the branch string "<parentStepId>-<key>". Steps are created in the
 * order they're given, then the caller must run Funnel::set_step_levels() to derive the final step_order,
 * step_level and branch paths from the tree, and then apply_deferred_settings().
 *
 * Settings are resolved through Step_Type_Schema::resolve_settings() (tag names to IDs, local step
 * references to real step IDs, etc.). A node can reference another node by its local `id` only if that
 * node was built earlier, so its real ID is known. Settings that depend on the final step order
 * (task_completed's `tasks`, filtered through is_before()) come back as `deferred_settings` and are only
 * written by apply_deferred_settings().
 *
 * On an error part way through, the steps already created are left in place, it's up to the caller to
 * remove them (create-flow deletes the whole funnel).
 */
class Step_Tree_Builder {

	/**
	 * @var Funnel
	 */
	protected $funnel;

	/**
	 * Steps that can be referenced by their local id, local id => [ 'id' => real step ID, 'type' => step type ]
	 *
	 * @var array
	 */
	protected $declared;

	/**
	 * Settings to write after the step order is final, [ [ 'step_id' => int, 'settings' => array ], ... ]
	 *
	 * @var array
	 */
	protected $deferred = [];

	/**
	 * The JSON Schema for a step node build() takes. Branches refer to it as `#/definitions/step_node`,
	 * so the ability's input schema must define it there.
	 *
	 * WordPress' own validator (rest_validate_value_from_schema(), run by
	 * WP_Ability::execute() on both input and output) doesn't resolve `$ref`
	 * and requires a `type` on every schema it walks. So abilities inline the
	 * top-level step nodes in full, and the recursive `$ref`s in `branches` carry
	 * a `type` alongside - WordPress checks that much, while MCP clients
	 * (JSON Schema 2020-12, where `$ref` siblings apply) get the full shape.
	 *
	 * It's `definitions`, not `$defs`: wp_prepare_json_schema_for_client() keeps only
	 * draft-04 keywords when WordPress publishes ability schemas (the abilities REST
	 * endpoint, the AI client), so `$defs` would be dropped and the `$ref`s left
	 * pointing at nothing. `#/definitions/...` is still a plain JSON pointer to newer
	 * clients.
	 *
	 * @return array
	 */
	public static function node_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'type' ],
			'properties'           => [
				'id' => [
					'type'        => 'string',
					'description' => __( 'A local key for this step, unique within this call - e.g. "welcome_email". Only needed if another step references it (task_completed\'s `tasks`, send_email\'s `reply_in_thread`). Not stored - the real, persisted step id is returned in the response instead.', 'groundhogg' ),
				],
				'type' => [
					'type'        => 'string',
					'enum'        => Step_Type_Schema::supported_types(),
					'description' => __( 'See groundhogg/list-step-types for what each type does and its exact `settings` shape (pass expand: ["settings_schema"], narrowed via `types` to just what you need).', 'groundhogg' ),
				],
				'title' => [
					'type'        => 'string',
					'description' => __( 'Internal admin title for this step. Defaults to the step type\'s own name (e.g. "Send Email") if omitted.', 'groundhogg' ),
				],
				'settings' => [
					'type'                 => 'object',
					'additionalProperties' => true,
					'description'          => __( 'This step\'s configuration - see groundhogg/list-step-types\' settings_schema for the exact shape per type. See this ability\'s own description for what is and isn\'t validated here.', 'groundhogg' ),
				],
				'branches' => [
					'type'                 => 'object',
					'additionalProperties' => [
						'type'  => 'array',
						'items' => [
							'type' => 'object',
							'$ref' => '#/definitions/step_node',
						],
					],
					'description'          => __( 'Only for branching logic types (currently just if_else - branch_keys ["yes","no"]). Maps each branch key to the ordered list of step nodes that branch contains.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * The JSON Schema for a step node build() returns. Branches refer to it as `#/definitions/step_node_out`,
	 * so the ability's output schema must define it there. See node_schema().
	 *
	 * @return array
	 */
	public static function node_out_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type'        => 'integer',
					'description' => __( 'The real, persisted step ID.', 'groundhogg' ),
				],
				'local_id' => [
					'type'        => 'string',
					'description' => __( 'Only present if the input node had its own `id`. Echoed back for reference.', 'groundhogg' ),
				],
				'type' => [
					'type' => 'string',
				],
				'type_name' => [
					'type' => 'string',
				],
				'group' => [
					'type' => 'string',
					'enum' => [ 'benchmark', 'action', 'logic' ],
				],
				'title' => [
					'type' => 'string',
				],
				'settings' => [
					'type'        => 'object',
					'description' => __( 'Echoes back the input node\'s own `settings`, as given (not Groundhogg\'s internal stored shape, where the two differ - e.g. if_else\'s include_condition/exclude_condition rather than its internal include_filters/exclude_filters).', 'groundhogg' ),
				],
				'branches' => [
					'type'                 => 'object',
					'additionalProperties' => [
						'type'  => 'array',
						'items' => [
							'type' => 'object',
							'$ref' => '#/definitions/step_node_out',
						],
					],
				],
			],
		];
	}

	/**
	 * @param Funnel $funnel   the funnel to add the steps to
	 * @param array  $declared steps that can already be referenced by a local id,
	 *                         local id => [ 'id' => real step ID, 'type' => step type ]
	 */
	public function __construct( Funnel $funnel, array $declared = [] ) {
		$this->funnel   = $funnel;
		$this->declared = $declared;
	}

	/**
	 * Steps that can be referenced by their local id, including the ones given to the constructor
	 *
	 * @return array local id => [ 'id' => real step ID, 'type' => step type ]
	 */
	public function get_declared(): array {
		return $this->declared;
	}

	/**
	 * Let a step be referenced by a key
	 *
	 * @param string $key
	 * @param int    $step_id
	 * @param string $type the step type
	 *
	 * @return void
	 */
	public function declare( string $key, int $step_id, string $type ) {
		$this->declared[ sanitize_key( $key ) ] = [ 'id' => $step_id, 'type' => $type ];
	}

	/**
	 * Write the settings that had to wait for the final step order.
	 * Call after Funnel::set_step_levels().
	 *
	 * @return void
	 */
	public function apply_deferred_settings() {

		foreach ( $this->deferred as $entry ) {
			// A fresh Step instance, not the one build() created - that one's
			// in-memory step_order is whatever Funnel::add_step() gave it at
			// insert time, never updated in place by set_step_levels()
			// (which works through its own, separately-fetched Step instances).
			// task_completed's own sanitizer filters `tasks` through
			// is_before($step), which reads $step's step_order - reusing the
			// stale instance here silently drops every reference, since as far as
			// it knows nothing is "before" it yet. Same caution applies to
			// whatever an add-on's own deferred_settings might need.
			( new Step( $entry['step_id'] ) )->update_meta( $entry['settings'] );
		}

		$this->deferred = [];
	}

	/**
	 * Create every step in one branch's ordered node list, then (for any
	 * branching logic step among them) recurse into its own `branches`.
	 *
	 * @param array  $nodes  This branch's step nodes, in order.
	 * @param string $branch The `branch` string new steps here get - 'main' for
	 *                       the root, "<parentStepId>-<key>" for a nested one.
	 *
	 * @return array|WP_Error Output step nodes for this branch, or the first error.
	 */
	public function build( array $nodes, string $branch = 'main' ) {

		$out = [];

		foreach ( $nodes as $node ) {

			$node = (array) $node;
			$type = $node['type'] ?? '';
			$info = Step_Type_Schema::get_type_info( $type );

			// The ability input schemas already restrict this to
			// Step_Type_Schema::supported_types(), but defend anyway rather than
			// trust that unconditionally.
			if ( ! $info || ! in_array( $type, Step_Type_Schema::supported_types(), true ) ) {
				return new WP_Error(
					'groundhogg_invalid_step_type',
					// Translators: %s is the unsupported step type key given.
					sprintf( __( '"%s" is not a step type groundhogg/create-flow can build. See groundhogg/list-step-types.', 'groundhogg' ), $type )
				);
			}

			$local_id = isset( $node['id'] ) && $node['id'] !== '' ? sanitize_key( (string) $node['id'] ) : '';

			if ( $local_id && isset( $this->declared[ $local_id ] ) ) {
				return new WP_Error(
					'groundhogg_duplicate_step_id',
					// Translators: %s is the duplicated local step id.
					sprintf( __( 'Step id "%s" is used more than once.', 'groundhogg' ), $local_id )
				);
			}

			$input_settings = (array) ( $node['settings'] ?? [] );

			// Passing $input_settings (not $resolved['settings'], computed below)
			// - branch keys are about the shape of what the caller asked for, so
			// a dynamic-branch-key type (see Step_Type_Schema::extend()'s own
			// docblock for a split_path-style example) should compute them from
			// what was actually given, not from whatever resolve_settings() below
			// may have already transformed those same settings into.
			$branch_keys = Step_Type_Schema::branch_keys( $type, $input_settings );

			if ( ! empty( $node['branches'] ) && empty( $branch_keys ) ) {
				return new WP_Error(
					'groundhogg_unexpected_branches',
					// Translators: %s is the step type given `branches` that doesn't support them.
					sprintf( __( 'Step type "%s" doesn\'t support `branches`.', 'groundhogg' ), $type )
				);
			}

			$resolved = Step_Type_Schema::resolve_settings( $type, $input_settings, $this->declared );

			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}

			$title = ! empty( $node['title'] ) ? sanitize_text_field( $node['title'] ) : $info['name'];

			$step = $this->funnel->add_step( [
				'step_title'  => $title,
				'step_type'   => $type,
				'step_group'  => $info['group'],
				'step_status' => 'inactive', // like steps added in the flow editor, activating or committing the funnel makes them active
				'branch'      => $branch,
				'meta'        => $resolved['settings'],
			] );

			if ( ! $step ) {
				return new WP_Error(
					'groundhogg_step_not_created',
					// Translators: %s is the step's title.
					sprintf( __( 'The step "%s" could not be created.', 'groundhogg' ), $title )
				);
			}

			if ( $local_id ) {
				$this->declared[ $local_id ] = [ 'id' => $step->get_id(), 'type' => $type ];
			}

			if ( ! empty( $resolved['deferred_settings'] ) ) {
				$this->deferred[] = [ 'step_id' => $step->get_id(), 'settings' => $resolved['deferred_settings'] ];
			}

			$out_node = [
				'id'        => $step->get_id(),
				'type'      => $type,
				'type_name' => $info['name'],
				'group'     => $info['group'],
				'title'     => $title,
				'settings'  => $input_settings,
			];

			if ( $local_id ) {
				$out_node['local_id'] = $local_id;
			}

			if ( ! empty( $node['branches'] ) && is_array( $node['branches'] ) ) {

				$out_branches = [];

				foreach ( $node['branches'] as $key => $sub_nodes ) {

					if ( ! in_array( $key, $branch_keys, true ) ) {
						return new WP_Error(
							'groundhogg_invalid_branch_key',
							sprintf(
							// Translators: %1$s branch key given, %2$s step type, %3$s valid branch keys.
								__( '"%1$s" is not a valid branch for step type "%2$s" - valid branches: %3$s.', 'groundhogg' ),
								$key,
								$type,
								implode( ', ', $branch_keys )
							)
						);
					}

					$sub_out = $this->build( (array) $sub_nodes, "{$step->get_id()}-{$key}" );

					if ( is_wp_error( $sub_out ) ) {
						return $sub_out;
					}

					$out_branches[ $key ] = $sub_out;
				}

				$out_node['branches'] = $out_branches;
			}

			$out[] = $out_node;
		}

		return $out;
	}
}
