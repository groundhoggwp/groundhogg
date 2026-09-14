<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Throwable;
use WP_Error;

class Search_Contacts extends Ability {

	protected const NAME       = 'groundhogg/search-contacts';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'view_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

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
							// 'sql' is search-contacts' own debugging addition, not
							// part of Contact_Schema::expand_options() (it's about
							// the query as a whole, not a per-contact section, and
							// wouldn't make sense on get-contact/create-contact/
							// update-contact's single-row fetches).
							'enum' => array_merge( Contact_Schema::expand_options(), [ 'sql' ] ),
						],
						'default'     => [ 'tags' ],
						'description' => __( 'Optional extra sections to expand, beyond the standard fields. Per-contact: tags, meta (raw custom field/meta values - see groundhogg/list-custom-fields to interpret them), plus any sections an installed add-on has registered. Query-level (not per-contact): "sql" adds a top-level `sql` field with the raw SQL Contact_Query built for this search - useful for debugging why a search matched (or didn\'t match) what was expected. Note it reflects the modern query builder\'s attempt specifically; if that attempt threw and Contact_Query silently fell back to its legacy query engine, this SQL will NOT be what actually ran.', 'groundhogg' ),
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
					'sql' => [
						'type'        => 'string',
						'description' => __( 'Only present when "sql" is passed in expand. The raw SQL Contact_Query built for this search - see the `expand` input\'s description for a caveat about legacy-query fallback.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 100 ) : 25;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		// Executes immediately below, so a live query object (to_contact_query())
		// rather than the plain array to_query() gives - lets an add-on hook
		// groundhogg/segment_schema/contact_query and manipulate the query
		// directly (joins, raw where conditions), and lets pagination be applied
		// here by just chaining onto it.
		$contact_query = Segment_Schema::to_contact_query( $input );

		if ( is_wp_error( $contact_query ) ) {
			return $contact_query;
		}

		$contact_query->setLimit( $limit )->setOffset( $offset )->setFoundRows( true );

		try {
			$results = $contact_query->query();
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

		$output = [
			'total_items' => $contact_query->found_items,
			'contacts'    => $contacts,
		];

		if ( in_array( 'sql', $expand, true ) ) {
			// Called after query() rather than before: get_sql()'s own
			// maybe_setup_query() call is a no-op by this point (already run), so
			// this reflects the exact query state query() actually built - not a
			// fresh, possibly-different build. See the `expand` input's
			// description for the legacy-fallback caveat.
			$output['sql'] = $contact_query->get_sql();
		}

		return $output;
	}
}
