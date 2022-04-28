<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Form\Form;
use Groundhogg\Form\Form_v2;
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

		register_rest_route( self::NAME_SPACE, '/forms', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'read_permissions_callback' ],
				'callback'            => [ $this, 'read' ],
			]
		] );

		register_rest_route( self::NAME_SPACE, '/forms/(?P<form>\d+)', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'submit' ],
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

		if ( $request->get_param( 'active' ) ) {
			$items = array_filter( $items, function ( $form ) {
				return $form->is_active();
			} );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => $total,
			'items'       => array_values( $items )
		] );
	}

	/**
	 * Handler to submit a specific form
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function submit( WP_REST_Request $request ) {
		$form_uuid = $request->get_param( 'form' );
		$form      = new Form_v2( [ 'id' => $form_uuid ] );

		if ( ! $form->exists() ) {
			return self::ERROR_404();
		}

		$form->submit();

		if ( $form->has_errors() ) {

			$error = new WP_Error( 'failed_to_submit', __( 'Your submission has errors.', 'groundhogg' ), [
				'status' => 400
			] );

			foreach ( $form->get_errors() as $_error ) {
				$error->add( $_error->get_error_code(), $_error->get_error_message() );
			}

			return $error;
		}

		return self::SUCCESS_RESPONSE();
	}
}
