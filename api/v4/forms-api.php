<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Form\Form;
use Groundhogg\Form\Form_v2;
use Groundhogg\Plugin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\after_form_submit_handler;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;

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
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'submit' ],
			]
		] );

		register_rest_route( self::NAME_SPACE, '/forms/(?P<form>\d+)/admin', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'read_permissions_callback' ],
				'callback'            => [ $this, 'admin_submit' ],
			]
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

		$contact = $form->submit();

		if ( $form->has_errors() ) {

			$error = new WP_Error( 'failed_to_submit', esc_html__( 'Your submission has errors.', 'groundhogg' ), [
				'status' => 400
			] );

			foreach ( $form->get_errors() as $_error ) {
				$error->add( uniqid(), $_error->get_error_message(), $_error->get_error_data() );
			}

			return $error;
		}

		after_form_submit_handler( $contact );

		if ( $form->is_ajax_submit() ) {
			return self::SUCCESS_RESPONSE( [
				'message' => $form->get_success_message()
			] );
		}

		return self::SUCCESS_RESPONSE( [
			'url' => $form->get_success_url()
		] );
	}

	/**
	 * Fetches the contact from the request based on the referer or explicit passed param
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return false|\Groundhogg\Contact|WP_Error
	 */
	protected static function get_contact_from_request( WP_REST_Request $request ) {

		if ( $request->has_param( 'contact' ) ) {
			return get_contactdata( $request->get_param( 'contact' ) );
		}

		if ( $request->has_param( 'email' ) ) {
			return get_contactdata( sanitize_email( $request->get_param( 'email' ) ) );
		}

		$referer = wp_get_referer();
		$params  = [];
		wp_parse_str( wp_parse_url( $referer, PHP_URL_QUERY ), $params );

		if ( get_array_var( $params, 'page' ) === 'gh_contacts' && isset_not_empty( $params, 'contact' ) ) {
			return get_contactdata( absint( $params['contact'] ) );
		}

		return parent::get_contact_from_request( $request );
	}

	/**
	 * Handler to submit a specific form
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function admin_submit( WP_REST_Request $request ) {

		$form_uuid   = $request->get_param( 'form' );
		$contact_ref = self::get_contact_from_request( $request );

		$form = new Form_v2( [
			'id'      => $form_uuid,
			'contact' => $contact_ref
		] );

		if ( ! $form->exists() ) {
			return self::ERROR_404();
		}

		$contact = $form->submit();

		if ( $form->has_errors() ) {

			$error = new WP_Error( 'failed_to_submit', esc_html__( 'Your submission has errors.', 'groundhogg' ), [
				'status' => 400
			] );

			foreach ( $form->get_errors() as $_error ) {
				$error->add( $_error->get_error_code(), $_error->get_error_message() );
			}

			return $error;
		}

		return self::SUCCESS_RESPONSE( [
			'contact' => $contact
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
			[ 'step_type', 'IN', [ 'form_fill', 'web_form' ] ],
			[ 'step_status', '=', 'active' ],
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

		$total       = get_db( 'steps' )->count( $query );
		$items       = get_db( 'steps' )->query( $query );
		$contact_ref = self::get_contact_from_request( $request );

		$items = array_map( function ( $form ) use ( $request, $contact_ref ) {

			switch ( $form->step_type ) {
				case 'web_form':
					return new Form_v2( [
						'id'      => $form->ID,
						'contact' => $contact_ref
					] );
				default:
				case 'form_fill':
					return new Form( [ 'id' => $form->ID ] );
			}
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
