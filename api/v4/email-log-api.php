<?php

namespace Groundhogg\Api\V4;

class Email_Log_Api extends Base_Object_Api {

	public function get_db_table_name() {
		return 'email_log';
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_logs' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'administrator' );
	}

	public function create_permissions_callback() {
		return current_user_can( 'administrator' );
	}

	public function delete_permissions_callback() {
		return current_user_can( 'delete_logs' );
	}
}
