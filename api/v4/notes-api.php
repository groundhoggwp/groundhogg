<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Classes\Note;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notes_Api
 *
 * @package Groundhogg\Api\V4
 */
class Notes_Api extends Base_Object_Api {

	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'notes';
	}
	
	protected function get_object_class() {
		return Note::class;
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_notes' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_notes' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_notes' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_notes' );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @param                  $cap
	 *
	 * @return bool|\WP_Error
	 */
	public function single_cap_check( \WP_REST_Request $request, $cap ){
		$note = $this->get_object_from_request( $request );

		if ( ! $note->exists() ){
			return self::ERROR_404();
		}

		return current_user_can( $cap, $note );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function update_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'edit_note' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function read_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'view_note' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'delete_note' );
	}
}