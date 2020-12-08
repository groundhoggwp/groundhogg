<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emails_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, "/send/", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );
	}

	/**
	 * Send an email
	 *
	 * @param \WP_REST_Request $request
	 */
	public function send_email( \WP_REST_Request $request ) {

		$to = sanitize_email( $request->get_param( 'to' ) );
		$content = wp_kses_post();
	}

	public function get_db_table_name() {
		return 'emails';
	}

	public function send_permissions_callback() {
		return current_user_can( 'send_emails' );
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_emails' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'edit_emails' );
	}

	public function create_permissions_callback() {
		return current_user_can( 'add_emails' );
	}

	public function delete_permissions_callback() {
		return current_user_can( 'delete_emails' );
	}
}