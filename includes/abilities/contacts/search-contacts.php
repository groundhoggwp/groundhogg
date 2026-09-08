<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Contact_Query;
use Throwable;
use WP_Error;
use function Groundhogg\parse_tag_list;

class Search_Contacts extends Ability {

	use Has_Optin_Status;

	protected const string NAME       = 'groundhogg/search-contacts';
	protected const string CATEGORY   = 'groundhogg-contacts';
	protected const string CAPABILITY = 'view_contacts';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Search Contacts', 'groundhogg' ),
			'description' => __( 'Search and list Groundhogg contacts, with pagination. Matches against the contact\'s name and email when a search term is provided; combine with the other params below, all ANDed together. The total_items in the response answers "how many contacts match" regardless of limit.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search matched against the contact\'s name and email address.', 'groundhogg' ),
					],
					'include' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include contacts with these contact IDs.', 'groundhogg' ),
					],
					'exclude' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Exclude contacts with these contact IDs.', 'groundhogg' ),
					],
					'tags_include' => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Only include contacts with at least one of these tags (or all of them, if tags_include_needs_all is true). Accepts tag names, slugs, or numeric IDs - resolved automatically, no need to look up IDs first.', 'groundhogg' ),
					],
					'tags_include_needs_all' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'If true, a contact must have ALL of tags_include rather than just one of them.', 'groundhogg' ),
					],
					'tags_exclude' => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Exclude contacts that have any of these tags (or only exclude when they have all of them, if tags_exclude_needs_all is true). Accepts tag names, slugs, or numeric IDs.', 'groundhogg' ),
					],
					'tags_exclude_needs_all' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'If true, a contact is only excluded when it has ALL of tags_exclude, rather than just one of them.', 'groundhogg' ),
					],
					'optin_status' => [
						'type'        => 'array',
						'items'       => [
							'enum' => self::optin_status_enum(),
						],
						'description' => __( 'Only include contacts with one of these opt-in statuses. Accepts either the raw int (see groundhogg/get-contact\'s optin_status.value) or its string label.', 'groundhogg' ),
					],
					'optin_status_exclude' => [
						'type'        => 'array',
						'items'       => [
							'enum' => self::optin_status_enum(),
						],
						'description' => __( 'Exclude contacts with one of these opt-in statuses. Accepts either the raw int or its string label.', 'groundhogg' ),
					],
					'marketable' => [
						'type'        => 'boolean',
						'description' => __( 'true to only include contacts who can currently be marketed to, false to only include contacts who cannot. Omit to include both.', 'groundhogg' ),
					],
					'owner' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include contacts owned by one of these WordPress user IDs.', 'groundhogg' ),
					],
					'users_include' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include contacts linked to one of these WordPress user IDs (i.e. the contact has a user account).', 'groundhogg' ),
					],
					'users_exclude' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Exclude contacts linked to one of these WordPress user IDs.', 'groundhogg' ),
					],
					'has_user' => [
						'type'        => 'boolean',
						'description' => __( 'true to only include contacts that have a linked WordPress user account, false to only include contacts that don\'t.', 'groundhogg' ),
					],
					'after' => [
						'type'        => 'string',
						'format'      => 'date',
						'description' => __( 'Only include contacts created after this date (Y-m-d).', 'groundhogg' ),
					],
					'before' => [
						'type'        => 'string',
						'format'      => 'date',
						'description' => __( 'Only include contacts created before this date (Y-m-d).', 'groundhogg' ),
					],
					'saved_search' => [
						'type'        => 'string',
						'description' => __( 'The ID of an existing saved search (Contacts > Saved Searches in the admin) to apply. Merges that saved search\'s own query on top of any other params given here; those other params still apply, so a param that conflicts with the saved search can over-narrow the results.', 'groundhogg' ),
					],
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
				],
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

		$query_vars = [
			'number'     => $limit,
			'offset'     => $offset,
			'found_rows' => true,
		];

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		// Maps directly to Contact_Query's own include/exclude query vars - no rename
		// needed here, since this ability's tags/meta-sections param is `expand`, not
		// `include` (see the schema above).
		if ( ! empty( $input['include'] ) ) {
			$query_vars['include'] = wp_parse_id_list( $input['include'] );
		}

		if ( ! empty( $input['exclude'] ) ) {
			$query_vars['exclude'] = wp_parse_id_list( $input['exclude'] );
		}

		if ( ! empty( $input['tags_include'] ) ) {
			// $create = false: a search should never create a tag as a side effect of
			// looking one up by name.
			$resolved_tags = parse_tag_list( $input['tags_include'], 'ID', false );

			if ( empty( $resolved_tags ) ) {
				// None of the given tags exist. Contact_Query::tags_include() treats an
				// empty resolved list as "no constraint" (matches every contact) rather
				// than "matches no contact" - the opposite of what a caller asking for a
				// nonexistent tag means. Short-circuit instead of silently returning the
				// whole table.
				return [
					'total_items' => 0,
					'contacts'    => [],
				];
			}

			$query_vars['tags_include'] = $resolved_tags;

			if ( ! empty( $input['tags_include_needs_all'] ) ) {
				$query_vars['tags_include_needs_all'] = true;
			}
		}

		if ( ! empty( $input['tags_exclude'] ) ) {
			$query_vars['tags_exclude'] = parse_tag_list( $input['tags_exclude'], 'ID', false );

			if ( ! empty( $input['tags_exclude_needs_all'] ) ) {
				$query_vars['tags_exclude_needs_all'] = true;
			}
		}

		if ( ! empty( $input['optin_status'] ) && is_array( $input['optin_status'] ) ) {
			$query_vars['optin_status'] = self::resolve_optin_statuses( $input['optin_status'] );
		}

		if ( ! empty( $input['optin_status_exclude'] ) && is_array( $input['optin_status_exclude'] ) ) {
			$query_vars['optin_status_exclude'] = self::resolve_optin_statuses( $input['optin_status_exclude'] );
		}

		if ( isset( $input['marketable'] ) ) {
			$query_vars['marketable'] = (bool) $input['marketable'];
		}

		if ( ! empty( $input['owner'] ) ) {
			$query_vars['owner'] = wp_parse_id_list( $input['owner'] );
		}

		if ( ! empty( $input['users_include'] ) ) {
			$query_vars['users_include'] = wp_parse_id_list( $input['users_include'] );
		}

		if ( ! empty( $input['users_exclude'] ) ) {
			$query_vars['users_exclude'] = wp_parse_id_list( $input['users_exclude'] );
		}

		if ( isset( $input['has_user'] ) && $input['has_user'] ) {
			$query_vars['has_user'] = true;
		} else if ( isset( $input['has_user'] ) && ! $input['has_user'] ) {
			$query_vars['no_user'] = true;
		}

		if ( ! empty( $input['after'] ) ) {
			$query_vars['after'] = sanitize_text_field( $input['after'] );
		}

		if ( ! empty( $input['before'] ) ) {
			$query_vars['before'] = sanitize_text_field( $input['before'] );
		}

		if ( ! empty( $input['saved_search'] ) ) {
			$query_vars['saved_search'] = sanitize_text_field( $input['saved_search'] );
		}

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

	/**
	 * Resolve a mix of raw status ints and string labels to status ints, via the
	 * Has_Optin_Status trait's singular resolve_optin_status() per value. An entry
	 * that doesn't resolve (resolve_optin_status() returns null) is dropped rather
	 * than defaulted - for a filter list, silently defaulting an unrecognized entry
	 * to UNCONFIRMED would add a filter condition nobody asked for. In normal
	 * operation this never happens anyway: the input_schema's `enum` (built from
	 * optin_status_enum()) rejects an unrecognized value before __invoke() runs.
	 *
	 * @param array $values
	 *
	 * @return int[]
	 */
	private static function resolve_optin_statuses( array $values ): array {

		return array_values( array_filter(
			array_map( [ self::class, 'resolve_optin_status' ], $values ),
			static fn( $status ) => $status !== null
		) );
	}
}
