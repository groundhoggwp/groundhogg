<?php

namespace Groundhogg\Api\V4;

use Elementor\Tracker;
use Groundhogg\Broadcast;
use Groundhogg\Campaign;
use Groundhogg\Email;
use Groundhogg\Utils\Micro_Time_Tracker;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\create_object_from_type;
use function Groundhogg\db;
use function Groundhogg\get_db;

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

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/cancel", [
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => [ $this, 'update_permissions_callback' ],
			'callback'            => [ $this, 'cancel_broadcast' ],
		] );
	}

	/**
	 * Create a broadcast
	 *
	 * @throws \Exception
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_Error|WP_REST_Response
	 */
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

		$date = new \DateTime( $time_string, wp_timezone() );

		$segment_type = $request->get_param( 'segment_type' ) ?: 'fixed';

		/* convert to UTC */
		if ( $request->get_param( 'send_now' ) ) {
			$meta['send_now'] = true;
			$date->setTimestamp( time() + 10 );

			// when using Send Now the segment type is fixed anyway
			$segment_type = 'fixed';
		}

		$meta['segment_type'] = $segment_type;

		// Save batching meta
		if ( $request->get_param( 'batching' ) ) {
			$meta['batch_interval']        = sanitize_text_field( $request->get_param( 'batch_interval' ) );
			$meta['batch_interval_length'] = absint( $request->get_param( 'batch_interval_length' ) );
			$meta['batch_amount']          = absint( $request->get_param( 'batch_amount' ) );
			$meta['batch_delay']          = 0; // initialize batch offset at 0
		}

		if ( $date->getTimestamp() < time() ) {
			return self::ERROR_401( 'invalid_date', __( 'Please select a time in the future', 'groundhogg' ) );
		}

		$query = map_deep( $request->get_param( 'query' ), 'sanitize_text_field' ) ?: [];

		$is_transactional         = method_exists( $object, 'is_transactional' ) ? $object->is_transactional() : false;
		$meta['is_transactional'] = $is_transactional;

		if ( ! $is_transactional ) {
			$query['marketable'] = true;
		}

		$num_contacts = get_db()->contacts->count( $query );

		if ( $num_contacts === 0 ) {
			return self::ERROR_401( 'error', __( 'No contacts match the given filters.', 'groundhogg' ) );
		}

		$broadcast = new Broadcast();

		$broadcast->create( [
			'object_id'    => $object_id,
			'object_type'  => $object_type,
			'send_time'    => $date->getTimestamp(),
			'scheduled_by' => get_current_user_id(),
			'status'       => 'pending',
			'query'        => $query,
		] );

		if ( $request->get_param( 'send_in_local_time' ) ) {
			$meta['send_in_local_time'] = true;
		}

		$broadcast->update_meta( $meta );

		$campaigns = wp_parse_id_list( $request->get_param( 'campaigns' ) );

		foreach ( $campaigns as $campaign ) {
			$broadcast->create_relationship( new Campaign( $campaign ) );
		}

		/**
		 * Fires after the broadcast is added to the DB but before the user is redirected to the scheduler
		 *
		 * @param int   $broadcast_id the ID of the broadcast
		 * @param array $meta         the config object which is passed to the scheduler
		 */
		do_action( 'groundhogg/admin/broadcast/scheduled', $broadcast->get_id(), $meta, $broadcast );

		// We can jumpstart the process if the segment is fixed
		if ( $segment_type === 'fixed' ) {

			// Sets up the initial state for the scheduler
			$items_scheduled = $broadcast->enqueue_batch();

			// Something is wrong scheduling the broadcast
			if ( ! $items_scheduled ) {

				$broadcast->cancel();
				$broadcast->delete();

				return self::ERROR_500( 'db_error', 'Unable to schedule events', $broadcast );
			}

			$tracker = new Micro_Time_Tracker();

			// schedule 5 seconds worth. Should cover most small broadcasts
			while ( $broadcast->is_pending() && $tracker->time_elapsed() < 5 ){
				$broadcast->enqueue_batch();
			}
		}

		// If the broadcast is still pending, create a background task
		$broadcast->maybe_schedule_in_background();

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

		$processed = $broadcast->enqueue_batch();

		return self::SUCCESS_RESPONSE( [
			'finished'         => $broadcast->is_scheduled(),
			'percent_complete' => $broadcast->get_percent_scheduled()
		] );

	}

	/**
	 * Cancel a broadcast
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function cancel_broadcast( WP_REST_Request $request ) {

		$broadcast = new Broadcast( $request->get_param( $this->get_primary_key() ) );

		if ( ! $broadcast->exists() ) {
			return self::ERROR_RESOURCE_NOT_FOUND();
		}

		if ( ! $broadcast->cancel() ) {
			return self::ERROR_400( 'error', 'The broadcast could not be cancelled.' );
		}

		return self::SUCCESS_RESPONSE();
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
