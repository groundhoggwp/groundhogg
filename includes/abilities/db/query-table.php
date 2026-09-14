<?php

namespace Groundhogg\Abilities\Db;

use Groundhogg\Abilities\Ability;
use Throwable;
use WP_Error;
use function Groundhogg\get_db;

/**
 * Direct, read-only access to Groundhogg's own database tables - for the ad-hoc
 * analysis that the fixed abilities (search-contacts, list-broadcasts, the
 * reporting abilities, ...) don't cover, e.g. cross-referencing a custom field
 * against another table. See groundhogg/describe-table to discover a table's
 * columns before querying it.
 *
 * Gated on `manage_options` rather than a Groundhogg `view_*` capability, and
 * deliberately so: several Groundhogg tables carry row-level scoping inside the
 * purpose-built abilities that a raw table scan would otherwise bypass - the
 * `view_contact` team scoping on contacts, and the `view_note`/`view_task`
 * associated-object cascade (see Main_Roles::map_meta_cap()). An administrator
 * isn't scoped by any of that in Groundhogg's own permission model to begin
 * with - they can already see all of it through wp-admin - so requiring
 * `manage_options` here doesn't hand out any access the caller doesn't already
 * have; it just gives it a query interface. (Notes/Tasks::query() still apply
 * their own author-only scoping when the caller lacks view_others_notes/tasks,
 * same as everywhere else those classes are used - moot for an actual admin.)
 *
 * `table` is restricted to a fixed whitelist (ALLOWED_TABLES) of Groundhogg's
 * own core CRM tables. Deliberately excluded:
 * - Any WordPress core table (users, usermeta, options, ...) - out of scope for
 *   a Groundhogg-branded tool, and options/usermeta can hold real secrets
 *   (API keys, tokens) that have nothing to do with the CRM.
 * - `permissions_keys` - the one Groundhogg table whose rows ARE bearer
 *   credentials (unauthenticated contact-facing links: preference center,
 *   unsubscribe, etc.). Exposing it would let a query hand out working access
 *   tokens, which is a different, worse problem than an admin merely seeing
 *   data they could already see.
 */
class Query_Table extends Ability {

	protected const NAME       = 'groundhogg/query-table';
	protected const CATEGORY   = 'groundhogg-db';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	/**
	 * Groundhogg's own core DB tables that may be queried through this ability.
	 * Keys as accepted by \Groundhogg\get_db(). See the class docblock for what's
	 * deliberately left out and why.
	 */
	public const ALLOWED_TABLES = [
		'contacts', 'contactmeta',
		'tags', 'tag_relationships',
		'notes', 'tasks',
		'broadcasts', 'broadcastmeta',
		'funnels', 'funnelmeta', 'steps', 'stepmeta',
		'emails', 'emailmeta', 'email_log',
		'activity', 'other_activity', 'activitymeta', 'other_activitymeta',
		'events', 'event_queue',
		'submissions', 'submissionmeta', 'form_impressions',
		'campaigns', 'object_relationships',
		'custom_objects', 'custom_object_meta',
		'page_visits', 'logs', 'background_tasks', 'user_agents',
	];

