<?php

namespace Groundhogg\Abilities\Broadcasts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Broadcast_Schema;
use Groundhogg\Broadcast;
use WP_Error;

/**
 * Retrieves a single broadcast by ID, always including its stats report
 * (sent / opened / clicked / unsubscribed plus rates, and how many send events
 * are still queued). Mirrors the GET gh/v4/broadcasts/<id>/report route, which
 * is built on Broadcast::get_report_data().
 */
class Get_Broadcast extends Ability {

	protected const NAME       = 'groundhogg/get-broadcast';
	protected const CATEGORY   = 'groundhogg-broadcasts';
	protected const CAPABILITY = 'view_broadcasts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Broadcast', 'groundhogg' ),
			'description' => __( 'Retrieve one broadcast by ID, including its full stats report. Find IDs with groundhogg/list-broadcasts.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'broadcast_id' ],
				'properties'           => [
					'broadcast_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
				],
			],

			'output_schema' => Broadcast_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		$broadcast = new Broadcast( absint( $input['broadcast_id'] ) );

		// A non-email/sms object_type ("recurring_broadcast") is a schedule record
		// sharing the table, not a broadcast a caller means to inspect here.
		if ( ! $broadcast->exists() || ! in_array( $broadcast->get_broadcast_type(), [ 'email', 'sms' ], true ) ) {
			return new WP_Error( 'groundhogg_broadcast_not_found', __( 'Broadcast not found.', 'groundhogg' ) );
		}

		return Broadcast_Schema::transform( $broadcast, [ 'stats' ] );
	}
}
