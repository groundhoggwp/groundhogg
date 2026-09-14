<?php

namespace Groundhogg\Abilities\Broadcasts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Broadcast;
use WP_Error;

/**
 * Cancels a scheduled or in-progress broadcast, wrapping \Groundhogg\Broadcast::cancel()
 * - the same call the POST gh/v4/broadcasts/<id>/cancel route uses. Every send
 * event still WAITING in the queue is moved to CANCELLED, the background
 * scheduling task (if any) is stopped, and the broadcast's status becomes
 * "cancelled". Contacts already sent to are unaffected.
 *
 * A broadcast that is already "cancelled" is a no-op success. One that is fully
 * "sent" cannot be cancelled.
 *
 * DESTRUCTIVE is true (queued sends are discarded and cannot be un-cancelled -
 * resuming means scheduling a new broadcast); IDEMPOTENT is true (a second call
 * leaves the broadcast in the same cancelled state).
 */
class Cancel_Broadcast extends Ability {

	protected const NAME       = 'groundhogg/cancel-broadcast';
	protected const CATEGORY   = 'groundhogg-broadcasts';
	protected const CAPABILITY = 'cancel_broadcasts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Cancel Broadcast', 'groundhogg' ),
			'description' => __( 'Cancel a scheduled or sending broadcast. Stops every send that has not gone out yet; contacts already emailed are not affected. A broadcast that has fully sent cannot be cancelled.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'broadcast_id' ],
				'properties'           => [
					'broadcast_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of the broadcast to cancel - see groundhogg/list-broadcasts.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'broadcast_id' => [
						'type' => 'integer',
					],
					'status' => [
						'type'        => 'string',
						'description' => __( 'The broadcast status after the call - "cancelled" on success.', 'groundhogg' ),
					],
					'cancelled' => [
						'type'        => 'boolean',
						'description' => __( 'True when the broadcast is now cancelled (including when it already was).', 'groundhogg' ),
					],
					'events_cancelled' => [
						'type'        => 'integer',
						'description' => __( 'Number of queued sends stopped by this call. 0 if it was already cancelled or nothing was left to send.', 'groundhogg' ),
					],
					'already_cancelled' => [
						'type'        => 'boolean',
						'description' => __( 'True if the broadcast was already cancelled before this call.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$broadcast = new Broadcast( absint( $input['broadcast_id'] ) );

		// A non-email/sms object_type ("recurring_broadcast") is a schedule record
		// sharing the table, not a broadcast to cancel here.
		if ( ! $broadcast->exists() || ! in_array( $broadcast->get_broadcast_type(), [ 'email', 'sms' ], true ) ) {
			return new WP_Error( 'groundhogg_broadcast_not_found', __( 'Broadcast not found.', 'groundhogg' ) );
		}

		if ( $broadcast->is_cancelled() ) {
			return [
				'broadcast_id'      => $broadcast->get_id(),
				'status'            => $broadcast->get_status(),
				'cancelled'         => true,
				'events_cancelled'  => 0,
				'already_cancelled' => true,
			];
		}

		if ( $broadcast->is_sent() ) {
			return new WP_Error(
				'groundhogg_broadcast_already_sent',
				__( 'The broadcast has already been fully sent and cannot be cancelled.', 'groundhogg' )
			);
		}

		// Capture the queued count before cancel() moves those events to CANCELLED.
		$pending = $broadcast->count_pending_events();

		if ( ! $broadcast->cancel() ) {
			return new WP_Error( 'groundhogg_broadcast_not_cancelled', __( 'The broadcast could not be cancelled.', 'groundhogg' ) );
		}

		return [
			'broadcast_id'      => $broadcast->get_id(),
			'status'            => $broadcast->get_status(),
			'cancelled'         => $broadcast->is_cancelled(),
			'events_cancelled'  => $pending,
			'already_cancelled' => false,
		];
	}
}
