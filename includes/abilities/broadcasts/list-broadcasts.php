<?php

namespace Groundhogg\Abilities\Broadcasts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Broadcast_Schema;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;
use Throwable;
use WP_Error;
use function Groundhogg\get_db;
use function Groundhogg\is_sms_plugin_active;

/**
 * Lists broadcasts, most recent first, so an agent can see what has been sent or
 * is scheduled and drill into one with groundhogg/get-broadcast.
 *
 * The gh_broadcasts table also stores recurring-broadcast schedule records
 * (object_type "recurring_broadcast"); those are not real broadcasts and are
 * filtered out - only email and SMS broadcasts are returned.
 *
 * Per-broadcast stats (sent / opened / clicked / unsubscribed) each cost a few
 * COUNT queries, so they are only included when "stats" is passed in expand -
 * request them with a small limit.
 *
 * The gh_broadcasts table has no title/subject column of its own to search -
 * `search` instead LEFT JOINs the emails table (and sms, if that add-on is
 * active) on object_id/object_type and matches against their title/subject,
 * mirroring exactly what Broadcasts_Table::prepare_items() does for the
 * search box on the Broadcasts admin page (admin/broadcasts/broadcasts-table.php).
 *
 * `campaigns` filters via the generic object_relationships-backed 'related'
 * query var (DB::query()) - a broadcast is the primary/parent side of its
 * relationship to a campaign, same direction Broadcast::get_related_objects('campaign')
 * reads.
 */
class List_Broadcasts extends Ability {

	protected const NAME       = 'groundhogg/list-broadcasts';
	protected const CATEGORY   = 'groundhogg-broadcasts';
	protected const CAPABILITY = 'view_broadcasts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Broadcasts', 'groundhogg' ),
			'description' => __( 'List broadcasts, most recent first, with pagination. Filter by status or a send-time range (after/before). Returns each broadcast\'s status, email, send time, and audience size; pass expand to also include open/click stats. Use groundhogg/get-broadcast for the full report on one.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'status' => [
						'type'        => 'string',
						'enum'        => [ 'pending', 'scheduled', 'sending', 'sent', 'cancelled' ],
						'description' => __( 'Only include broadcasts with this status.', 'groundhogg' ),
					],
					'after' => [
						'type'        => 'string',
						'description' => __( 'Only include broadcasts whose send time is on or after this. A date ("2026-01-01") or datetime, interpreted in the site timezone. Relative expressions like "-30 days" also work.', 'groundhogg' ),
					],
					'before' => [
						'type'        => 'string',
						'description' => __( 'Only include broadcasts whose send time is on or before this. Same accepted formats as "after".', 'groundhogg' ),
					],
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search matched against the title and subject of the broadcast\'s email (or SMS, if active) - the broadcast itself has no searchable text of its own.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include broadcasts tagged with at least one of these campaign IDs. Find IDs with groundhogg/list-campaigns.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'stats' ],
						],
						'default'     => [],
						'description' => __( 'Extra sections per broadcast. "stats" adds sent/opened/clicked/unsubscribed counts and rates - a few extra queries each, so use a small limit.', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 50,
						'default'     => 20,
						'description' => __( 'Maximum number of broadcasts to return. total_items is unaffected.', 'groundhogg' ),
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
						'description' => __( 'Total broadcasts matching the query, ignoring limit/offset.', 'groundhogg' ),
					],
					'broadcasts' => [
						'type'  => 'array',
						'items' => Broadcast_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 50 ) : 20;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = [
			'limit'       => $limit,
			'offset'      => $offset,
			'found_rows'  => true,
			'orderby'     => 'send_time',
			'order'       => 'DESC',
			// Exclude "recurring_broadcast" schedule-pointer rows that share this table.
			'object_type' => [ 'email', 'sms' ],
		];

		if ( ! empty( $input['status'] ) ) {
			$query_vars['status'] = sanitize_text_field( $input['status'] );
		}

		if ( ! empty( $input['campaigns'] ) ) {
			$query_vars['related'] = [
				'id'   => wp_parse_id_list( $input['campaigns'] ),
				'type' => 'campaign',
			];
		}

		// The broadcasts DB knows send_time is a UNIX timestamp (get_date_key_format
		// => 'unix'), so the native before/after query vars build a correct
		// comparison. Validate the inputs here for a clean error rather than letting
		// DateTimeHelper throw from inside the query.
		foreach ( [ 'after', 'before' ] as $param ) {

			if ( empty( $input[ $param ] ) ) {
				continue;
			}

			try {
				new DateTimeHelper( (string) $input[ $param ] );
			} catch ( Throwable $e ) {
				return new WP_Error(
					'groundhogg_invalid_date',
					// Translators: This string is used to display an error message if a provided date or datetime argument is invalid.
					sprintf( __( 'Could not understand "%s" as a date.', 'groundhogg' ), $param )
				);
			}

			$query_vars[ $param ] = sanitize_text_field( $input[ $param ] );
		}

		$db = get_db( 'broadcasts' );

		$search_callback = null;

		if ( ! empty( $input['search'] ) ) {

			$search = sanitize_text_field( $input['search'] );

			// Same join-and-LIKE approach as Broadcasts_Table::prepare_items() -
			// gh_broadcasts itself has no title/subject to search, so this joins
			// in the object it's actually about.
			$search_callback = function ( Table_Query &$query ) use ( $search ) {

				$emailJoin = $query->addJoin( 'LEFT', 'emails' );
				$emailJoin->onColumn( 'ID', 'object_id' )
				          ->equals( "$query->alias.object_type", 'email' );

				$searchWhere = $query->where()->subWhere();

				$searchWhere->like( "$emailJoin->alias.title", '%' . $query->db->esc_like( $search ) . '%' );
				$searchWhere->like( "$emailJoin->alias.subject", '%' . $query->db->esc_like( $search ) . '%' );

				if ( is_sms_plugin_active() ) {
					$smsJoin = $query->addJoin( 'LEFT', 'sms' );
					$smsJoin->onColumn( 'ID', 'object_id' )
					        ->equals( "$query->alias.object_type", 'sms' );

					$searchWhere->like( "$smsJoin->alias.title", '%' . $query->db->esc_like( $search ) . '%' );
				}
			};

			add_action( 'groundhogg/broadcast/pre_get_results', $search_callback );
		}

		$results = $db->query( $query_vars );

		if ( $search_callback ) {
			remove_action( 'groundhogg/broadcast/pre_get_results', $search_callback );
		}

		// Capture found_rows() before Broadcast_Schema transform runs its own
		// queries (the stats expand especially) - same caveat as groundhogg/list-tags.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		$broadcasts = array_map( function ( $broadcast ) use ( $expand ) {
			return Broadcast_Schema::transform( $broadcast, $expand );
		}, $results );

		return [
			'total_items' => $total_items,
			'broadcasts'  => $broadcasts,
		];
	}
}
