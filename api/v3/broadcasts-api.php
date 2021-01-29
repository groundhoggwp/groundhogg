<?php

namespace Groundhogg\Api\V3;

use Groundhogg\Broadcast;
use Groundhogg\Email;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use Groundhogg\Tag;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\validate_tags;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Broadcasts_Api extends Base {

	public function register_routes() {

		$auth_callback = $this->get_auth_callback();

		register_rest_route( self::NAME_SPACE, '/broadcasts', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_broadcasts' ],
				'permission_callback' => $auth_callback,
			],
//  Needs to define use of this endpoints
//			[
//				'methods'             => WP_REST_Server::CREATABLE,
//				'callback'            => [ $this, 'create_broadcast' ],
//				'permission_callback' => $auth_callback,
//			],
//			[
//				'methods'             => WP_REST_Server::EDITABLE,
//				'callback'            => [ $this, 'update_broadcast' ],
//				'permission_callback' => $auth_callback,
//			],
//			[
//				'methods'             => WP_REST_Server::DELETABLE,
//				'callback'            => [ $this, 'delete_broadcast' ],
//				'permission_callback' => $auth_callback,
//			]
		] );

		register_rest_route( self::NAME_SPACE, '/broadcasts/schedule', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'schedule_broadcast_v2' ],
			'permission_callback' => $auth_callback,
		) );


		register_rest_route( self::NAME_SPACE, '/broadcasts/cancel', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'cancel_broadcast' ],
			'permission_callback' => $auth_callback,

		) );

	}

	/**
	 * Get a list of broadcast.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_broadcasts( WP_REST_Request $request ) {
		if ( ! current_user_can( 'view_reports' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$offset  = $request->get_param( 'offset' ) ? : 0;
		$limit   = $request->get_param( 'limit' ) ? : 100;
		$order   = $request->get_param( 'order' ) ? : 'DESC';
		$orderby = $request->get_param( 'orderby' ) ? : 'ID';

		$broadcast_ids = wp_parse_id_list( wp_list_pluck( get_db( 'broadcasts' )->query( [
			'orderby' => $orderby,
			'order'   => $order,
			'limit'   => $limit,
			'offset'  => $offset
		] ), 'ID' ) );

		$response_broadcast = [];

		foreach ( $broadcast_ids as $broadcast_id ) {

			$broadcast            = new Broadcast( $broadcast_id );
			$response_broadcast[] = $broadcast->get_as_array();
		}

		return self::SUCCESS_RESPONSE( [ 'broadcasts' => $response_broadcast ] );
	}

	/**
	 * Schedule broadcast for provided tags.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function schedule_broadcast( WP_REST_Request $request ) {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$config = [];

		$object_id        = intval( $request->get_param( 'email_or_sms_id' ) );
		$tags             = wp_parse_id_list( $request->get_param( 'tags' ) );
		$exclude_tags     = wp_parse_id_list( $request->get_param( 'exclude_tags' ) );
		$date             = $request->get_param( 'date' );
		$time             = $request->get_param( 'time' );
		$send_now         = $request->get_param( 'send_now' );
		$send_in_timezone = $request->get_param( 'local_time' );
		$type             = $request->get_param( 'type' );

		/* Set the object  */
		$config[ 'object_id' ]   = $object_id;
		$config[ 'object_type' ] = $type;

		if ( $config[ 'object_type' ] === 'email' ) {
			$email = new Email( $object_id );
			if ( $email->is_draft() ) {
				return self::ERROR_400( 'email_in_draft_mode', sprintf( _x( 'You cannot schedule an email while it is in draft mode.', 'api', 'groundhogg' ) ) );
			}
		}

		$contact_sum = 0;

		foreach ( $tags as $tag_id ) {
			$tag = new Tag( $tag_id );

			if ( $tag->exists() ) {
				$contact_sum += $tag->get_contact_count();
			}
		}

		if ( $contact_sum === 0 ) {
			return self::ERROR_400( 'no_contacts', sprintf( _x( 'Please select a tag with at least 1 contact.', 'api', 'groundhogg' ) ) );
		}

		if ( $date ) {
			$send_date = $date;
		} else {
			$send_date = date( 'Y/m/d', strtotime( 'tomorrow' ) );
		}

		if ( $time ) {
			$send_time = $time;
		} else {
			$send_time = '9:30';
		}

		$time_string = $send_date . ' ' . $send_time;

		/* convert to UTC */
		$send_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

		if ( $send_now ) {
			$config[ 'send_now' ] = true;
			$send_time            = time() + 10;
		}

		if ( $send_time < time() ) {
			return self::ERROR_400( 'invalid_date', _x( 'Please select a time in the future', 'api', 'groundhogg' ) );
		}

		/* Set the email */
		$config[ 'send_time' ] = $send_time;

		$args = array(
			'object_id'    => $object_id,
			'object_type'  => $config[ 'object_type' ],
			'tags'         => $tags,
			'send_time'    => $send_time,
			'scheduled_by' => get_current_user_id(),
			'status'       => 'scheduled',
		);

		$broadcast = new Broadcast( $args );

		if ( ! $broadcast->exists() ) {
			return self::ERROR_UNKNOWN();
		}

		$config[ 'broadcast_id' ] = $broadcast->get_id();

		$query = array(
			'tags_include' => $tags,
			'tags_exclude' => $exclude_tags
		);

		$config[ 'contact_query' ] = $query;

		if ( $send_in_timezone ) {
			$config[ 'send_in_local_time' ] = true;
		}

		set_transient( 'gh_get_broadcast_config', $config, HOUR_IN_SECONDS );

		return self::SUCCESS_RESPONSE( [], _x( 'Broadcast scheduled successfully.', 'api', 'groundhogg' ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function create_broadcast( WP_REST_Request $request ) {
		return self::ERROR_400( 'NO_ENDPOINT', 'Please Use schedule broadcast ', [] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function update_broadcast( WP_REST_Request $request ) {
		return self::ERROR_400( 'NO_ENDPOINT', 'Please Use schedule broadcast ', [] );
	}


	/**
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function delete_broadcast( WP_REST_Request $request ) {
		return self::ERROR_400( 'NO_ENDPOINT', 'Please Use schedule broadcast ', [] );
	}


	/**
	 *
	 */
	public function cancel_broadcast() {

	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function schedule_broadcast_v2( WP_REST_Request $request ) {

		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$meta = [];

		$object_id = intval( $request->get_param( 'email_or_sms_id' ) );

		if ( ! $object_id ) {
			return self::ERROR_400( 'no_email_or_sms_id', sprintf( _x( 'Email or SMS ID not found while scheduling broadcast.', 'api', 'groundhogg' ) ) );
		}

		/* Set the object  */
		$meta[ 'object_id' ]   = $object_id;
		$meta[ 'object_type' ] = $request->get_param( 'type' ) ? $request->get_param( 'type' ) : 'email'; // by default returns email

		if ( $meta[ 'object_type' ] === 'email' ) {

			$email = Plugin::$instance->utils->get_email( $object_id );

			if ( $email->is_draft() ) {
				return self::ERROR_400( 'email_in_draft_mode', sprintf( _x( 'You cannot schedule an email while it is in draft mode.', 'api', 'groundhogg' ) ) );
			}
		}

		$send_date = $request->get_param( 'date' ) ? $request->get_param( 'date' ) : date( 'Y-m-d', strtotime( 'tomorrow' ) );
		$send_time = $request->get_param( 'time' ) ? $request->get_param( 'time' ) : '09:30';

		$time_string = $send_date . ' ' . $send_time;

		/* convert to UTC */
		$send_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

		if ( $request->get_param( 'send_now' ) ) {
			$meta[ 'send_now' ] = true;
			$send_time          = time() + 10;
		}

		if ( $send_time < time() ) {
			return self::ERROR_400( 'invalid_date', _x( 'Please select a time in the future', 'api', 'groundhogg' ) );
		}

		/* Set the email */
		$meta[ 'send_time' ] = $send_time;

		$include_tags = validate_tags( wp_parse_id_list( $request->get_param( 'tags' ) ) );
		$exclude_tags = validate_tags( wp_parse_id_list( $request->get_param( 'exclude_tags' ) ) );

		$query = array(
			'tags_include' => $include_tags,
			'tags_exclude' => $exclude_tags,
//			'tags_include_needs_all' => absint( get_request_var( 'tags_include_needs_all' ) ), // todo check for this two fields
//			'tags_exclude_needs_all' => absint( get_request_var( 'tags_exclude_needs_all' ) )
		);

		//TODO Handle the saved search

//		// Use a saved search instead.
//		if ( $saved_search = sanitize_text_field( get_post_var( 'saved_search' ) ) ) {
//			$search = Saved_Searches::instance()->get( $saved_search );
//			if ( $search ) {
//				$query = $search[ 'query' ];
//			}
//		} else if ( $custom_query = get_post_var( 'custom_query' ) ) {
//			$query = map_deep( json_decode( $custom_query, true ), 'sanitize_text_field' );
//		}
//
//		// Unset the search param from the query...
//		unset( $query[ 'is_searching' ] );
//
//		$query = wp_parse_args( $query, [
//			'optin_status' => [
//				Preferences::CONFIRMED,
//				Preferences::UNCONFIRMED,
//			]
//		] );

		// Assume marketing by default...
		$meta[ 'is_transactional' ] = false;

		// if the email is a transactional email we will remove the optin statuses from the query
		if ( $meta[ 'object_type' ] === 'email' && isset( $email ) && $email->is_transactional() ) {

			// Include additional statuses
			unset( $query[ 'optin_status' ] );

			$query[ 'optin_status_exclude' ] = [
				Preferences::SPAM,
				Preferences::HARD_BOUNCE,
				Preferences::COMPLAINED
			];

			// make transactional
			$meta[ 'is_transactional' ] = true;
		}

		$query = array_filter( $query );

		$args = array(
			'object_id'    => $object_id,
			'object_type'  => $meta[ 'object_type' ],
			'send_time'    => $send_time,
			'scheduled_by' => get_current_user_id(),
			'status'       => 'pending',
			'query'        => $query,
		);

		$num_contacts = get_db( 'contacts' )->count( $query );

		if ( $num_contacts === 0 ) {
			return self::ERROR_400( 'no_contacts', sprintf( _x( 'Please select a tag with at least 1 contact.', 'api', 'groundhogg' ) ) );
		}

		$broadcast_id = get_db( 'broadcasts' )->add( $args );

		if ( ! $broadcast_id ) {
			return self::ERROR_400( 'unable_to_add_broadcast', sprintf( _x( 'Something went wrong while adding the broadcast.', 'api', 'groundhogg' ) ) );
		}

		$meta[ 'send_in_local_time' ] = $request->get_param( 'send_in_timezone' ) ? true : false;

		$broadcast = new Broadcast( $broadcast_id );

		foreach ( $meta as $key => $value ) {
			$broadcast->update_meta( $key, $value );
		}


		$broadcast->update( [ 'status' => 'scheduled' ] );

		set_transient( 'gh_current_broadcast_id', $broadcast_id, DAY_IN_SECONDS );

		return self::SUCCESS_RESPONSE( [], _x( 'Broadcast scheduled successfully.', 'api', 'groundhogg' ) );

//		/**
//		 * Fires after the broadcast is added to the DB but before the user is redirected to the scheduler
//		 *
//		 * @param int $broadcast_id the ID of the broadcast
//		 * @param array $meta the config object which is passed to the scheduler
//		 */
//		do_action( 'groundhogg/admin/broadcast/scheduled', $broadcast_id, $meta, $broadcast );
//
//		$this->add_notice( 'review', __( 'Review your broadcast before scheduling!', 'groundhogg' ), 'warning' );
//
//		return admin_page_url( 'gh_broadcasts', [
//			'action'    => 'preview',
//			'broadcast' => $broadcast_id,
//		] );
	}


}