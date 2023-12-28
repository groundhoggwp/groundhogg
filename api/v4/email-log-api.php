<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Email_Log_Item;
use Groundhogg\Email_Logger;
use WP_REST_Request;

class Email_Log_Api extends Base_Object_Api {

	/**
	 * Nice error messages depending if email logging is enabled or not
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {
		$response = parent::read( $request );

		$items = $response->get_data()['items'];

		// If no items
		if ( empty( $items ) ) {
			if ( ! Email_Logger::is_enabled() ) {
				return new \WP_Error( 'not_enabled', 'Email logging is not currently enabled. Enable it to track detailed outgoing email information.' );
			}

			return new \WP_Error( 'not_found', 'The requested email log could not be found. It may have been deleted in accordance with your log retention settings.' );
		}

		return $response;
	}

	public function read_single( WP_REST_Request $request ) {
		$response = parent::read_single( $request );

		if ( is_wp_error( $response ) ) {
			if ( ! Email_Logger::is_enabled() ) {
				return self::ERROR_401( 'not_enabled', 'Email logging is not currently enabled. Enable it to track detailed outgoing email information.' );
			}

			return self::ERROR_404( 'not_found', 'The requested email log could not be found. It may have been deleted in accordance with your log retention settings.' );
		}

		return $response;
	}

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
