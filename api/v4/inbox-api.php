<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Inbox_Client;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The settings UI's API for the inbox: its status, and the buttons to set it up, change it and turn it off.
 *
 * This just calls Inbox_Client and reports what it did, the actual work (talking to the relay, the pending secret
 * dance, ...) is all there. Everything here needs manage_options, the same as the settings page.
 *
 * @package Groundhogg\Api\V4
 */
class Inbox_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, '/inbox', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
			],
		] );

		foreach ( [ 'enable', 'update', 'rotate', 'disable', 'forget' ] as $action ) {
			register_rest_route( self::NAME_SPACE, "/inbox/$action", [
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, $action ],
					'permission_callback' => [ $this, 'permissions_callback' ],
				],
			] );
		}
	}

	public function permissions_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * The state the settings page needs to render itself
	 *
	 * @return \WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {
		return self::SUCCESS_RESPONSE( self::state() );
	}

	/**
	 * @return array
	 */
	protected static function state() {
		return [
			'provisioned'      => Inbox::is_provisioned(),
			'site_matches'     => Inbox::site_matches(),
			'active'           => Inbox::is_active(),
			'address'          => Inbox::pretty_address(),
			'reply_address'    => Inbox::reply_address(),
			'endpoint_current' => Inbox::endpoint_is_current(),
			'last_received'    => Inbox::last_received(),
			'reply_to_enabled' => (bool) apply_filters( 'groundhogg/message/reply_to_enabled', true, '' ),
			'has_license'      => (bool) Inbox_Client::license_key(),
		];
	}

	/**
	 * Wrap what Inbox_Client returned as a response the JS can render from, without it needing to know PHP's
	 * true|WP_Error convention.
	 *
	 * @param true|WP_Error $result
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	protected static function respond( $result ) {

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();

			return self::ERROR_CODE(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'reason' => $data['reason'] ?? '', 'retry_after' => $data['retry_after'] ?? 0 ],
				is_array( $data ) && ! empty( $data['status'] ) ? $data['status'] : 500
			);
		}

		return self::SUCCESS_RESPONSE( self::state() );
	}

	public function enable( WP_REST_Request $request ) {
		return self::respond( Inbox_Client::enable() );
	}

	public function update( WP_REST_Request $request ) {
		return self::respond( Inbox_Client::update() );
	}

	public function rotate( WP_REST_Request $request ) {
		return self::respond( Inbox_Client::update( true ) );
	}

	public function disable( WP_REST_Request $request ) {
		return self::respond( Inbox_Client::disable() );
	}

	public function forget( WP_REST_Request $request ) {
		Inbox_Client::forget();

		return self::SUCCESS_RESPONSE( self::state() );
	}
}
