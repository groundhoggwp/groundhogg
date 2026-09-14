<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Campaign;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;

/**
 * Creates a new Groundhogg flow (funnel), including its full sequence of
 * benchmark/action/logic steps and any branching, in a single call - mirroring
 * groundhogg/create-email-template's "always creates new, never updates" shape.
 * There is no update-flow yet; fixing a mistake means creating a fresh flow.
 *
 * `steps` is a *tree*, not Groundhogg's own internal flat/branch-string storage
 * shape (Step::$branch, step_order, step_level) - the exact bookkeeping a
 * from-scratch flow-tree design (documented separately) exists specifically to
 * spare an author from having to compute by hand. A step's own `branches` map
 * (only meaningful for branching logic types - currently just if_else,
 * branch_keys ["yes","no"], see groundhogg/list-step-types) nests the next step
 * node(s) for each branch directly, recursively. This ability walks that tree
 * top-down, creating real Step rows with real IDs as it goes (via
 * Funnel::add_step()), then calls Funnel::set_step_levels() once at the end to
 * derive step_order/step_level/branch strings from the tree shape - the same
 * thing the live wp-admin flow editor's save path does, just driven by this
 * input tree instead of drag-and-drop.
 *
 * Every step type in `steps` must be one groundhogg/list-step-types reports as
 * `buildable: true` (Step_Type_Schema::supported_types()) - the built-in set,
 * plus anything a 3rd-party add-on has opted in via Step_Type_Schema::extend()
 * (see that class's own docblock). `settings` is intentionally NOT validated
 * against a per-type JSON Schema in *this ability's* own input schema (see
 * groundhogg/list-step-types' settings_schema for that, per type - embedding
 * all of them here would be impractical given how many step types exist and
 * how varied their shapes are, and impossible to do at all for a type an add-on
 * registers after this ability's own schema is already built).
 *
 * All cross-reference resolution/translation (tag names -> real tag IDs,
 * send_email's `email_id` needing to reference an existing email, the two
 * step-to-step references below, if_else's condition -> Filters DSL
 * translation, and whatever a 3rd-party type's own extend() resolver does) is
 * centralized in Step_Type_Schema::resolve_settings() - this ability has no
 * type-specific knowledge of its own beyond calling it. Anything that method
 * doesn't touch passes through untouched for Groundhogg's own per-type
 * sanitizer (Step::update_meta() -> that type's get_settings_schema(), if it
 * has one) - a pure coercer, not a validator, so it never surfaces a rejection
 * on its own.
 *
 * Two built-in settings reference *other steps in this same flow*, by a
 * caller-assigned local `id` (a step node's own `id` property) rather than
 * Groundhogg's real, not-yet-known step ID: send_email's `reply_in_thread` and
 * task_completed's `tasks` (Step_Type_Schema::resolve_step_reference(), also
 * available to an add-on's own resolver). Both require the referenced step to
 * already appear earlier in the input tree (its real ID is then already known -
 * no placeholder-remap pass needed). A resolver can ask for a setting to be
 * written only after Funnel::set_step_levels() has finalized step ordering
 * (via `deferred_settings` - task_completed's `tasks` needs this since its own
 * sanitizer filters referenced IDs through `is_before($step)`, which needs
 * real, final step_order to evaluate correctly, not the provisional order a
 * step gets at insert time); `reply_in_thread` has no such ordering-dependent
 * check and is written immediately.
 *
 * Always created `status: inactive` - a live/active funnel's Step::update_meta()
 * routes through a changes/commit queue meant for the wp-admin editor's own
 * draft/review UX, which direct, bulk construction like this doesn't want any
 * part of. Activating is a separate, later concern (a future activate-flow
 * ability, or just the admin, once the flow's been reviewed).
 *
 * On any validation failure partway through, the partially-created funnel (and
 * whatever steps already exist under it) is deleted before returning the error -
 * Funnel::delete() cascades to its steps - so a failed call never leaves a
 * broken half-built flow behind.
 */
class Create_Flow extends Ability {

