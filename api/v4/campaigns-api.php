<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Tag;
use WP_REST_Server;
use function Groundhogg\validate_tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Campaigns_Api extends Base_Object_Api {

	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'campaigns';
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'manage_campaigns' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'manage_campaigns' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'manage_campaigns' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'manage_campaigns' );
	}
}