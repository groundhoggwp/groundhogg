<?php

namespace Groundhogg\Abilities\Setup;

use Groundhogg\Abilities\Ability;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\is_event_queue_processing;
use function Groundhogg\is_option_enabled;

/**
 * Where the cron setup stands, and exactly what the cron jobs should call - the same as Tools › Cron Setup, for an
 * agent. Two things run on cron:
 *
 * - the event queue (flow steps, broadcast emails), which gh-cron.php runs. It should be called every minute by a
 *   real cron job; until it is, WordPress cron runs the queue when people visit the site.
 * - WordPress cron itself, which runs background tasks like imports and scheduling broadcasts, plus WordPress's own
 *   jobs. By default it runs on page loads. It can be turned off once a real cron job calls wp-cron.php instead.
 *
 * Creating the cron job takes access to the server or host, so a person does that part. gh_cron_last_ping and
 * wp_cron_last_ping record when each last ran (see track_gh_cron_ping()), which is how this knows it's working.
 */
class Get_Cron_Status extends Ability {

	protected const NAME       = 'groundhogg/get-cron-status';
	protected const CATEGORY   = 'groundhogg-setup';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	/**
	 * WordPress cron runs background tasks, it's late after this long
	 */
	const WP_CRON_LATE_AFTER = 15 * MINUTE_IN_SECONDS;

	protected function get_args(): array {

		$job = [
			'type'       => 'object',
			'properties' => [
				'url'      => [
					'type'        => 'string',
					'description' => __( 'For a cron job that requests a URL, e.g. one set up with the host or a cron service.', 'groundhogg' ),
				],
				'command'  => [
					'type'        => 'string',
					'description' => __( 'For a cron job on the server itself. The path to php may differ on the server.', 'groundhogg' ),
				],
				'schedule' => [ 'type' => 'string' ],
			],
		];

		$last_run = [
			'last_run'               => [
				'type'        => [ 'string', 'null' ],
				'description' => __( 'When it last ran, in UTC, or null if never.', 'groundhogg' ),
			],
			'seconds_since_last_run' => [ 'type' => [ 'integer', 'null' ] ],
		];

		return [
			'label'       => __( 'Get Cron Status', 'groundhogg' ),
			'description' => __( 'Check whether the cron jobs that run automation are set up and working, and get exactly what a person needs to create them: the URL or command to run every minute. Flow steps and broadcast emails run late or not at all without them. Use groundhogg/set-up-cron to install the cron file.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'event_queue_cron' => [
						'type'        => 'object',
						'description' => __( 'gh-cron.php, which runs flow steps and broadcast emails.', 'groundhogg' ),
						'properties'  => array_merge( [
							'installed' => [ 'type' => 'boolean' ],
							'working'   => [
								'type'        => 'boolean',
								'description' => __( 'Whether a cron job called it in the last 90 seconds.', 'groundhogg' ),
							],
							'job'       => $job,
						], $last_run ),
					],
					'wordpress_cron'   => [
						'type'        => 'object',
						'description' => __( 'WordPress cron, which runs background tasks like imports and scheduling broadcasts.', 'groundhogg' ),
						'properties'  => array_merge( [
							'runs_on_page_loads' => [
								'type'        => 'boolean',
								'description' => __( 'False once it has been turned off, so it only runs when a cron job calls wp-cron.php.', 'groundhogg' ),
							],
							'turned_off_by'      => [
								'type'        => [ 'string', 'null' ],
								'enum'        => [ 'wp-config', 'setting', null ],
								'description' => __( '"wp-config" can only be changed in wp-config.php, by a person.', 'groundhogg' ),
							],
							'on_time'            => [ 'type' => 'boolean' ],
							'job'                => $job,
						], $last_run ),
					],
					'next_steps'       => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
				],
			],
		];
	}

	/**
	 * When an option recorded something last ran
	 *
	 * @param string $option
	 *
	 * @return array
	 */
	protected function last_run( string $option ) {

		$time = absint( get_option( $option ) );

		return [
			'last_run'               => $time ? gmdate( 'Y-m-d H:i:s', $time ) : null,
			'seconds_since_last_run' => $time ? time() - $time : null,
		];
	}

	/**
	 * What a cron job for a file in the WordPress root runs
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	protected function job( string $file ) {
		return [
			'url'      => site_url( $file ),
			'command'  => sprintf( 'php "%s" >/dev/null 2>&1', wp_normalize_path( ABSPATH . $file ) ),
			'schedule' => '* * * * *',
		];
	}

	public function __invoke( $input ) {

		$installed = gh_cron_installed();
		$working   = $installed && is_event_queue_processing();

		$queue_cron = array_merge( [
			'installed' => $installed,
			'working'   => $working,
			'job'       => $this->job( 'gh-cron.php' ),
		], $this->last_run( 'gh_cron_last_ping' ) );

		$in_config     = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON && ! defined( 'GH_SHOW_DISABLE_WP_CRON_OPTION' );
		$by_setting    = is_option_enabled( 'gh_disable_wp_cron' );
		$wp_last_run   = $this->last_run( 'wp_cron_last_ping' );
		$wp_on_time    = $wp_last_run['seconds_since_last_run'] !== null && $wp_last_run['seconds_since_last_run'] < self::WP_CRON_LATE_AFTER;
		$on_page_loads = ! $in_config && ! $by_setting;

		$wp_cron = array_merge( [
			'runs_on_page_loads' => $on_page_loads,
			'turned_off_by'      => $in_config ? 'wp-config' : ( $by_setting ? 'setting' : null ),
			'on_time'            => $wp_on_time,
			'job'                => $this->job( 'wp-cron.php' ),
		], $wp_last_run );

		$steps = [];

		if ( ! $installed ) {
			$steps[] = __( 'Install the cron file with groundhogg/set-up-cron.', 'groundhogg' );
		}

		if ( ! $working ) {
			$steps[] = sprintf(
				/* translators: 1: a URL, 2: a shell command */
				__( 'Ask a person with access to the server or host to create a cron job that runs every minute (* * * * *). It either requests %1$s, which works with the host\'s cron tool or a cron service, or runs %2$s on the server. Then check again: it\'s working once it has run in the last 90 seconds.', 'groundhogg' ),
				$queue_cron['job']['url'],
				$queue_cron['job']['command']
			);
		}

		if ( ! $on_page_loads && ! $wp_on_time ) {
			$steps[] = $by_setting
				? __( 'WordPress cron is turned off and hasn\'t run in the last 15 minutes, so background tasks like imports and scheduling broadcasts have stopped. A person should check the cron job that calls wp-cron.php, or turn WordPress cron back on with groundhogg/set-up-cron (wordpress_cron "page_loads").', 'groundhogg' )
				: __( 'WordPress cron is turned off in wp-config.php and hasn\'t run in the last 15 minutes, so background tasks like imports and scheduling broadcasts have stopped. A person should check the cron job that calls wp-cron.php.', 'groundhogg' );
		} else if ( $working && $on_page_loads ) {
			$steps[] = sprintf(
				/* translators: %s: a URL */
				__( 'Optional: WordPress cron runs on page loads, which slows the site down a little. A person can add a second cron job that requests %s every minute, then you can turn page-load cron off with groundhogg/set-up-cron (wordpress_cron "cron_job").', 'groundhogg' ),
				$wp_cron['job']['url']
			);
		}

		return [
			'event_queue_cron' => $queue_cron,
			'wordpress_cron'   => $wp_cron,
			'next_steps'       => $steps,
		];
	}
}
