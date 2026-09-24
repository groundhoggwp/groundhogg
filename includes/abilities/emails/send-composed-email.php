<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Message;
use Groundhogg\Email_Logger;
use WP_Error;
use function Groundhogg\do_replacements;
use function Groundhogg\email_kses;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\get_sender_profiles;
use function Groundhogg\is_sending;
use function Groundhogg\redact;

/**
 * Sends a one-off, free-form ("composed") email - subject and HTML body supplied
 * directly, not a saved Groundhogg email. Mirrors the POST gh/v4/emails/send
 * route (Emails_Api::send_email()): the body is run through email_kses() and
 * merge-field replacements (resolved against the first `to` address that belongs
 * to a contact), the site's custom footer text is appended, the message is sent
 * immediately through the configured sending service for the given message
 * `type`, and a `composed_email_sent` activity is recorded against every
 * recipient that is a known contact.
 *
 * Like the admin contact-record composer, this does NOT check marketing consent
 * or marketability - it is meant for 1:1 and transactional-style messages. Use
 * groundhogg/send-email-template (or a broadcast) to send a saved email through
 * the normal event queue with all of its sending rules applied.
 *
 * On top of the `send_emails` capability, every recipient (to/cc/bcc) that
 * belongs to an existing contact must be one the caller can `view_contact` -
 * otherwise `send_emails` alone could be used to reach contacts outside the
 * caller's team. Raw addresses with no matching contact are left alone. If any
 * recipient fails this check nothing is sent (the message goes out in a single
 * call). Emails_Api::send_email() enforces the same.
 *
 * DESTRUCTIVE is true: sending mail is an irreversible outward-facing side
 * effect, so hosts that gate destructive abilities behind confirmation should
 * do so here even though no data is destroyed.
 */
class Send_Composed_Email extends Ability {

