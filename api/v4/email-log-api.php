<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Email_Log_Item;

class Email_Log_Api extends Base_Object_Api {

	public function get_db_table_name() {
		return 'email_log';
	}

	protected function get_object_class() {
		return Email_Log_Item::class;
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
