<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Classes\Task;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notes_Api
 *
 * @package Groundhogg\Api\V4
 */
class Tasks_Api extends Notes_Api {

	protected function get_object_class() {
		return Task::class;
	}

	public function get_db_table_name() {
		return 'tasks';
	}

	/**
	 * @param $data
	 * @param $meta
	 * @param $force
	 *
	 * @return Task
	 */
	public function create_new_object( $data, $meta = [], $force = false ) {
		return parent::create_new_object( $data, $meta, $force );
	}

	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();
		$key = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/$route/(?P<{$key}>\d+)/complete", [
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'complete' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/$route/(?P<{$key}>\d+)/incomplete", [
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'incomplete' ],
				'permission_callback' => [ $this, 'update_single_permissions_callback' ]
			],
		] );
	}

	/**
	 * Mark a task as complete
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function complete( \WP_REST_Request $request ){

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );
		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->complete();

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}


	/**
	 * Mark a task as incomplete
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function incomplete( \WP_REST_Request $request ){

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );
		$object = $this->create_new_object( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->incomplete();

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_tasks' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_tasks' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_tasks' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_tasks' );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @param                  $cap
	 *
	 * @return bool|\WP_Error
	 */
	public function single_cap_check( \WP_REST_Request $request, $cap ){
		$task = $this->get_object_from_request( $request );

		if ( ! $task->exists() ){
			return self::ERROR_404();
		}

		return current_user_can( $cap, $task );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function update_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'edit_task' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function read_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'view_task' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'delete_task' );
	}
}
