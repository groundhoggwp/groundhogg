<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\Utils\DateTimeHelper;
use WP_REST_Server;
use function Groundhogg\list_broadcasts_archive;
use function Groundhogg\list_campaigns_archive;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Campaigns_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();
		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/archive", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_archive' ],
				'permission_callback' => '__return_true'
			]
		] );
	}

	/**
	 * Fetch a list of any public campaigns
	 *
	 * @param  \WP_REST_Request  $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function read_archive( \WP_REST_Request $request ) {

		$list = list_campaigns_archive( [
			'page'     => absint( $request->get_param( 'page' ) ),
			'per_page' => absint( $request->get_param( 'per_page' ) ),
			'search'   => sanitize_text_field( $request->get_param( 'search' ) ),
		] );

		return self::SUCCESS_RESPONSE( $list );
	}

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
