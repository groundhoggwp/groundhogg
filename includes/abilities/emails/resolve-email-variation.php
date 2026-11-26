<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use WP_Error;
use function Groundhogg\get_contactdata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which version of an email a contact would be sent, and why: for answering "why did this contact get the English
 * one?" and for checking a variation's audience before making it ready. Changes nothing.
 */
class Resolve_Email_Variation extends Ability {

	protected const NAME       = 'groundhogg/resolve-email-variation';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Resolve Email Variation', 'groundhogg' ),
			'description' => __( 'Find out which version of an email a contact would be sent right now, the email itself or one of its variations, and why each variation was or wasn\'t chosen. Sends nothing. Use it to check a variation\'s audience, or to explain why a contact got a version.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'contact_id' ],
				'properties'           => [
					'email_id'   => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The email flows and broadcasts send, not one of its variations.', 'groundhogg' ),
					],
					'contact_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id'       => [
						'type'        => 'integer',
						'description' => __( 'The ID of the email the contact would be sent: the email\'s own ID, or a variation\'s.', 'groundhogg' ),
					],
					'is_variation'   => [ 'type' => 'boolean' ],
					'contact_locale' => [
						'type'        => 'string',
						'description' => __( 'The language the contact gets emails in.', 'groundhogg' ),
					],
					'variations'     => [
						'type'        => 'array',
						'description' => __( 'Each variation in the order they\'re checked, and why it was or wasn\'t chosen.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'id'      => [ 'type' => 'integer' ],
								'title'   => [ 'type' => 'string' ],
								'chosen'  => [ 'type' => 'boolean' ],
								'reason'  => [
									'type'        => 'string',
									'enum'        => [ 'chosen', 'not_ready', 'language', 'filters', 'not_checked', 'filtered' ],
									'description' => __( '"chosen": the contact gets it. "not_ready": it\'s a draft, so nobody gets it. "language": the contact doesn\'t speak one of its languages. "filters": the contact doesn\'t match its filters. "not_checked": a variation before it was chosen. "filtered": the contact is in its audience, but code on the site picked another version.', 'groundhogg' ),
								],
							],
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

		if ( $email->is_variation() ) {
			return new WP_Error( 'groundhogg_is_variation', sprintf(
				/* translators: %d: the ID of the email the variation belongs to */
				__( 'This is a variation. Resolve the email it\'s a variation of instead, email_id %d.', 'groundhogg' ),
				$email->get_parent_id()
			) );
		}

		$contact = get_contactdata( absint( $input['contact_id'] ) );

		if ( ! $contact || ! current_user_can( 'view_contact', $contact ) ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
		}

		// The same choice Email::send() makes, so this can't disagree with what's sent
		$sent    = Email_Variations::resolve( $email, $contact );
		$sent_id = $sent ? $sent->get_id() : $email->get_id();

		$found      = false;
		$variations = [];

		foreach ( $email->get_variations() as $variation ) {

			$chosen = $variation->get_id() === $sent_id;

			if ( $chosen ) {
				$reason = 'chosen';
			} else if ( $found ) {
				$reason = 'not_checked';
			} else if ( ! $variation->is_ready() ) {
				$reason = 'not_ready';
			} else {
				$audience = Email_Variations::get_audience( $variation );

				if ( ! empty( $audience['locales'] ) && ! in_array( $contact->get_locale(), $audience['locales'], true ) ) {
					$reason = 'language';
				} else if ( ! Email_Variations::matches( $variation, $contact ) ) {
					$reason = 'filters';
				} else {
					$reason = 'filtered';
				}
			}

			$found = $found || $chosen;

			$variations[] = [
				'id'     => $variation->get_id(),
				'title'  => $variation->get_title(),
				'chosen' => $chosen,
				'reason' => $reason,
			];
		}

		return [
			'email_id'       => $sent_id,
			'is_variation'   => (bool) $sent,
			'contact_locale' => $contact->get_locale(),
			'variations'     => $variations,
		];
	}
}
