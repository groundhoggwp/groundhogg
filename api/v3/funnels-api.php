<?php

namespace Groundhogg\Api\V3;

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

class Funnels_Api extends Base {

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

	}

	/**
	 * Create a new step!
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
	public function read( WP_REST_Request $request ){

		$funnel_id = absint( $request->get_param( 'funnel_id' ) );

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ){
			return self::ERROR_404( 'error', 'Funnel not found.' );
		}

		return self::SUCCESS_RESPONSE( [ 'funnel' => $funnel->get_as_array() ] );
	}

	/**
	 * Update a funnel
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update( WP_REST_Request $request ){

		$funnel_id = absint( $request->get_param( 'funnel_id' ) );

		$args = $request->get_param( 'args' );
		$args = map_deep( $args, 'sanitize_text_field' );

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ){
			return self::ERROR_404( 'error', 'Funnel not found.' );
		}

		$funnel->update( $args );

		// Update the step order
		$steps = $request->get_param( 'steps' );

		if ( ! empty( $steps ) ){
			$ids = wp_list_pluck( $steps, 'ID' );

			// handle the re-ordering of the steps
			foreach ( $ids as $i => $id ){
				get_db( 'steps' )->update( $id, [ 'step_order' => $i+1 ] );
			}
		}

		return self::SUCCESS_RESPONSE( [ 'funnel' => $funnel->get_as_array() ] );
	}

	public function delete( WP_REST_Request $request ){
		return self::ERROR_403( 'error', 'endpoint not in service' );
	}


}