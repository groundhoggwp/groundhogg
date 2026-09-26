<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Step_Type_Schema;
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_Error;

/**
 * Gets one flow (funnel) as a tree of step nodes, in the same shape
 * groundhogg/create-flow takes as `steps`, plus each step's real id and state.
 * What an agent reads before changing a flow.
 *
 * By default it shows the draft: what the flow editor shows, with staged changes
 * merged in and new steps included (Funnel::while_editing()). On an active flow
 * that can differ from what contacts go through until the changes are
 * published, `view: "live"` shows that instead.
 *
 * Settings come back through Step_Type_Schema::export_settings(), in a form the
 * flow abilities accept again - including if_else's conditions as the stored
 * include_filters/exclude_filters, and references to other steps as real step
 * IDs. Step types create-flow can't build come back with `buildable: false` and
 * no settings.
 *
 * Branches: a branching logic step's branches are keyed like create-flow's
 * (if_else's "yes"/"no"). A benchmark can have steps that only run after that
 * trigger (the flow editor's "add step" inside a trigger group), those come
 * back as its "then" branch. Steps whose branch doesn't belong to any step in
 * the flow are listed in `unplaced_steps` rather than dropped.
 */
class Get_Flow extends Ability {

	protected const NAME       = 'groundhogg/get-flow';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'view_funnels';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	/**
	 * The branch key for the steps inside a benchmark, which are stored with the benchmark's ID as their branch
	 */
	const BENCHMARK_BRANCH = 'then';

	/**
	 * Which steps point at which while a flow is described, see Funnel::get_step_references_map()
	 *
	 * @var array
	 */
	protected static $references = [];

