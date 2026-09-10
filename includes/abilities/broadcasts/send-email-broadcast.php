<?php

namespace Groundhogg\Abilities\Broadcasts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Broadcast_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Broadcast;
use Groundhogg\DraftException;
use Groundhogg\Email;
use Groundhogg\NoContactsException;
use Groundhogg\SchedulingException;
use Throwable;
use WP_Error;

/**
 * Schedules a one-off email broadcast to a segment of contacts, wrapping
 * \Groundhogg\Broadcast::schedule() - the same helper the POST gh/v4/broadcasts
 * route uses. The email is enqueued for every matching, marketable contact at the
 * requested time (or ~immediately with send_now); Groundhogg's queue does the
 * actual sending.
 *
 * Scope is deliberately narrow compared to Broadcast::schedule():
 *
 * - Email broadcasts only. SMS broadcasts get their own ability rather than an
 *   object_type switch on this one.
 * - One-off only - recurring broadcasts and their ~10 schedule parameters are
 *   not exposed here.
 * - The audience uses the shared Segment_Schema params, but must be named
 *   explicitly: without a narrowing param (tags_include, saved_search, ...) the
 *   call is refused unless send_to_all is set, rather than defaulting to
 *   "everyone".
 *
 * Batching (throttled delivery - release N per interval) IS supported, via the
 * optional `batching` object.
 *
 * DESTRUCTIVE is true and IDEMPOTENT is false: each call schedules a new, real
 * bulk send that is hard to fully undo (cancelling only stops events not yet
 * sent). Hosts should confirm before running this.
 */
class Send_Email_Broadcast extends Ability {

	protected const string NAME       = 'groundhogg/send-email-broadcast';
	protected const string CATEGORY   = 'groundhogg-broadcasts';
	protected const string CAPABILITY = 'schedule_broadcasts';

