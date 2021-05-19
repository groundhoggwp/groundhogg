<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Steps_Api extends Base_Object_Api {

	public function settings( \WP_REST_Request $request ) {

		$step = new Step( $request->get_param( $this->get_primary_key() ) );

		if ( ! $step->exists() ){
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$step->html_v2();

	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'steps';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can('edit_funnels' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can('edit_funnels' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can('edit_funnels' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can('edit_funnels' );
	}
}