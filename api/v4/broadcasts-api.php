<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\create_object_from_type;
use function Groundhogg\enqueue_event;
use function Groundhogg\get_db;
use Groundhogg\Tag;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\utils;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Broadcasts_Api extends Base_Object_Api {

	public function register_routes() {

		parent::register_routes();

		$route = $this->get_route();
		$key   = $this->get_primary_key();

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/schedule", [
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => [ $this, 'create_permissions_callback' ],
			'callback'            => [ $this, 'schedule_broadcast' ],
		] );

	}

	public function create( WP_REST_Request $request ) {

		$meta = [];

		$object_id   = absint( $request->get_param( 'object_id' ) );
		$object_type = sanitize_text_field( $request->get_param( 'object_type' ) ) ?: 'email';

		$object = create_object_from_type( $object_id, $object_type );

		if ( $object_type === 'email' ) {

			$email = new Email( $object_id );

			if ( $email->is_draft() ) {
				return self::ERROR_401( 'email_in_draft_mode', __( 'You cannot schedule an email while it is in draft mode.', 'groundhogg' ) );
			}
		}

		$date = sanitize_text_field( $request->get_param( 'date' ) ) ?: date( 'Y-m-d', strtotime( 'tomorrow' ) );
		$time = sanitize_text_field( $request->get_param( 'time' ) ) ?: '9:00:00';

		$time_string = $date . ' ' . $time;

		/* convert to UTC */
		$send_time = utils()->date_time->convert_to_utc_0( strtotime( $time_string ) );

		if ( $request->get_param( 'send_now' ) ) {
			$meta['send_now'] = true;
			$send_time        = time() + 10;
		}

		if ( $send_time < time() ) {
			return self::ERROR_401( 'invalid_date', __( 'Please select a time in the future', 'groundhogg' ) );
		}

		$query = map_deep( $request->get_param( 'query' ), 'sanitize_text_field' ) ?: [];

		$is_transactional         = method_exists( $object, 'is_transactional' ) ? $object->is_transactional() : false;
		$meta['is_transactional'] = $is_transactional;

		if ( ! $is_transactional ) {
			$query['marketable'] = true;
		}

		$num_contacts = get_db( 'contacts' )->count( $query );

		if ( $num_contacts === 0 ) {
			return self::ERROR_401( 'error', __( 'No contacts match the given filters.', 'groundhogg' ) );
		}

		$broadcast_id = new Broadcast( [
			'object_id'    => $object_id,
			'object_type'  => $object_type,
			'send_time'    => $send_time,
			'scheduled_by' => get_current_user_id(),
			'status'       => 'pending',
			'query'        => $query,
		] );

		if ( $request->get_param( 'send_in_local_time' ) ) {
			$meta['send_in_local_time'] = true;
		}

		$broadcast = new Broadcast( $broadcast_id );

		$broadcast->update_meta( $meta );

		/**
		 * Fires after the broadcast is added to the DB but before the user is redirected to the scheduler
		 *
		 * @param int   $broadcast_id the ID of the broadcast
		 * @param array $meta         the config object which is passed to the scheduler
		 */
		do_action( 'groundhogg/admin/broadcast/scheduled', $broadcast_id, $meta, $broadcast );

		return self::SUCCESS_RESPONSE( [
			'item' => $broadcast
		] );
	}

	/**
	 * Schedule broadcast for provided tags.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function schedule_broadcast( WP_REST_Request $request ) {

		$broadcast = new Broadcast( $request->get_param( $this->get_primary_key() ) );

		if ( ! $broadcast->exists() ) {
			return self::ERROR_RESOURCE_NOT_FOUND();
		}

		if ( $broadcast->get_status() === 'pending' ){
			$broadcast->update( [
				'status' => 'scheduled'
			] );
		}

		$query = $broadcast->get_query();

		$limit    = absint( $request->get_param( 'limit' ) ) ?: 500;
		$offset   = absint( $broadcast->get_meta( 'num_scheduled' ) ) ?: 0;
		$in_lt    = (bool) $broadcast->get_meta( 'send_in_local_time' );
		$send_now = (bool) $broadcast->get_meta( 'send_now' );

		$query['number'] = $limit;
		$query['offset'] = $offset;

		$c_query  = new Contact_Query();
		$contacts = $c_query->query( $query, true );
		$total    = $c_query->count( $query );

		foreach ( $contacts as $contact ) {

			$offset ++;

			// No point in scheduling an email to a contact that is not marketable.
			if ( ! $broadcast->is_transactional() && ! $contact->is_marketable() ) {
				continue;
			}

			$local_time = $broadcast->get_send_time();

			if ( $in_lt && ! $send_now ) {

				$local_time = $contact->get_local_time_in_utc_0( $local_time );

				if ( $local_time < time() ) {
					$local_time += DAY_IN_SECONDS;
				}
			}

			$args = [
				'time'       => $local_time,
				'contact_id' => $contact->get_id(),
				'funnel_id'  => Broadcast::FUNNEL_ID,
				'step_id'    => $broadcast->get_id(),
				'event_type' => Event::BROADCAST,
				'status'     => Event::WAITING,
				'priority'   => 100,
			];

			if ( $broadcast->is_email() ) {
				$args['email_id'] = $broadcast->get_object_id();
			}

			enqueue_event( $args );

		}

		$broadcast->update_meta( 'num_scheduled', $offset );

		return self::SUCCESS_RESPONSE( [
			'finished'  => $offset >= $total,
			'scheduled' => $offset
		] );

	}

	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'broadcasts';
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_broadcasts' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_broadcasts' );
	}

	/**
	 * @inheritDoc
	 */
	public function create_permissions_callback() {
		return current_user_can( 'schedule_broadcasts' );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_broadcasts' );
	}
}