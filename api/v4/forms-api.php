<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Forms_Api extends Base_Api {

	/**
	 * Register the relevant REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/forms/submit', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => function ( WP_REST_Request $request ) {
					return wp_verify_nonce( $request->get_param( '_ghnonce' ), 'groundhogg_frontend' );
				},
				'callback'            => [ $this, 'ajax_submit' ],
				'args'                => [
					'_ghnonce'  => [
						'description' => 'Need this!',
						'required'    => true
					],
					'form_data' => [
						'description' => 'Data from the form.',
						'required'    => true,
					]
				]
			]
		] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function ajax_submit( WP_REST_Request $request ) {
		do_action( 'groundhogg/api/v3/forms/submit', $request );

		$errors = Plugin::$instance->submission_handler->get_errors();

		return self::ERROR_401( 'invalid_request', 'Invalid request.', [ 'errors' => $errors ] );
	}
}