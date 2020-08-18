<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
use function Groundhogg\get_url_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPGH_API_V3_EMAILS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class Bulk_Job_Api extends Base {

	public function register_routes() {

		$auth_callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/bulkjob/(?P<action>[A-z_]+)', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'process' ],
				'permission_callback' => $auth_callback,
			],
		] );
	}

	/**
	 * Get a list of broadcast.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function process( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'perform_bulk_actions' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$action = $request['action'];
		$action = sanitize_text_field( "groundhogg/bulk_job/{$action}/rest" );

		if ( ! has_action( $action ) ) {
			return self::ERROR_404( 'no_action', 'The requested action was not found.', [
				'action' => $action
			] );
		}

		$items_per_request = $request->get_param( 'items_per_request' );
		$items_offset      = $request->get_param( 'items_offset' );
		$context           = $request->get_param( 'context' );

		do_action( $action, $items_per_request, $items_offset, $context );

		return self::ERROR_403( 'invalid_action', 'Invalid bulk action provided.' );

	}

}