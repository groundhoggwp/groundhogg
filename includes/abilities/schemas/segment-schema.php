<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
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
 * Also includes behavioural properties (email_activity, page_visits,
 * form_submissions, flow_conversions, activity) - see behaviour_filter_conditions().
 * They're expressed as Filters-DSL conditions only, and their `within_days` is
 * always a rolling range evaluated when the query runs, so a stored segment or
 * rule built from them never goes stale.
 *
 * Unlike the other classes in this namespace it does NOT extend Schema: it
 * describes *input*, not an output shape, so it has no get_schema()/transform().
 *
 * Three ways to turn a validated $input into something queryable, depending on
 * what the calling ability actually does with it:
 *
 *   // Executes immediately (groundhogg/search-contacts): a live Contact_Query,
 *   // so the caller can keep chaining (setLimit(), setOrderby(), ...) and an
 *   // add-on can manipulate the query object directly - see
 *   // groundhogg/segment_schema/contact_query below.
 *   $contact_query = Segment_Schema::to_contact_query( $input );
 *   if ( is_wp_error( $contact_query ) ) { return $contact_query; }
 *   $results = $contact_query->setLimit( $limit )->setFoundRows( true )->query();
 *
 *   // Stored and (re-)run later (groundhogg/send-email-broadcast's dynamic
 *   // segments, groundhogg/add-to-flow's background batches): a plain,
 *   // serializable array of query vars - a live query object (and whatever a
 *   // filter attached to it via addJoin()/where()) can't survive that round
 *   // trip.
 *   $query = Segment_Schema::to_query( $input );
 *   if ( is_wp_error( $query ) ) { return $query; }
 *   // ... hand $query to Broadcast::schedule() / Background_Tasks / ...
 *
 *   // A branch/logic step's own condition setting (e.g. if_else's
 *   // `include_filters`) - Groundhogg's Filters DSL directly, not a
 *   // Contact_Query-vars array at all.
 *   $filters = Segment_Schema::to_filters( $input );
 *   if ( is_wp_error( $filters ) ) { return $filters; }
 *   // ... use as an if_else/branch-logic step's include_filters setting
 *
 *   // in get_args(), for all three:
 *   'properties' => array_merge( Segment_Schema::properties(), [ ...ability-specific... ] ),
 *
 * Extensible, so an add-on can add its own audience-filtering params to every
 * ability built on this schema (groundhogg/search-contacts,
 * groundhogg/send-email-broadcast, groundhogg/add-to-flow, ...) without modifying
 * this class or any of them individually.
 *
 * The easy way in is Segment_Schema::extend() - one call registers a new input
 * property (its schema) and a callback that turns it into Groundhogg's Filters
 * DSL (the same condition format Contact_Query's own `include_filters` query var
 * and branch-logic step settings like if_else's `include_filters` consume - see
 * `Groundhogg\DB\Query\Filters` and Contact_Query's `filter_*` methods for the
 * full vocabulary of condition `type`s). Returning filter conditions rather than
 * mutating a query-vars array directly means one extend() call works for BOTH
 * to_query()/to_contact_query() (folded into `include_filters`) AND to_filters()
 * (used for branch/logic conditions) - not two things to hook separately:
 *
 *     Segment_Schema::extend(
 *         'woocommerce',
 *         [
 *             'type'                 => 'object',
 *             'additionalProperties' => false,
 *             'properties'           => [
 *                 'min_orders' => [
 *                     'type'        => 'integer',
 *                     'description' => __( 'Only contacts with at least this many WooCommerce orders.', 'my-plugin' ),
 *                 ],
 *             ],
 *         ],
 *         function ( array $input ): array {
 *             if ( empty( $input['woocommerce']['min_orders'] ) ) {
 *                 return [];
 *             }
 *             // Requires a filter type actually registered with Contact_Query's
 *             // Filters instance (via groundhogg/contact_query/filters/register) -
 *             // extend() only carries conditions through, it doesn't invent new
 *             // filter types on its own.
 *             return [ [
 *                 'type'    => 'woocommerce_orders',
 *                 'compare' => 'greater_than_or_equal_to',
 *                 'value'   => absint( $input['woocommerce']['min_orders'] ),
 *             ] ];
 *         },
 *         true // this alone counts as naming a deliberate audience - see has_audience()
 *     );
 *
 * Call this once (e.g. on `init`, after confirming both Groundhogg and the
 * dependency it needs are active) - before any ability builds its input schema.
 *
 * For query logic no Filters condition type can express at all (a join, a raw
 * where condition, a custom select), reach for to_contact_query()'s live
 * Contact_Query object instead, via the `groundhogg/segment_schema/contact_query`
 * action below - only usable by abilities that execute the query themselves
 * rather than storing it (see to_contact_query()'s own docblock).
 *
 * Everything extend() does is built on filters, also available directly for
 * anything extend() can't express (e.g. contributing to more than one property
 * from a single registration):
 *
 * - `groundhogg/segment_schema/properties` filters the input properties (properties(),
 *   below).
 * - `groundhogg/segment_schema/query` filters the plain Contact_Query-vars array
 *   built from $input (to_query(), below) - runs after extend()'d conditions are
 *   already folded into `include_filters`.
 * - `groundhogg/segment_schema/filters` filters the Filters-DSL array built from
 *   $input (to_filters(), below) - same timing, after extend()'d conditions are
 *   already merged in.
 * - `groundhogg/segment_schema/audience_keys` adds to the keys has_audience() treats
 *   as deliberately naming a group of contacts, so an ability that must not act on
 *   the whole database by accident still recognizes a request that only supplies
 *   an add-on's own param as targeted, not "everyone".
 */
