<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Api\Api_Loader;

class Activity_Api extends Base_Object_Api{

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'activity';
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {

		$request = Api_Loader::get_request();

		// from contact screen
		$contact_id = $request->get_param( 'contact_id' );
		if ( $contact_id && current_user_can('view_contact', $contact_id ) ){
			return true;
		}

		return current_user_can( 'view_activity' );
	}


	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_activity' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_activity' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_activity' );
	}
}
