<?php

namespace Groundhogg\Abilities\Extensions;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\License_Schema;
use Groundhogg\License_Manager;

/**
 * Activates a Groundhogg license key against groundhogg.io's licensing API
 * (License_Manager::activate_license()). Activating an already-active license on this same site
 * is safe to repeat - EDD's activate_license endpoint doesn't consume an extra activation for it.
 */
class Activate_License extends Ability {

	protected const NAME       = 'groundhogg/activate-license';
	protected const CATEGORY   = 'groundhogg-extensions';
	protected const CAPABILITY = 'manage_gh_licenses';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Activate License', 'groundhogg' ),
			'description' => __( 'Activate a Groundhogg license key for this site. Use groundhogg/check-license to verify status later, and groundhogg/install-extension to install what the license grants access to.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'license_key' ],
				'properties'           => [
					'license_key' => [
						'type'        => 'string',
						'description' => __( 'The license key, as found on the groundhogg.io account/purchase page.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => License_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		$license_key = sanitize_text_field( $input['license_key'] );

		$result = License_Manager::activate_license( $license_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return License_Schema::transform( $license_key, $result );
	}
}
