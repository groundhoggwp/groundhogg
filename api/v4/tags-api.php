<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tags_Api extends Base_Object_Api {


	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'tags';
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_tags' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_tags' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_tags' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_tags' );
	}
}