<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use WP_Error;

/**
 * Removes a custom sender profile, the ones on Settings › Email › Sender Profiles. The default and per-user profiles
 * always exist, so only custom ones can go. An email set to send from a removed profile sends from the default
 * instead, see Email::get_from_profile().
 */
class Delete_Sender_Profile extends Ability {

	protected const NAME       = 'groundhogg/delete-sender-profile';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Delete Sender Profile', 'groundhogg' ),
			'description' => __( 'Remove a custom sender profile. Emails set to send from it send from the default sender instead. Only custom profiles ("custom-<id>" in groundhogg/list-sender-profiles) can be removed.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id' ],
				'properties'           => [
					'id' => [
						'type'        => 'string',
						'pattern'     => '^custom-[a-zA-Z0-9]+$',
						'description' => __( 'The custom profile\'s id, e.g. "custom-1a2b3c4d".', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'deleted' => [ 'type' => 'boolean' ],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$key      = substr( (string) ( $input['id'] ?? '' ), strlen( 'custom-' ) );
		$profiles = Save_Sender_Profile::get_custom_profiles();

		$remaining = array_filter( $profiles, function ( $profile ) use ( $key ) {
			return ( $profile[2] ?? '' ) !== $key;
		} );

		if ( count( $remaining ) === count( $profiles ) ) {
			return new WP_Error( 'groundhogg_profile_not_found', __( 'There is no custom sender profile with that id. See groundhogg/list-sender-profiles.', 'groundhogg' ) );
		}

		Save_Sender_Profile::save_custom_profiles( $remaining );

		return [ 'deleted' => true ];
	}
}
