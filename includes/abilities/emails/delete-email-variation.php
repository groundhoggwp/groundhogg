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
 * Delete a variation of an email, so its audience gets the email itself again. What was already sent of it stays in the
 * email's reports.
 */
class Delete_Email_Variation extends Ability {

	protected const NAME       = 'groundhogg/delete-email-variation';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'delete_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Delete Email Variation', 'groundhogg' ),
			'description' => __( 'Permanently delete a variation of an email, so the contacts in its audience get the email itself again. It can\'t be undone. To stop sending it but keep it, set its status to "draft" with groundhogg/update-email-template instead.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'variation_id' ],
				'properties'           => [
					'variation_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The variation\'s email ID, from the `variations` of groundhogg/get-email-template. Only a variation can be deleted with this.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'deleted'      => [ 'type' => 'boolean' ],
					'variation_of' => [
						'type'        => 'integer',
						'description' => __( 'The email it was a variation of.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$variation = new Email( absint( $input['variation_id'] ) );

		if ( ! $variation->exists() || ! $variation->is_variation() ) {
			return new WP_Error( 'groundhogg_variation_not_found', __( 'No variation with that ID. groundhogg/get-email-template lists an email\'s variations.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'delete_email', $variation ) ) {
			return new WP_Error( 'groundhogg_cannot_delete_email', __( 'You are not allowed to delete this email.', 'groundhogg' ) );
		}

		$parent_id = $variation->get_parent_id();
		$deleted   = (bool) $variation->delete();

		Email_Variations::flush();

		return [
			'deleted'      => $deleted,
			'variation_of' => $parent_id,
		];
	}
}
