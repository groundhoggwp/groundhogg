<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use WP_Error;
use function Groundhogg\get_contactdata;
use function Groundhogg\process_events;
use function Groundhogg\send_email_notification;

/**
 * Sends an existing saved Groundhogg email to one or more contacts, mirroring
 * the POST gh/v4/emails/<id>/send route (Emails_Api::send_email_by_id()). Each
 * recipient gets an EMAIL_NOTIFICATION event enqueued through
 * send_email_notification() and run through the normal event queue, so the
 * email's own sending rules (from address, unsubscribe link, click/open
 * tracking, etc.) all apply. Immediate sends are processed inline per recipient,
 * exactly like the REST route; a future `when` schedules the event for cron to
 * pick up instead.
 *
 * Recipients are matched to existing contacts only - an address or ID with no
 * matching contact is reported in recipients[].status = "skipped" rather than
 * creating a contact.
 *
 * On top of the `send_emails` capability, each recipient must be a contact the
 * caller can `view_contact` - one they can't is reported as
 * recipients[].status = "forbidden" and not sent to, so `send_emails` alone
 * can't be used to reach contacts outside the caller's team. Emails_Api's
 * send route enforces the same (as an all-or-nothing 403).
 *
 * DESTRUCTIVE is true: sending mail is an irreversible outward-facing side
 * effect, so hosts that gate destructive abilities behind confirmation should do
 * so here even though no data is destroyed.
 */
class Send_Email_Template extends Ability {

	protected const NAME       = 'groundhogg/send-email-template';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'send_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Send Email Template', 'groundhogg' ),
			'description' => __( 'Send an existing saved Groundhogg email to one or more contacts through the normal event queue. Optionally schedule it for a future time. Recipients are given as contact IDs or email addresses and must already belong to a contact; unmatched recipients are skipped and reported back.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'to' ],
				'properties'           => [
					'email_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of the saved email to send - see groundhogg/list-email-templates to find it.', 'groundhogg' ),
					],
					'to' => [
						'type'        => 'array',
						'minItems'    => 1,
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Recipients, as contact IDs or email addresses. Each must already belong to a contact.', 'groundhogg' ),
					],
					'when' => [
						'type'        => 'string',
						'description' => __( 'When to send. Omit (or give a past/now value) to send immediately; a future value schedules the send via the event queue. Any PHP strtotime()-compatible string in the site\'s server timezone - an ISO 8601 timestamp ("2026-09-17T09:00:00-04:00"), a plain datetime ("2026-09-17 09:00:00"), or a relative expression ("+1 week", "tomorrow 9am", "next monday").', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id' => [
						'type' => 'integer',
					],
					'email_title' => [
						'type' => 'string',
					],
					'scheduled' => [
						'type'        => 'boolean',
						'description' => __( 'True when the send was scheduled for a future time rather than sent immediately.', 'groundhogg' ),
					],
					'scheduled_for' => [
						'type'        => [ 'string', 'null' ],
						'description' => __( 'ISO 8601 (UTC) scheduled send time, or null when sent immediately.', 'groundhogg' ),
					],
					'recipients' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'to' => [
									'type'        => [ 'string', 'integer' ],
									'description' => __( 'The recipient value as supplied.', 'groundhogg' ),
								],
								'contact_id' => [
									'type' => [ 'integer', 'null' ],
								],
								'status' => [
									'type'        => 'string',
									'enum'        => [ 'sent', 'scheduled', 'skipped', 'forbidden', 'failed' ],
									'description' => __( 'sent: delivered to the queue and processed now. scheduled: enqueued for a future time. skipped: no matching contact. forbidden: the caller cannot view this contact. failed: could not be enqueued or the queue returned an error.', 'groundhogg' ),
								],
								'error' => [
									'type'        => [ 'string', 'null' ],
									'description' => __( 'Failure reason, when status is skipped or failed.', 'groundhogg' ),
								],
							],
							'required' => [ 'to', 'contact_id', 'status', 'error' ],
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

		// A global block is a reusable content fragment, not a standalone email -
		// groundhogg/list-email-templates already hides these, but guard the send
		// path too in case an ID is passed directly.
		if ( $email->is_global_block() ) {
			return new WP_Error( 'groundhogg_not_sendable', __( 'That email is a global block and cannot be sent on its own.', 'groundhogg' ) );
		}

		$to = $input['to'] ?? [];

		if ( empty( $to ) ) {
			return new WP_Error( 'groundhogg_no_recipients', __( 'At least one recipient is required.', 'groundhogg' ) );
		}

		// Emails_Api passes `when` straight to send_email_notification(), which
		// runs strtotime() on a string. Mirror that (accepting any strtotime()
		// input - absolute or relative), but reject an unparseable value rather
		// than silently falling through to an immediate send. Only a future time
		// counts as "scheduled"; a past/now value sends immediately.
		$when    = $input['when'] ?? '';
		$when_ts = 0;

		if ( $when !== '' && $when !== 0 ) {

			$when_ts = is_numeric( $when ) ? (int) $when : strtotime( (string) $when );

			if ( ! $when_ts ) {
				return new WP_Error(
					'groundhogg_invalid_when',
					__( 'Could not understand the "when" value as a date/time.', 'groundhogg' )
				);
			}
		}

		$future = $when_ts && $when_ts > time();

		$recipients = [];

		foreach ( $to as $value ) {

			$lookup  = is_numeric( $value ) ? absint( $value ) : sanitize_email( (string) $value );
			$contact = $lookup ? get_contactdata( $lookup ) : false;

			if ( ! $contact || ! $contact->exists() ) {
				$recipients[] = [
					'to'         => $value,
					'contact_id' => null,
					'status'     => 'skipped',
					'error'      => __( 'No contact matches this recipient.', 'groundhogg' ),
				];
				continue;
			}

			// send_emails gates the ability; the caller also has to be able to
			// view this specific contact.
			if ( ! current_user_can( 'view_contact', $contact ) ) {
				$recipients[] = [
					'to'         => $value,
					'contact_id' => $contact->get_id(),
					'status'     => 'forbidden',
					'error'      => __( 'You do not have permission to send email to this contact.', 'groundhogg' ),
				];
				continue;
			}

			$enqueued = (bool) send_email_notification( $email, $contact, $when_ts );

			if ( ! $enqueued ) {
				$recipients[] = [
					'to'         => $value,
					'contact_id' => $contact->get_id(),
					'status'     => 'failed',
					'error'      => __( 'The send event could not be enqueued.', 'groundhogg' ),
				];
				continue;
			}

			if ( $future ) {
				$recipients[] = [
					'to'         => $value,
					'contact_id' => $contact->get_id(),
					'status'     => 'scheduled',
					'error'      => null,
				];
				continue;
			}

			// Process the queue for this contact now, same as
			// Emails_Api::send_email_by_id(). process_events() returns an array
			// of WP_Errors on failure, true otherwise.
			$result = process_events( [ $contact ] );
			$error  = null;

			if ( is_array( $result ) && ! empty( $result ) ) {
				$first = $result[0];
				$error = is_wp_error( $first ) ? $first->get_error_message() : (string) $first;
			}

			$recipients[] = [
				'to'         => $value,
				'contact_id' => $contact->get_id(),
				'status'     => $error ? 'failed' : 'sent',
				'error'      => $error,
			];
		}

		return [
			'email_id'      => $email->get_id(),
			'email_title'   => $email->get_title(),
			'scheduled'     => (bool) $future,
			'scheduled_for' => $future ? gmdate( 'Y-m-d\TH:i:s\Z', $when_ts ) : null,
			'recipients'    => $recipients,
		];
	}
}
