<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * End an email's A/B test, so everyone in it gets the winning version from now on, or start it over.
 */
class Declare_Email_Winner extends Ability {

	protected const NAME       = 'groundhogg/declare-email-winner';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'edit_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Declare Email Winner', 'groundhogg' ),
			'description' => __( 'End an email\'s A/B test: from now on everyone in the test gets the winner, the email itself or one of its test versions. Check groundhogg/get-email-variation-report first, and only declare a winner it suggests unless the person you\'re working for picks one. winner_id 0 starts the test over, counting from now.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'winner_id' ],
				'properties'           => [
					'email_id'  => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The email with the test, not one of its versions.', 'groundhogg' ),
					],
					'winner_id' => [
						'type'        => 'integer',
						'minimum'     => 0,
						'description' => __( 'The winning version\'s email ID: the email\'s own ID, or a ready test version\'s. 0 starts the test over.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id' => [ 'type' => 'integer' ],
					'winner'   => [
						'type'        => 'integer',
						'description' => __( '0 when the test was started over.', 'groundhogg' ),
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

		if ( ! current_user_can( 'edit_email', $email ) ) {
			return new WP_Error( 'groundhogg_cannot_edit_email', __( 'You are not allowed to edit this email.', 'groundhogg' ) );
		}

		$winner = absint( $input['winner_id'] );

		if ( $winner === 0 ) {
			Email_Variations::restart_test( $email );
		} else {

			$declared = Email_Variations::declare_winner( $email, $winner );

			if ( is_wp_error( $declared ) ) {
				return new WP_Error( 'groundhogg_' . $declared->get_error_code(), $declared->get_error_message() );
			}
		}

		return [
			'email_id' => $email->get_id(),
			'winner'   => Email_Variations::get_winner_id( new Email( $email->get_id() ) ),
		];
	}
}
