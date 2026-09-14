<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Email;
use WP_Error;

/**
 * Retrieves a single saved Groundhogg email by ID, always including its full
 * HTML body (and plain-text alternative) - unlike groundhogg/list-email-templates,
 * where those are large enough per row that they're opt-in via expand. Global
 * blocks (message_type = 'global_block') are fetchable here even though they're
 * excluded from list-email-templates, since a caller who already has the id
 * (e.g. from groundhogg/query-table) has a legitimate reason to inspect one.
 */
class Get_Email_Template extends Ability {

	protected const NAME       = 'groundhogg/get-email-template';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Email Template', 'groundhogg' ),
			'description' => __( 'Retrieve one saved Groundhogg email by ID, including its full HTML body and plain-text alternative. Find IDs with groundhogg/list-email-templates.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
				],
			],

			'output_schema' => Email_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		return Email_Schema::transform( $email, [ 'content', 'plain_text' ] );
	}
}
