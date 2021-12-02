<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Form\Form;
use Groundhogg\Plugin;
use Groundhogg\Step;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\get_db;

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

		register_rest_route( self::NAME_SPACE, '/forms', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'read_permissions_callback' ],
				'callback'            => [ $this, 'read' ],
			]
		] );

		register_rest_route( self::NAME_SPACE, '/forms/(?P<form>\d+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'read_permissions_callback' ],
				'callback'            => [ $this, 'read_single' ],
			]
		] );
	}


	/**
	 * Add contacts because internal forms are used in the quick add forms
	 */
	public function read_permissions_callback() {
		return current_user_can( 'add_contacts' );
	}

	/**
	 * Takes a single parameter 'query' or empty to return a list of contacts.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$search = $request->get_param( 'search' );

		$where = [
			[ 'step_type', '=', 'form_fill' ],
		];

		if ( ! empty( $search ) ) {
			$where[] = [ 'step_title', 'RLIKE', sanitize_text_field( $search ) ];
		}

		$query = [
			'where'   => $where,
			'select'  => '*',
			'orderby' => 'ID',
			'order'   => 'DESC',
			'limit'   => 25,
		];

		$total = get_db( 'steps' )->count( $query );
		$items = get_db( 'steps' )->query( $query );

		$items = array_map( function ( $form ) {
			return new Form( [ 'id' => $form->ID ] );
		}, $items );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $total,
			'items'       => $items
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