	protected const NAME       = 'groundhogg/create-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'add_funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		$step_node_schema = [
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
						'items' => [ '$ref' => '#/$defs/step_node' ],
					],
					'description'          => __( 'Only for branching logic types (currently just if_else - branch_keys ["yes","no"]). Maps each branch key to the ordered list of step nodes that branch contains.', 'groundhogg' ),
				],
			],
		];

		return [
			'label'       => __( 'Create Flow', 'groundhogg' ),
			'description' => __( 'Create a new Groundhogg flow (funnel) with its full sequence of steps, including branching logic. Always creates a brand-new, inactive flow - never returns an existing one, and never activates it. See groundhogg/list-step-types for the available step types and their settings. For guidance on choosing step types, structuring branches, and making sure every step is reachable - plus a downloadable Claude skill that packages this reasoning - see https://groundhogg.io/doc/working-with-the-abilities-api-mcp/.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'title', 'steps' ],
				'$defs'                => [
					'step_node' => $step_node_schema,
				],
				'properties'           => [
					'title' => [
						'type' => 'string',
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Existing campaign IDs to tag this flow with - see groundhogg/list-campaigns.', 'groundhogg' ),
					],
					'steps' => [
						'type'        => 'array',
						'minItems'    => 1,
						'items'       => [ '$ref' => '#/$defs/step_node' ],
						'description' => __( 'The flow\'s main sequence, in order. A benchmark (trigger) step is an entry/jump point and can appear anywhere in the sequence, not just first - a contact can enter (or jump to) the flow at any trigger whenever it matches, independent of the steps around it. Adjacent triggers act as an OR ("any of these happen").', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'$defs'      => [
					'step_node_out' => [
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
									'items' => [ '$ref' => '#/$defs/step_node_out' ],
								],
							],
						],
					],
				],
				'properties' => [
					'id' => [
						'type' => 'integer',
					],
					'title' => [
						'type' => 'string',
					],
					'status' => [
						'type' => 'string',
					],
					'admin_link' => [
						'type' => 'string',
					],
					'steps' => [
						'type'  => 'array',
						'items' => [ '$ref' => '#/$defs/step_node_out' ],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$funnel = new Funnel();

		// Always inactive - see the class docblock.
		$funnel->create( [
			'title'  => sanitize_text_field( $input['title'] ),
			'status' => 'inactive',
			'author' => get_current_user_id(),
		] );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_flow_not_created', __( 'The flow could not be created.', 'groundhogg' ) );
		}

		$declared = []; // local id => [ 'id' => real step ID, 'type' => step type ]
		$deferred = []; // [ [ 'step_id' => int, 'settings' => [key=>value, ...] ], ... ] - see Step_Type_Schema::resolve_settings()

		$steps_out = $this->create_branch( $funnel, $input['steps'], 'main', $declared, $deferred );

		if ( is_wp_error( $steps_out ) ) {
			$funnel->delete();

			return $steps_out;
		}

		// Derives step_order/step_level/branch_path/ancestors for every step from
		// the branch structure just built - see the class docblock.
		$funnel->set_step_levels();

		foreach ( $deferred as $entry ) {
			// A fresh Step instance, not the one create_branch() built - that one's
			// in-memory step_order is whatever Funnel::add_step() gave it at
			// insert time, never updated in place by set_step_levels() above
			// (which works through its own, separately-fetched Step instances).
			// task_completed's own sanitizer filters `tasks` through
			// is_before($step), which reads $step's step_order - reusing the
			// stale instance here silently drops every reference, since as far as
			// it knows nothing is "before" it yet. Same caution applies to
			// whatever an add-on's own deferred_settings might need.
			( new Step( $entry['step_id'] ) )->update_meta( $entry['settings'] );
		}

		if ( ! empty( $input['campaigns'] ) ) {
			foreach ( wp_parse_id_list( $input['campaigns'] ) as $campaign_id ) {
				$funnel->create_relationship( new Campaign( $campaign_id ) );
			}
		}

		// Mirrors the REST create path's own action for other code hooking flow
		// creation (cache busting, integrations, etc.).
		do_action( 'groundhogg/api/funnel/created', $funnel );

		return [
			'id'         => $funnel->get_id(),
			'title'      => $funnel->get_title(),
			'status'     => $funnel->get_status(),
			'admin_link' => $funnel->admin_link(),
			'steps'      => $steps_out,
		];
	}

	/**
	 * Recursively create every step in one branch's ordered node list, then
	 * (for any branching logic step among them) recurse into its own `branches`.
	 * Mutates $declared/$deferred as steps are created - see the class docblock.
	 *
	 * @param Funnel $funnel
	 * @param array  $nodes    This branch's step nodes, in order.
	 * @param string $branch   The `branch` string new steps here get - 'main' for
	 *                         the root, "<parentStepId>-<key>" for a nested one.
	 * @param array  $declared By reference. local id => ['id' => int, 'type' => string].
	 * @param array  $deferred By reference. Accumulates task_completed writes to
	 *                         apply after set_step_levels() - see __invoke().
	 *
	 * @return array|WP_Error Output step nodes for this branch, or the first error.
	 */
	private function create_branch( Funnel $funnel, array $nodes, string $branch, array &$declared, array &$deferred ) {

		$out = [];

		foreach ( $nodes as $node ) {

			$node = (array) $node;
			$type = $node['type'] ?? '';
			$info = Step_Type_Schema::get_type_info( $type );

			// The input schema's `type` enum already restricts this to
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

			if ( $local_id && isset( $declared[ $local_id ] ) ) {
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

			$resolved = Step_Type_Schema::resolve_settings( $type, $input_settings, $declared );

			if ( is_wp_error( $resolved ) ) {
				return $resolved;
			}

			$title = ! empty( $node['title'] ) ? sanitize_text_field( $node['title'] ) : $info['name'];

			$step = $funnel->add_step( [
				'step_title' => $title,
				'step_type'  => $type,
				'step_group' => $info['group'],
				'branch'     => $branch,
				'meta'       => $resolved['settings'],
			] );

			if ( ! $step ) {
				return new WP_Error(
					'groundhogg_step_not_created',
					// Translators: %s is the step's title.
					sprintf( __( 'The step "%s" could not be created.', 'groundhogg' ), $title )
				);
			}

			if ( $local_id ) {
				$declared[ $local_id ] = [ 'id' => $step->get_id(), 'type' => $type ];
			}

			if ( ! empty( $resolved['deferred_settings'] ) ) {
				$deferred[] = [ 'step_id' => $step->get_id(), 'settings' => $resolved['deferred_settings'] ];
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

					$sub_out = $this->create_branch( $funnel, (array) $sub_nodes, "{$step->get_id()}-{$key}", $declared, $deferred );

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
