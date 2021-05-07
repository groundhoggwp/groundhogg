<?php

namespace Groundhogg\Api\V4;

use WP_REST_Server;
use function Groundhogg\get_db;
use function Groundhogg\key_to_words;

class Fields_Api extends Base_Api {

	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/fields", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ]
			],
		] );
	}

	public function read(){
		$table = get_db( 'contactmeta' );

		global $wpdb;

		$keys = $wpdb->get_col(
			"SELECT DISTINCT meta_key FROM {$table->get_table_name()} ORDER BY meta_key ASC"
		);

		$fields = array_map( function ( $key ) {
			return [
				'id'    => $key,
				'label' => key_to_words( $key ),
				'value' => $key
			];
		}, $keys );

		/**
		 * Filter the json response for the meta key picker
		 *
		 * @param $response array[]
		 * @param $search   string
		 */
		$response = apply_filters( 'groundhogg/api/v4/fields/read', $fields );

		return self::SUCCESS_RESPONSE( [
			'items' => $response,
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
