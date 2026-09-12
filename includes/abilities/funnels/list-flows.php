<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Funnel_Schema;
use function Groundhogg\get_db;

/**
 * Lists flows (funnels), so an agent can discover the flow_id and step_id to
 * pass to groundhogg/add-to-flow.
 *
 * The step list is only included when "steps" is passed in expand - it loads
 * every step object per flow.
 *
 * `campaigns` filters via the generic object_relationships-backed 'related'
 * query var (DB::query()) - a funnel is the primary/parent side of its
 * relationship to a campaign, same direction Funnel::get_related_objects('campaign')
 * reads.
 */
class List_Flows extends Ability {

	protected const string NAME       = 'groundhogg/list-flows';
	protected const string CATEGORY   = 'groundhogg-funnels';
	protected const string CAPABILITY = 'view_funnels';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Flows', 'groundhogg' ),
			'description' => __( 'List Groundhogg flows (funnels), most recently updated first. Filter by status or search the title. Pass expand to include each flow\'s steps. Use the returned id with groundhogg/add-to-flow.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the flow title.', 'groundhogg' ),
					],
					'status' => [
						'type'        => 'string',
						'enum'        => [ 'active', 'inactive', 'archived' ],
						'description' => __( 'Only include flows with this status. Contacts can only be added to "active" flows.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include flows tagged with at least one of these campaign IDs. Find IDs with groundhogg/list-campaigns.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'steps' ],
						],
						'default'     => [],
						'description' => __( 'Extra sections per flow. "steps" lists the flow\'s steps in order.', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 50,
						'default'     => 20,
						'description' => __( 'Maximum number of flows to return. total_items is unaffected.', 'groundhogg' ),
					],
					'offset' => [
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total_items' => [
						'type'        => 'integer',
						'description' => __( 'Total flows matching the query, ignoring limit/offset.', 'groundhogg' ),
					],
					'flows' => [
						'type'  => 'array',
						'items' => Funnel_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 50 ) : 20;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = [
			'limit'      => $limit,
			'offset'     => $offset,
			'found_rows' => true,
			'orderby'    => 'last_updated',
			'order'      => 'DESC',
		];

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['status'] ) ) {
			$query_vars['status'] = sanitize_text_field( $input['status'] );
		}

		if ( ! empty( $input['campaigns'] ) ) {
			$query_vars['related'] = [
				'id'   => wp_parse_id_list( $input['campaigns'] ),
				'type' => 'campaign',
			];
		}

		$db      = get_db( 'funnels' );
		$results = $db->query( $query_vars );

		// Capture found_rows() before Funnel_Schema transform runs its own queries
		// (the steps expand) - same caveat as groundhogg/list-tags.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		$flows = array_map( function ( $funnel ) use ( $expand ) {
			return Funnel_Schema::transform( $funnel, $expand );
		}, $results );

		return [
			'total_items' => $total_items,
			'flows'       => $flows,
		];
	}
}
