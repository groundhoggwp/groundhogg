<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Funnels_Api extends Base_Object_Api {


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