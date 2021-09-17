<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Reports;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Reports_Api extends Base_Api {

	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/reports', [
			[
				'methods'              => WP_REST_Server::READABLE,
				'callback'             => [ $this, 'read' ],
				'permissions_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/reports/(?P<id>\w+)', [
			[
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'read_single' ],
				 'permissions_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_reports' );
	}

	/**
	 * Verify the reporting dat is valid
	 *
	 * @param $param
	 * @param $request
	 * @param $key
	 *
	 * @return bool
	 */
	public function is_valid_report_date( $param, $request, $key ) {
		return strtotime( $param ) !== false;
	}

	/**
	 * Return report results
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {
		$start = strtotime( sanitize_text_field( $request->get_param( 'start' ) ?: date( 'Y-m-d', time() - MONTH_IN_SECONDS ) ) );
		$end   = strtotime( sanitize_text_field( $request->get_param( 'end' ) ?: date( 'Y-m-d' ) ) ) + ( DAY_IN_SECONDS - 1 );

		$params  = $request->get_param( 'params' );
		$reports = map_deep( $request->get_param( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start, $end, $params );

		if ( empty( $reports ) ) {
			return self::ERROR_404( 'error', 'report not found' );
		}

		$results = [];

		foreach ( $reports as $report_id ) {
			$data                  = $reporting->get_data_3_0( $report_id );
			$results[ $report_id ] = array_merge( [ 'id' => $report_id ], $data );
		}

		return self::SUCCESS_RESPONSE( [
			'start'   => $start,
			'end'     => $end,
			'reports' => $results
		] );
	}

	/**
	 * Return report results
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function read_single( WP_REST_Request $request ) {

		$start   = strtotime( sanitize_text_field( $request->get_param( 'start' ) ?: date( 'Y-m-d', time() - MONTH_IN_SECONDS ) ) );
		$end     = strtotime( sanitize_text_field( $request->get_param( 'end' ) ?: date( 'Y-m-d' ) ) ) + ( DAY_IN_SECONDS - 1 );
		$context = $request->get_param( 'context' );

		$report    = $request->get_param( 'id' );
		$reporting = new Reports( $start, $end, $context );

		$results = $reporting->get_data( $report );

		return self::SUCCESS_RESPONSE( [
			'start' => $start,
			'end'   => $end,
			'items' => $results
		] );
	}

}
