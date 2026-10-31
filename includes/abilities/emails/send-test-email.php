<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Contact;
use Groundhogg\Email;
use WP_Error;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_a_contact;

/**
 * Sends a design-only test of a saved Groundhogg email - the same path as
 * wp-admin's email editor "Send Test" button (and the "design" type of
 * POST gh/v4/emails/<id>/send-test), not the real send pipeline. Mirrors
 * Emails_Api::sent_test() with type "design":
 *
 * - No real event is enqueued and no event queue runs - the email is built
 *   and mailed directly via Email::send(), so it works on a draft and
 *   doesn't require the email to be "ready".
 * - Open/click tracking links, the unsubscribe link, and the real sending
 *   rules (consent, marketability, undeliverable) are skipped - Email::is_testing()
 *   bypasses all of that, same as a real send would hit them.
 * - The subject is prefixed with "[TEST]" and the message is always routed to
 *   the given `to` addresses, regardless of whose data resolves merge tags -
 *   so a saved contact's real address is never actually mailed.
 *
 * There is deliberately no "functional" test mode here (the type that enqueues
 * a real TEST_EMAIL event through the queue) - that path exercises the live
 * sending/tracking pipeline against a throwaway contact, which isn't a
 * meaningfully different signal for an ability caller than just sending the
 * real thing with groundhogg/send-email-template. This is for checking what
 * an email looks like and reads like, not functional verification.
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
			'description' => __( 'Send a design-only test of a saved Groundhogg email to one or more addresses - the same as wp-admin\'s "Send Test" button. Works on a draft. Does not go through the real event queue or tracking (no open/click tracking, no unsubscribe link, no consent/marketability checks), and the subject is prefixed with "[TEST]". Use this to check what an email looks like before sending it for real with groundhogg/send-email-template.', 'groundhogg' ),

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
						'description' => __( 'Addresses to actually mail the test to. The email is always routed here, regardless of whose data resolves merge tags via contact_id.', 'groundhogg' ),
					],
					'contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Resolve merge tags/replacement codes against this existing contact\'s data. Defaults to the current user\'s own name/email (not saved as a contact) when omitted.', 'groundhogg' ),
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
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The final subject line, after merge-field replacement, with the "[TEST] " prefix added.', 'groundhogg' ),
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
			'subject'     => $email->get_merged_subject_line(),
			'preview_url' => $email->get_preview_url(),
		];
	}
}
