<?php

namespace Groundhogg\Abilities\Extensions;

use Groundhogg\Abilities\Ability;
use Groundhogg\Extension;
use Groundhogg\Extension_Upgrader;
use Groundhogg\License_Manager;

/**
 * Lists Groundhogg's official add-on extensions (Extension_Upgrader's own known-extension
 * registry), each with its install/license status on this site. Use the returned item_id with
 * groundhogg/install-extension.
 *
 * Deliberately local-only - no network call to groundhogg.io's store catalog for names/pricing,
 * just what this site already knows (the extension registry, its own installed-plugins list, and
 * its own registered licenses), so this is always fast and side-effect-free.
 */
class List_Extensions extends Ability {

	protected const NAME       = 'groundhogg/list-extensions';
	protected const CATEGORY   = 'groundhogg-extensions';
	protected const CAPABILITY = 'manage_gh_licenses';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Extensions', 'groundhogg' ),
			'description' => __( 'List Groundhogg\'s official add-on extensions and their install/license status on this site. Use the returned item_id with groundhogg/install-extension.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'installable_only' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Only include extensions this site\'s licenses actually grant access to and that aren\'t already installed.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'extensions' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'item_id'   => [ 'type' => 'integer' ],
								'slug'      => [ 'type' => 'string' ],
								'installed' => [
									'type'        => 'boolean',
									'description' => __( 'Installed, active, and loaded on this site.', 'groundhogg' ),
								],
								'licensed'  => [
									'type'        => 'boolean',
									'description' => __( 'Whether a currently valid license on this site grants access to it.', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$installable_only = ! empty( $input['installable_only'] );
		$installable       = Extension_Upgrader::get_installable_items();

		$paths = array_combine( Extension_Upgrader::get_extension_ids(), Extension_Upgrader::get_extension_paths() );

		$extensions = [];

		foreach ( $paths as $item_id => $path ) {

			$installed = Extension::installed( $item_id );

			if ( $installable_only && ( $installed || ! in_array( $item_id, $installable, true ) ) ) {
				continue;
			}

			$extensions[] = [
				'item_id'   => $item_id,
				'slug'      => basename( $path, '.php' ),
				'installed' => $installed,
				'licensed'  => License_Manager::is_licensed( $item_id ),
			];
		}

		return [ 'extensions' => $extensions ];
	}
}
