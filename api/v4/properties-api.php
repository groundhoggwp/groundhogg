<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Properties;
use WP_REST_Server;
use function Groundhogg\get_db;
use function Groundhogg\key_to_words;

class Properties_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/properties", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
		] );
	}

	public function read(){
		return self::SUCCESS_RESPONSE( [
			'items' => Properties::instance()->get_fields(),
		] );
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {
		return current_user_can( 'edit_contacts' );
	}
}
