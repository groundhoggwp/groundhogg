<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Contact_Query;
use Throwable;
use WP_Error;

class Search_Contacts extends Ability {

	protected const string NAME       = 'groundhogg/search-contacts';
	protected const string CATEGORY   = 'groundhogg-contacts';
	protected const string CAPABILITY = 'view_contacts';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Search Contacts', 'groundhogg' ),
			'description' => __( 'Search and list Groundhogg contacts, with pagination. All the segment params below are ANDed together; omit them all to list everyone. total_items answers "how many contacts match" regardless of limit.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array_merge( Segment_Schema::properties(), [
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'tags', 'meta' ],
						],
						'default'     => [ 'tags' ],
						'description' => __( 'Optional extra sections to expand on each contact, beyond the standard fields. Available: tags, meta (raw custom field/meta values - see groundhogg/list-custom-fields to interpret them).', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
						'default'     => 25,
						'description' => __( 'Maximum number of contacts to return (max 100). total_items in the response is unaffected by this, and is the fastest way to answer a "how many" question without needing a high limit.', 'groundhogg' ),
					],
					'offset' => [
						'type'        => 'integer',
						'minimum'     => 0,
						'default'     => 0,
						'description' => __( 'Number of contacts to skip, for pagination.', 'groundhogg' ),
					],
				] ),
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total_items' => [
						'type'        => 'integer',
						'description' => __( 'Total number of contacts matching the query, ignoring limit/offset.', 'groundhogg' ),
					],
					'contacts' => [
						'type'  => 'array',
						'items' => Contact_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 100 ) : 25;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = Segment_Schema::to_query( $input );

		if ( is_wp_error( $query_vars ) ) {
			return $query_vars;
		}

		$query_vars['number']     = $limit;
		$query_vars['offset']     = $offset;
		$query_vars['found_rows'] = true;

		try {
			$contact_query = new Contact_Query();
			$results       = $contact_query->query( $query_vars );
		} catch ( Throwable $e ) {
			// Contact_Query's modern query building already falls back to a legacy query on
			// most exceptions, but malformed values can still throw a TypeError (not an
			// Exception), which is not caught internally. Keep this broad catch as a backstop.
			return new WP_Error( 'groundhogg_invalid_query', $e->getMessage() );
		}

		$expand = $input['expand'] ?? [ 'tags' ];

		$contacts = array_map( function ( $raw ) use ( $expand ) {
			return Contact_Schema::transform( $raw, $expand );
		}, $results );

		return [
			'total_items' => $contact_query->found_items,
			'contacts'    => $contacts,
		];
	}
}
