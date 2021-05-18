<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Tag;
use WP_REST_Server;
use function Groundhogg\validate_tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tags_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();

		register_rest_route( self::NAME_SPACE, "/{$route}/validate", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'validate' ],
				'permission_callback' => [ $this, 'create_permissions_callback' ]
			],
		] );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function validate( \WP_REST_Request $request ){

		$tags = validate_tags( $request->get_json_params() );

		return self::SUCCESS_RESPONSE( [
			'items' => array_map( function ( $tag ){
				return new Tag( $tag );
			}, $tags )
		] );
	}

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