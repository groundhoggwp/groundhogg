<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Classes\Page_Visit;

class Page_Visits_Api extends Base_Object_Api{

	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'page_visits';
	}

	protected function get_object_class() {
		return Page_Visit::class;
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'view_contacts' );
	}
}