	protected function get_args(): array {

		return [
			'label'       => __( 'Query Table', 'groundhogg' ),
			'description' => __( 'Run a read-only, filtered query against one of Groundhogg\'s own database tables - for ad-hoc analysis the other abilities don\'t cover. See groundhogg/describe-table for a table\'s columns first.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'table' ],
				'properties'           => [
					'table' => [
						'type'        => 'string',
						'enum'        => self::ALLOWED_TABLES,
						'description' => __( 'Which table to query - see groundhogg/describe-table.', 'groundhogg' ),
					],
					'select' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Columns to return. Omit for every column.', 'groundhogg' ),
					],
					'where' => [
						'type'        => 'array',
						'description' => __( 'Conditions, ANDed together.', 'groundhogg' ),
						'items'       => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'required'             => [ 'column' ],
							'properties'           => [
								'column' => [
									'type'        => 'string',
									'description' => __( 'Must be a real column on the chosen table.', 'groundhogg' ),
								],
								'value' => [
									'description' => __( 'Value to compare against. Omit for compare "empty"/"not_empty". An array of values for "in"/"not_in".', 'groundhogg' ),
								],
								'compare' => [
									'type'        => 'string',
									'enum'        => [
										'equals', 'not_equals',
										'less_than', 'greater_than', 'less_than_or_equal_to', 'greater_than_or_equal_to',
										'in', 'not_in',
										'like', 'not_like', 'contains', 'not_contains', 'starts_with', 'ends_with',
										'empty', 'not_empty',
									],
									'default'     => 'equals',
								],
							],
						],
					],
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the table\'s own searchable (text) columns - see groundhogg/describe-table\'s searchable_columns.', 'groundhogg' ),
					],
					'orderby' => [
						'type'        => 'string',
						'description' => __( 'Column to sort by. Must be a real column. Defaults to the table\'s primary key.', 'groundhogg' ),
					],
					'order' => [
						'type'    => 'string',
						'enum'    => [ 'ASC', 'DESC' ],
						'default' => 'DESC',
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 100,
						'default'     => 25,
						'description' => __( 'Maximum rows to return (max 100, always enforced server-side). total_items is unaffected.', 'groundhogg' ),
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
					'table' => [
						'type' => 'string',
					],
					'total_items' => [
						'type'        => 'integer',
						'description' => __( 'Total rows matching where/search, ignoring limit/offset.', 'groundhogg' ),
					],
					'columns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'The columns actually present on each item (all of them, or just `select` if given).', 'groundhogg' ),
					],
					'items' => [
						'type'        => 'array',
						'items'       => [
							'type'                 => 'object',
							'additionalProperties' => true,
						],
						'description' => __( 'Raw rows, values as stored (not type-cast or relabeled).', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$table = $input['table'] ?? '';

		if ( ! in_array( $table, self::ALLOWED_TABLES, true ) ) {
			return new WP_Error( 'groundhogg_table_not_allowed', __( 'That table is not queryable through this ability.', 'groundhogg' ) );
		}

		$db = get_db( $table );

		if ( ! $db ) {
			return new WP_Error( 'groundhogg_table_not_found', __( 'That table is not registered on this site.', 'groundhogg' ) );
		}

		$allowed_columns = $db->get_allowed_columns();

		$select = [];

		if ( ! empty( $input['select'] ) && is_array( $input['select'] ) ) {

			$select = array_values( array_intersect( array_map( 'sanitize_key', $input['select'] ), $allowed_columns ) );

			if ( empty( $select ) ) {
				return new WP_Error( 'groundhogg_invalid_select', __( 'None of the given "select" columns exist on this table. See groundhogg/describe-table.', 'groundhogg' ) );
			}
		}

		$where = [];

		foreach ( (array) ( $input['where'] ?? [] ) as $condition ) {

			$condition = (array) $condition;
			$column    = isset( $condition['column'] ) ? sanitize_key( $condition['column'] ) : '';

			if ( ! $column || ! in_array( $column, $allowed_columns, true ) ) {
				return new WP_Error(
					'groundhogg_invalid_column',
					sprintf(
						/* translators: 1: the invalid column name, 2: the table name */
						__( '"%1$s" is not a column on "%2$s". See groundhogg/describe-table.', 'groundhogg' ),
						$column ?: '(missing)',
						$table
					)
				);
			}

			$compare = $condition['compare'] ?? 'equals';
			$value   = $condition['value'] ?? '';

			$where[] = [
				'column'  => $column,
				'compare' => $compare,
				// Where::compare() parameterizes this, so no further sanitizing here
				// beyond keeping it to a scalar or a flat array of scalars (for
				// "in"/"not_in").
				'value'   => is_array( $value )
					? array_values( array_filter( $value, 'is_scalar' ) )
					: ( is_scalar( $value ) ? $value : '' ),
			];
		}

		$orderby = $db->get_primary_key();

		if ( ! empty( $input['orderby'] ) ) {

			$requested = sanitize_key( $input['orderby'] );

			if ( ! in_array( $requested, $allowed_columns, true ) ) {
				return new WP_Error(
					'groundhogg_invalid_orderby',
					sprintf(
						/* translators: 1: the invalid column name, 2: the table name */
						__( '"%1$s" is not a column on "%2$s".', 'groundhogg' ),
						$requested,
						$table
					)
				);
			}

			$orderby = $requested;
		}

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 100 ) : 25;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = [
			'where'      => $where,
			'orderby'    => $orderby,
			'order'      => ( $input['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'limit'      => $limit,
			'offset'     => $offset,
			'found_rows' => true,
		];

		if ( ! empty( $select ) ) {
			$query_vars['select'] = $select;
		}

		if ( ! empty( $input['search'] ) ) {
			$query_vars['search'] = sanitize_text_field( $input['search'] );
		}

		try {
			$results = $db->query( $query_vars );
			$total   = $db->found_rows();
		} catch ( Throwable $e ) {
			return new WP_Error( 'groundhogg_invalid_query', $e->getMessage() );
		}

		$items = array_map( static function ( $row ) {
			return (array) $row;
		}, $results );

		return [
			'table'       => $table,
			'total_items' => absint( $total ),
			'columns'     => $select ?: $allowed_columns,
			'items'       => $items,
		];
	}
}
