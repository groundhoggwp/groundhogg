<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Change who gets a variation of an email. Its content, subject and status are changed like any email's, with
 * groundhogg/edit-email and groundhogg/update-email-template.
 */
class Update_Email_Variation extends Ability {

	protected const NAME       = 'groundhogg/update-email-variation';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'edit_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Email Variation', 'groundhogg' ),
			'description' => __( 'Change who gets a variation of an email: its audience, its priority or the language it\'s written in. Only the fields you pass change. Change its content, subject and status (draft or ready) with groundhogg/edit-email and groundhogg/update-email-template, like any email.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'variation_id' ],
				'properties'           => [
					'variation_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The variation\'s email ID, from the `variations` of groundhogg/get-email-template.', 'groundhogg' ),
					],
					'audience'     => Create_Email_Variation::audience_input_schema(),
					'locale'       => [
						'type'        => 'string',
						'description' => __( 'The language the variation is written in, as a WordPress locale. An empty string clears it.', 'groundhogg' ),
					],
					'priority'     => [
						'type'        => 'integer',
						'description' => __( 'For a contact in the audience of more than one variation: the lowest goes first, then the oldest.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'variation' => Email_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$variation = new Email( absint( $input['variation_id'] ) );

		if ( ! $variation->exists() || ! $variation->is_variation() ) {
			return new WP_Error( 'groundhogg_variation_not_found', __( 'No variation with that ID. groundhogg/get-email-template lists an email\'s variations.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'edit_email', $variation ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_email', __( 'You are not allowed to edit this email.', 'groundhogg' ) );
		}

		$args = [];

		if ( array_key_exists( 'audience', $input ) ) {

			$audience = Create_Email_Variation::audience_from_input( is_array( $input['audience'] ) ? $input['audience'] : [] );

			if ( is_wp_error( $audience ) ) {
				return $audience;
			}

			$args['audience'] = $audience;
		}

		foreach ( [ 'locale', 'priority' ] as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$args[ $key ] = $input[ $key ];
			}
		}

		if ( empty( $args ) ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'Nothing to change was given. Pass audience, locale or priority.', 'groundhogg' ) );
		}

		$updated = Email_Variations::update( $variation, $args );

		if ( is_wp_error( $updated ) ) {
			return new WP_Error( 'groundhogg_' . $updated->get_error_code(), $updated->get_error_message() );
		}

		return [
			'variation' => Email_Schema::transform( new Email( $variation->get_id() ), [ 'version' ] ),
		];
	}
}
