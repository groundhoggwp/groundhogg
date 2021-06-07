<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Funnel;
use Groundhogg\Step;
use WP_REST_Server;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\map_func_to_attr;
use function Groundhogg\sanitize_object_meta;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Funnels_Api extends Base_Object_Api {

	/**
	 * register the commit route
	 *
	 * @return mixed|void
	 */
	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/commit", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'commit' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Commit the funnel
	 *
	 * @param \WP_REST_Request $request
	 */
	public function commit( \WP_REST_Request $request ) {

		$funnel = new Funnel( $request->get_param( $this->get_primary_key() ) );

		$funnel->update_meta( $request->get_json_params() );

		// If the commit was successful, meaning no errors, return he updated funnel
		if ( $funnel->commit() ) {

			return self::SUCCESS_RESPONSE( [
				'item' => $funnel
			] );

		} // If the commit failed, return all the errors
		else {

			return self::ERROR_400( 'error', 'Unable to commit changes.', [
				'errors' => $funnel->get_errors(),
				'item'   => $funnel
			] );
		}

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
		return current_user_can( 'export_funnels' );
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
