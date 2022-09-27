<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Api\Api_Loader;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use function Groundhogg\get_db;

class Events_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/execute", [
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Execute the event
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function execute( \WP_REST_Request $request ) {

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$event = new Event( $primary_key );

		if ( ! $event->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		// Move the events over... only delete if the status is not complete
		$event_id = get_db( 'events' )->move_events_to_queue( [ 'ID' => $event->get_id() ], $event->is_complete() ? false : true, [
			'status' => Event::WAITING,
			'time'   => time()
		] );

		return self::SUCCESS_RESPONSE();

	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'events';
	}

	public function get_object_class() {
		return Event::class;
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

		if ( $contact_id && current_user_can( 'view_contact', $contact_id ) ) {
			return true;
		}

		return current_user_can( 'view_events' );
	}

	/**
	 * Permissions callback for update
	 *
	 * @return mixed
	 */
	public function update_permissions_callback() {
		return current_user_can( 'execute_events' );
	}

	/**
	 * Permissions callback for create
	 *
	 * @return mixed
	 */
	public function create_permissions_callback() {
		return current_user_can( 'add_events' );
	}

	/**
	 * Permissions callback for delete
	 *
	 * @return mixed
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_events' );
	}
}
