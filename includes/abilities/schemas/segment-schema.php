<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Abilities\Traits\Has_Optin_Status;
use WP_Error;
use function Groundhogg\parse_tag_list;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A reusable contact-segment definition, shared by every ability that operates on
 * "a set of contacts" - groundhogg/search-contacts, groundhogg/send-email-broadcast,
 * groundhogg/add-to-flow, and so on - so they all accept the same audience
 * parameters and turn them into a Contact_Query the same way.
 *
 * Includes filtering by contact meta / custom field values (`meta`), built on
 * Contact_Query's own `meta_query` query var (the same mechanism the pie-chart /
 * table custom reports use via joinMeta()) - so any ability built on this schema
 * can, for example, segment on a custom field the site collected in a survey.
 *
 * Unlike the other classes in this namespace it does NOT extend Schema: it
 * describes *input*, not an output shape, so it has no get_schema()/transform().
 *
 *   // in get_args():
 *   'properties' => array_merge( Segment_Schema::properties(), [ ...ability-specific... ] ),
 *
 *   // in __invoke():
 *   $query = Segment_Schema::to_query( $input );
 *   if ( is_wp_error( $query ) ) { return $query; }
 *   // ... hand $query to Contact_Query / Broadcast::schedule() / ...
 */
class Segment_Schema {

	use Has_Optin_Status;

	/**
	 * Parameters that, on their own, name a deliberate group of contacts (as
	 * opposed to refinements like tags_exclude or marketable, or nothing at all).
	 * Used by has_audience().
	 */
	private const AUDIENCE_KEYS = [ 'search', 'include', 'tags_include', 'saved_search', 'owner', 'users_include', 'meta' ];

	/**
	 * Comparison operators accepted by a `meta` condition's `compare`, matching
	 * what Where::compare() understands. "empty"/"not_empty" ignore `value`.
	 */
	private const META_COMPARISONS = [
		'equals', 'not_equals',
		'less_than', 'greater_than', 'less_than_or_equal_to', 'greater_than_or_equal_to',
		'in', 'not_in',
		'like', 'not_like', 'contains', 'not_contains', 'starts_with', 'ends_with',
		'empty', 'not_empty',
	];