	/**
	 * The JSON Schema for a step node in the tree. Branches refer to it as `#/definitions/step_node`,
	 * see flow_schema().
	 *
	 * @return array
	 */
	public static function node_schema(): array {

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'integer',
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
				'buildable' => [
					'type'        => 'boolean',
					'description' => __( 'Whether groundhogg/create-flow can build this step type. If false, settings are omitted.', 'groundhogg' ),
				],
				'settings' => [
					'type'        => 'object',
					'description' => __( 'The step\'s settings in the shape groundhogg/list-step-types describes, except that if_else\'s conditions are the stored include_filters/exclude_filters, and references to other steps (reply_in_thread, tasks) are real step IDs.', 'groundhogg' ),
				],
				'is_entry' => [
					'type'        => 'boolean',
					'description' => __( 'Benchmarks only. Contacts can enter the flow at this trigger.', 'groundhogg' ),
				],
				'is_conversion' => [
					'type'        => 'boolean',
					'description' => __( 'Benchmarks only. Completing this trigger counts as a conversion.', 'groundhogg' ),
				],
				'can_passthru' => [
					'type'        => 'boolean',
					'description' => __( 'Benchmarks only. Contacts can pass through this trigger without completing it.', 'groundhogg' ),
				],
				'is_locked' => [
					'type'        => 'boolean',
					'description' => __( 'Locked in the flow editor, it shouldn\'t be changed.', 'groundhogg' ),
				],
				'has_unpublished_changes' => [
					'type'        => 'boolean',
					'description' => __( 'Draft view of an active flow only. The step is new or has changes contacts don\'t go through yet.', 'groundhogg' ),
				],
				'waiting_contacts' => [
					'type'        => 'integer',
					'description' => __( 'Contacts currently waiting at this step, or paused at it while the flow is inactive.', 'groundhogg' ),
				],
				'referenced_by' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'description' => __( 'IDs of the steps that point at this one in their settings (a jump, a reply in thread, a task to complete, add to flow from another flow...). It can\'t be deleted until they\'re changed or deleted too.', 'groundhogg' ),
				],
				'branches' => [
					'type'                 => 'object',
					'description'          => __( 'Branch key => the ordered step nodes in that branch. if_else has "yes" and "no". A benchmark\'s "then" branch holds steps that only run after that trigger.', 'groundhogg' ),
					'additionalProperties' => [
						'type'  => 'array',
						'items' => [
							'type' => 'object',
							'$ref' => '#/definitions/step_node',
						],
					],
				],
			],
		];
	}

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Flow', 'groundhogg' ),
			'description' => __( 'Get one Groundhogg flow (funnel) with all of its steps as a tree, in the same shape groundhogg/create-flow takes, with each step\'s settings, real id, and state. By default this is the draft the flow editor shows; on an active flow, pass view "live" for what contacts go through until changes are published. Find flow ids with groundhogg/list-flows.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'flow_id' ],
				'properties'           => [
					'flow_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'view'    => [
						'type'        => 'string',
						'enum'        => [ 'draft', 'live' ],
						'default'     => 'draft',
						'description' => __( '"draft" (default) includes changes that haven\'t been published yet. "live" is what contacts go through. Only differs for active flows.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => self::flow_schema(),
		];
	}

	/**
	 * The JSON Schema for what describe() returns
	 *
	 * @return array
	 */
	public static function flow_schema(): array {

		$step_node_schema = self::node_schema();

		return [
			'type'       => 'object',
			'definitions' => [
				'step_node' => $step_node_schema,
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
				'view' => [
					'type' => 'string',
				],
				'admin_link' => [
					'type' => 'string',
				],
				'has_unpublished_changes' => [
					'type'        => 'boolean',
					'description' => __( 'The flow is active and has changes (including deleted steps) contacts don\'t go through yet. Published with the flow editor\'s Update button.', 'groundhogg' ),
				],
				'revision' => [
					'type'        => 'string',
					'description' => __( 'Changes whenever any step in the draft changes. Compare to tell if the flow was edited since it was read.', 'groundhogg' ),
				],
				'steps' => [
					'type'  => 'array',
					'items' => $step_node_schema,
				],
				'unplaced_steps' => [
					'type'        => 'array',
					'description' => __( 'Steps whose branch doesn\'t belong to any step in the flow, so they can\'t be reached. Usually empty.', 'groundhogg' ),
					'items'       => $step_node_schema,
				],
			],
		];
	}

	public function __invoke( $input ) {

		$funnel = new Funnel( absint( $input['flow_id'] ) );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_flow_not_found', __( 'Flow not found.', 'groundhogg' ) );
		}

		return self::describe( $funnel, ( $input['view'] ?? 'draft' ) === 'live' ? 'live' : 'draft' );
	}

	/**
	 * Changes whenever any step in the draft changes
	 *
	 * @param Funnel $funnel
	 *
	 * @return string
	 */
	public static function revision( Funnel $funnel ): string {
		return md5( wp_json_encode( $funnel->snapshot() ) );
	}

	/**
	 * The flow as a tree of step nodes, see flow_schema()
	 *
	 * @param Funnel $funnel
	 * @param string $view 'draft' or 'live'
	 *
	 * @return array
	 */
	public static function describe( Funnel $funnel, string $view = 'draft' ): array {

		if ( $view === 'live' ) {
			return self::get_flow( $funnel, $view );
		}

		return $funnel->while_editing( function () use ( $funnel, $view ) {
			return self::get_flow( $funnel, $view );
		} );
	}

	/**
	 * @param Funnel $funnel
	 * @param string $view
	 *
	 * @return array
	 */
	protected static function get_flow( Funnel $funnel, string $view ) {

		$steps = $funnel->get_steps();

		self::$references = $funnel->get_step_references_map();

		// the draft of an active flow can differ from what's live
		$has_drafts = $view === 'draft' && $funnel->is_active();

		$by_branch = [];

		foreach ( $steps as $step ) {
			$by_branch[ $step->branch ][] = $step;
		}

		$tree = self::build_branch( 'main', $by_branch, $has_drafts );

		// anything left wasn't reached from the main branch
		$unplaced = [];

		foreach ( $by_branch as $branch_steps ) {
			foreach ( $branch_steps as $step ) {
				$unplaced[] = self::step_node( $step, [], $has_drafts );
			}
		}

		return [
			'id'                      => $funnel->get_id(),
			'title'                   => $funnel->get_title(),
			'status'                  => $funnel->get_status(),
			'view'                    => $view,
			'admin_link'              => $funnel->admin_link(),
			'has_unpublished_changes' => $has_drafts && Flow_Changes::has_unpublished_changes( $funnel ),
			'revision'                => self::revision( $funnel ),
			'steps'                   => $tree,
			'unplaced_steps'          => $unplaced,
		];
	}

	/**
	 * The step nodes in a branch, taking them out of $by_branch as they're placed
	 *
	 * @param string $branch
	 * @param array  $by_branch  branch => Step[] in order, by reference
	 * @param bool   $has_drafts
	 *
	 * @return array
	 */
	protected static function build_branch( string $branch, array &$by_branch, bool $has_drafts ) {

		if ( empty( $by_branch[ $branch ] ) ) {
			return [];
		}

		$steps = $by_branch[ $branch ];
		unset( $by_branch[ $branch ] );

		$nodes = [];

		foreach ( $steps as $step ) {

			$branches = [];

			foreach ( self::get_branch_keys( $step, $by_branch ) as $key => $sub_branch ) {
				$branches[ $key ] = self::build_branch( $sub_branch, $by_branch, $has_drafts );
			}

			$nodes[] = self::step_node( $step, $branches, $has_drafts );
		}

		return $nodes;
	}

	/**
	 * The branches that can hang off a step
	 *
	 * @param Step  $step
	 * @param array $by_branch branch => Step[]
	 *
	 * @return string[] branch key => stored branch string
	 */
	protected static function get_branch_keys( Step $step, array $by_branch ) {

		if ( $step->is_benchmark() ) {
			return isset( $by_branch[ "$step->ID" ] ) ? [ self::BENCHMARK_BRANCH => "$step->ID" ] : [];
		}

		if ( ! $step->is_branch_logic() ) {
			return [];
		}

		// every branch the step type has, even empty ones, plus any that have steps
		$keys = Step_Type_Schema::branch_keys( $step->get_type(), (array) Step_Type_Schema::export_settings( $step ) );

		$branches = [];

		foreach ( $keys as $key ) {
			$branches[ $key ] = "$step->ID-$key";
		}

		foreach ( array_keys( $by_branch ) as $branch ) {
			$branch = (string) $branch;
			if ( str_starts_with( $branch, "$step->ID-" ) ) {
				$branches[ substr( $branch, strlen( "$step->ID-" ) ) ] = $branch;
			}
		}

		return $branches;
	}

	/**
	 * @param Step  $step
	 * @param array $branches   branch key => step nodes
	 * @param bool  $has_drafts
	 *
	 * @return array
	 */
	protected static function step_node( Step $step, array $branches, bool $has_drafts ) {

		$settings = Step_Type_Schema::export_settings( $step );

		$node = [
			'id'               => $step->get_id(),
			'type'             => $step->get_type(),
			'type_name'        => $step->get_type_name(),
			'group'            => $step->get_group(),
			'title'            => $step->get_title(),
			'buildable'        => $settings !== null,
			'is_locked'        => $step->is_locked(),
			'waiting_contacts' => $step->get_funnel()->count_pending_events( $step ),
			'referenced_by'    => self::$references[ $step->get_id() ] ?? [],
		];

		if ( $settings !== null ) {
			// an object even when empty
			$node['settings'] = (object) $settings;
		}

		if ( $step->is_benchmark() ) {
			$node['is_entry']      = (bool) $step->is_entry;
			$node['is_conversion'] = (bool) $step->is_conversion;
			$node['can_passthru']  = (bool) $step->can_passthru;
		}

		if ( $has_drafts ) {
			$node['has_unpublished_changes'] = $step->has_changes() || $step->step_status === 'inactive';
		}

		if ( ! empty( $branches ) ) {
			$node['branches'] = $branches;
		}

		return $node;
	}
}
