<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use WP_Error;
use function Groundhogg\enqueue_event;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_a_contact;
use function Groundhogg\process_events;

/**
 * Sends a test of a saved Groundhogg email, the same two ways wp-admin's email
 * editor "Send Test" button does (POST gh/v4/emails/<id>/send-test, see
 * Emails_Api::sent_test()). Both work on a draft, which
 * groundhogg/send-email-template refuses.
 *
 * "design" (the default, the editor's "Design only"):
 *
 * - No real event is enqueued and no event queue runs - the email is built
 *   and mailed directly via Email::send(), so it doesn't require the email to
 *   be "ready".
 * - Open/click tracking links, the unsubscribe link, and the real sending
 *   rules (consent, marketability, undeliverable) are skipped - Email::is_testing()
 *   bypasses all of that, same as a real send would hit them.
 * - The subject is prefixed with "[TEST]" and the message is always routed to
 *   the given `to` addresses, regardless of whose data resolves merge tags -
 *   so a saved contact's real address is never actually mailed.
 *
 * "simulated" (the editor's "Simulated", the API's "functional"):
 *
 * - Sends it the way a broadcast or flow would: each `to` address is a
 *   contact (created when there isn't one), a TEST_EMAIL event is enqueued for
 *   it and the queue runs it, so tracking, unsubscribe and view in browser
 *   links, superlinks and conditional blocks all work, using that contact's
 *   own data. No "[TEST]" prefix.
 * - A draft is treated as ready for this send only, through the
 *   groundhogg/test_email/before_send hook, the same as the editor does.
 * - The difference from groundhogg/send-email-template is exactly that, plus
 *   the opt-in status is taken as confirmed (see Queue\Test_Email::run()), so
 *   it reaches the tester whatever their own status is.
 *
 * See groundhogg/send-email-template to actually send the email for real.
 */
class Send_Test_Email extends Ability {

