<?php

namespace Groundhogg\cli;

use Groundhogg\Extension_Upgrader;
use Groundhogg\License_Manager;
use function WP_CLI\Utils\make_progress_bar;

/**
 * Manage licenses
 *
 * ## EXAMPLES
 *
 *     # Activate a license.
 *     $ wp groundhogg-license install_activate 1234 a1b2c3d4e5f6g7
 *     Success: License activated!
 */
class License {

	/**
	 * Installs an addon and then activates the license for it
	 *
	 * ## OPTIONS
	 *
	 * <addon>
	 * : The ID of the addon
	 *
	 * [<license>]
	 * : The license, if no license is supplied, uses the master licenses
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-license install_activate 1234 a1b2c3d4e5f6g7
	 */
	function install_activate( $args ) {

		$plugin  = $args[0];
		$license = $args[1];

		$item_id = absint( $plugin );
		$license = trim( $license ?: get_option( 'gh_master_license' ) );

		if ( ! $license ) {
			return \WP_CLI::error( 'No valid license supplied.' );
		}

		$installed = Extension_Upgrader::remote_install( $item_id, $license );

		if ( ! $installed ) {
			return \WP_CLI::error( 'Could not remote install add-on.' );
		}

		if ( is_wp_error( $installed ) ) {
			return \WP_CLI::error( $installed );
		}

		$result = License_Manager::activate_license_quietly( $license, $item_id );

		if ( ! $result ){
			return \WP_CLI::error( 'Add-on installed but could not activate license.' );
		}

		if ( is_wp_error( $result ) ) {
			return \WP_CLI::error( $result );
		}

		return \WP_CLI::success( 'Add-on installed with license activated.' );
	}

}