class Segment_Schema {

	use Has_Optin_Status;

	/**
	 * Parameters that, on their own, name a deliberate group of contacts (as
	 * opposed to refinements like tags_exclude or marketable, or nothing at all).
	 * Used by has_audience().
	 */
	private const AUDIENCE_KEYS = [
		'search', 'include', 'tags_include', 'saved_search', 'owner', 'users_include', 'meta',
		'email_activity', 'page_visits', 'form_submissions', 'flow_conversions', 'activity',
	];

	/**
	 * Comparisons accepted by `page_visits.compare`, all understood by Filters::string().
	 */
	private const PAGE_COMPARISONS = [ 'equals', 'starts_with', 'contains' ];

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
	 * Add-on-registered input properties, keyed by property name. Populated by
	 * extend().
	 *
	 * @var array<string, array{schema: array, callback: callable, audience: bool}>
	 */
	private static array $extensions = [];

	/**
	 * Register an additional audience-filtering input property - see the class
	 * docblock for the full picture. One call adds the property to the input
	 * schema (properties(), below) and wires the callback that turns it into
	 * Filters-DSL conditions, merged into `include_filters` for to_query()/
	 * to_contact_query() and into the result of to_filters() alike - instead of
	 * hooking groundhogg/segment_schema/properties and (one or both of)
	 * groundhogg/segment_schema/query / groundhogg/segment_schema/filters by hand.
	 *
	 * @param string   $key             The property name, e.g. "woocommerce". Must
	 *                                  not collide with a built-in name or an
	 *                                  existing extend()'d one - either is refused
	 *                                  with _doing_it_wrong() rather than silently
	 *                                  overwriting something else already relying
	 *                                  on that key.
	 * @param array    $schema          The full JSON schema fragment for this
	 *                                  property (type, description, enum,
	 *                                  properties, items, ...), used as-is. Unlike
	 *                                  Contact_Schema::extend()'s simpler
	 *                                  description+type shorthand, a segment
	 *                                  filter's shape varies too much (an object
	 *                                  with its own sub-properties, an enum, a
	 *                                  plain scalar, ...) to usefully paper over.
	 * @param callable $callback        function( array $input ): array|WP_Error.
	 *                                  Receives the full $input and returns a flat
	 *                                  list of Filters-DSL condition arrays (each
	 *                                  `['type' => ..., ...]`) contributed by this
	 *                                  property - empty array if $key isn't present
	 *                                  in $input - or a WP_Error to reject the
	 *                                  input outright, same as to_query()'s own
	 *                                  tags_include handling. The `type` used must
	 *                                  be a filter type actually registered with
	 *                                  Contact_Query's Filters instance (see the
	 *                                  class docblock) - extend() carries
	 *                                  conditions through, it doesn't register new
	 *                                  filter types itself. Called on every
	 *                                  to_query()/to_contact_query()/to_filters(),
	 *                                  regardless of whether $key is actually
	 *                                  present - check $input[ $key ] yourself.
	 * @param bool     $is_audience_key Whether supplying this property alone (with
	 *                                  no other segment param) should count as
	 *                                  deliberately naming a group of contacts -
	 *                                  see has_audience(). Default false.
	 *
	 * @return void
	 */
	public static function extend( string $key, array $schema, callable $callback, bool $is_audience_key = false ) {

		if ( $key === 'exclude_segment' || array_key_exists( $key, self::base_properties() ) || isset( self::$extensions[ $key ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf( 'A Segment_Schema property named "%s" already exists.', $key ),
				'4.8'
			);

			return;
		}

		self::$extensions[ $key ] = [
			'schema'   => $schema,
			'callback' => $callback,
			'audience' => $is_audience_key,
		];
	}

	/**
	 * This schema's own built-in input properties, before add-on extensions or
	 * the groundhogg/segment_schema/properties filter. Split out from
	 * properties() so extend() can check a proposed key against it without
	 * re-running the filter (and without properties() needing to special-case
	 * its own extensions when checking for collisions).
	 *
	 * @return array
	 */
	private static function base_properties(): array {

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
			'email_activity' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Contacts who opened or clicked emails, from flows and broadcasts alike. Pass an empty object for "opened any email at least once". For contacts who did NOT, put this inside exclude_segment.', 'groundhogg' ),
				'properties'           => [
					'action'      => [
						'type'        => 'string',
						'enum'        => [ 'opened', 'clicked' ],
						'default'     => 'opened',
						'description' => __( 'Count email opens, or link clicks in emails.', 'groundhogg' ),
					],
					'email_ids'   => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only count activity on these email IDs - see groundhogg/list-email-templates. Omit for any email.', 'groundhogg' ),
					],
					'min_count'   => self::min_count_schema( __( 'At least this many opens or clicks in total, across all matching emails. Default 1.', 'groundhogg' ) ),
					'within_days' => self::within_days_schema(),
				],
			],
			'page_visits' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Contacts who visited tracked pages on this site. Pass an empty object for "visited any page". For contacts who did NOT, put this inside exclude_segment.', 'groundhogg' ),
				'properties'           => [
					'path'               => [
						'type'        => 'string',
						'description' => __( 'The page path, e.g. "/pricing/". A full URL also works, only its path is used. Omit for any page.', 'groundhogg' ),
					],
					'compare'            => [
						'type'        => 'string',
						'enum'        => self::PAGE_COMPARISONS,
						'default'     => 'starts_with',
						'description' => __( 'How path is matched. Paths are stored as visited, usually with a trailing slash, so "equals" needs the exact form.', 'groundhogg' ),
					],
					'min_count'          => self::min_count_schema( __( 'At least this many visits to a single matching page (not summed across different pages - use min_distinct_pages for that). Default 1.', 'groundhogg' ) ),
					'min_distinct_pages' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'At least this many different matching pages visited, e.g. 3 with path "/blog/" for "read 3 or more blog posts". When given without min_count, visit counts per page are not checked.', 'groundhogg' ),
					],
					'within_days'        => self::within_days_schema(),
				],
			],
			'form_submissions' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Contacts who submitted Groundhogg forms. Pass an empty object for "submitted any form". For contacts who did NOT, put this inside exclude_segment.', 'groundhogg' ),
				'properties'           => [
					'form_ids'    => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only these forms, by the step ID of each form\'s "form_fill" step - see groundhogg/list-flows with expand ["steps"]. Omit for any form.', 'groundhogg' ),
					],
					'within_days' => self::within_days_schema(),
				],
			],
			'flow_conversions' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'description'          => __( 'Contacts who reached a step marked as a conversion in a flow. Pass an empty object for "converted in any flow". For contacts who did NOT, put this inside exclude_segment.', 'groundhogg' ),
				'properties'           => [
					'funnel_ids'  => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only conversions in these flow IDs - see groundhogg/list-flows. Omit for any flow.', 'groundhogg' ),
					],
					'step_ids'    => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only conversions at these step IDs - steps with is_conversion in groundhogg/list-flows with expand ["steps"]. Combined with funnel_ids, both must match. Omit for any conversion step.', 'groundhogg' ),
					],
					'within_days' => self::within_days_schema(),
				],
			],
			'activity' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'type' ],
				'description'          => __( 'Contacts with activity of a given type in their activity log. For contacts WITHOUT it, put this inside exclude_segment.', 'groundhogg' ),
				'properties'           => [
					'type'        => [
						'type'        => 'string',
						'description' => __( 'The activity type, e.g. "wp_login", "wp_logout", "email_opened", "email_link_click", "unsubscribed", or a type recorded by an integration.', 'groundhogg' ),
					],
					'min_count'   => self::min_count_schema( __( 'At least this many activities of this type. Default 1.', 'groundhogg' ) ),
					'within_days' => self::within_days_schema(),
				],
			],
		];
	}

	/**
	 * Schema for the `within_days` sub-property shared by the behavioural properties
	 *
	 * @return array
	 */
	private static function within_days_schema(): array {
		return [
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => __( 'Only count what happened in the last N days, measured back from whenever the segment is evaluated, so stored segments keep rolling forward. Omit for any time.', 'groundhogg' ),
		];
	}

	/**
	 * Schema for a `min_count` sub-property
	 *
	 * @param string $description
	 *
	 * @return array
	 */
	private static function min_count_schema( string $description ): array {
		return [
			'type'        => 'integer',
			'minimum'     => 1,
			'default'     => 1,
			'description' => $description,
		];
	}

	/**
	 * Turn the behavioural properties (email_activity, page_visits, form_submissions, flow_conversions,
	 * activity) into Filters-DSL conditions, for merging into to_query()'s `include_filters` or
	 * to_filters()'s AND-group. Each is an object where presence alone is a condition, so an empty
	 * object is "did this at all".
	 *
	 * within_days always maps to Filters' `x_days` range, which is computed relative to when the query
	 * runs, never to a fixed date.
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error
	 */
	private static function behaviour_filter_conditions( array $input ) {

		$conditions = [];

		$email_activity = self::get_object_param( $input, 'email_activity' );

		if ( $email_activity !== null ) {

			// custom_activity rather than email_opened/email_link_clicked, because those leave out
			// broadcast activity unless a funnel_id is given
			$conditions[] = array_merge( [
				'type'          => 'custom_activity',
				'activity'      => ( $email_activity['action'] ?? 'opened' ) === 'clicked' ? Activity::EMAIL_CLICKED : Activity::EMAIL_OPENED,
				'email_id'      => wp_parse_id_list( $email_activity['email_ids'] ?? [] ),
				'count'         => self::parse_min_count( $email_activity['min_count'] ?? 1 ),
				'count_compare' => 'greater_than_or_equal_to',
			], self::within_days_range( $email_activity ) );
		}

		$page_visits = self::get_object_param( $input, 'page_visits' );

		if ( $page_visits !== null ) {

			$page_filter = [
				'link'    => sanitize_text_field( $page_visits['path'] ?? '' ),
				'compare' => in_array( $page_visits['compare'] ?? '', self::PAGE_COMPARISONS, true ) ? $page_visits['compare'] : 'starts_with',
			];

			$range = self::within_days_range( $page_visits );

			if ( empty( $page_visits['min_distinct_pages'] ) || isset( $page_visits['min_count'] ) ) {
				$conditions[] = array_merge( [ 'type' => 'page_visited' ], $page_filter, [
					'count'         => self::parse_min_count( $page_visits['min_count'] ?? 1 ),
					'count_compare' => 'greater_than_or_equal_to',
				], $range );
			}

			if ( ! empty( $page_visits['min_distinct_pages'] ) ) {
				$conditions[] = array_merge( [ 'type' => 'distinct_pages_visited' ], $page_filter, [
					'count'         => self::parse_min_count( $page_visits['min_distinct_pages'] ),
					'count_compare' => 'greater_than_or_equal_to',
				], $range );
			}
		}

		$form_submissions = self::get_object_param( $input, 'form_submissions' );

		if ( $form_submissions !== null ) {
			$conditions[] = array_merge( [
				'type'    => 'form_submissions',
				'form_id' => wp_parse_id_list( $form_submissions['form_ids'] ?? [] ),
			], self::within_days_range( $form_submissions ) );
		}

		$flow_conversions = self::get_object_param( $input, 'flow_conversions' );

		if ( $flow_conversions !== null ) {

			// Conversions are logged as activity by Step::run(), not as their own events, and a conversion
			// step's funnel_history would also match contacts who completed it before it was a conversion
			$conditions[] = array_merge( [
				'type'          => 'custom_activity',
				'activity'      => 'funnel_conversion',
				'funnel_id'     => wp_parse_id_list( $flow_conversions['funnel_ids'] ?? [] ),
				'step_id'       => wp_parse_id_list( $flow_conversions['step_ids'] ?? [] ),
				'count'         => 1,
				'count_compare' => 'greater_than_or_equal_to',
			], self::within_days_range( $flow_conversions ) );
		}

		$activity = self::get_object_param( $input, 'activity' );

		if ( $activity !== null ) {

			$type = sanitize_key( $activity['type'] ?? '' );

			if ( ! $type ) {
				return new WP_Error(
					'groundhogg_invalid_activity',
					__( 'activity.type is required.', 'groundhogg' )
				);
			}

			$conditions[] = array_merge( [
				'type'          => 'custom_activity',
				'activity'      => $type,
				'count'         => self::parse_min_count( $activity['min_count'] ?? 1 ),
				'count_compare' => 'greater_than_or_equal_to',
			], self::within_days_range( $activity ) );
		}

		return $conditions;
	}

	/**
	 * An object-typed param as an array, or null when it isn't given at all
	 *
	 * @param array  $input
	 * @param string $key
	 *
	 * @return array|null
	 */
	private static function get_object_param( array $input, string $key ) {

		if ( ! isset( $input[ $key ] ) ) {
			return null;
		}

		if ( is_object( $input[ $key ] ) ) {
			return (array) $input[ $key ];
		}

		return is_array( $input[ $key ] ) ? $input[ $key ] : null;
	}

	/**
	 * @param mixed $count
	 *
	 * @return int at least 1
	 */
	private static function parse_min_count( $count ): int {
		return max( 1, absint( $count ) );
	}

	/**
	 * The Filters date range for a behavioural property's within_days. Uses the rolling `x_days` range
	 * so a stored segment keeps meaning "the last N days" whenever it runs.
	 *
	 * @param array $param
	 *
	 * @return array empty for any time
	 */
	private static function within_days_range( array $param ): array {

		$days = absint( $param['within_days'] ?? 0 );

		if ( ! $days ) {
			return [];
		}

		return [
			'date_range' => 'x_days',
			'days'       => $days,
		];
	}

	/**
	 * The input_schema `properties` for a contact segment. Every condition is
	 * ANDed together. Spread these into an ability's own `properties`.
	 *
	 * @return array
	 */
	public static function properties(): array {

		$properties = self::base_properties();

		foreach ( self::$extensions as $key => $extension ) {
			$properties[ $key ] = $extension['schema'];
		}

		// Nests the schema inside itself one level deep - built from $properties
		// as it stands right now (before `exclude_segment` is added below), so
		// this can't recurse any further. See to_query()'s handling of
		// `exclude_segment` for how it's turned into the `exclude_filters` query
		// var via to_filters().
		$properties['exclude_segment'] = [
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
			'description'          => __( 'Exclude contacts matching this nested segment definition - same shape as this schema itself (minus exclude_segment; nesting only goes one level deep). Combined with `AND NOT (...)` against the rest of this segment.', 'groundhogg' ),
		];

		// See the class docblock for how an add-on adds its own properties here.
		return apply_filters( 'groundhogg/segment_schema/properties', $properties );
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

		$audience_keys = self::AUDIENCE_KEYS;

		foreach ( self::$extensions as $key => $extension ) {
			if ( $extension['audience'] ) {
				$audience_keys[] = $key;
			}
		}

		// See the class docblock for how an add-on adds its own key here.
		$audience_keys = apply_filters( 'groundhogg/segment_schema/audience_keys', $audience_keys );

		$properties = self::properties();

		foreach ( $audience_keys as $key ) {

			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}

			// An object-typed param names a group even when empty, e.g. email_activity: {} is
			// "opened any email"
			if ( ( $properties[ $key ]['type'] ?? '' ) === 'object' && ( is_array( $input[ $key ] ) || is_object( $input[ $key ] ) ) ) {
				return true;
			}

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
	 * `exclude_segment`, if present, is itself run through to_filters() and
	 * merged in as the `exclude_filters` query var - contacts matching that
	 * nested segment are excluded rather than required. See properties()' own
	 * definition of `exclude_segment` for its shape.
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

		// The behavioural properties have no native query var, so like extend()'d
		// properties they're expressed as Filters-DSL conditions.
		$behaviour_conditions = self::behaviour_filter_conditions( $input );

		if ( is_wp_error( $behaviour_conditions ) ) {
			return $behaviour_conditions;
		}

		$extension_conditions = self::extension_filter_conditions( $input );

		if ( is_wp_error( $extension_conditions ) ) {
			return $extension_conditions;
		}

		$filter_conditions = array_merge( $behaviour_conditions, $extension_conditions );

		if ( ! empty( $filter_conditions ) ) {
			// Merged into the same single AND-group `include_filters` already uses
			// elsewhere in this method (see to_filters() for the built-in fields'
			// equivalent) - Contact_Query natively understands `include_filters`
			// alongside the native query vars above (db.php's `case 'include_filters'`),
			// so this doesn't conflict with anything already in $query.
			$query['include_filters'][0] = array_merge(
				$query['include_filters'][0] ?? [],
				$filter_conditions
			);
		}

		if ( ! empty( $input['exclude_segment'] ) && is_array( $input['exclude_segment'] ) ) {

			$exclude_filters = self::to_filters( $input['exclude_segment'] );

			if ( is_wp_error( $exclude_filters ) ) {
				return $exclude_filters;
			}

			if ( ! empty( $exclude_filters ) ) {
				// Contact_Query natively understands `exclude_filters` alongside
				// `include_filters` (db.php's `case 'exclude_filters'`) - contacts
				// matching this Filters-DSL condition list are excluded rather than
				// required.
				$query['exclude_filters'] = $exclude_filters;
			}
		}

		// See the class docblock for how an add-on translates its own input
		// properties into query vars here. Only reached on success - the early
		// WP_Error returns above (e.g. unknown tags, an extend()'d callback
		// rejecting its own input) bypass this filter, same as any other hard
		// validation failure.
		return apply_filters( 'groundhogg/segment_schema/query', $query, $input );
	}

	/**
	 * Run every extend()'d property's callback against $input and collect the
	 * Filters-DSL conditions they contribute, for merging into either
	 * to_query()'s `include_filters` or to_filters()'s result.
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error A flat list of condition arrays (possibly empty), or
	 *                        the first WP_Error a callback returns.
	 */
	private static function extension_filter_conditions( array $input ) {

		$conditions = [];

		foreach ( self::$extensions as $key => $extension ) {

			$result = call_user_func( $extension['callback'], $input );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( ! empty( $result ) ) {
				array_push( $conditions, ...array_values( $result ) );
			}
		}

		return $conditions;
	}

	/**
	 * Maps a `meta` condition's `compare` (this class's own vocabulary, matching
	 * Where::compare() - see META_COMPARISONS) to the equivalent
	 * Groundhogg\DB\Query\Filters::string() compare keyword, for to_filters().
	 * The two vocabularies grew independently and mostly agree, except:
	 * "in"/"not_in" here must become "any_of"/"none_of" for Filters::string() -
	 * passed through unmapped, Filters::string() silently compares against only
	 * the first array value instead of the whole list, since it only special-cases
	 * exactly "any_of"/"none_of" before falling back to a scalar comparison.
	 * "like"/"not_like" have no exact Filters::string() equivalent and are mapped
	 * to the closest substring match ("contains"/"not_contains").
	 */
	private const META_COMPARE_TO_FILTER_COMPARE = [
		'equals'                   => 'equals',
		'not_equals'               => 'not_equals',
		'less_than'                => 'less_than',
		'greater_than'             => 'greater_than',
		'less_than_or_equal_to'    => 'less_than_or_equal_to',
		'greater_than_or_equal_to' => 'greater_than_or_equal_to',
		'in'                       => 'any_of',
		'not_in'                   => 'none_of',
		'like'                     => 'contains',
		'not_like'                 => 'not_contains',
		'contains'                 => 'contains',
		'not_contains'             => 'not_contains',
		'starts_with'              => 'starts_with',
		'ends_with'                => 'ends_with',
		'empty'                    => 'empty',
		'not_empty'                => 'not_empty',
	];

	/**
	 * Build Groundhogg's Filters DSL (Groundhogg\DB\Query\Filters::sanitize()'s
	 * expected shape - an OR-of-AND condition list, e.g.
	 * `[[ {type:'tags', ...}, {type:'owner', ...} ]]`) from a validated segment
	 * $input. This is the format branch/logic step settings consume directly
	 * (e.g. if_else's `include_filters`/`exclude_filters`) - unlike to_query()/
	 * to_contact_query(), the result here is NOT a Contact_Query-vars array, and
	 * running it requires a Contact_Query (or a step type) that understands the
	 * Filters DSL, not a raw query.
	 *
	 * Every translated condition lands in a single AND-group (`[[ ...all of
	 * them... ]]`), matching Segment_Schema's own "every condition ANDed
	 * together" semantics - to_query()'s notion of ANDing everything, expressed
	 * here as one AND-group with no OR alternatives.
	 *
	 * `meta_relation: OR` is expressed via the `sub_query` filter type
	 * (Contact_Query::filter_sub_query()), which takes its own nested
	 * `include_filters` (a full OR-of-AND array) and applies it inside a
	 * sub-Where group - exactly the primitive needed to nest an OR *inside* the
	 * single outer AND-group everything else lands in, without restructuring the
	 * whole condition list or duplicating every other condition into each OR
	 * branch. See the `meta` handling below.
	 *
	 * One known gap: `search` has no Filters equivalent (Contact_Query's own
	 * `search` handling is a query-var-level heuristic - email vs. full name -
	 * not a Filters condition) and is silently ignored here. Use
	 * to_query()/to_contact_query() if `search` needs to actually apply.
	 *
	 * @param array $input
	 *
	 * @return array|WP_Error
	 */
	public static function to_filters( array $input ) {

		$conditions = [];

		if ( ! empty( $input['include'] ) ) {
			$conditions[] = [
				'type'    => 'contact_id',
				'compare' => 'any_of',
				'value'   => wp_parse_id_list( $input['include'] ),
			];
		}

		if ( ! empty( $input['exclude'] ) ) {
			$conditions[] = [
				'type'    => 'contact_id',
				'compare' => 'none_of',
				'value'   => wp_parse_id_list( $input['exclude'] ),
			];
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

			$conditions[] = [
				'type'     => 'tags',
				'tags'     => $resolved,
				'compare'  => 'includes',
				'compare2' => ! empty( $input['tags_include_needs_all'] ) ? 'all' : 'any',
			];
		}

		if ( ! empty( $input['tags_exclude'] ) ) {

			$resolved = parse_tag_list( $input['tags_exclude'], 'ID', false );

			if ( ! empty( $resolved ) ) {
				$conditions[] = [
					'type'     => 'tags',
					'tags'     => $resolved,
					'compare'  => 'excludes',
					'compare2' => ! empty( $input['tags_exclude_needs_all'] ) ? 'all' : 'any',
				];
			}
		}

		if ( ! empty( $input['optin_status'] ) && is_array( $input['optin_status'] ) ) {
			$conditions[] = [
				'type'    => 'optin_status',
				'value'   => self::resolve_optin_status_list( $input['optin_status'] ),
				'compare' => 'in',
			];
		}

		if ( ! empty( $input['optin_status_exclude'] ) && is_array( $input['optin_status_exclude'] ) ) {
			$conditions[] = [
				'type'    => 'optin_status',
				'value'   => self::resolve_optin_status_list( $input['optin_status_exclude'] ),
				'compare' => 'not_in',
			];
		}

		if ( isset( $input['marketable'] ) ) {
			$conditions[] = [
				'type'       => 'is_marketable',
				'marketable' => $input['marketable'] ? 'yes' : 'no',
			];
		}

		if ( ! empty( $input['owner'] ) ) {
			$conditions[] = [
				'type'    => 'owner',
				'value'   => wp_parse_id_list( $input['owner'] ),
				'compare' => 'in',
			];
		}

		if ( ! empty( $input['users_include'] ) ) {
			$conditions[] = [
				'type'    => 'user_id',
				'value'   => wp_parse_id_list( $input['users_include'] ),
				'compare' => 'any_of',
			];
		}

		if ( ! empty( $input['users_exclude'] ) ) {
			$conditions[] = [
				'type'    => 'user_id',
				'value'   => wp_parse_id_list( $input['users_exclude'] ),
				'compare' => 'none_of',
			];
		}

		if ( isset( $input['has_user'] ) ) {
			if ( $input['has_user'] ) {
				$conditions[] = [ 'type' => 'is_user' ];
			} else {
				// No negated "is_user" filter type exists - approximate "no linked
				// user" as user_id being exactly the unset value.
				$conditions[] = [ 'type' => 'user_id', 'compare' => 'equals', 'value' => 0 ];
			}
		}

		if ( ! empty( $input['created_after'] ) && ! empty( $input['created_before'] ) ) {
			$conditions[] = [
				'type'       => 'date_created',
				'date_range' => 'between',
				'after'      => sanitize_text_field( $input['created_after'] ),
				'before'     => sanitize_text_field( $input['created_before'] ),
			];
		} else if ( ! empty( $input['created_after'] ) ) {
			$conditions[] = [
				'type'       => 'date_created',
				'date_range' => 'after',
				'after'      => sanitize_text_field( $input['created_after'] ),
			];
		} else if ( ! empty( $input['created_before'] ) ) {
			$conditions[] = [
				'type'       => 'date_created',
				'date_range' => 'before',
				'before'     => sanitize_text_field( $input['created_before'] ),
			];
		}

		if ( ! empty( $input['saved_search'] ) ) {
			$conditions[] = [
				'type'    => 'saved_search',
				'search'  => sanitize_text_field( $input['saved_search'] ),
				'compare' => 'in',
			];
		}

		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {

			$meta_conditions = [];

			foreach ( $input['meta'] as $condition ) {

				$condition = (array) $condition;
				$key       = isset( $condition['key'] ) ? sanitize_key( $condition['key'] ) : '';

				if ( ! $key ) {
					continue;
				}

				$compare = in_array( $condition['compare'] ?? 'equals', self::META_COMPARISONS, true )
					? $condition['compare']
					: 'equals';

				$meta_conditions[] = [
					'type'    => 'meta',
					'meta'    => $key,
					'compare' => self::META_COMPARE_TO_FILTER_COMPARE[ $compare ] ?? 'equals',
					'value'   => $condition['value'] ?? '',
				];
			}

			$is_or = ! empty( $input['meta_relation'] ) && strtoupper( $input['meta_relation'] ) === 'OR';

			if ( $is_or && count( $meta_conditions ) > 1 ) {
				// Nest as one OR-of-AND sub-group inside the outer AND-group, via
				// the sub_query filter type - see the class docblock for why this
				// is the only way to express an OR here without restructuring
				// everything else. Each meta condition becomes its own single-item
				// AND-group, ORed together.
				$conditions[] = [
					'type'            => 'sub_query',
					'include_filters' => array_map( static fn( $condition ) => [ $condition ], $meta_conditions ),
				];
			} else {
				// Either explicitly ANDed, or only one condition (where AND/OR are
				// equivalent) - no need for the sub_query wrapper, just AND them
				// into the outer group directly like everything else.
				array_push( $conditions, ...$meta_conditions );
			}
		}

		$behaviour_conditions = self::behaviour_filter_conditions( $input );

		if ( is_wp_error( $behaviour_conditions ) ) {
			return $behaviour_conditions;
		}

		array_push( $conditions, ...$behaviour_conditions );

		$extension_conditions = self::extension_filter_conditions( $input );

		if ( is_wp_error( $extension_conditions ) ) {
			return $extension_conditions;
		}

		array_push( $conditions, ...$extension_conditions );

		// See the class docblock for how an add-on adds its own conditions here.
		return apply_filters( 'groundhogg/segment_schema/filters', empty( $conditions ) ? [] : [ $conditions ], $input );
	}

	/**
	 * Build a wp-admin Contacts page URL (`gh_contacts`) pre-loaded with this
	 * segment's filters, for handing back to a human as "here's this audience in
	 * the UI" - e.g. an ability's output alongside a live Contact_Query result.
	 * Uses the same `filters`/`exclude_filters` query args (base64_json_encode()'d
	 * Filters-DSL) the Contacts page's own scripts() method and
	 * contact_filters_link() already read - see admin\contacts\contacts-page.php.
	 *
	 * `exclude_segment`, if present, becomes `exclude_filters` here exactly as it
	 * does in to_query() - see to_query()'s docblock.
	 *
	 * @param array $input
	 *
	 * @return string|WP_Error
	 */
	public static function to_admin_url( array $input ) {

		$filters = self::to_filters( $input );

		if ( is_wp_error( $filters ) ) {
			return $filters;
		}

		$args = [];

		if ( ! empty( $filters ) ) {
			$args['filters'] = base64_json_encode( $filters );
		}

		if ( ! empty( $input['exclude_segment'] ) && is_array( $input['exclude_segment'] ) ) {

			$exclude_filters = self::to_filters( $input['exclude_segment'] );

			if ( is_wp_error( $exclude_filters ) ) {
				return $exclude_filters;
			}

			if ( ! empty( $exclude_filters ) ) {
				$args['exclude_filters'] = base64_json_encode( $exclude_filters );
			}
		}

		return admin_page_url( 'gh_contacts', $args );
	}

	/**
	 * Build a live Contact_Query from a validated segment $input - same
	 * conditions as to_query(), but as a query object the caller can keep
	 * building on (setLimit(), setOrderby(), ...) and an add-on can manipulate
	 * directly via `groundhogg/segment_schema/contact_query` for anything a
	 * plain query-var array can't express (a join, a raw where condition).
	 * See the class docblock for when to reach for this vs. to_query().
	 *
	 * Does not run the query - the caller decides when (query(), get_results(),
	 * count(), ...) and with what pagination/ordering on top.
	 *
	 * @param array $input
	 * @param int   $flags Passed through to Contact_Query's constructor (e.g.
	 *                     Contact_Query::IS_SUB_QUERY).
	 *
	 * @return Contact_Query|WP_Error
	 */
	public static function to_contact_query( array $input, int $flags = 0 ) {

		$query_vars = self::to_query( $input );

		if ( is_wp_error( $query_vars ) ) {
			return $query_vars;
		}

		$contact_query = new Contact_Query( $query_vars, $flags );

		/**
		 * Fires with the live Contact_Query built from a segment $input, so an
		 * add-on can manipulate it directly - see the class docblock's
		 * WooCommerce example. The query hasn't run yet, so anything applied
		 * here (addJoin(), where(), setSelect(), ...) takes effect normally.
		 *
		 * @param Contact_Query $contact_query
		 * @param array         $input
		 */
		do_action( 'groundhogg/segment_schema/contact_query', $contact_query, $input );

		return $contact_query;
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