	protected const NAME       = 'groundhogg/send-test-email';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'send_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Send Test Email', 'groundhogg' ),
			'description' => __( 'Send a test of a saved Groundhogg email to one or more addresses - the same as wp-admin\'s "Send Test" button. Works on a draft, unlike groundhogg/send-email-template. Two types: "design" (default) checks how it looks - no tracking, unsubscribe or view in browser links, conditional blocks all shown, "[TEST]" in the subject. "simulated" sends it the way a broadcast or flow would, with working tracking and links and conditional blocks, using each recipient\'s own contact record (created if missing) - use it to check links and personalization. Only send tests to the people doing the testing, never to contacts.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'to' ],
				'properties'           => [
					'email_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The ID of the saved email to test - see groundhogg/list-email-templates to find it.', 'groundhogg' ),
					],
					'to' => [
						'type'        => 'array',
						'minItems'    => 1,
						'items'       => [ 'type' => 'string', 'format' => 'email' ],
						'description' => __( 'Addresses to actually mail the test to. For a "design" test the email is always routed here, regardless of whose data resolves merge tags via contact_id. For a "simulated" test each address is the contact it\'s sent to.', 'groundhogg' ),
					],
					'type' => [
						'type'        => 'string',
						'enum'        => [ 'design', 'simulated' ],
						'default'     => 'design',
						'description' => __( '"design" to check how it looks, "simulated" to send it as a broadcast or flow would, with working links and tracking.', 'groundhogg' ),
					],
					'contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Design tests only: resolve merge tags/replacement codes against this existing contact\'s data. Defaults to the current user\'s own name/email (not saved as a contact) when omitted. A simulated test uses each recipient\'s own contact record instead.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'sent' => [
						'type'        => 'boolean',
						'description' => __( 'Always true on success; the call returns an error instead when the send fails.', 'groundhogg' ),
					],
					'to' => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
					'type' => [
						'type' => 'string',
					],
					'subject' => [
						'type'        => 'string',
						'description' => __( 'For a design test, the final subject line, after merge-field replacement, with the "[TEST] " prefix added. For a simulated test, the subject line as written, since it\'s merged for each recipient.', 'groundhogg' ),
					],
					'preview_url' => [
						'type'        => 'string',
						'description' => __( 'A signed link (valid about a week) that renders this email in the browser, no login required - the same content this test was built from.', 'groundhogg' ),
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

		if ( $email->is_global_block() ) {
			return new WP_Error( 'groundhogg_not_sendable', __( 'That email is a global block and cannot be sent on its own.', 'groundhogg' ) );
		}

		$to = array_values( array_filter( array_map( 'sanitize_email', $input['to'] ?? [] ) ) );

		if ( empty( $to ) ) {
			return new WP_Error( 'groundhogg_no_recipients', __( 'At least one recipient is required.', 'groundhogg' ) );
		}

		if ( ( $input['type'] ?? 'design' ) === 'simulated' ) {

			if ( ! empty( $input['contact_id'] ) ) {
				return new WP_Error( 'groundhogg_invalid_input', __( '`contact_id` only applies to design tests. A simulated test uses the contact record of each address in `to`.', 'groundhogg' ) );
			}

			return $this->simulate( $email, $to );
		}

		if ( ! empty( $input['contact_id'] ) ) {

			$contact = get_contactdata( absint( $input['contact_id'] ) );

			if ( ! is_a_contact( $contact ) ) {
				return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
			}

			if ( ! current_user_can( 'view_contact', $contact ) ) {
				return new WP_Error(
					'groundhogg_cannot_view_contact',
					__( 'You do not have permission to use this contact to resolve merge tags.', 'groundhogg' )
				);
			}
		} else {

			// A temporary, unsaved contact from the current user - the same
			// fallback Email::get_as_array() uses for its own live preview when
			// there's no contact in context. Properties are set directly
			// (Base_Object::__set() just holds them in memory) rather than via
			// the constructor's email-array form, which would create/update a
			// real contact record - this test must not write one.
			$user = wp_get_current_user();

			$contact             = new Contact();
			$contact->email      = $user->user_email;
			$contact->first_name = $user->first_name;
			$contact->last_name  = $user->last_name;
		}

		$email->enable_test_mode();

		// Always mail the given addresses, never the contact's real one.
		add_filter( 'groundhogg/email/to', function () use ( $to ) {
			return implode( ',', $to );
		} );

		// Prefix subject line with [TEST], same as the wp-admin "Send Test" button.
		add_filter( 'groundhogg/email/subject', function ( $subject ) {
			/* translators: %s: the subject line */
			return sprintf( __( '[TEST] %s', 'groundhogg' ), $subject );
		} );

		$sent = $email->send( $contact );

		if ( is_wp_error( $sent ) ) {
			return $sent;
		}

		if ( ! $sent ) {
			return new WP_Error( 'groundhogg_test_email_not_sent', __( 'The test email could not be sent.', 'groundhogg' ) );
		}

		return [
			'sent'        => true,
			'to'          => $to,
			'type'        => 'design',
			'subject'     => $email->get_merged_subject_line(),
			'preview_url' => $email->get_preview_url(),
		];
	}

	/**
	 * Send it to each address the way a broadcast or flow would, through a TEST_EMAIL event. See the class docblock.
	 *
	 * @param Email    $email
	 * @param string[] $to
	 *
	 * @return array|WP_Error
	 */
	protected function simulate( Email $email, array $to ) {

		// a draft would be refused when it sends, so it counts as ready for this test only, as in the editor
		$force_ready = function ( Email $test_email ) {
			$test_email->status = 'ready';
		};

		$failures     = [];
		$collect_fail = function ( $error ) use ( &$failures ) {
			$failures[] = $error;
		};

		add_action( 'groundhogg/test_email/before_send', $force_ready );
		add_action( 'wp_mail_failed', $collect_fail );

		$result = $this->send_simulated( $email, $to );

		remove_action( 'groundhogg/test_email/before_send', $force_ready );
		remove_action( 'wp_mail_failed', $collect_fail );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! empty( $failures ) ) {
			return $failures[0];
		}

		return [
			'sent'        => true,
			'to'          => $to,
			'type'        => 'simulated',
			'subject'     => $email->get_subject_line(),
			'preview_url' => $email->get_preview_url(),
		];
	}

	/**
	 * @param Email    $email
	 * @param string[] $to
	 *
	 * @return true|WP_Error
	 */
	protected function send_simulated( Email $email, array $to ) {

		foreach ( $to as $address ) {

			$contact = get_contactdata( $address );

			if ( ! is_a_contact( $contact ) ) {

				if ( ! current_user_can( 'add_contacts' ) ) {
					return new WP_Error(
						'groundhogg_cannot_add_contact',
						/* translators: %s: an email address */
						sprintf( __( 'There is no contact for %s, and you are not allowed to add one. Send a simulated test to an address that has a contact record, or use a design test.', 'groundhogg' ), $address )
					);
				}

				$contact = new Contact( [ 'email' => $address ] );

				if ( ! $contact->exists() ) {
					/* translators: %s: an email address */
					return new WP_Error( 'groundhogg_contact_not_created', sprintf( __( 'Could not create a contact for %s.', 'groundhogg' ), $address ) );
				}
			}

			$event = enqueue_event( [
				'email_id'   => $email->get_id(),
				'contact_id' => $contact->get_id(),
				'event_type' => Event::TEST_EMAIL,
				'priority'   => 1,
				'status'     => Event::WAITING,
			] );

			if ( ! $event ) {
				/* translators: %s: an email address */
				return new WP_Error( 'groundhogg_test_email_not_sent', sprintf( __( 'Could not queue the test email for %s.', 'groundhogg' ), $address ) );
			}

			$processed = process_events( $contact );

			if ( $processed !== true ) {
				return is_array( $processed ) && is_wp_error( $processed[0] ?? null )
					? $processed[0]
					: new WP_Error( 'groundhogg_test_email_not_sent', __( 'The test email could not be sent.', 'groundhogg' ) );
			}
		}

		return true;
	}
}
