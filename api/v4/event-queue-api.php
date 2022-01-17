<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Event_Queue_Item;

class Event_Queue_Api extends Events_Api {

	public function register_routes() {
		parent::register_routes();

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/cancel", [
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'cancel' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Change the execution tie of an event to the present
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function execute( \WP_REST_Request $request ){

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = new Event_Queue_Item( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->update([
			'time' => time()
		]);

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );

	}

	/**
	 * Cancel a pending event
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function cancel( \WP_REST_Request $request ){

		$primary_key = absint( $request->get_param( $this->get_primary_key() ) );

		$object = new Event_Queue_Item( $primary_key );

		if ( ! $object->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$object->cancel();

		return self::SUCCESS_RESPONSE( [ 'item' => $object ] );

	}

	/**
	 * The name of the table resource to use
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		return 'event_queue';
	}

	public function get_object_class() {
		return Event_Queue_Item::class;
	}
}
