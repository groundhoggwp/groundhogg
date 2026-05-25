<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\DraftException;
use Groundhogg\NoContactsException;
use Groundhogg\SchedulingException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\sanitize_payload;

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

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/report", [
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => [ $this, 'read_permissions_callback' ],
			'callback'            => [ $this, 'read_report' ],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/cancel", [
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => [ $this, 'update_permissions_callback' ],
			'callback'            => [ $this, 'cancel_broadcast' ],
		] );
	}

	public function read_report( WP_REST_Request $request ) {

		$broadcast = new Broadcast( $request->get_param( $this->get_primary_key() ) );

		if ( ! $broadcast->exists() ) {
			return self::ERROR_RESOURCE_NOT_FOUND();
		}

		return self::SUCCESS_RESPONSE( [
			'report' => $broadcast->get_report_data()
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

		try {

			$broadcast = Broadcast::schedule( [
				'object_id'                     => absint( $request->get_param( 'object_id' ) ),
				'object_type'                   => sanitize_text_field( $request->get_param( 'object_type' ) ),
				'date'                          => sanitize_text_field( $request->get_param( 'date' ) ),
				'time'                          => sanitize_text_field( $request->get_param( 'time' ) ),
				'dates'                         => $request->get_param( 'dates' ), // sanitized downstream
				'segment_type'                  => sanitize_text_field( $request->get_param( 'segment_type' ) ),
				'send_now'                      => (bool) $request->get_param( 'send_now' ),
				'send_in_local_time'            => (bool) $request->get_param( 'send_in_local_time' ),
				'batching'                      => (bool) $request->get_param( 'batching' ),
				'batch_interval'                => sanitize_text_field( $request->get_param( 'batch_interval' ) ),
				'batch_interval_length'         => absint( $request->get_param( 'batch_interval_length' ) ),
				'batch_amount'                  => absint( $request->get_param( 'batch_amount' ) ),
				'campaigns'                     => wp_parse_id_list( $request->get_param( 'campaigns' ) ),
				'query'                         => sanitize_payload( $request->get_param( 'query' ) ),
				'is_recurring'                  => (bool) $request->get_param( 'is_recurring' ),
				// sanitization happens downstream
				'repeats_every_amount'          => $request->get_param( 'repeats_every_amount' ),
				'repeats_every_interval'        => $request->get_param( 'repeats_every_interval' ),
				'repeats_dow'                   => $request->get_param( 'repeats_dow' ),
				'repeats_dow_occurrence'        => $request->get_param( 'repeats_dow_occurrence' ),
				'repeats_month_occurrence_type' => $request->get_param( 'repeats_month_occurrence_type' ),
				'repeats_dom'                   => $request->get_param( 'repeats_dom' ),
				'repeats_until'                 => $request->get_param( 'repeats_until' ),
				'repeats_until_date'            => $request->get_param( 'repeats_until_date' ),
				'repeats_until_occurrences'     => $request->get_param( 'repeats_until_occurrences' ),
			] );

			return self::SUCCESS_RESPONSE( [
				'item' => $broadcast
			] );

		} catch ( DraftException $e ) {

			return self::ERROR_400( 'draft_error', $e->getMessage() );

		} catch ( NoContactsException $e ) {

			return self::ERROR_400( 'no_contacts', $e->getMessage() );

		} catch ( SchedulingException $e ) {

			return self::ERROR_400( 'scheduling_error', $e->getMessage() );

		} catch ( \DateException $e ) {

			return self::ERROR_400( 'date_error', $e->getMessage() );

		}
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
		return current_user_can( 'cancel_broadcasts' );
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
		return current_user_can( 'cancel_broadcasts' );
	}
}
