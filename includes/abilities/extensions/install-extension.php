<?php

namespace Groundhogg\Abilities\Extensions;

use Groundhogg\Abilities\Ability;
use Groundhogg\Extension;
use Groundhogg\Extension_Upgrader;
use WP_Error;

/**
 * Remotely installs and activates one of Groundhogg's official add-on extensions
 * (Extension_Upgrader::remote_install()) - the same mechanism the `wp groundhogg-license
 * install_activate` WP-CLI command uses, the only other caller of it in this codebase.
 *
 * The download link is never caller-supplied - it only ever accepts a known item_id (validated
 * against Extension_Upgrader's own fixed registry of official extensions), and the actual
 * download link is looked up server-side from groundhogg.io's licensing API. There is no way to
 * point this at an arbitrary URL.
 *
 * Gated on the real WordPress `install_plugins` capability, not Groundhogg's own
 * `manage_gh_licenses` - this genuinely installs and activates new code on the site, which is a
 * meaningfully bigger grant than license-key management, so it needs the capability WordPress
 * itself requires for that on its own Plugins screen. remote_install() also activates the plugin
 * once installed, so `activate_plugins` is checked explicitly too (see can_execute()) -
 * Extension_Upgrader has no capability checks of its own for either.
 */
class Install_Extension extends Ability {

	protected const NAME       = 'groundhogg/install-extension';
	protected const CATEGORY   = 'groundhogg-extensions';
	protected const CAPABILITY = 'install_plugins';

	protected const IDEMPOTENT = true;

	public function can_execute( $input = null ) {
		return parent::can_execute( $input ) && current_user_can( 'activate_plugins' );
	}

	protected function get_args(): array {

		return [
			'label'       => __( 'Install Extension', 'groundhogg' ),
			'description' => __( 'Remotely install and activate one of Groundhogg\'s official add-on extensions. See groundhogg/list-extensions for valid item_ids. Requires a valid license granting access to the extension - either already registered on this site, or supplied here to activate it in the same call.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'item_id' ],
				'properties'           => [
					'item_id'     => [
						'type'        => 'integer',
						'description' => __( 'The extension\'s item_id - see groundhogg/list-extensions.', 'groundhogg' ),
					],
					'license_key' => [
						'type'        => 'string',
						'description' => __( 'A license key granting access to this item, if one isn\'t already registered on this site. Ignored if the extension is already installed.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'item_id'   => [ 'type' => 'integer' ],
					'installed' => [ 'type' => 'boolean' ],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$item_id = absint( $input['item_id'] );

		if ( ! in_array( $item_id, Extension_Upgrader::get_extension_ids(), true ) ) {
			return new WP_Error( 'groundhogg_invalid_extension', __( 'Unknown item_id - see groundhogg/list-extensions for valid ids.', 'groundhogg' ) );
		}

		$license_key = ! empty( $input['license_key'] ) ? sanitize_text_field( $input['license_key'] ) : '';

		$result = Extension_Upgrader::remote_install( $item_id, $license_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return [
			'item_id'   => $item_id,
			'installed' => Extension::installed( $item_id ),
		];
	}
}