	protected const NAME       = 'groundhogg/send-composed-email';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'send_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Send Composed Email', 'groundhogg' ),
			'description' => __( 'Send a one-off email with a subject and HTML body supplied directly (not a saved Groundhogg email). Sends immediately to the given to/cc/bcc addresses. Merge fields like {first_name} are resolved against the first "to" address that belongs to a contact. Bypasses marketing-consent checks - intended for 1:1 and transactional messages, not bulk sending.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'subject', 'content' ],
				'properties'           => [
					'to' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string', 'format' => 'email' ],
						'description' => __( 'Primary recipient email addresses. At least one of to, cc, or bcc must be provided.', 'groundhogg' ),
					],
					'cc' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string', 'format' => 'email' ],
						'description' => __( 'CC email addresses.', 'groundhogg' ),
					],
					'bcc' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string', 'format' => 'email' ],
						'description' => __( 'BCC email addresses.', 'groundhogg' ),
					],
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The subject line. Supports merge-field replacements.', 'groundhogg' ),
					],
					'content' => [
						'type'        => 'string',
						'description' => __( 'The email body as HTML. Sanitized with the same allowed-HTML rules as the email editor, and supports merge-field replacements. The site\'s custom email footer text is appended automatically.', 'groundhogg' ),
					],
					'from_email' => [
						'type'        => 'string',
						'format'      => 'email',
						'description' => __( 'Sender address. Defaults to the site\'s default "from" email. If unsure what is safe to send as, call groundhogg/list-sender-profiles for the valid from name/email combinations.', 'groundhogg' ),
					],
					'from_name' => [
						'type'        => 'string',
						'description' => __( 'Sender name. Defaults to the site\'s default "from" name. See groundhogg/list-sender-profiles for valid from name/email combinations.', 'groundhogg' ),
					],
					'type' => [
						'type'        => 'string',
						'enum'        => [ 'wordpress', 'transactional', 'marketing' ],
						'default'     => 'wordpress',
						'description' => __( 'Which configured sending service to route through. Defaults to "wordpress".', 'groundhogg' ),
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
					'from' => [
						'type' => 'string',
					],
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The final subject line, after merge-field replacement and redaction of any sensitive values.', 'groundhogg' ),
					],
					'recipients' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Every address the message was sent to (to + cc + bcc, de-duplicated).', 'groundhogg' ),
					],
					'log_id' => [
						'type'        => 'integer',
						'description' => __( 'The email log entry ID. Only present when email logging is enabled.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		is_sending( true );

		$to  = array_values( array_filter( array_map( 'sanitize_email', $input['to'] ?? [] ) ) );
		$cc  = array_values( array_filter( array_map( 'sanitize_email', $input['cc'] ?? [] ) ) );
		$bcc = array_values( array_filter( array_map( 'sanitize_email', $input['bcc'] ?? [] ) ) );

		if ( empty( $to ) && empty( $cc ) && empty( $bcc ) ) {
			return new WP_Error( 'groundhogg_no_recipients', __( 'At least one to, cc, or bcc address is required.', 'groundhogg' ) );
		}

		// send_emails gates the ability; on top of that the caller must be able to
		// view any recipient that is a known contact. All-or-nothing: this is one
		// send_type() call, so a single forbidden recipient blocks the whole send.
		foreach ( array_unique( array_merge( $to, $cc, $bcc ) ) as $address ) {

			$recipient_contact = get_contactdata( $address );

			if ( $recipient_contact && $recipient_contact->exists() && ! current_user_can( 'view_contact', $recipient_contact ) ) {
				return new WP_Error(
					'groundhogg_cannot_send_to_recipient',
					__( 'You do not have permission to send email to one or more of the specified recipients.', 'groundhogg' )
				);
			}
		}

		$from_email = get_default_from_email();
		$from_name  = get_default_from_name();

		$profile_id = ! empty( $input['sender_profile'] ) ? sanitize_text_field( $input['sender_profile'] ) : '';
		$profile    = false;

		if ( $profile_id ) {

			$profiles = get_sender_profiles();

			if ( ! isset( $profiles[ $profile_id ] ) ) {
				return new WP_Error(
					'groundhogg_invalid_sender_profile',
					__( 'Not a valid sender profile. See groundhogg/list-sender-profiles.', 'groundhogg' )
				);
			}

			$profile    = $profiles[ $profile_id ];
			$from_email = $profile['from_email'];
			$from_name  = $profile['from_name'];
		}

		if ( ! empty( $input['from_email'] ) ) {
			$from_email = sanitize_email( $input['from_email'] );
		}

		if ( ! empty( $input['from_name'] ) ) {
			$from_name = sanitize_text_field( $input['from_name'] );
		}

		// Merge-field replacement is resolved against the first `to` address, if
		// it belongs to a contact - same as Emails_Api::send_email().
		$contact = ! empty( $to ) ? get_contactdata( $to[0] ) : false;

		// Profiles such as "owner" contain merge tags; resolve them against the contact.
		if ( $profile ) {

			if ( $contact && $contact->exists() ) {
				$from_email = sanitize_email( do_replacements( $from_email, $contact ) );
				$from_name  = sanitize_text_field( do_replacements( $from_name, $contact ) );
			}

			if ( ! is_email( $from_email ) ) {
				return new WP_Error(
					'groundhogg_invalid_sender_profile',
					__( 'The sender profile could not be resolved to a valid email address. The "owner" profile requires the first "to" address to belong to a contact with an owner.', 'groundhogg' )
				);
			}
		}

		$content = $input['content'];

		if ( apply_filters( 'groundhogg/add_custom_footer_text_to_personal_emails', true ) ) {
			$content .= wpautop( get_option( 'gh_custom_email_footer_text' ) );
		}

		$content = email_kses( $content );
		$subject = sanitize_text_field( $input['subject'] );
		$type    = sanitize_text_field( $input['type'] ?? 'wordpress' );

		if ( $contact && $contact->exists() ) {
			$content = do_replacements( $content, $contact );
			$subject = do_replacements( $subject, $contact );
		}

		$headers = [
			'Content-Type: text/html',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		];

		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . implode( ',', $cc );
		}

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ',', $bcc );
		}

		// Emails_Api uses its Supports_Errors trait to catch wp_mail_failed;
		// abilities don't have that, so capture the error locally instead.
		$mail_error = null;
		$catch      = static function ( $wp_error ) use ( &$mail_error ) {
			$mail_error = $wp_error;
		};

		add_action( 'wp_mail_failed', $catch );

		// we assign the Message-ID ourselves, so that a reply can be matched to what we sent
		$message_id = Message::use_message_id();

		// replies go to the inbox, when there is one
		if ( $reply_to = Inbox::reply_to( $message_id ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$result = \Groundhogg_Email_Services::send_type( $type, $to, $subject, $content, $headers );

		Message::release_message_id();

		remove_action( 'wp_mail_failed', $catch );

		if ( is_wp_error( $mail_error ) ) {
			return $mail_error;
		}

		if ( ! $result ) {
			return new WP_Error( 'groundhogg_email_not_sent', __( 'The email could not be sent.', 'groundhogg' ) );
		}

		$response_subject = redact( $subject );

		$all_recipients = array_values( array_unique( array_merge( $to, $cc, $bcc ) ) );

		$log_id = Email_Logger::is_enabled() ? Email_Logger::get_last_log_id() : null;

		foreach ( $all_recipients as $recipient ) {

			$recipient_contact = get_contactdata( $recipient );

			if ( ! $recipient_contact ) {
				continue;
			}

			Message::record_composed_email( $recipient_contact, [
				'subject'      => $subject,
				'content'      => $content,
				'from_address' => $from_email,
				'user_id'      => get_current_user_id(),
				'email_log_id' => $log_id ?: 0,
				'message_id'   => $message_id,
			] );
		}

		$response = [
			'sent'       => true,
			'from'       => $from_email,
			'subject'    => $response_subject,
			'recipients' => $all_recipients,
		];

		if ( $log_id ) {
			$response['log_id'] = $log_id;
		}

		return $response;
	}
}
