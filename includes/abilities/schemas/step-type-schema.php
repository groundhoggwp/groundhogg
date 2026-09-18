<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use WP_Error;
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catalog of Groundhogg flow/funnel step types, shared by groundhogg/list-step-types
 * (discovery) and groundhogg/create-flow (validating/building each step's `settings`).
 * Single source of truth so the two abilities can't drift apart.
 *
 * `name`/`description`/`group` are read live from the registered step type
 * (`Plugin::instance()->step_manager`), not duplicated here - only the `settings`
 * JSON Schema (something Groundhogg itself has no declarative equivalent of; each
 * step type's own `get_settings_schema()` is a sanitizer-rules array, not a JSON
 * Schema) is hand-authored, in settings_schema() below.
 *
 * Scope, deliberately narrower than "every registered step type":
 *
 * - Premium step types are excluded entirely (not in supported_types(), no settings
 *   schema) - deferred, see the create-flow/list-step-types planning. A premium
 *   add-on can still opt its own types in via extend(), same as any other.
 * - Two non-premium but Groundhogg-flagged-legacy types are excluded from
 *   BUILTIN_TYPES (still visible via list-step-types' full registered-type
 *   listing, just not buildable): `form_fill` (superseded by `web_form`) and
 *   `email_opened` (superseded by nothing directly, but Groundhogg's own
 *   `is_legacy()` marks it deprecated).
 * - A type outside supported_types() still gets *some* schema from
 *   settings_schema() (a generic `{type: object, additionalProperties: true}`
 *   passthrough) rather than nothing - create-flow can still be asked to build
 *   one, just without real per-field validation/guidance.
 *
 * Extensible, via extend(), so a 3rd-party add-on's own step type can become
 * something groundhogg/create-flow can build - opt-in, not automatic, since a
 * step type existing at all (registered with Groundhogg's own step manager)
 * says nothing about what its settings should look like or how any
 * cross-references in them should resolve; only the add-on itself knows that:
 *
 *     Step_Type_Schema::extend(
 *         'my_addon_step',                 // already registered via
 *                                           // Plugin::instance()->step_manager->add_step(),
 *                                           // separately - extend() opts an
 *                                           // *existing* step type into
 *                                           // create-flow, it doesn't register
 *                                           // one with Groundhogg itself
 *         [
 *             'type'                 => 'object',
 *             'additionalProperties' => false,
 *             'required'             => [ 'thing_id' ],
 *             'properties'           => [
 *                 'thing_id' => [ 'type' => 'integer', 'description' => __( 'ID of an existing Thing.', 'my-plugin' ) ],
 *             ],
 *         ],
 *         [],   // branch_keys - only non-empty for a branching logic type
 *         function ( array $settings, array $declared ) {
 *             // Optional - omit entirely for a type with nothing to resolve
 *             // beyond what Groundhogg's own per-type sanitizer already does.
 *             if ( ! empty( $settings['thing_id'] ) && ! my_addon_thing_exists( $settings['thing_id'] ) ) {
 *                 return new WP_Error( 'my_addon_thing_not_found', __( 'thing_id does not match an existing Thing.', 'my-plugin' ) );
 *             }
 *             return [ 'settings' => $settings ];
 *             // or, for a setting that must be written only after
 *             // Funnel::set_step_levels() has finalized step ordering (see
 *             // task_completed's own `tasks` handling in resolve_settings()
 *             // below for why that's sometimes necessary):
 *             // return [ 'settings' => $settings, 'deferred_settings' => [ 'some_key' => $value ] ];
 *         }
 *     );
 *
 * Call this once (e.g. on `init`, after both Groundhogg and the add-on's own
 * step type registration have run) - before any ability builds its schema.
 *
 * A branch-logic type whose branch keys are defined *per step instance* (by
 * that step's own `settings`, not fixed for the whole type - e.g. a
 * split_path-style step, where a caller's `settings.branches` map names the
 * branches, plus an implicit 'else') registers branch_keys as a callable
 * instead of a plain array:
 *
 *     Step_Type_Schema::extend(
 *         'my_addon_split',
 *         [
 *             'type'                 => 'object',
 *             'additionalProperties' => false,
 *             'properties'           => [
 *                 'branches' => [
 *                     'type'        => 'object',
 *                     'description' => __( 'Maps a branch key to that branch\'s audience.', 'my-plugin' ),
 *                     'additionalProperties' => [
 *                         'type'       => 'object',
 *                         'properties' => Segment_Schema::properties(),
 *                     ],
 *                 ],
 *             ],
 *         ],
 *         function ( array $settings ): array {
 *             // Called with the real step node's `settings` while create-flow
 *             // validates/builds its `branches` map, and with `[]` when no
 *             // specific instance exists yet (groundhogg/list-step-types'
 *             // abstract, type-level discovery) - `[]` here still correctly
 *             // reports the one branch every instance always has.
 *             return array_merge( array_keys( $settings['branches'] ?? [] ), [ 'else' ] );
 *         },
 *         function ( array $settings, array $declared ) {
 *             // Translate each named branch's Segment_Schema-shaped audience
 *             // into whatever this step type's real settings need, the same
 *             // way if_else's own resolve_settings() case translates
 *             // include_condition/exclude_condition via Segment_Schema::to_filters().
 *             foreach ( (array) ( $settings['branches'] ?? [] ) as $key => $condition ) {
 *                 $filters = Segment_Schema::to_filters( (array) $condition );
 *                 if ( is_wp_error( $filters ) ) {
 *                     return $filters;
 *                 }
 *                 $settings['branches'][ $key ] = [ 'filters' => $filters ];
 *             }
 *             return [ 'settings' => $settings ];
 *         }
 *     );
 *
 * Two settings reference *other steps in the same flow being created*, not an
 * existing Groundhogg object: `task_completed`'s `tasks` and `send_email`'s
 * `reply_in_thread` (also `email_opened`'s `email_steps`, irrelevant here since
 * that type isn't supported). These are documented in their schemas as taking the
 * *local* `id` a caller assigns to an earlier step node in create-flow's input
 * tree - resolved to real step IDs via resolve_step_reference() (below, also
 * available to an add-on's own resolver), and only written after
 * Funnel::set_step_levels() has finalized step ordering, since e.g.
 * task_completed's own sanitizer filters referenced IDs through
 * `is_before($step)`, which needs real, final step_order to evaluate correctly
 * (not the temporary order a step gets at insert time).
 */
class Step_Type_Schema {

	use Has_Optin_Status;

	/**
	 * Step types groundhogg/create-flow ships already knowing how to build.
	 * Everything else either isn't registered at all, is premium (deferred), is
	 * a legacy type Groundhogg itself has superseded, or hasn't been opted in via
	 * extend() - see the class docblock.
	 */
	private const BUILTIN_TYPES = [
		// Benchmarks
		'web_form',
		'account_created',
		'link_click',
		'tag_applied',
		'tag_removed',
		'email_confirmed',
		'optin_status_changed',
		'task_completed',
		// Actions
		'send_email',
		'admin_notification',
		'apply_tag',
		'remove_tag',
		'apply_note',
		'create_task',
		'delay_timer',
		'add_to_flow',
		// Logic
		'if_else',
	];

	/**
	 * Add-on-registered step types, keyed by type. Populated by extend().
	 *
	 * @var array<string, array{settings_schema: array, branch_keys: string[]|callable, resolver: ?callable}>
	 */
	private static array $extensions = [];

	/**
	 * Opt an already-registered Groundhogg step type into groundhogg/create-flow -
	 * see the class docblock for the full picture.
	 *
	 * @param string          $type            The step type key. Must already be
	 *                                         registered with Groundhogg's own step
	 *                                         manager (Plugin::instance()->step_manager) -
	 *                                         refused with _doing_it_wrong() if not,
	 *                                         since extend() opts an existing type
	 *                                         in rather than registering a new one.
	 *                                         Also refused if already supported
	 *                                         (a BUILTIN_TYPES member or a previous
	 *                                         extend() call).
	 * @param array           $settings_schema The JSON Schema for this type's
	 *                                         `settings` in create-flow.
	 * @param string[]|callable $branch_keys   The valid `branches` map keys for this
	 *                                         type, if it's a branching logic type -
	 *                                         empty array for anything else. A plain
	 *                                         array for a fixed set (mirroring
	 *                                         if_else's hardcoded 'yes'/'no'), or a
	 *                                         callable - function( array $settings ): string[] -
	 *                                         when branch keys are dynamic, defined
	 *                                         per step instance by that step's own
	 *                                         `settings` rather than fixed by type
	 *                                         (e.g. a split_path-style step, where
	 *                                         the branches come from the caller's
	 *                                         own `settings.branches` map plus an
	 *                                         implicit 'else' - see the class
	 *                                         docblock's example). Called with `[]`
	 *                                         when no specific instance is available
	 *                                         (groundhogg/list-step-types' abstract,
	 *                                         type-level discovery) - return
	 *                                         whatever's true with no settings at
	 *                                         all (e.g. just the implicit branch).
	 * @param callable|null   $resolver        function( array $settings, array $declared ): array|WP_Error.
	 *                                         Optional - omit for a type with
	 *                                         nothing to resolve beyond what
	 *                                         Groundhogg's own per-type sanitizer
	 *                                         already does. $declared is
	 *                                         local id => ['id'=>int,'type'=>string]
	 *                                         for every step created so far (see
	 *                                         resolve_step_reference()). Returns
	 *                                         `['settings' => array]`, or
	 *                                         `['settings' => array, 'deferred_settings' => array]`
	 *                                         for a meta key/value map that must be
	 *                                         written only after
	 *                                         Funnel::set_step_levels() has run -
	 *                                         see resolve_settings()'s own docblock.
	 *                                         Return a WP_Error to reject the input.
	 *
	 * @return void
	 */
	public static function extend( string $type, array $settings_schema, $branch_keys = [], ?callable $resolver = null ) {

		if ( ! is_array( $branch_keys ) && ! is_callable( $branch_keys ) ) {
			_doing_it_wrong( __METHOD__, '$branch_keys must be an array or a callable.', '4.8' );

			return;
		}

		if ( ! Plugin::instance()->step_manager->type_is_registered( $type ) ) {
			_doing_it_wrong(
				__METHOD__,
				// Translators: %s is the step type key.
				sprintf( 'Step type "%s" is not registered with Groundhogg\'s step manager - register it there (Plugin::instance()->step_manager->add_step()) before opting it into create-flow.', $type ),
				'4.8'
			);

			return;
		}

		if ( in_array( $type, self::BUILTIN_TYPES, true ) || isset( self::$extensions[ $type ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				// Translators: %s is the step type key.
				sprintf( 'Step type "%s" is already supported by groundhogg/create-flow.', $type ),
				'4.8'
			);

			return;
		}

		self::$extensions[ $type ] = [
			'settings_schema' => $settings_schema,
			'branch_keys'     => $branch_keys,
			'resolver'        => $resolver,
		];
	}

	/**
	 * Step types groundhogg/create-flow can actually build - the built-in set
	 * plus anything opted in via extend(). Everything else either isn't
	 * registered at all, is premium (deferred), or is a legacy type Groundhogg
	 * itself has superseded - see the class docblock.
	 *
	 * @return string[]
	 */
	public static function supported_types(): array {
		return array_merge( self::BUILTIN_TYPES, array_keys( self::$extensions ) );
	}

	/**
	 * Every step type Groundhogg has registered on this site right now (includes
	 * premium types, if that add-on is active, and the two legacy types excluded
	 * from supported_types()) - the full universe groundhogg/list-step-types can
	 * report on.
	 *
	 * @return string[]
	 */
	public static function all_registered_types(): array {
		return Plugin::instance()->step_manager->get_types();
	}

	/**
	 * Live metadata for one registered step type - name/description/group read
	 * directly from the registered Funnel_Step instance, not duplicated here.
	 *
	 * @param string $type
	 *
	 * @return array{type: string, group: string, name: string, description: string, buildable: bool}|null
	 *         Null if $type isn't registered on this site at all.
	 */
	public static function get_type_info( string $type ): ?array {

		$manager = Plugin::instance()->step_manager;

		if ( ! $manager->type_is_registered( $type ) ) {
			return null;
		}

		$element = $manager->get_element( $type );

		return [
			'type'        => $type,
			'group'       => $element->get_group(),
			'name'        => $element->get_name(),
			'description' => $element->get_description(),
			'buildable'   => in_array( $type, self::supported_types(), true ),
		];
	}

	/**
	 * The valid branch keys for a branch-logic step type - what create-flow's input
	 * tree's `branches` map keys must be for a step of this type. `if_else` is
	 * fixed ('yes'/'no' - Groundhogg's own If_Else::get_branches() is hardcoded,
	 * not configurable); an extend()'d type's own branch_keys come from its
	 * registration, either a fixed array or (for a type whose branches are
	 * defined per-instance by its own `settings` - e.g. a split_path-style step,
	 * see extend()'s own docblock) a callable evaluated against $settings. Any
	 * other type (including a premium branch-logic type not opted in) returns [].
	 *
	 * @param string $type
	 * @param array  $settings The step node's own `settings`, for a type whose
	 *                         branch_keys is a callable. Pass [] (the default) for
	 *                         the abstract, type-level case with no specific
	 *                         instance available (groundhogg/list-step-types'
	 *                         discovery) - a callable should return whatever's
	 *                         true with no settings at all (e.g. just an implicit
	 *                         branch every instance of the type always has).
	 *
	 * @return string[]
	 */
	public static function branch_keys( string $type, array $settings = [] ): array {

		if ( $type === 'if_else' ) {
			return [ 'yes', 'no' ];
		}

		$branch_keys = self::$extensions[ $type ]['branch_keys'] ?? [];

		if ( is_callable( $branch_keys ) ) {
			return (array) call_user_func( $branch_keys, $settings );
		}

		return $branch_keys;
	}

	/**
	 * The JSON Schema for a step type's `settings` input - what create-flow
	 * validates each step node's `settings` against, and what list-step-types
	 * echoes back per type for discovery. Falls back to a generic passthrough
	 * object for anything not in supported_types() - see the class docblock.
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public static function settings_schema( string $type ): array {

		if ( isset( self::$extensions[ $type ] ) ) {
			return self::$extensions[ $type ]['settings_schema'];
		}

		switch ( $type ) {
			case 'web_form':
				return self::web_form_settings_schema();
			case 'account_created':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'role' => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => __( 'WordPress role slugs (e.g. "subscriber", "administrator", or a custom role) - only accounts created with one of these roles trigger this. Empty/omitted matches any role.', 'groundhogg' ),
						],
					],
				];
			case 'link_click':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'redirect_to' => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => __( 'Where the tracking link redirects the contact after they click it.', 'groundhogg' ),
						],
					],
				];
			case 'tag_applied':
				return self::tag_condition_settings_schema( __( 'Only trigger when at least one (or all, with condition) of these tags is applied.', 'groundhogg' ) );
			case 'tag_removed':
				return self::tag_condition_settings_schema( __( 'Only trigger when at least one (or all, with condition) of these tags is removed.', 'groundhogg' ) );
			case 'email_confirmed':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [],
				];
			case 'optin_status_changed':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'from_status' => [
							'type'        => 'array',
							'items'       => [ 'enum' => self::optin_status_enum() ],
							'description' => __( 'Only trigger when changing FROM one of these opt-in statuses. Empty/omitted matches any prior status.', 'groundhogg' ),
						],
						'status' => [
							'type'        => 'array',
							'items'       => [ 'enum' => self::optin_status_enum() ],
							'description' => __( 'Only trigger when changing TO one of these opt-in statuses. Empty/omitted matches any new status.', 'groundhogg' ),
						],
					],
				];
			case 'task_completed':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'tasks' => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => __( 'The local `id` (see create-flow\'s step node `id`) of one or more create_task action steps earlier in this flow - only completing one of THOSE specific tasks triggers this. Empty/omitted matches completing any task. Each referenced step must already appear earlier in the flow and must be a create_task step.', 'groundhogg' ),
						],
						'condition' => [
							'type'        => 'string',
							'enum'        => [ 'any', 'all' ],
							'default'     => 'any',
							'description' => __( '"any" = triggers once any one of `tasks` is completed; "all" = only once every one of them has been.', 'groundhogg' ),
						],
					],
				];
			case 'send_email':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'email_id' ],
					'properties'           => [
						'email_id' => [
							'type'        => 'integer',
							'description' => __( 'ID of an existing, ready-to-send Groundhogg email - see groundhogg/list-email-templates. Must already exist; create-flow does not create emails.', 'groundhogg' ),
						],
						'skip_if_confirmed' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Skip sending if the contact has already confirmed their email address (opt-in confirmed).', 'groundhogg' ),
						],
						'reply_in_thread' => [
							'type'        => 'string',
							'description' => __( 'The local `id` of an earlier send_email action step in this flow - threads this email\'s reply under that one. Omit for no threading.', 'groundhogg' ),
						],
					],
				];
			case 'admin_notification':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'send_to', 'subject' ],
					'properties'           => [
						'send_to' => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => __( 'Recipients: literal email addresses, or the special tokens "{owner_email}" (the contact\'s owner) / "{email}" (the contact themselves).', 'groundhogg' ),
						],
						'reply_to_type' => [
							'type'        => 'string',
							'enum'        => [ 'contact', 'owner', 'custom' ],
							'description' => __( 'Whose address the "reply-to" header uses. "custom" requires `reply_to`.', 'groundhogg' ),
						],
						'reply_to' => [
							'type'        => 'string',
							'description' => __( 'Email address or a merge-tag token - only used when reply_to_type is "custom".', 'groundhogg' ),
						],
						'hide_admin_links' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Hide the "view contact"/"edit" admin links normally appended to this notification.', 'groundhogg' ),
						],
						'subject' => [
							'type' => 'string',
						],
						'note_text' => [
							'type'        => 'string',
							'description' => __( 'The notification body (HTML allowed).', 'groundhogg' ),
						],
					],
				];
			case 'apply_tag':
				return self::tags_only_settings_schema( __( 'Tag names, slugs, or IDs to apply. Resolved to existing tags only - a name matching no tag is an error, never a new tag.', 'groundhogg' ) );
			case 'remove_tag':
				return self::tags_only_settings_schema( __( 'Tag names, slugs, or IDs to remove. Resolved to existing tags only - a name matching no tag is an error, never a new tag.', 'groundhogg' ) );
			case 'apply_note':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'note_text' ],
					'properties'           => [
						'note_type' => [
							'type'    => 'string',
							'enum'    => [ 'note', 'call', 'email', 'meeting' ],
							'default' => 'note',
						],
						'note_text' => [
							'type' => 'string',
						],
					],
				];
			case 'create_task':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'summary' ],
					'properties'           => [
						'summary' => [
							'type' => 'string',
						],
						'content' => [
							'type' => 'string',
						],
						'task_type' => [
							'type'    => 'string',
							'enum'    => [ 'task', 'email', 'call', 'meeting' ],
							'default' => 'task',
						],
						'time' => [
							'type'        => 'string',
							'default'     => '17:00:00',
							'description' => __( 'Time of day the task is due, H:i:s.', 'groundhogg' ),
						],
						'delay_unit' => [
							'type'    => 'string',
							'enum'    => [ 'days', 'weeks', 'months' ],
							'default' => 'days',
						],
						'delay_amount' => [
							'type'        => 'integer',
							'minimum'     => 0,
							'default'     => 0,
							'description' => __( 'How long after this step runs the task is due, in delay_unit units.', 'groundhogg' ),
						],
						'assign_to' => [
							'type'        => 'integer',
							'default'     => 0,
							'description' => __( 'WordPress user ID to assign the task to. 0 (default) assigns it to the contact\'s owner.', 'groundhogg' ),
						],
					],
				];
			case 'delay_timer':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => [
						'delay_amount' => [
							'type'    => 'integer',
							'minimum' => 0,
							'default' => 0,
						],
						'delay_type' => [
							'type'    => 'string',
							'enum'    => [ 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'none' ],
							'default' => 'days',
						],
						'run_on_type' => [
							'type'        => 'string',
							'enum'        => [ 'any', 'weekday', 'weekend', 'day_of_month', 'day_of_week' ],
							'default'     => 'any',
							'description' => __( 'Restrict which days the delay is allowed to land on, beyond the raw delay_amount/delay_type.', 'groundhogg' ),
						],
						'run_when' => [
							'type'        => 'string',
							'enum'        => [ 'now', 'later', 'between' ],
							'default'     => 'now',
							'description' => __( 'Whether to also restrict to a specific time of day (run_time) or window (run_time/run_time_to).', 'groundhogg' ),
						],
						'run_time' => [
							'type'        => 'string',
							'default'     => '09:00:00',
							'description' => __( 'H:i:s - used when run_when is "later" or "between".', 'groundhogg' ),
						],
						'run_time_to' => [
							'type'        => 'string',
							'default'     => '17:00:00',
							'description' => __( 'H:i:s - only used when run_when is "between".', 'groundhogg' ),
						],
						'send_in_timezone' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Evaluate run_time/run_time_to in the contact\'s own timezone rather than the site\'s.', 'groundhogg' ),
						],
						'run_on_dow_type' => [
							'type'        => 'string',
							'enum'        => [ 'any', 'first', 'second', 'third', 'fourth', 'last' ],
							'default'     => 'any',
							'description' => __( 'Only used when run_on_type is "day_of_week" - e.g. "first" + run_on_dow to mean "the first Monday of the month".', 'groundhogg' ),
						],
						'run_on_dow' => [
							'type'  => 'array',
							'items' => [ 'type' => 'string', 'enum' => [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] ],
						],
						'run_on_month_type' => [
							'type'    => 'string',
							'enum'    => [ 'any', 'specific' ],
							'default' => 'any',
						],
						'run_on_months' => [
							'type'  => 'array',
							'items' => [ 'type' => 'string', 'enum' => [ 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december' ] ],
						],
						'run_on_dom' => [
							'type'        => 'array',
							'items'       => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 31 ],
							'description' => __( 'Only used when run_on_type is "day_of_month".', 'groundhogg' ),
						],
					],
				];
			case 'add_to_flow':
				return [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'flow_id' ],
					'properties'           => [
						'flow_id' => [
							'type'        => 'integer',
							'description' => __( 'ID of an existing flow to add the contact to - see groundhogg/list-flows. Must already exist; create-flow does not create the target flow.', 'groundhogg' ),
						],
						'step_id' => [
							'type'        => 'integer',
							'description' => __( 'Enter the contact at this step instead of flow_id\'s first action step. Must belong to flow_id - see groundhogg/list-flows with expand "steps".', 'groundhogg' ),
						],
					],
				];
			case 'if_else':
				return self::if_else_settings_schema();
			default:
				// Not in supported_types() - accepted, but with no real validation.
				// See the class docblock.
				return [
					'type'                 => 'object',
					'additionalProperties' => true,
				];
		}
	}

	/**
	 * Resolve one step's `settings` into what should actually be written via
	 * Step::update_meta() - the single place cross-references/translations
	 * happen for both built-in and add-on-registered (extend()'s $resolver)
	 * step types alike, so groundhogg/create-flow itself doesn't need any
	 * type-specific knowledge beyond what's registered here.
	 *
	 * @param string $type
	 * @param array  $settings The input node's `settings`, as given.
	 * @param array  $declared local id => ['id'=>int,'type'=>string] of every
	 *                         step created so far (earlier in create-flow's
	 *                         input tree) - see resolve_step_reference().
	 *
	 * @return array{settings: array, deferred_settings: array}|WP_Error
	 *         `settings` is written immediately by the caller; `deferred_settings`
	 *         (a meta key=>value map, often empty) is written only after
	 *         Funnel::set_step_levels() has finalized step ordering - for any
	 *         setting whose own sanitizer depends on final order, like
	 *         task_completed's `tasks` below (filtered through
	 *         `is_before($step)`, which needs real, final step_order to
	 *         evaluate correctly - not the provisional order a step gets at
	 *         insert time).
	 */
	public static function resolve_settings( string $type, array $settings, array $declared ) {

		if ( isset( self::$extensions[ $type ]['resolver'] ) ) {

			$result = call_user_func( self::$extensions[ $type ]['resolver'], $settings, $declared );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return wp_parse_args( (array) $result, [ 'settings' => $settings, 'deferred_settings' => [] ] );
		}

		$deferred = [];

		switch ( $type ) {

			case 'web_form':

				// Form_v2 (includes/form/form-v2.php, get_cleaned_json_config()) reads
				// fields/button/recaptcha/turnstile as ONE nested object under a single
				// 'form' meta key, not as separate flat meta keys the way every other
				// step type's settings are written (and the way web_form_settings_schema()
				// otherwise describes them, matching groundhogg/list-step-types' generic
				// per-key documentation). after_submit/success_message/success_page/
				// form_name/enable_ajax/accent_color/theme genuinely ARE flat meta keys
				// (Form_v2::get_after_submit()/get_success_message() read them directly) -
				// only these four belong nested.
				$form_config = [];

				foreach ( [ 'fields', 'button', 'recaptcha', 'turnstile' ] as $key ) {
					if ( isset( $settings[ $key ] ) ) {
						$form_config[ $key ] = $settings[ $key ];
						unset( $settings[ $key ] );
					}
				}

				if ( ! empty( $form_config ) ) {
					$settings['form'] = $form_config;
				}

				break;

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

					$ref = self::resolve_step_reference( $settings['reply_in_thread'], $declared, 'send_email' );

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

						$ref = self::resolve_step_reference( $local_ref, $declared, 'create_task' );

						if ( is_wp_error( $ref ) ) {
							return $ref;
						}

						$ids[] = $ref;
					}

					$deferred['tasks'] = $ids;
				}

				// Written later regardless of whether `tasks` was given (an empty
				// deferred write is harmless, and keeps this one code path).
				unset( $settings['tasks'] );

				break;

			case 'add_to_flow':

				if ( ! empty( $settings['flow_id'] ) ) {

					$funnel = new Funnel( absint( $settings['flow_id'] ) );

					if ( ! $funnel->exists() ) {
						return new WP_Error( 'groundhogg_flow_not_found', __( 'add_to_flow\'s flow_id does not match an existing flow. Find one with groundhogg/list-flows.', 'groundhogg' ) );
					}

					unset( $settings['flow_id'] );
					$settings['funnel_id'] = $funnel->get_id();

					if ( ! empty( $settings['step_id'] ) ) {

						$target_step = new Step( absint( $settings['step_id'] ) );

						if ( ! $target_step->exists() || $target_step->get_funnel_id() !== $funnel->get_id() ) {
							return new WP_Error( 'groundhogg_step_not_in_flow', __( 'add_to_flow\'s step_id does not belong to flow_id.', 'groundhogg' ) );
						}

						$settings['step_id'] = $target_step->get_id();
					}
				}

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

		return [ 'settings' => $settings, 'deferred_settings' => $deferred ];
	}

	/**
	 * Resolve a settings field referencing another step in the same flow by its
	 * local `id` (create-flow's step node `id`), requiring it to already exist
	 * (i.e. be declared earlier in the input tree) and be of the expected step
	 * type. Available to an add-on's own extend() resolver, not just the
	 * built-in send_email/task_completed handling above.
	 *
	 * @param mixed  $local_id
	 * @param array  $declared      local id => ['id'=>int,'type'=>string].
	 * @param string $expected_type
	 *
	 * @return int|WP_Error The real step ID.
	 */
	public static function resolve_step_reference( $local_id, array $declared, string $expected_type ) {

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

	/**
	 * Shared shape for apply_tag/remove_tag (action steps - a single `tags` list,
	 * no `condition`, unlike the tag_applied/tag_removed benchmarks).
	 *
	 * @param string $tags_description
	 *
	 * @return array
	 */
	private static function tags_only_settings_schema( string $tags_description ): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'tags' ],
			'properties'           => [
				'tags' => [
					'type'        => 'array',
					'items'       => [ 'type' => [ 'string', 'integer' ] ],
					'description' => $tags_description,
				],
			],
		];
	}

	/**
	 * Shared shape for tag_applied/tag_removed (benchmark steps - `tags` plus an
	 * any/all `condition`, unlike the apply_tag/remove_tag actions).
	 *
	 * @param string $tags_description
	 *
	 * @return array
	 */
	private static function tag_condition_settings_schema( string $tags_description ): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'tags' ],
			'properties'           => [
				'tags' => [
					'type'        => 'array',
					'items'       => [ 'type' => [ 'string', 'integer' ] ],
					'description' => $tags_description,
				],
				'condition' => [
					'type'        => 'string',
					'enum'        => [ 'any', 'all' ],
					'default'     => 'any',
					'description' => __( '"any" = at least one of `tags`; "all" = every one of them.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * if_else's settings, expressed via Segment_Schema rather than Groundhogg's raw
	 * Filters DSL directly - `include_condition`/`exclude_condition` here use the
	 * exact same audience-param shape as groundhogg/search-contacts and friends
	 * (Segment_Schema::properties()), translated to the step's real
	 * `include_filters`/`exclude_filters` meta via Segment_Schema::to_filters() at
	 * write time. Deliberately NOT named `include_filters`/`exclude_filters` to
	 * avoid implying this is Groundhogg's native Filters DSL shape - it isn't, it's
	 * translated into that shape.
	 *
	 * @return array
	 */
	private static function if_else_settings_schema(): array {

		$condition_schema = [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => Segment_Schema::properties(),
		];

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'include_condition' => array_merge( $condition_schema, [
					'description' => __( 'Contacts matching this segment (same params as groundhogg/search-contacts) go down the "yes" branch. Omit/empty on both include_condition and exclude_condition sends everyone down "yes".', 'groundhogg' ),
				] ),
				'exclude_condition' => array_merge( $condition_schema, [
					'description' => __( 'Contacts matching this segment are excluded from "yes" (sent down "no") even if they matched include_condition.', 'groundhogg' ),
				] ),
			],
		];
	}

	/**
	 * web_form's field types, excluding `button`/`recaptcha`/`turnstile` - those
	 * live at `form.button`/`form.recaptcha`/`form.turnstile`, not inside
	 * `form.fields[]`. See Form_v2::register_fields() (includes/form/form-v2.php).
	 */
	private const WEB_FORM_FIELD_TYPES = [
		'first', 'last', 'email', 'phone',
		'line1', 'line2', 'city', 'state', 'zip_code', 'country',
		'gdpr', 'terms',
		'text', 'hidden', 'custom_email', 'tel', 'url', 'date', 'datetime', 'time', 'number', 'textarea',
		'dropdown', 'radio', 'checkboxes', 'checkbox',
		'file', 'birthday', 'custom_field', 'html',
	];

	/**
	 * One field object's schema - shared (as a union of every field type's own
	 * properties, not a strict per-type discriminated schema) by `form.fields[]`
	 * items and `form.button`. See the class docblock on web_form_settings_schema()
	 * for why this isn't a full oneOf-per-type schema.
	 *
	 * @return array
	 */
	private static function web_form_field_schema(): array {
		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => [ 'type' ],
			'properties'           => [
				'id' => [
					'type'        => 'string',
					'description' => __( 'A unique key for this field within the form, e.g. "email-1". Auto-generated if omitted.', 'groundhogg' ),
				],
				'type' => [
					'type' => 'string',
					'enum' => self::WEB_FORM_FIELD_TYPES,
				],
				'name' => [
					'type'        => 'string',
					'description' => __( 'Meta/property key the submitted value is stored under. Several types force their own fixed name regardless of this: first -> first_name, last -> last_name, email -> email, phone -> {phone_type}_phone, and the address fields line1/line2/city/state/zip_code/country each use their own name.', 'groundhogg' ),
				],
				'label' => [
					'type' => 'string',
				],
				'value' => [
					'type'        => 'string',
					'description' => __( 'Default/static value. Supports merge tags.', 'groundhogg' ),
				],
				'placeholder' => [
					'type' => 'string',
				],
				'className' => [
					'type' => 'string',
				],
				'hide_label' => [
					'type'    => 'boolean',
					'default' => false,
				],
				'required' => [
					'type'    => 'boolean',
					'default' => false,
				],
				'column_width' => [
					'type'    => 'string',
					'enum'    => [ '1/1', '1/2', '1/3', '1/4', '2/3', '3/4' ],
					'default' => '1/1',
				],
				'redact' => [
					'type'        => 'integer',
					'enum'        => [ 0, 1, 6, 12, 24 ],
					'default'     => 0,
					'description' => __( 'Hours after which to auto-redact this value from stored submissions (0 = never). Only meaningful on types text/hidden/url/date/datetime/time/number/textarea/custom_field.', 'groundhogg' ),
				],
				'phone_type' => [
					'type'        => 'string',
					'enum'        => [ 'primary', 'mobile', 'company' ],
					'default'     => 'primary',
					'description' => __( 'Only for type "phone".', 'groundhogg' ),
				],
				'options' => [
					'type'        => 'array',
					'description' => __( 'Only for type "dropdown"/"radio"/"checkboxes". Each entry is [value, comma-separated tag names/slugs/IDs to apply if this option is selected - may be an empty string for none].', 'groundhogg' ),
					'items'       => [
						'type'     => 'array',
						'minItems' => 1,
						'maxItems' => 2,
						'items'    => [ 'type' => 'string' ],
					],
				],
				'multiple' => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'Only for type "dropdown" - allow selecting more than one option.', 'groundhogg' ),
				],
				'checked' => [
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'Only for type "checkbox" - default checked state.', 'groundhogg' ),
				],
				'tags' => [
					'type'        => 'array',
					'items'       => [ 'type' => [ 'string', 'integer' ] ],
					'description' => __( 'Only for type "checkbox" - tag names/slugs/IDs applied to the contact if checked on submit.', 'groundhogg' ),
				],
				'file_types' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => __( 'Only for type "file" - allowed extensions without the dot, e.g. "pdf", "jpg".', 'groundhogg' ),
				],
				'property' => [
					'type'        => 'string',
					'description' => __( 'Only for type "custom_field" - the custom field\'s id or name (see groundhogg/list-custom-fields). The field then behaves as whichever type that custom field is defined as.', 'groundhogg' ),
				],
				'html' => [
					'type'        => 'string',
					'description' => __( 'Only for type "html" - the literal HTML block to display (no submission behavior).', 'groundhogg' ),
				],
				'text' => [
					'type'        => 'string',
					'description' => __( 'Only for the button object (form.button) - its label. Distinct from `label`.', 'groundhogg' ),
				],
			],
		];
	}

	/**
	 * web_form's settings ("form" field object). Field-level shape
	 * (form.fields[]/form.button) is a union of all field types' own properties
	 * rather than a strict oneOf-per-type schema - Form_v2 (includes/form/form-v2.php)
	 * has no declarative per-type schema of its own to mirror (each type is a
	 * closure reading `$field[...]` defensively), so a fully strict discriminated
	 * union here would mean re-deriving and maintaining 30 branches by hand with no
	 * authoritative source to check them against. This still validates `type`
	 * against the real registered field types and every property's own shape/enum -
	 * it just doesn't forbid e.g. `options` being present on a `text` field.
	 *
	 * Layout is flat: `form.fields` is a plain ordered array, each field carrying
	 * its own `column_width` - NOT a nested row/column tree (that's a separate,
	 * legacy, shortcode-based system Web_Form doesn't use).
	 *
	 * @return array
	 */
	private static function web_form_settings_schema(): array {

		$recaptcha_like = function ( array $theme_enum, array $size_enum ): array {
			return [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'enabled' => [ 'type' => 'boolean', 'default' => false ],
					'captcha_theme' => [ 'type' => 'string', 'enum' => $theme_enum ],
					'captcha_size' => [ 'type' => 'string', 'enum' => $size_enum ],
				],
			];
		};

		return [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'fields' => [
					'type'        => 'array',
					'items'       => self::web_form_field_schema(),
					'description' => __( 'The form\'s input fields, in display order.', 'groundhogg' ),
				],
				'button' => array_merge( self::web_form_field_schema(), [
					'description' => __( 'The submit button. `type` should be "button"; its label is `text`, not `label`.', 'groundhogg' ),
				] ),
				'recaptcha' => $recaptcha_like( [ 'light', 'dark' ], [ 'normal', 'compact' ] ),
				'turnstile' => $recaptcha_like( [ 'auto', 'light', 'dark' ], [ 'normal', 'flexible', 'compact' ] ),
				'form_name' => [
					'type'    => 'string',
					'default' => 'Web Form',
				],
				'enable_ajax' => [
					'type'    => 'boolean',
					'default' => true,
				],
				'after_submit' => [
					'type'    => 'string',
					'enum'    => [ '', 'success_message', 'success_page', 'reload_page' ],
					'default' => 'success_message',
				],
				'accent_color' => [
					'type'        => 'string',
					'description' => __( 'Hex color, e.g. "#4064e6".', 'groundhogg' ),
				],
				'theme' => [
					'type' => 'string',
				],
				'success_message' => [
					'type'        => 'string',
					'description' => __( 'Shown when after_submit is "success_message".', 'groundhogg' ),
				],
				'success_page' => [
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'Redirect target when after_submit is "success_page".', 'groundhogg' ),
				],
			],
		];
	}
}
