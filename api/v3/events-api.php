<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\get_contactdata;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\sort_by_string_in_array;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Events_Api extends Base {

	public function register_routes() {

		$auth_callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/events', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_events' ],
				'permission_callback' => $auth_callback,
				'args'                => [
					'select'  => [
						'required'    => false,
						'description' => _x( 'Whether to retrieve as available for a select input.', 'api', 'groundhogg' ),
					],
					'select2' => [
						'required'    => false,
						'description' => _x( 'Whether to retrieve as available for an ajax select2 input.', 'api', 'groundhogg' ),
					],
					'search'  => [
						'required'    => false,
						'description' => _x( 'Search string for tag name.', 'api', 'groundhogg' ),
					],
					'q'       => [
						'required'    => false,
						'description' => _x( 'Shorthand for search.', 'api', 'groundhogg' ),
					],
				]

			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_event' ],
				'permission_callback' => $auth_callback,

			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_event' ],
				'permission_callback' => $auth_callback,

			],

		] );

		register_rest_route( self::NAME_SPACE, '/events/run-again', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'run_again' ],
				'permission_callback' => $auth_callback,
				'args'                => [
					'events' => [
						'required'    => true,
						'description' => _x( 'Array of events id or single event id which user wants to run again.', 'api', 'groundhogg' ),
					],
				]
			]
		] );


		register_rest_route( self::NAME_SPACE, '/events/cancel', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'cancel_event' ],
				'permission_callback' => $auth_callback,
				'args'                => [
					'events' => [
						'required'    => true,
						'description' => _x( 'Array of events id or single event id which user wants to run again.', 'api', 'groundhogg' ),
					],
				]
			]
		] );


		register_rest_route( self::NAME_SPACE, '/events/uncancel', [
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'uncancel_event' ],
				'permission_callback' => $auth_callback,
				'args'                => [
					'events' => [
						'required'    => true,
						'description' => _x( 'Array of events id or single event id which user wants to run again.', 'api', 'groundhogg' ),
					],
				]
			]
		] );


	}

	/**
	 * Get list of all the event
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_events( WP_REST_Request $request ) {

		$offset  = $request->get_param( 'offset' ) ? : 0;
		$limit   = $request->get_param( 'limit' ) ? : 100;
		$order   = $request->get_param( 'order' ) ? : 'DESC';
		$orderby = $request->get_param( 'orderby' ) ? : 'ID';
		$view    = $request->get_param( 'view' ) ? : '';

		$where = [];
		if ( $view ) {
			$where[] = [
				'relationship' => "AND",
				[ 'col' => 'status', 'val' => $view, 'compare' => '=' ],
			];
		}

		// todo manage request query

//		$request_query = get_request_query( [], [], array_keys( get_db( 'events' )->get_columns() ) );

//		unset( $request_query['status'] );

//		if ( ! empty( $request_query ) ) {
//			foreach ( $request_query as $key => $value ) {
//				$where[] = [ 'col' => $key, 'val' => $value, 'compare' => '=' ];
//			}
//		}

		$args = array(
			'where'   => $where,
			'limit'   => $limit,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);

		$table = ( $view === Event::WAITING ) ? 'event_queue' : 'events';

		$events = get_db( $table )->query( $args );
		$total  = get_db( $table )->count( $args );

		$events_to_return = [];
		foreach ( $events as $event ) {

			$event_data = $event;

			$event = new Event( $event->ID, $table );

			$events_to_return[] = array_merge( (array) $event_data , [
				'contact_email' => $event->get_contact()->get_email(),
				'funnel_title'  => $event->get_funnel_title(),
				'step_title'    => $event->get_step_title(),

			] );

//			$events_to_return[] = $event;

		}


		return self::SUCCESS_RESPONSE( [
			'total'  => $total,
			'events' => $events_to_return
		] );
	}

	/**
	 * create new event using api
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */

	public function create_event( WP_REST_Request $request ) {

		//todo mostly created using the internal API

	}

	/**
	 * Delete event to clear list
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_event( WP_REST_Request $request ) {

	}


	/**
	 * Reschedule the event
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function run_again( WP_REST_Request $request ) {
		// code to reschedule the task

		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		global $wpdb;

		$events_table      = get_db( 'events' )->get_table_name();
		$event_queue_table = get_db( 'event_queue' )->get_table_name();

		// getting the event
		$events = $request->get_param( 'events' );
		$events = is_array( $events ) ? $events : array( $events );

		$event_ids = implode( ',', $events );
		$time      = time();
		$waiting   = Event::WAITING;

		$claim = substr( md5( wp_json_encode( $events ) ), 0, 20 );

		// Update the claim column
		$wpdb->query( "UPDATE {$events_table} SET `claim` = '$claim' WHERE `ID` in ({$event_ids});" );

		// Move the events over... only delete if the status is not complete
		get_db( 'events' )->move_events_to_queue( [ 'claim' => $claim ], get_request_var( 'status' ) === Event::COMPLETE ? false : true );

		// Update claim, status, and time...
		$wpdb->query( "UPDATE {$event_queue_table} SET `claim` = '', `status` = '$waiting', `time` = $time WHERE `claim` = '$claim';" );

		return self::SUCCESS_RESPONSE( [], _x( sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $events ), 'api', 'groundhogg' ), count( $events ) ), 'api', 'groundhogg' ) );

	}


	/**
	 * Cancel scheduled event - only runs for the events in processing
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function cancel_event( WP_REST_Request $request ) {

		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		global $wpdb;


		// getting the event
		$events = $request->get_param( 'events' );
		$events = is_array( $events ) ? $events : array( $events );


		$event_queue = get_db( 'event_queue' )->get_table_name();
		$event_ids   = implode( ',', $events );
		$cancelled   = Event::CANCELLED;

		// Update the time
		$wpdb->query( "UPDATE {$event_queue} SET `status` = '$cancelled' WHERE `ID` in ({$event_ids})" );

		// Move the items over...
		get_db( 'event_queue' )->move_events_to_history( [ 'ID' => $events ] );

		return self::SUCCESS_RESPONSE( [], sprintf( _nx( '%d event cancelled', '%d events cancelled', count( $events ), 'notice', 'groundhogg' ), count( $events ) ) );

	}


	/**
	 * Put cancelled events back in event queue..
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function uncancel_event( WP_REST_Request $request ) {

		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}


		global $wpdb;

		// getting the event
		$events = $request->get_param( 'events' );
		$events = is_array( $events ) ? $events : array( $events );


		$events_table = get_db( 'events' )->get_table_name();
		$event_ids    = implode( ',', $events );
		$cancelled    = Event::CANCELLED;
		$waiting      = Event::WAITING;

		// Update the status back to waiting...
		$wpdb->query( "UPDATE {$events_table} SET `status` = '$waiting' WHERE `ID` in ({$event_ids}) AND `status` = '$cancelled';" );

		// Move the events over...
		get_db( 'events' )->move_events_to_queue( [ 'ID' => $events, 'status' => $waiting ], true );

		return self::SUCCESS_RESPONSE( [], sprintf( _nx( '%d event uncancelled', '%d events uncancelled', count( $events ), 'notice', 'groundhogg' ), count( $events ) ) );

	}

}