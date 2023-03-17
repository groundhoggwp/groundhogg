<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Api\Api_Loader;
use Groundhogg\Classes\Activity;
use WP_REST_Request;
use function Groundhogg\get_array_var;
use function Groundhogg\track_activity;
use function Groundhogg\track_activity_actions;

class Activity_Api extends Base_Object_Api{

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'activity';
	}

	protected function get_object_class() {
		return Activity::class;
	}

	/**
	 * Permissions callback for read
	 *
	 * @return bool
	 */
	public function read_permissions_callback() {

		$request = Api_Loader::get_request();

		// from contact screen
		$contact_id = $request->get_param( 'contact_id' );
		if ( $contact_id && current_user_can('view_contact', $contact_id ) ){
			return true;
		}

		return current_user_can( 'view_activity' );
	}

	/**
	 * Create new activity records
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_Error|\WP_REST_Response
	 */
	public function create( WP_REST_Request $request ) {

		$items = $request->get_json_params();

		if ( empty( $items ) ) {
			return self::ERROR_422( 'error', 'No data provided.' );
		}

		// Create single resource
		if ( get_array_var( $items, 'data' ) ) {
			return $this->create_single( $request );
		}

		$added = [];

		foreach ( $items as $item ) {

			$data = get_array_var( $item, 'data' ) ?: $item;
			$meta = get_array_var( $item, 'meta' );

			$activity = $this->create_new_object( $data, $meta, true );

			if ( ! $activity->exists() ) {
				continue;
			}

			$added[] = $activity;

			track_activity_actions( $activity );
		}

		return self::SUCCESS_RESPONSE( [
			'total_items' => count( $added ),
			'items'       => $added,
		] );
	}

	/**
	 * Create a singular activity record
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_Error|\WP_REST_Response
	 */
	public function create_single( WP_REST_Request $request ) {

		$data = $request->get_param( 'data' );
		$meta = $request->get_param( 'meta' );

		$activity = $this->create_new_object( $data, $meta, true );

		if ( ! $activity->exists() ) {

			global $wpdb;

			return self::ERROR_400( 'error', 'Bad request.', [
				'data' => $data,
				'meta' => $meta,
				'wpdb' => $wpdb->last_error
			] );
		}

		track_activity_actions( $activity );

		return self::SUCCESS_RESPONSE( [
			'item' => $activity
		] );

	}


	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_activity' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_activity' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_activity' );
	}
}
