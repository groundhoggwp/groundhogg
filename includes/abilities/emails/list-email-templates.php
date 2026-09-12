<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use function Groundhogg\get_db;

/**
 * Lists the saved Groundhogg emails, so a caller can discover the email_id to
 * pass to groundhogg/send-email-template without guessing.
 *
 * Global blocks (message_type = 'global_block') are excluded: they are reusable
 * content fragments embedded into other emails, not standalone sendable emails,
 * so they would only be noise here. The result set is limited to the sendable
 * message types ('marketing', 'transactional').
 *
 * The email body (`content` / `plain_text`) is large - tens of KB per email - and
 * is almost never needed just to pick which email to send, so it is only
 * returned when explicitly requested via `expand`. When expanding content, keep
 * `limit` small.
 *
 * `campaigns` filters via the generic object_relationships-backed 'related'
 * query var (DB::query()) - an email is the primary/parent side of its
 * relationship to a campaign, same direction Email::get_related_objects('campaign')
 * reads (see groundhogg/create-email's and groundhogg/update-email's campaigns param).
 */
class List_Email_Templates extends Ability {

	protected const string NAME       = 'groundhogg/list-email-templates';
	protected const string CATEGORY   = 'groundhogg-email';
	protected const string CAPABILITY = 'view_emails';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Email Templates', 'groundhogg' ),
			'description' => __( 'List saved Groundhogg emails, with pagination. Returns each email\'s id, title, subject, status, and sender by default; pass expand to also include the (large) HTML body. Use the returned id with groundhogg/send-email-template.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search matched against the title, subject, pre-header, and body.', 'groundhogg' ),
					],
					'status' => [
						'type'        => 'string',
						'enum'        => [ 'ready', 'draft' ],
						'description' => __( 'Only include emails with this status. Only "ready" emails send cleanly.', 'groundhogg' ),
					],
					'is_template' => [
						'type'        => 'boolean',
						'description' => __( 'true to only include emails saved as reusable templates, false to only include regular one-off emails. Omit to include both.', 'groundhogg' ),
					],
					'message_type' => [
						'type'        => 'string',
						'enum'        => [ 'marketing', 'transactional' ],
						'description' => __( 'Only include emails of this message type. Global blocks are always excluded regardless of this filter.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Only include emails tagged with at least one of these campaign IDs. Find IDs with groundhogg/list-campaigns.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'content', 'plain_text' ],
						],
						'default'     => [],
						'description' => __( 'Extra sections to include on each email. "content" is the full HTML body and can be very large - only request it with a small limit.', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 50,
						'default'     => 20,
						'description' => __( 'Maximum number of emails to return. total_items is unaffected by this.', 'groundhogg' ),
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
						'description' => __( 'Total emails matching the query, ignoring limit/offset.', 'groundhogg' ),
					],
					'emails' => [
						'type'  => 'array',
						'items' => Email_Schema::get_schema(),
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

		if ( isset( $input['is_template'] ) ) {
			$query_vars['is_template'] = $input['is_template'] ? 1 : 0;
		}

		// Global blocks live in the same table but aren't sendable on their own -
		// exclude them. A specific message_type filter narrows further; without
		// one, everything except global_block is in scope.
		if ( ! empty( $input['message_type'] ) && $input['message_type'] !== 'global_block' ) {
			$query_vars['message_type'] = sanitize_text_field( $input['message_type'] );
		} else {
			$query_vars['message_type'] = [ '!=', 'global_block' ];
		}

		if ( ! empty( $input['campaigns'] ) ) {
			$query_vars['related'] = [
				'id'   => wp_parse_id_list( $input['campaigns'] ),
				'type' => 'campaign',
			];
		}

		$db      = get_db( 'emails' );
		$results = $db->query( $query_vars );

		// Capture found_rows() immediately after the query, before Email_Schema
		// transform runs anything that could issue its own query - same caveat as
		// groundhogg/list-tags.
		$total_items = $db->found_rows();

		$expand = $input['expand'] ?? [];

		$emails = array_map( function ( $email ) use ( $expand ) {
			return Email_Schema::transform( $email, $expand );
		}, $results );

		return [
			'total_items' => $total_items,
			'emails'      => $emails,
		];
	}
}
