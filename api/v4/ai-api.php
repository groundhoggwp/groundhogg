<?php

namespace Groundhogg\Api\V4;

use Groundhogg\AI\AI;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI on the site's own key, see Groundhogg\AI\AI: what's available, and doing a job by name. The browser never sends a
 * prompt, only a job's name and its input.
 */
class AI_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, '/ai', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/ai/(?P<job>[a-z0-9-]+)', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'run' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
			],
		] );
	}

	public function permissions_callback() {
		return current_user_can( AI::CAP );
	}

	public function read() {
		return self::SUCCESS_RESPONSE( AI::client_data() );
	}

	public function run( WP_REST_Request $request ) {

		$input = $request->get_json_params();

		if ( ! is_array( $input ) ) {
			$input = $request->get_body_params();
		}

		$result = AI::run( (string) $request->get_param( 'job' ), is_array( $input ) ? $input : [] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return self::SUCCESS_RESPONSE( $result );
	}
}
