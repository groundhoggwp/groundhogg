<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Funnel;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Funnels_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, "/funnels/(?P<ID>\d+)/duplicate", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'duplicate' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
		] );
	}

	/**
	 * Duplicate the funnel
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function duplicate( \WP_REST_Request $request ){

		$ID = absint( $request->get_param( 'ID' ) );
		$funnel = new Funnel( $ID );

		if ( ! $funnel->exists() ){
			return self::ERROR_404();
		}

		$new_funnel = new Funnel();

		$new_funnel->import( $funnel->export() );

		return self::SUCCESS_RESPONSE( [
			'item' => $new_funnel
		] );
	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'funnels';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_funnels' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_funnels' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_funnels' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_funnels' );
	}
}