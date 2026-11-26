<?php

namespace Groundhogg\Abilities\Broadcasts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Broadcast_Schema;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Broadcast;
use Groundhogg\DraftException;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
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

	protected const NAME       = 'groundhogg/send-email-broadcast';
	protected const CATEGORY   = 'groundhogg-broadcasts';
	protected const CAPABILITY = 'schedule_broadcasts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

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
					'ab_test' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'description'          => __( 'Test the email\'s versions on a random sample of the audience first, then send the one that did best to everyone else. The email needs ready test versions, see groundhogg/create-email-variation with kind "test". This call sends to the sample; after wait_hours the winner is declared and the email is broadcast to the rest, who all get it. groundhogg/get-email-variation-report shows how it\'s going. Always a fixed segment.', 'groundhogg' ),
						'properties'           => [
							'sample_percent' => [
								'type'        => 'integer',
								'minimum'     => 5,
								'maximum'     => 50,
								'default'     => 20,
								'description' => __( 'How much of the audience the test is sent to, in percent, split between the versions by their weights.', 'groundhogg' ),
							],
							'wait_hours'     => [
								'type'        => 'integer',
								'minimum'     => 1,
								'maximum'     => 168,
								'default'     => 4,
								'description' => __( 'How long after the sample is sent to pick the winner and send it to the rest.', 'groundhogg' ),
							],
							'metric'         => [
								'type'        => 'string',
								'enum'        => [ 'clicks', 'opens' ],
								'default'     => 'clicks',
								'description' => __( 'What the winner is picked by. Clicks are more reliable, since some email apps open every email.', 'groundhogg' ),
							],
						],
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
							'id'          => [ 'type' => 'integer' ],
							'title'       => [ 'type' => 'string' ],
							'preview_url' => [
								'type'        => 'string',
								'description' => __( 'A signed link (valid about a week) that renders this email in the browser, no login required.', 'groundhogg' ),
							],
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
						'description' => __( 'How many contacts the broadcast is going to. When scheduling_complete is true this is exactly the number of send events queued, with contacts who can\'t be delivered to already left out. Otherwise (a dynamic segment, which is evaluated just before sending, or a large audience still being enqueued) it is the number of contacts matching the audience right now.', 'groundhogg' ),
					],
					'scheduling_complete' => [
						'type'        => 'boolean',
						'description' => __( 'True if all send events were enqueued synchronously; false if a background task will finish enqueueing a larger audience.', 'groundhogg' ),
					],
					'ab_test' => [
						'type'        => 'object',
						'description' => __( 'With ab_test: this broadcast is the sample, and when the winner is sent to the rest.', 'groundhogg' ),
						'properties'  => [
							'sample'         => [ 'type' => 'integer' ],
							'rest'           => [ 'type' => 'integer' ],
							'winner_sent_at' => Broadcast_Schema::datetime_schema(),
						],
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

		// to_query(), not to_contact_query()'s live query object - this gets stored
		// (Broadcast::schedule()'s 'query' arg below) and, for a "dynamic"
		// segment_type, re-run later against whatever then matches; a live query
		// object (and anything attached to it) can't survive that round trip.
		// It is the very same translation groundhogg/search-contacts runs
		// (to_contact_query() is to_query() plus a Contact_Query), so every segment
		// param narrows the audience identically in both - exclude_segment becomes
		// the `exclude_filters` query var, tags_exclude and the other native params
		// stay native, and `search` carries through. NOT to_filters(), which is the
		// translation for branch/logic conditions and leaves exclude_segment and
		// `search` out.
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

		$ab_test = is_array( $input['ab_test'] ?? null ) ? $input['ab_test'] : null;

		try {

			if ( $ab_test ) {

				// the sample now, the winner to everyone else later
				$schedule = array_diff_key( $args, array_flip( [ 'object_id', 'object_type', 'query', 'segment_type' ] ) );
				$broadcast = Email_Variations::start_broadcast_test( $email, $query, $schedule, $ab_test );

				if ( is_wp_error( $broadcast ) ) {
					return new WP_Error( 'groundhogg_' . $broadcast->get_error_code(), $broadcast->get_error_message() );
				}

				$args['segment_type'] = 'fixed';
			} else {
				$broadcast = Broadcast::schedule( $args );
			}
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

		$output = [
			'broadcast_id'         => $broadcast->get_id(),
			'status'               => $broadcast->get_status(),
			'email'                => [
				'id'          => $email->get_id(),
				'title'       => $email->get_title(),
				'preview_url' => $email->get_preview_url(),
			],
			'send_now'             => $send_now,
			'send_time'            => Broadcast_Schema::datetime( $broadcast->get_send_time() ),
			'segment_type'         => $args['segment_type'],
			'batching'             => $batching ? [
				'size'            => $args['batch_amount'],
				'interval'        => $args['batch_interval'],
				'interval_length' => $args['batch_interval_length'],
			] : null,
			// Once every event is enqueued this is the number actually queued, so it can't
			// disagree with the events. Until then (a dynamic segment, or a list too big to
			// finish synchronously) it's the count of contacts the stored query matches.
			'estimated_recipients' => $broadcast->is_scheduled()
				? $broadcast->get_num_queued()
				: absint( $broadcast->get_meta( 'total_contacts' ) ),
			'scheduling_complete'  => $broadcast->is_scheduled(),
		];

		if ( $ab_test ) {
			$test = ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY );

			$output['ab_test'] = [
				'sample'         => absint( $test['sample'] ?? 0 ),
				'rest'           => absint( $test['rest'] ?? 0 ),
				'winner_sent_at' => Broadcast_Schema::datetime( absint( $test['finish_at'] ?? 0 ) ),
			];
		}

		return $output;
	}
}
