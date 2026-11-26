<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DraftException;
use Groundhogg\Email;
use Groundhogg\Email_Variations;
use Groundhogg\NoContactsException;
use Groundhogg\SchedulingException;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\list_broadcasts_archive;
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

		register_rest_route( self::NAME_SPACE, "/{$route}/queries", [
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => [ $this, 'create_permissions_callback' ],
			'callback'            => [ $this, 'read_recent_queries' ],
		] );

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

		register_rest_route( self::NAME_SPACE, "/{$route}/archive", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_archive' ],
				'permission_callback' => '__return_true'
			]
		] );
	}

	/**
	 * Fetch a list of public assets associated with a specific public campaign
	 *
	 * @param  \WP_REST_Request  $request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function read_archive( \WP_REST_Request $request ) {

		try {
			$list = list_broadcasts_archive( [
				'page'     => absint( $request->get_param( 'page' ) ) ?: 1,
				'per_page' => absint( $request->get_param( 'per_page' ) ) ?: 10,
				'search'   => sanitize_text_field( $request->get_param( 'search' ) ),
				'campaign' => sanitize_text_field( $request->get_param( 'campaign' ) ),
			] );
		} catch ( \Exception $e ) {
			return self::ERROR_401( 'error', $e->getMessage() );
		}

		// sanitize the items for the public endpoint
		$list['items'] = array_map( function ( Broadcast $item ) use ( $request ) {

			$email = $item->get_object();

			$json = [
				'ID'         => $item->get_id(),
				'subject'    => $email->get_merged_subject_line(),
				'preview'    => $email->get_merged_pre_header(),
				'content'    => $email->build(),
				'plain'      => $email->get_merged_alt_body(),
				'sent'       => ( new DateTimeHelper( $item->get_send_time() ) )->date_i18n(),
				'from_name'  => $email->get_from_name(),
				'from_email' => $email->get_from_email(),
			];

			/**hj
			 * Allow modifying the response object of an individual item
			 *
			 * @param $json array the response item
			 * @param $item Broadcast the original broadcast
			 * @param $request WP_REST_Request the current request
			 */
			return apply_filters( 'groundhogg/api/broadcasts/archive/item', $json, $item, $request );
		}, $list['items'] );

		return self::SUCCESS_RESPONSE( $list );

	}

	/**
	 * Read a list of broadcasts.
	 *
	 * Serializing a broadcast in full also serializes the email or SMS behind it,
	 * which merges replacements and builds the whole message. That's far too
	 * expensive for a list, so `context=embed` returns just the columns, the
	 * audience size and the title. This is what the broadcast calendar asks for.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$response = parent::read( $request );

		if ( is_wp_error( $response ) || $request->get_param( 'context' ) !== 'embed' ) {
			return $response;
		}

		$data = $response->get_data();

		$data['items'] = array_values( array_map( [ $this, 'map_to_embedded_array' ], $data['items'] ) );

		$response->set_data( $data );

		return $response;
	}

	/**
	 * A broadcast without the object it's sending
	 *
	 * @param Broadcast $broadcast
	 *
	 * @return array
	 */
	protected function map_to_embedded_array( Broadcast $broadcast ) {
		return [
			'ID'    => $broadcast->get_id(),
			'data'  => [
				'object_id'      => $broadcast->get_object_id(),
				'object_type'    => $broadcast->get_broadcast_type(),
				'schedule_id'    => absint( $broadcast->schedule_id ),
				'scheduled_by'   => $broadcast->get_scheduled_by_id(),
				'send_time'      => $broadcast->get_send_time(),
				'query'          => $broadcast->get_query(),
				'status'         => $broadcast->get_status(),
				'date_scheduled' => $broadcast->get_date_scheduled(),
			],
			'meta'  => [
				'total_contacts' => absint( $broadcast->get_meta( 'total_contacts' ) ),
			],
			'title' => $broadcast->get_title(),
		];
	}

	/**
	 * Get recent queries used in broadcasts
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return WP_REST_Response
	 */
	public function read_recent_queries( WP_REST_Request $request ) {

		$limit = absint( $request->get_param( 'limit' ) ) ?: 10;

		$query = new Table_Query( 'broadcasts' );
		$query->setSelect( 'query' )->setLimit( $limit )->setOrderby( [ 'send_time', 'DESC' ] )
		      ->where()
		      ->contains( 'query', 'filters' ); // custom filter query
		$queries = array_unique( wp_list_pluck( $query->get_results(), 'query' ) );
		// make sure not keyed

		$queries = array_values( array_map( fn( $query ) => maybe_unserialize( $query ), $queries ) );
		// only include queries that have filters

		return self::SUCCESS_RESPONSE( ['queries' => $queries ] );
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

		$ab_test = $request->get_param( 'ab_test' );

		if ( is_array( $ab_test ) && ! empty( $ab_test ) ) {
			return $this->create_ab_test( $request, $ab_test );
		}

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
	 * Broadcast an email that has test versions to a random sample first, and the winner to everyone else after a
	 * wait, see Email_Variations::start_broadcast_test(). Returns the sample's broadcast.
	 *
	 * @param WP_REST_Request $request
	 * @param array           $ab_test sample_percent, wait_hours, metric
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	protected function create_ab_test( WP_REST_Request $request, array $ab_test ) {

		if ( $request->get_param( 'object_type' ) !== 'email' ) {
			return self::ERROR_400( 'ab_test_error', __( 'Only an email can be A/B tested.', 'groundhogg' ) );
		}

		// a test is one send: the sample, then the winner
		if ( $request->get_param( 'is_recurring' ) || count( (array) $request->get_param( 'dates' ) ) > 1 ) {
			return self::ERROR_400( 'ab_test_error', __( 'An A/B tested broadcast is sent once, so it can\'t repeat or go out on several dates.', 'groundhogg' ) );
		}

		$email = new Email( absint( $request->get_param( 'object_id' ) ) );

		if ( ! $email->exists() ) {
			return self::ERROR_404();
		}

		$schedule = [
			'date'                  => sanitize_text_field( $request->get_param( 'date' ) ),
			'time'                  => sanitize_text_field( $request->get_param( 'time' ) ),
			'send_now'              => (bool) $request->get_param( 'send_now' ),
			'send_in_local_time'    => (bool) $request->get_param( 'send_in_local_time' ),
			'batching'              => (bool) $request->get_param( 'batching' ),
			'batch_interval'        => sanitize_text_field( $request->get_param( 'batch_interval' ) ),
			'batch_interval_length' => absint( $request->get_param( 'batch_interval_length' ) ),
			'batch_amount'          => absint( $request->get_param( 'batch_amount' ) ),
			'campaigns'             => wp_parse_id_list( $request->get_param( 'campaigns' ) ),
		];

		// one date given as a list, like the scheduler does when more dates could be added
		$dates = (array) $request->get_param( 'dates' );

		if ( count( $dates ) === 1 && is_array( $dates[0] ) && count( $dates[0] ) === 2 ) {
			$schedule['date'] = sanitize_text_field( $dates[0][0] );
			$schedule['time'] = sanitize_text_field( $dates[0][1] );
		}

		try {
			$broadcast = Email_Variations::start_broadcast_test( $email, sanitize_payload( $request->get_param( 'query' ) ) ?: [], $schedule, [
				'sample_percent' => absint( $ab_test['sample_percent'] ?? 20 ),
				'wait_hours'     => absint( $ab_test['wait_hours'] ?? 4 ),
				'metric'         => sanitize_text_field( $ab_test['metric'] ?? 'clicks' ),
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

		if ( is_wp_error( $broadcast ) ) {
			return self::ERROR_400( 'ab_test_error', $broadcast->get_error_message() );
		}

		$test = ( new Email( $email->get_id() ) )->get_meta( Email_Variations::BROADCAST_TEST_KEY );

		return self::SUCCESS_RESPONSE( [
			'item'    => $broadcast,
			'ab_test' => [
				'sample'         => absint( $test['sample'] ?? 0 ),
				'rest'           => absint( $test['rest'] ?? 0 ),
				'winner_sent_at' => absint( $test['finish_at'] ?? 0 ),
			],
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
