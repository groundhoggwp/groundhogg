<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Telemetry {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'init_cron' ] );
		add_action( 'groundhogg/telemetry', [ $this, 'maybe_send_telemetry' ] );
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
		if ( ! wp_next_scheduled( 'groundhogg/telemetry' ) ) {
			wp_schedule_event( time(), 'weekly', 'groundhogg/telemetry' );
		}
	}

	/**
	 * Opt-in to the stats tracker system
	 *
	 * @deprecated
	 */
	public function stats_tracking_optin() {
		return $this->optin( true );
	}

	/**
	 * Send the initial request to the GH server and get a response.
	 *
	 * @param bool $subscribed whether the user should be opted in.
	 *
	 * @return false|\WP_Error|Object
	 */
	public function optin( $subscribed = false ) {

		update_option( 'gh_opted_in_stats_collection', [ 'on' ] );

		$telemetry_email = wp_get_current_user()->user_email;

		if ( ! is_email( $telemetry_email ) ) {
			return new \WP_Error( 'invalid_email', 'Invalid email address.' );
		}

		update_option( 'gh_telemetry_email', $telemetry_email );

		$request = [
			'email'       => $telemetry_email,
			'date'        => Ymd_His(),
			'subscribed'  => $subscribed ? 'yes' : 'no',
			'time_zone'   => get_user_timezone()->getName(),
			'system_info' => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'gh_version'  => GROUNDHOGG_VERSION,
				'site_lang'   => get_bloginfo( 'language' ),
				'time_zone'   => wp_timezone()->getName()
			]
		];

		return remote_post_json( 'https://www.groundhogg.io/wp-json/gh/v4/webhooks/1724-telemetry-optin?token=vNhKt6H', $request );
	}

	/**
	 * @return void
	 */
	public function maybe_send_telemetry() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		self::send_telemetry();
	}

	/**
	 * Continue continuous tracking of the site.
	 * Include anonymous site key
	 */
	public static function send_telemetry() {

		$date      = new \DateTime( 'now', wp_timezone() );
		$_7daysago = clone $date;
		$_7daysago->modify( '6 days ago 12:00 am' );

		$request = [
			'email'            => get_option( 'gh_telemetry_email' ) ?: get_bloginfo( 'admin_email' ),
			'date'             => Ymd_His(),
			'is_licensed'      => has_premium_features(),
			'master_license'   => get_option( 'gh_master_license' ),
			'is_white_labeled' => is_white_labeled(),
			'system_info'      => [
				'php_version' => PHP_VERSION,
				'wp_version'  => get_bloginfo( 'version' ),
				'gh_version'  => GROUNDHOGG_VERSION,
				'site_lang'   => get_bloginfo( 'language' ),
			],
			'usage'            => [
				'funnels'    => get_db( 'funnels' )->count( [ 'status' => 'active' ] ),
				'contacts'   => get_db( 'contacts' )->count( [ 'after' => $_7daysago->format( 'Y-m-d H:i:s' ) ] ),
				'broadcasts' => get_db( 'broadcasts' )->count( [ 'after' => $_7daysago->getTimestamp() ] )
			],
			'extensions'       => implode( ',', Extension::$extension_ids )
		];

		remote_post_json( 'https://www.groundhogg.io/wp-json/gh/v4/webhooks/1727-receive-telemetry?token=JVq8f3u', $request );
	}

}
