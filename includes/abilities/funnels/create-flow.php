<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Campaign;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;
use function Groundhogg\parse_tag_list;

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
 * `buildable: true`. `settings` is intentionally NOT validated against a
 * per-type JSON Schema here (see groundhogg/list-step-types' settings_schema for
 * that, per type - embedding all of them into this ability's own static input
 * schema would be impractical given how many step types and how varied their
 * shapes are). Unknown/malformed settings keys are silently dropped or coerced
 * by Groundhogg's own per-type sanitizer (Step::update_meta() -> that type's
 * get_settings_schema(), if it has one) - a pure coercer, not a validator, so it
 * never surfaces a rejection. This ability adds its own explicit validation only
 * where silent coercion would be actively misleading: tag names/slugs/IDs are
 * resolved against real tags (parse_tag_list(..., false) - never auto-creates,
 * unlike Groundhogg's own tag-setting sanitizers, since by the time a real
 * contact submits a form in the admin UI a tag picker has already resolved
 * things - there's no equivalent picker step here), send_email's `email_id`
 * must reference an existing email, and step-to-step references (below) must
 * resolve to an already-declared step of the right type.
 *
 * Two settings reference *other steps in this same flow*, by a caller-assigned
 * local `id` (a step node's own `id` property) rather than Groundhogg's real,
 * not-yet-known step ID: send_email's `reply_in_thread` and task_completed's
 * `tasks`. Both require the referenced step to already appear earlier in the
 * input tree (its real ID is then already known - no placeholder-remap pass
 * needed). `tasks` specifically is also written in a *third* pass, after
 * Funnel::set_step_levels() has finalized step ordering - Task_Completed's own
 * sanitizer filters referenced IDs through `is_before($step)`, which needs real,
 * final step_order to evaluate correctly, not the provisional order a step gets
 * at insert time (Funnel::add_step()'s default is just an incrementing count).
 * `reply_in_thread` has no such ordering-dependent check and is written
 * immediately.
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

	protected const string NAME       = 'groundhogg/create-flow';
	protected const string CATEGORY   = 'groundhogg-funnels';
	protected const string CAPABILITY = 'add_funnels';

	protected const bool READONLY    = false;
	protected const bool DESTRUCTIVE = false;
	protected const bool IDEMPOTENT  = false;

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
			'description' => __( 'Create a new Groundhogg flow (funnel) with its full sequence of steps, including branching logic. Always creates a brand-new, inactive flow - never returns an existing one, and never activates it. See groundhogg/list-step-types for the available step types and their settings.', 'groundhogg' ),

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
		$deferred = []; // [ [ 'step_id' => int, 'ids' => [...real create_task step IDs...] ], ... ]

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
			// Task_Completed's own sanitizer filters `tasks` through
			// is_before($step), which reads $step's step_order - reusing the
			// stale instance here silently drops every reference, since as far as
			// it knows nothing is "before" it yet.
			( new Step( $entry['step_id'] ) )->update_meta( 'tasks', $entry['ids'] );
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

			$branch_keys = Step_Type_Schema::branch_keys( $type );

			if ( ! empty( $node['branches'] ) && empty( $branch_keys ) ) {
				return new WP_Error(
					'groundhogg_unexpected_branches',
					// Translators: %s is the step type given `branches` that doesn't support them.
					sprintf( __( 'Step type "%s" doesn\'t support `branches`.', 'groundhogg' ), $type )
				);
			}

			$input_settings = (array) ( $node['settings'] ?? [] );
			$deferred_tasks = null;

			$resolved_settings = $this->resolve_settings( $type, $input_settings, $declared, $deferred_tasks );

			if ( is_wp_error( $resolved_settings ) ) {
				return $resolved_settings;
			}

			$title = ! empty( $node['title'] ) ? sanitize_text_field( $node['title'] ) : $info['name'];

			$step = $funnel->add_step( [
				'step_title' => $title,
				'step_type'  => $type,
				'step_group' => $info['group'],
				'branch'     => $branch,
				'meta'       => $resolved_settings,
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

			if ( $deferred_tasks !== null ) {
				$deferred[] = [ 'step_id' => $step->get_id(), 'ids' => $deferred_tasks ];
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

	/**
	 * Resolve one step's `settings` into what actually gets written via
	 * Step::update_meta() - only the handful of fields this ability explicitly
	 * validates/translates (see the class docblock); everything else passes
	 * through untouched for Groundhogg's own per-type sanitizer to handle.
	 *
	 * @param string     $type
	 * @param array      $settings       The input node's `settings`, as given.
	 * @param array      $declared       local id => ['id'=>int,'type'=>string] of
	 *                                   every step created so far (earlier in tree
	 *                                   order) - see create_branch().
	 * @param array|null $deferred_tasks By reference, out param. Set to the
	 *                                   resolved list of real create_task step IDs
	 *                                   when $type is task_completed and `tasks`
	 *                                   was given - the caller writes these
	 *                                   separately, after set_step_levels().
	 *
	 * @return array|WP_Error
	 */
	private function resolve_settings( string $type, array $settings, array $declared, &$deferred_tasks ) {

		switch ( $type ) {

			case 'apply_tag':
			case 'remove_tag':
			case 'tag_applied':
			case 'tag_removed':

				if ( ! empty( $settings['tags'] ) ) {

					// $create = false: never mint a tag from a typo.
					$resolved = parse_tag_list( $settings['tags'], 'ID', false );

					if ( empty( $resolved ) ) {
						return new WP_Error( 'groundhogg_unknown_tags', __( 'None of the tags given match an existing tag.', 'groundhogg' ) );
					}

					$settings['tags'] = $resolved;
				}

				break;

			case 'send_email':

				if ( ! empty( $settings['email_id'] ) ) {

					$email = new Email( absint( $settings['email_id'] ) );

					if ( ! $email->exists() ) {
						return new WP_Error( 'groundhogg_email_not_found', __( 'send_email\'s email_id does not match an existing email. Find one with groundhogg/list-email-templates.', 'groundhogg' ) );
					}
				}

				if ( ! empty( $settings['reply_in_thread'] ) ) {

					$ref = $this->resolve_step_reference( $settings['reply_in_thread'], $declared, 'send_email' );

					if ( is_wp_error( $ref ) ) {
						return $ref;
					}

					$settings['reply_in_thread'] = $ref;
				}

				break;

			case 'task_completed':

				if ( ! empty( $settings['tasks'] ) && is_array( $settings['tasks'] ) ) {

					$ids = [];

					foreach ( $settings['tasks'] as $local_ref ) {

						$ref = $this->resolve_step_reference( $local_ref, $declared, 'create_task' );

						if ( is_wp_error( $ref ) ) {
							return $ref;
						}

						$ids[] = $ref;
					}

					$deferred_tasks = $ids;
				}

				// Written later regardless of whether `tasks` was given (an empty
				// deferred write is harmless, and keeps this one code path).
				unset( $settings['tasks'] );

				break;

			case 'if_else':

				$include = Segment_Schema::to_filters( (array) ( $settings['include_condition'] ?? [] ) );

				if ( is_wp_error( $include ) ) {
					return $include;
				}

				$exclude = Segment_Schema::to_filters( (array) ( $settings['exclude_condition'] ?? [] ) );

				if ( is_wp_error( $exclude ) ) {
					return $exclude;
				}

				unset( $settings['include_condition'], $settings['exclude_condition'] );

				$settings['include_filters'] = $include;
				$settings['exclude_filters'] = $exclude;

				break;
		}

		return $settings;
	}

	/**
	 * Resolve a settings field referencing another step in this flow by its
	 * local `id`, requiring it to already exist (i.e. be declared earlier in the
	 * input tree) and be of the expected step type.
	 *
	 * @param mixed  $local_id
	 * @param array  $declared      local id => ['id'=>int,'type'=>string].
	 * @param string $expected_type
	 *
	 * @return int|WP_Error The real step ID.
	 */
	private function resolve_step_reference( $local_id, array $declared, string $expected_type ) {

		$local_id = sanitize_key( (string) $local_id );

		if ( ! isset( $declared[ $local_id ] ) ) {
			return new WP_Error(
				'groundhogg_unknown_step_reference',
				// Translators: %s is the referenced local step id.
				sprintf( __( '"%s" doesn\'t refer to an earlier step in this flow. It must be declared (via that step\'s own `id`) before the step that references it.', 'groundhogg' ), $local_id )
			);
		}

		if ( $declared[ $local_id ]['type'] !== $expected_type ) {
			return new WP_Error(
				'groundhogg_wrong_step_reference_type',
				sprintf(
				// Translators: %1$s local id, %2$s its actual type, %3$s expected type.
					__( '"%1$s" refers to a %2$s step, but a %3$s step was expected here.', 'groundhogg' ),
					$local_id,
					$declared[ $local_id ]['type'],
					$expected_type
				)
			);
		}

		return $declared[ $local_id ]['id'];
	}
}