	protected const bool READONLY    = false;
	protected const bool DESTRUCTIVE = true;
	protected const bool IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Send Email Broadcast', 'groundhogg' ),
			'description' => __( 'Schedule a one-off email broadcast to a segment of contacts (or send it now). Specify the saved email, when to send, and the audience (a saved search, tags, or send_to_all). Only "ready" emails can be broadcast, and only contacts who can be marketed to are included.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => array_merge( Segment_Schema::properties(), [
					'email_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of the saved email to broadcast - see groundhogg/list-email-templates. Must be "ready", not a draft.', 'groundhogg' ),
					],
					'when' => [
						'type'        => 'string',
						'description' => __( 'When to send. Required unless send_now is true. Any strtotime()-compatible string in the site timezone: an ISO 8601 timestamp, a plain datetime ("2026-09-20 09:00:00"), or a relative expression ("tomorrow 9am", "+3 days"). Must be in the future.', 'groundhogg' ),
					],
					'send_now' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Send almost immediately (within seconds) instead of scheduling. Ignores "when" and forces a fixed segment.', 'groundhogg' ),
					],
					'send_in_local_time' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Deliver at the "when" time-of-day in each contact\'s own timezone rather than a single absolute moment.', 'groundhogg' ),
					],
					'send_to_all' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Explicitly broadcast to the entire marketable list. Required to proceed when no narrowing segment param (tags_include, saved_search, ...) is given.', 'groundhogg' ),
					],
					'segment_type' => [
						'type'        => 'string',
						'enum'        => [ 'fixed', 'dynamic' ],
						'default'     => 'fixed',
						'description' => __( '"fixed" locks in the matching contacts now. "dynamic" re-evaluates the audience just before the scheduled send (only meaningful for a future "when").', 'groundhogg' ),
					],
					'batching' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'required'             => [ 'size' ],
						'description'          => __( 'Throttle delivery instead of releasing the whole broadcast at once: send "size" emails, pause, repeat. Omit for an unthrottled send.', 'groundhogg' ),
						'properties'           => [
							'size' => [
								'type'        => 'integer',
								'minimum'     => 1,
								'description' => __( 'How many emails to release per batch.', 'groundhogg' ),
							],
							'interval' => [
								'type'        => 'string',
								'enum'        => [ 'minutes', 'hours', 'days' ],
								'default'     => 'minutes',
								'description' => __( 'Unit of the pause between batches.', 'groundhogg' ),
							],
							'interval_length' => [
								'type'        => 'integer',
								'minimum'     => 1,
								'default'     => 10,
								'description' => __( 'How many "interval" to pause between batches. interval "minutes" + interval_length 10 = one batch every 10 minutes.', 'groundhogg' ),
							],
						],
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Campaign IDs to associate the broadcast with, for reporting.', 'groundhogg' ),
					],
				] ),
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'broadcast_id' => [
						'type' => 'integer',
					],
					'status' => [
						'type'        => 'string',
						'description' => __( '"pending" or "scheduled" while events are still being enqueued, "sending" once delivery has begun.', 'groundhogg' ),
					],
					'email' => [
						'type'       => 'object',
						'properties' => [
							'id'    => [ 'type' => 'integer' ],
							'title' => [ 'type' => 'string' ],
						],
					],
					'send_now' => [
						'type' => 'boolean',
					],
					'send_time' => Broadcast_Schema::datetime_schema(),
					'segment_type' => [
						'type' => 'string',
					],
					'batching' => [
						'type'        => [ 'object', 'null' ],
						'description' => __( 'The batching config that was applied, or null if the broadcast sends unthrottled.', 'groundhogg' ),
						'properties'  => [
							'size'            => [ 'type' => 'integer' ],
							'interval'        => [ 'type' => 'string' ],
							'interval_length' => [ 'type' => 'integer' ],
						],
					],
					'estimated_recipients' => [
						'type'        => 'integer',
						'description' => __( 'Contacts matching the audience at schedule time. Actual sends may be lower once per-contact deliverability is checked.', 'groundhogg' ),
					],
					'scheduling_complete' => [
						'type'        => 'boolean',
						'description' => __( 'True if all send events were enqueued synchronously; false if a background task will finish enqueueing a larger audience.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( $email->is_draft() ) {
			return new WP_Error( 'groundhogg_email_is_draft', __( 'That email is a draft and cannot be broadcast. Mark it ready first.', 'groundhogg' ) );
		}

		$send_now = ! empty( $input['send_now'] );
		$when     = isset( $input['when'] ) ? trim( (string) $input['when'] ) : '';

		if ( ! $send_now && $when === '' ) {
			return new WP_Error( 'groundhogg_missing_when', __( 'Provide "when", or set send_now to true.', 'groundhogg' ) );
		}

		if ( ! Segment_Schema::has_audience( $input ) && empty( $input['send_to_all'] ) ) {
			return new WP_Error(
				'groundhogg_no_audience',
				__( 'Name an audience with a segment param (tags_include, saved_search, ...), or pass send_to_all to broadcast to the entire marketable list.', 'groundhogg' )
			);
		}

		$query = Segment_Schema::to_query( $input );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$args = [
			'object_id'          => $email->get_id(),
			'object_type'        => 'email',
			'send_now'           => $send_now,
			'send_in_local_time' => ! empty( $input['send_in_local_time'] ),
			'segment_type'       => ( $input['segment_type'] ?? 'fixed' ) === 'dynamic' ? 'dynamic' : 'fixed',
			'query'              => $query,
			'campaigns'          => array_map( 'absint', $input['campaigns'] ?? [] ),
		];

		if ( ! $send_now ) {
			// Pass the whole datetime string as `date` with `time` nulled, so
			// Broadcast::schedule() parses it as one value via DateTimeHelper.
			$args['date'] = $when;
			$args['time'] = null;
		}

		$batching = ( ! empty( $input['batching'] ) && is_array( $input['batching'] ) ) ? $input['batching'] : null;

		if ( $batching ) {
			$args['batching']              = true;
			$args['batch_amount']          = max( 1, absint( $batching['size'] ?? 0 ) );
			$args['batch_interval']        = in_array( $batching['interval'] ?? 'minutes', [ 'minutes', 'hours', 'days' ], true )
				? $batching['interval']
				: 'minutes';
			$args['batch_interval_length'] = max( 1, absint( $batching['interval_length'] ?? 10 ) );
		}

		try {
			$broadcast = Broadcast::schedule( $args );
		} catch ( DraftException $e ) {
			return new WP_Error( 'groundhogg_email_is_draft', $e->getMessage() );
		} catch ( NoContactsException $e ) {
			return new WP_Error( 'groundhogg_no_contacts', $e->getMessage() );
		} catch ( SchedulingException $e ) {
			return new WP_Error( 'groundhogg_scheduling_failed', $e->getMessage() );
		} catch ( \DateException $e ) {
			// A "when" in the past, or one DateTimeHelper couldn't parse
			// (\DateMalformedStringException extends \DateException).
			return new WP_Error( 'groundhogg_invalid_when', $e->getMessage() );
		} catch ( Throwable $e ) {
			return new WP_Error( 'groundhogg_broadcast_not_scheduled', $e->getMessage() );
		}

		return [
			'broadcast_id'         => $broadcast->get_id(),
			'status'               => $broadcast->get_status(),
			'email'                => [
				'id'    => $email->get_id(),
				'title' => $email->get_title(),
			],
			'send_now'             => $send_now,
			'send_time'            => Broadcast_Schema::datetime( $broadcast->get_send_time() ),
			'segment_type'         => $args['segment_type'],
			'batching'             => $batching ? [
				'size'            => $args['batch_amount'],
				'interval'        => $args['batch_interval'],
				'interval_length' => $args['batch_interval_length'],
			] : null,
			'estimated_recipients' => absint( $broadcast->get_meta( 'total_contacts' ) ),
			'scheduling_complete'  => $broadcast->is_scheduled(),
		];
	}
}
