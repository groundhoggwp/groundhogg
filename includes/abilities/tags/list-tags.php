<?php

namespace Groundhogg\Abilities\Tags;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Tag_Schema;
use function Groundhogg\get_db;

/**
 * Lists the tags defined in Groundhogg (Contacts > Tags in the admin), so a caller can
 * discover valid tag names/IDs to pass as groundhogg/search-contacts' tags_include/
 * tags_exclude params without guessing.
 */
class List_Tags extends Ability {

	protected const string NAME       = 'groundhogg/list-tags';
	protected const string CATEGORY   = 'groundhogg-tags';
	protected const string CAPABILITY = 'manage_tags';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Tags', 'groundhogg' ),
			'description' => __( 'List the tags defined in Groundhogg. Use the returned id or name with groundhogg/search-contacts\' tags_include/tags_exclude params.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the tag name, slug, and description.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'contact_count' ],
						],
						'default'     => [],
						'description' => __( 'Optional extra sections to expand on each tag. Available: contact_count (costs an extra query per tag - only request it when actually needed).', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 200,
						'default'     => 100,
						'description' => __( 'Maximum number of tags to return.', 'groundhogg' ),
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
						'description' => __( 'Total tags matching the search, ignoring limit/offset.', 'groundhogg' ),
					],
					'tags' => [
						'type'  => 'array',
						'items' => Tag_Schema::get_schema(),
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
			'orderby'    => 'tag_name',
			'order'      => 'ASC',
		];

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		$db      = get_db( 'tags' );
		$results = $db->query( $query_vars );

		// found_rows() reads MySQL's connection-scoped FOUND_ROWS(), which only reflects
		// the immediately preceding query - it must be captured now, before the expand
		// loop below runs any further queries (e.g. contact_count's per-tag COUNT), or it
		// silently reports the found-rows of whatever query happened to run last instead
		// of this search. Confirmed live: calling found_rows() after the loop returned 1
		// instead of the correct 3 for a 3-tag search combined with expand=contact_count.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		$tags = array_map( function ( $tag ) use ( $expand ) {
			return Tag_Schema::transform( $tag, $expand );
		}, $results );

		return [
			'total_items' => $total_items,
			'tags'        => $tags,
		];
	}
}
