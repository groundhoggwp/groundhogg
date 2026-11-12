<?php

namespace Groundhogg\Abilities\Setup;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\install_gh_cron_file;
use function Groundhogg\is_event_queue_processing;
use function Groundhogg\is_option_enabled;

/**
 * Does the parts of the cron setup that don't need the server: installs gh-cron.php in the WordPress root (what
 * Tools › Cron Setup's "Install the special cron file" does), and turns WordPress cron's page-load runs off or back on.
 *
 * Turning page-load cron off is guarded. WordPress cron runs background tasks (imports, scheduling broadcasts) and
 * WordPress's own jobs, so once it's off they only run if a real cron job calls wp-cron.php. That can't be verified
 * up front - wp_cron_last_ping is also updated by page-load runs - so this only allows it once gh-cron.php is
 * being called every minute, which shows the person has a cron job set up, and get-cron-status reports it if
 * WordPress cron stops running afterwards. gh_disable_wp_cron isn't in the settings registry for the same reason.
 */
class Set_Up_Cron extends Ability {

	protected const NAME       = 'groundhogg/set-up-cron';
	protected const CATEGORY   = 'groundhogg-setup';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Set Up Cron', 'groundhogg' ),
			'description' => __( 'Install the cron file that runs automation (gh-cron.php, in the WordPress root), and optionally switch WordPress cron between running on page loads and running from a cron job. A person still has to create the cron job on the server or with the host - see groundhogg/get-cron-status for exactly what it should call, and to check it\'s working.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'install_file'   => [
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Install gh-cron.php if it isn\'t there already.', 'groundhogg' ),
					],
					'wordpress_cron' => [
						'type'        => 'string',
						'enum'        => [ 'page_loads', 'cron_job' ],
						'description' => __( 'Leave out to keep it as it is. "cron_job" stops WordPress cron running on page loads: only do it once the person confirms a cron job calls wp-cron.php every minute, or imports and broadcast scheduling stop. It\'s refused until gh-cron.php is being called every minute. "page_loads" turns it back on.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'file_installed'          => [ 'type' => 'boolean' ],
					'wordpress_cron'          => [
						'type' => 'string',
						'enum' => [ 'page_loads', 'cron_job' ],
					],
					'event_queue_cron_working' => [
						'type'        => 'boolean',
						'description' => __( 'Whether a cron job called gh-cron.php in the last 90 seconds.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		if ( ( $input['install_file'] ?? true ) && ! gh_cron_installed() && ! install_gh_cron_file() ) {
			return new WP_Error(
				'groundhogg_cron_file_not_installed',
				__( 'Could not write gh-cron.php to the WordPress root, usually because the folder isn\'t writable. A person can install it by hand from Tools › Cron Setup: download the file, upload it next to wp-config.php, and rename it to gh-cron.php.', 'groundhogg' )
			);
		}

		$mode = $input['wordpress_cron'] ?? '';

		// the constant comes from the option at init, so this request's change only shows in the option
		$in_config = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' );

		if ( $mode === 'cron_job' && ! $in_config && ! is_option_enabled( 'gh_disable_wp_cron' ) ) {

			if ( ! gh_cron_installed() || ! is_event_queue_processing() ) {
				return new WP_Error(
					'groundhogg_cron_not_running',
					__( 'WordPress cron can only stop running on page loads once a cron job is set up. gh-cron.php hasn\'t been called in the last 90 seconds - see groundhogg/get-cron-status.', 'groundhogg' )
				);
			}

			update_option( 'gh_disable_wp_cron', true );
		}

		if ( $mode === 'page_loads' ) {

			if ( $in_config ) {
				return new WP_Error(
					'groundhogg_cron_disabled_in_config',
					__( 'WordPress cron is turned off in wp-config.php (DISABLE_WP_CRON), which only a person with access to the files can change.', 'groundhogg' )
				);
			}

			delete_option( 'gh_disable_wp_cron' );
		}

		return [
			'file_installed'           => gh_cron_installed(),
			'wordpress_cron'           => $in_config || is_option_enabled( 'gh_disable_wp_cron' ) ? 'cron_job' : 'page_loads',
			'event_queue_cron_working' => gh_cron_installed() && is_event_queue_processing(),
		];
	}
}
