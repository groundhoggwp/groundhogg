<?php

namespace Groundhogg;

class Stats_Collection {

	static protected $api_url = 'https://www.groundhogg.io/wp-json/gh/v3/stats/';

	const ACTION = 'gh_do_stats_collection';

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		if ( $this->is_enabled() ) {
			add_action( 'admin_init', [ $this, 'init_cron' ] );
			add_action( 'gh_do_stats_collection', [ $this, 'send_stats' ] );
		}
	}

	/**
	 * Whether stats collection is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return apply_filters( 'groundhogg/stats_collection/is_enabled', is_option_enabled( 'gh_opted_in_stats_collection' ) );
	}

	/**
	 * Perform the request weekly, daily is too much
	 */
	public function init_cron() {
		if ( ! wp_next_scheduled( 'gh_do_stats_collection' ) ) {
			wp_schedule_event( time(), 'weekly', 'gh_do_stats_collection' );
		}
	}

	/**
	 * Optin to the stats tracker system
	 *
	 * @deprecated
	 */
	public function stats_tracking_optin() {
		return $this->optin( true );
	}

	/**
	 * Send the initial request to the GH server and get a response.
	 *
	 * @param bool $optin_to_marketing whether the user should be opted in.
	 *
	 * @return false|\WP_Error|Object
	 */
	public function optin( $optin_to_marketing = false ) {

		$site_key = $this->generate_site_key();

		update_option( 'gh_site_key', $site_key );

		$stats = [
			'site_key'           => $site_key,
			'site_email'         => base64_encode( wp_get_current_user()->user_email ),
			'display_name'       => base64_encode( wp_get_current_user()->display_name ),
			'optin_to_marketing' => $optin_to_marketing ? 'yes' : 'no',
			'is_v3'              => true,
		];

		$response = remote_post_json( 'https://www.groundhogg.io/wp-json/gh/stats/optin/', $stats );

		if ( is_wp_error( $response ) ) {

			if ( $response->get_error_code() === 'already_registered' ) {
				update_option( 'gh_opted_in_stats_collection', 1 );

				return true;
			}

			return $response;
		}

		$json = $response;

		update_option( 'gh_opted_in_stats_collection', 1 );

		return $json;
	}

	protected function generate_site_key() {
		return md5( str_replace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) ) );
	}

	/**
	 * Continue continuous tracking of the site.
	 * Include anonymous site key
	 */
	public function send_stats() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		global $wpdb;

		$events = get_db( 'events' )->get_table_name();
		$steps  = get_db( 'steps' )->get_table_name();
		$time   = time();

		$num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE e.time <= $time AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );
		$num_opens       = get_db( 'activity' )->count( array( 'end' => $time, 'activity_type' => 'email_opened' ) );
		$num_clicks      = get_db( 'activity' )->count( array(
			'end'           => $time,
			'activity_type' => 'email_link_click'
		) );

		$stats = [
			'site_key'    => get_option( 'gh_site_key', $this->generate_site_key() ),
			'system_info' => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'gh_version'  => GROUNDHOGG_VERSION,
				'site_lang'   => get_bloginfo( 'language' ),
			],
			'usages'      => [
				'contacts' => get_db( 'contacts' )->count(),
				'funnels'  => get_db( 'funnels' )->count(),
				'emails'   => get_db( 'emails' )->count(),
				'sent'     => $num_emails_sent,
				'opens'    => $num_opens,
				'clicks'   => $num_clicks,
			],
			'extensions'  => array_values( Extension::$extension_ids )
		];

		apply_filters( 'groundhogg/stats_collection/report', $stats );

		// TODO Change to stats.groundhogg.io
		$response = remote_post_json( self::$api_url, $stats );

		/* Success */
		if ( is_wp_error( $response ) ) {

			/* Optin if not already and optin enabled via settings... */
			if ( $response->get_error_code() === 'site_unregistered' ) {
				$this->optin( false );
			}
		}
	}

}
