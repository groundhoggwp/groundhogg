<?php

namespace Groundhogg\Abilities\Campaigns;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Campaign_Schema;
use function Groundhogg\get_db;

/**
 * Lists the campaigns defined in Groundhogg (Contacts > Campaigns in the admin), so a caller
 * can discover valid campaign ids/names to pass as create-email-template's/update-email-template's
 * campaigns param, or to filter flows/broadcasts/emails by campaign, without guessing.
 */
class List_Campaigns extends Ability {

	protected const NAME       = 'groundhogg/list-campaigns';
	protected const CATEGORY   = 'groundhogg-campaigns';
	protected const CAPABILITY = 'manage_campaigns';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Campaigns', 'groundhogg' ),
			'description' => __( 'List the campaigns defined in Groundhogg. Use the returned id or name with groundhogg/create-email-template\'s or groundhogg/update-email-template\'s campaigns param.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the campaign name, slug, and description.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'email_count', 'broadcast_count', 'funnel_count' ],
						],
						'default'     => [],
						'description' => __( 'Optional extra sections to expand on each campaign. Available: email_count, broadcast_count, funnel_count (each costs an extra query per campaign - only request what you need).', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 200,
						'default'     => 100,
						'description' => __( 'Maximum number of campaigns to return.', 'groundhogg' ),
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
						'description' => __( 'Total campaigns matching the search, ignoring limit/offset.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'  => 'array',
						'items' => Campaign_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 200 ) : 100;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = [
			'limit'      => $limit,
			'offset'     => $offset,
			'found_rows' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		$db      = get_db( 'campaigns' );
		$results = $db->query( $query_vars );

		// found_rows() reads MySQL's connection-scoped FOUND_ROWS(), which only reflects
		// the immediately preceding query - it must be captured now, before the expand
		// loop below runs any further queries (e.g. *_count's per-campaign COUNT), or it
		// silently reports the found-rows of whatever query happened to run last instead
		// of this search.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		$campaigns = array_map( function ( $campaign ) use ( $expand ) {
			return Campaign_Schema::transform( $campaign, $expand );
		}, $results );

		return [
			'total_items' => $total_items,
			'campaigns'   => $campaigns,
		];
	}
}