	/**
	 * The input_schema `properties` for a contact segment. Every condition is
	 * ANDed together. Spread these into an ability's own `properties`.
	 *
	 * @return array
	 */
	public static function properties(): array {
		return [
			'search' => [
				'type'        => 'string',
				'description' => __( 'Free-text search matched against the contact\'s name and email address.', 'groundhogg' ),
			],
			'include' => [
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
				'description' => __( 'Only these contact IDs.', 'groundhogg' ),
			],
			'exclude' => [
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
				'description' => __( 'Never these contact IDs.', 'groundhogg' ),
			],
			'tags_include' => [
				'type'        => 'array',
				'items'       => [ 'type' => [ 'string', 'integer' ] ],
				'description' => __( 'Contacts with at least one of these tags (or all, with tags_include_needs_all). Tag names, slugs, or IDs - resolved automatically; a name matching no tag is an error, never a new tag.', 'groundhogg' ),
			],
			'tags_include_needs_all' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Require ALL of tags_include rather than any one.', 'groundhogg' ),
			],
			'tags_exclude' => [
				'type'        => 'array',
				'items'       => [ 'type' => [ 'string', 'integer' ] ],
				'description' => __( 'Exclude contacts with any of these tags (or only when they have all, with tags_exclude_needs_all). Unknown names are ignored here.', 'groundhogg' ),
			],
			'tags_exclude_needs_all' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Only exclude a contact that has ALL of tags_exclude.', 'groundhogg' ),
			],
			'optin_status' => [
				'type'        => 'array',
				'items'       => [ 'enum' => self::optin_status_enum() ],
				'description' => __( 'Only contacts with one of these opt-in statuses (raw int or string label).', 'groundhogg' ),
			],
			'optin_status_exclude' => [
				'type'        => 'array',
				'items'       => [ 'enum' => self::optin_status_enum() ],
				'description' => __( 'Exclude contacts with one of these opt-in statuses.', 'groundhogg' ),
			],
			'marketable' => [
				'type'        => 'boolean',
				'description' => __( 'true = only marketable contacts, false = only non-marketable. Omit for both.', 'groundhogg' ),
			],
			'owner' => [
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
				'description' => __( 'Only contacts owned by one of these WordPress user IDs - see groundhogg/list-owners.', 'groundhogg' ),
			],
			'users_include' => [
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
				'description' => __( 'Only contacts linked to one of these WordPress user IDs.', 'groundhogg' ),
			],
			'users_exclude' => [
				'type'        => 'array',
				'items'       => [ 'type' => 'integer' ],
				'description' => __( 'Exclude contacts linked to one of these WordPress user IDs.', 'groundhogg' ),
			],
			'has_user' => [
				'type'        => 'boolean',
				'description' => __( 'true = only contacts with a linked WordPress user, false = only those without.', 'groundhogg' ),
			],
			'created_after' => [
				'type'        => 'string',
				'format'      => 'date',
				'description' => __( 'Only contacts created on or after this date (Y-m-d).', 'groundhogg' ),
			],
			'created_before' => [
				'type'        => 'string',
				'format'      => 'date',
				'description' => __( 'Only contacts created on or before this date (Y-m-d).', 'groundhogg' ),
			],
			'saved_search' => [
				'type'        => 'string',
				'description' => __( 'ID of a saved search to merge in - see groundhogg/list-saved-searches. Other params still apply on top.', 'groundhogg' ),
			],
			'meta' => [
				'type'        => 'array',
				'description' => __( 'Filter by contact meta / custom field values - see groundhogg/list-custom-fields for keys. Conditions combine per meta_relation.', 'groundhogg' ),
				'items'       => [
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => [ 'key' ],
					'properties'           => [
						'key' => [
							'type'        => 'string',
							'description' => __( 'The meta/custom-field key, e.g. "cancel_reason".', 'groundhogg' ),
						],
						'value' => [
							'description' => __( 'Value to compare against. Omit for compare "empty" or "not_empty". An array of values for "in" / "not_in".', 'groundhogg' ),
						],
						'compare' => [
							'type'        => 'string',
							'enum'        => self::META_COMPARISONS,
							'default'     => 'equals',
							'description' => __( 'How to compare. "contains"/"starts_with"/"ends_with" are substring matches; "in"/"not_in" expect value to be an array.', 'groundhogg' ),
						],
					],
				],
			],
			'meta_relation' => [
				'type'        => 'string',
				'enum'        => [ 'AND', 'OR' ],
				'default'     => 'AND',
				'description' => __( 'How multiple `meta` conditions combine with each other. Doesn\'t affect how `meta` combines with the other segment params, which are always ANDed in.', 'groundhogg' ),
			],
		];
	}

	/**
	 * Whether $input names a deliberate group of contacts (vs. "everyone" or just
	 * refinements). An ability that must not act on the whole database by
	 * accident can require this, or an explicit opt-in flag of its own.
	 *
	 * @param array $input
	 *
	 * @return bool
	 */
	public static function has_audience( array $input ): bool {

		foreach ( self::AUDIENCE_KEYS as $key ) {
			if ( ! empty( $input[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build Contact_Query query vars from a validated segment $input. Conditions
	 * are ANDed. Returns a WP_Error when tags_include names only tags that do not
	 * exist - without this, Contact_Query would treat the empty resolved list as
	 * "no tag constraint" and match every contact.
	 *
	 * Does not run the query. The caller should wrap its own Contact_Query call in
	 * try/catch - a malformed value can still throw a TypeError from inside it.
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error
	 */
	public static function to_query( array $input ) {

		$query = [];

		if ( ! empty( $input['search'] ) ) {
			$query['search'] = sanitize_text_field( $input['search'] );
		}

		if ( ! empty( $input['include'] ) ) {
			$query['include'] = wp_parse_id_list( $input['include'] );
		}

		if ( ! empty( $input['exclude'] ) ) {
			$query['exclude'] = wp_parse_id_list( $input['exclude'] );
		}

		if ( ! empty( $input['tags_include'] ) ) {

			// $create = false: resolving an audience must never mint a tag.
			$resolved = parse_tag_list( $input['tags_include'], 'ID', false );

			if ( empty( $resolved ) ) {
				return new WP_Error(
					'groundhogg_unknown_tags',
					__( 'None of the tags in tags_include match an existing tag.', 'groundhogg' )
				);
			}

			$query['tags_include'] = $resolved;

			if ( ! empty( $input['tags_include_needs_all'] ) ) {
				$query['tags_include_needs_all'] = true;
			}
		}

		if ( ! empty( $input['tags_exclude'] ) ) {

			$resolved = parse_tag_list( $input['tags_exclude'], 'ID', false );

			if ( ! empty( $resolved ) ) {

				$query['tags_exclude'] = $resolved;

				if ( ! empty( $input['tags_exclude_needs_all'] ) ) {
					$query['tags_exclude_needs_all'] = true;
				}
			}
		}

		if ( ! empty( $input['optin_status'] ) && is_array( $input['optin_status'] ) ) {
			$query['optin_status'] = self::resolve_optin_status_list( $input['optin_status'] );
		}

		if ( ! empty( $input['optin_status_exclude'] ) && is_array( $input['optin_status_exclude'] ) ) {
			$query['optin_status_exclude'] = self::resolve_optin_status_list( $input['optin_status_exclude'] );
		}

		if ( isset( $input['marketable'] ) ) {
			$query['marketable'] = (bool) $input['marketable'];
		}

		if ( ! empty( $input['owner'] ) ) {
			$query['owner'] = wp_parse_id_list( $input['owner'] );
		}

		if ( ! empty( $input['users_include'] ) ) {
			$query['users_include'] = wp_parse_id_list( $input['users_include'] );
		}

		if ( ! empty( $input['users_exclude'] ) ) {
			$query['users_exclude'] = wp_parse_id_list( $input['users_exclude'] );
		}

		if ( isset( $input['has_user'] ) ) {
			if ( $input['has_user'] ) {
				$query['has_user'] = true;
			} else {
				$query['no_user'] = true;
			}
		}

		if ( ! empty( $input['created_after'] ) ) {
			$query['after'] = sanitize_text_field( $input['created_after'] );
		}

		if ( ! empty( $input['created_before'] ) ) {
			$query['before'] = sanitize_text_field( $input['created_before'] );
		}

		if ( ! empty( $input['saved_search'] ) ) {
			$query['saved_search'] = sanitize_text_field( $input['saved_search'] );
		}

		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {

			$meta_query = [];

			foreach ( $input['meta'] as $condition ) {

				$condition = (array) $condition;
				$key       = isset( $condition['key'] ) ? sanitize_key( $condition['key'] ) : '';

				if ( ! $key ) {
					continue;
				}

				$compare = in_array( $condition['compare'] ?? 'equals', self::META_COMPARISONS, true )
					? $condition['compare']
					: 'equals';

				// "empty"/"not_empty" ignore the value entirely (Where::compare()
				// matches on $compare before ever looking at it), but Contact_Query's
				// meta_query parsing destructures 'value' out of every condition
				// regardless - always provide the key so that never hits an
				// undefined-array-key warning. Where::compare() parameterizes the
				// value, so no further sanitizing here beyond keeping it to a scalar
				// or a flat array of scalars (for "in"/"not_in").
				$value = $condition['value'] ?? '';

				$meta_query[] = [
					'key'     => $key,
					'compare' => $compare,
					'value'   => is_array( $value )
						? array_values( array_filter( $value, 'is_scalar' ) )
						: ( is_scalar( $value ) ? $value : '' ),
				];
			}

			if ( ! empty( $meta_query ) ) {

				if ( ! empty( $input['meta_relation'] ) && strtoupper( $input['meta_relation'] ) === 'OR' ) {
					$meta_query['relation'] = 'OR';
				}

				$query['meta_query'] = $meta_query;
			}
		}

		return $query;
	}

	/**
	 * Resolve a mix of raw status ints and string labels to status ints, dropping
	 * anything unrecognized rather than defaulting it (which would add a filter
	 * condition nobody asked for). The schema enum normally rejects bad values
	 * before this runs.
	 *
	 * @param array $values
	 *
	 * @return int[]
	 */
	private static function resolve_optin_status_list( array $values ): array {

		return array_values( array_filter(
			array_map( [ self::class, 'resolve_optin_status' ], $values ),
			static fn( $status ) => $status !== null
		) );
	}
}
