<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;

/**
 * Lists the flow/funnel step types available on this site, so a caller can
 * discover valid `type` values and their `settings` shape before calling
 * groundhogg/create-flow - the wide range of step types (triggers, actions,
 * branching logic) makes that impractical to fully enumerate in create-flow's
 * own static input schema the way a small fixed enum (e.g. groundhogg/create-email's
 * `message_type`) can.
 *
 * `settings_schema` is only included when "settings_schema" is passed in expand,
 * and only for the types actually being returned - some (web_form especially) are
 * large, so narrow with `types` first rather than expanding across everything
 * registered on the site in one call.
 *
 * See Step_Type_Schema's own docblock for exactly which step types
 * groundhogg/create-flow can build (`buildable: true` here) versus which are
 * listed for completeness only (unregistered add-ons active elsewhere, premium
 * types, or the two Groundhogg-flagged-legacy types `form_fill`/`email_opened`).
 */
class List_Step_Types extends Ability {

	protected const NAME       = 'groundhogg/list-step-types';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'view_funnels';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Step Types', 'groundhogg' ),
			'description' => __( 'List the flow/funnel step types available on this site (triggers, actions, and branching logic), with their settings shape. Use the returned type/settings_schema with groundhogg/create-flow. See groundhogg/create-flow\'s description for a link to further flow-design guidance.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'types' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Only these step type keys (e.g. "send_email", "if_else"). Omit to list every registered type.', 'groundhogg' ),
					],
					'group' => [
						'type'        => 'string',
						'enum'        => [ 'benchmark', 'action', 'logic' ],
						'description' => __( 'Only step types in this group. "benchmark" = triggers/entry points, "action" = does something, "logic" = branches the flow.', 'groundhogg' ),
					],
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the step type\'s name and description.', 'groundhogg' ),
					],
					'buildable_only' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Only include step types groundhogg/create-flow can actually build (its `buildable` field). Registered-but-unbuildable types (premium, or Groundhogg-flagged-legacy) are otherwise still listed for completeness.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'settings_schema' ],
						],
						'default'     => [],
						'description' => __( 'Optional extra sections per step type. "settings_schema" adds the JSON Schema for that type\'s `settings` - can be large (especially "web_form"), so narrow with `types` first rather than expanding across everything at once.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'step_types' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'type' => [
									'type'        => 'string',
									'description' => __( 'The value to use as a step node\'s `type` in groundhogg/create-flow.', 'groundhogg' ),
								],
								'group' => [
									'type' => 'string',
									'enum' => [ 'benchmark', 'action', 'logic' ],
								],
								'name' => [
									'type'        => 'string',
									'description' => __( 'Human-readable label, as shown in wp-admin\'s flow editor.', 'groundhogg' ),
								],
								'description' => [
									'type' => 'string',
								],
								'buildable' => [
									'type'        => 'boolean',
									'description' => __( 'Whether groundhogg/create-flow can build a step of this type. See the ability description for why some registered types are excluded.', 'groundhogg' ),
								],
								'branch_keys' => [
									'type'        => 'array',
									'items'       => [ 'type' => 'string' ],
									'description' => __( 'Only present (non-empty) for branching logic types. The valid keys for that step node\'s `branches` map in groundhogg/create-flow - e.g. ["yes","no"] for if_else.', 'groundhogg' ),
								],
								'settings_schema' => [
									'type'        => 'object',
									'description' => __( 'Only present when "settings_schema" is passed in expand. The JSON Schema for this step type\'s `settings` in groundhogg/create-flow.', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$types = ! empty( $input['types'] )
			? array_values( array_intersect( Step_Type_Schema::all_registered_types(), $input['types'] ) )
			: Step_Type_Schema::all_registered_types();

		$expand = $input['expand'] ?? [];
		$search = ! empty( $input['search'] ) ? strtolower( sanitize_text_field( $input['search'] ) ) : '';

		$step_types = [];

		foreach ( $types as $type ) {

			// The internal fallback used when a step's real type is missing (e.g. a
			// disabled add-on) - not a real, creatable type, and doesn't belong to
			// any of the three groups (Error::get_group() returns '').
			if ( $type === 'error' ) {
				continue;
			}

			$info = Step_Type_Schema::get_type_info( $type );

			if ( ! $info ) {
				continue;
			}

			if ( ! empty( $input['group'] ) && $info['group'] !== $input['group'] ) {
				continue;
			}

			if ( ! empty( $input['buildable_only'] ) && ! $info['buildable'] ) {
				continue;
			}

			if ( $search && ! str_contains( strtolower( $info['name'] ), $search ) && ! str_contains( strtolower( $info['description'] ), $search ) ) {
				continue;
			}

			$branch_keys = Step_Type_Schema::branch_keys( $type );

			if ( ! empty( $branch_keys ) ) {
				$info['branch_keys'] = $branch_keys;
			}

			if ( in_array( 'settings_schema', $expand, true ) ) {
				$info['settings_schema'] = Step_Type_Schema::settings_schema( $type );
			}

			$step_types[] = $info;
		}

		return [
			'step_types' => $step_types,
		];
	}
}
