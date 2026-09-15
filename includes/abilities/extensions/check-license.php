<?php

namespace Groundhogg\Abilities\Extensions;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\License_Schema;
use Groundhogg\License_Manager;

/**
 * Checks the status of one or all registered Groundhogg licenses against groundhogg.io's
 * licensing API (License_Manager::check_license()), refreshing the locally cached record either
 * way. Not marked READONLY since it does write the refreshed status back to the gh_licenses
 * option - it just never activates/deactivates anything.
 */
class Check_License extends Ability {

	protected const NAME       = 'groundhogg/check-license';
	protected const CATEGORY   = 'groundhogg-extensions';
	protected const CAPABILITY = 'manage_gh_licenses';

	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Check License', 'groundhogg' ),
			'description' => __( 'Check the status of a Groundhogg license, or every registered license if none is given. Refreshes the locally cached status either way. Use groundhogg/activate-license to register a new one.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'license_key' => [
						'type'        => 'string',
						'description' => __( 'Check just this license. Omit to check every license currently registered on this site.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'licenses' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => array_merge( License_Schema::get_schema()['properties'], [
								'error' => [
									'type'        => 'string',
									'description' => __( 'Present, and valid false, when this license failed its check (expired, invalid, etc).', 'groundhogg' ),
								],
							] ),
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$keys = ! empty( $input['license_key'] )
			? [ sanitize_text_field( $input['license_key'] ) ]
			: array_keys( License_Manager::get_licenses() );

		$licenses = array_map( function ( $license_key ) {

			$result = License_Manager::check_license( $license_key );

			if ( is_wp_error( $result ) ) {
				return [
					'license_key' => $license_key,
					'valid'       => false,
					'error'       => $result->get_error_message(),
				];
			}

			return License_Schema::transform( $license_key, $result );
		}, $keys );

		return [ 'licenses' => $licenses ];
	}
}
