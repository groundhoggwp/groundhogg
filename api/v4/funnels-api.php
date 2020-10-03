<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Funnel;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\get_db;

class Funnels_Api extends Base_Api {

	public function register_routes() {

		$callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/funnels', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'create' ],
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'read' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'update' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'delete' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/funnels/activate', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'permission_callback' => $callback,
				'callback'            => [ $this, 'activate' ],
			]
		] );
	}

	/**
	 * Create a new funnel!
	 *
	 * Requires:
	 *  - step_type
	 *  - funnel_id
	 *  - settings (optional)
	 *  - order
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function create( WP_REST_Request $request ) {
		return self::ERROR_403( 'error', 'endpoint not in service' );
	}

	/**
	 * Get JSON export of the funnel
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		if ( ! current_user_can( 'edit_funnels' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$args = array(
			'where'   => $request->get_param( 'where' ) ?: [],
			'limit'   => absint( $request->get_param( 'limit' ) ) ?: 25,
			'offset'  => absint( $request->get_param( 'offset' ) ) ?: 0,
			'order'   => sanitize_text_field( $request->get_param( 'offset' ) ) ?: 'DESC',
			'orderby' => sanitize_text_field( $request->get_param( 'orderby' ) ) ?: 'ID',
			'select'  => sanitize_text_field( $request->get_param( 'select' ) ) ?: '*',
			'search'  => sanitize_text_field( $request->get_param( 'search' ) ),
		);

		$total = get_db( 'funnels' )->count( $args );
		$items = get_db( 'funnels' )->query( $args );
		$items = array_map( function ( $item ) {
			return new Funnel( $item->ID );
		}, $items );

		return self::SUCCESS_RESPONSE( [ 'items' => $items, 'total_items' => $total ] );
	}

	/**
	 * Update a funnel
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'funnel_id' ) );

		$args = $request->get_param( 'data' );
		$args = map_deep( $args, 'sanitize_text_field' );

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return self::ERROR_404( 'error', 'Funnel not found.' );
		}

		$args['last_updated'] = current_time( 'mysql' );

		$funnel->update( $args );

		// Update the step order
		$steps = $request->get_param( 'steps' );

		if ( ! empty( $steps ) ) {
			$ids = wp_list_pluck( $steps, 'ID' );

			// handle the re-ordering of the steps
			foreach ( $ids as $i => $id ) {
				get_db( 'steps' )->update( $id, [ 'step_order' => $i + 1 ] );
			}
		}

		return self::SUCCESS_RESPONSE( [ 'funnel' => $funnel->get_as_array() ] );
	}

	public function delete( WP_REST_Request $request ) {
		return self::ERROR_403( 'error', 'endpoint not in service' );
	}

	/**
	 * Activate a funnel
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function activate( WP_REST_Request $request ) {

		$funnel_id = absint( $request->get_param( 'funnel_id' ) );

//		$args = $request->get_param( 'data' );

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return self::ERROR_404( 'error', 'Funnel not found.' );
		}

		if ( $funnel->isValidFunnel() ) {

			$funnel->update( [
				'status' => 'active'
			] );

			return self::SUCCESS_RESPONSE( [ 'funnel' => $funnel->get_as_array() ] );
		}

		return rest_ensure_response( [
			'errors' => $funnel->get_errors(),
			'funnel' => $funnel->get_as_array(),
		] );
	}


}