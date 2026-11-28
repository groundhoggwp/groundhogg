<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use Groundhogg\Email_Strings;
use WP_Error;
use function Groundhogg\check_lock;
use function Groundhogg\get_edit_version;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Change an email's strings by key, the ones groundhogg/get-email-strings returns, like to translate a variation.
 * See Email_Strings.
 */
class Set_Email_Strings extends Ability {

	protected const NAME       = 'groundhogg/set-email-strings';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'edit_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Set Email Strings', 'groundhogg' ),
			'description' => __( 'Change an email\'s text by the keys groundhogg/get-email-strings returns, like to translate a variation into its language. Keep every placeholder like <1>…</1> or <2/> exactly once, around the words it belongs to, and keep merge tags like {first} as they are. Strings you leave out don\'t change; give a string its current text to mark it as translated when it\'s the same in both languages, like a name. All or nothing: an unknown key or a lost placeholder changes nothing.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'strings' ],
				'properties'           => [
					'email_id'         => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'strings'          => [
						'type'                 => 'object',
						'minProperties'        => 1,
						'additionalProperties' => [ 'type' => 'string' ],
						'description'          => __( 'key => new text, like {"subject": "Bienvenue", "text-cc143b": "Bonjour <1>{first}</1>,"}.', 'groundhogg' ),
					],
					'expected_version' => [
						'type'        => 'string',
						'description' => __( 'The version groundhogg/get-email-strings returned. If the email was changed since, nothing is changed.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id'           => [ 'type' => 'integer' ],
					'changed'            => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
					'version'            => [ 'type' => 'string' ],
					'left_to_translate'  => [
						'type'        => 'integer',
						'description' => __( 'For a variation: how many strings still need translating, including text the variation is missing.', 'groundhogg' ),
					],
					'not_translated'     => Get_Email_Strings::not_translated_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'edit_email', $email ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_email', __( 'You are not allowed to edit this email.', 'groundhogg' ) );
		}

		$locked_by = check_lock( $email );

		if ( $locked_by ) {
			return new WP_Error( 'groundhogg_email_locked', sprintf(
				/* translators: %s: the name of the user editing the email */
				__( '%s is editing this email in the email editor, so it can\'t be changed until they\'re done.', 'groundhogg' ),
				get_userdata( $locked_by )->display_name
			) );
		}

		if ( ! empty( $input['expected_version'] ) && get_edit_version( $email ) !== $input['expected_version'] ) {
			return new WP_Error( 'groundhogg_email_changed', __( 'The email was changed since it was read. Read it again with groundhogg/get-email-strings and retry.', 'groundhogg' ) );
		}

		$result = Email_Strings::set( $email, is_array( $input['strings'] ?? null ) ? $input['strings'] : (array) ( $input['strings'] ?? [] ) );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'groundhogg_' . $result->get_error_code(), $result->get_error_message(), $result->get_error_data() );
		}

		if ( ! empty( $result['changed'] ) ) {
			// Mirrors Emails_Api::do_object_updated_action(), like groundhogg/edit-email
			do_action( 'groundhogg/api/email/updated', $email );
		}

		$email = new Email( $email->get_id() );

		$output = [
			'email_id' => $email->get_id(),
			'changed'  => $result['changed'],
			'version'  => get_edit_version( $email ),
		];

		if ( $email->is_variation() ) {
			$output['left_to_translate'] = (int) Email_Strings::count_to_translate( $email );
		}

		$output['not_translated'] = Email_Strings::not_translated( $email );

		return $output;
	}
}
