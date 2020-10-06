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

		register_rest_route( self::NAME_SPACE, '/reports/(?P<id>\d+)', [
			[
				'methods'              => WP_REST_Server::READABLE,
				'callback'             => [ $this, 'read_single' ],
				'permissions_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_reports' );
	}

	/**
	 * Return report results
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {
		$start   = strtotime( sanitize_text_field( $request->get_param( 'start' ) ) );
		$end     = strtotime( sanitize_text_field( $request->get_param( 'end' ) ) ) + ( DAY_IN_SECONDS - 1 );
		$context = $request->get_param( 'context' );

		$reports   = $request->get_param( 'reports' );
		$reporting = new Reports( $start, $end, $context );

		$results = [];

		foreach ( $reports as $report_id ) {
			$results[ $report_id ] = $reporting->get_data( $report_id );
		}

		return self::SUCCESS_RESPONSE( [
			'start'   => $start,
			'end'     => $end,
			'results' => $results
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

		$start   = strtotime( sanitize_text_field( $request->get_param( 'start' ) ) );
		$end     = strtotime( sanitize_text_field( $request->get_param( 'end' ) ) ) + ( DAY_IN_SECONDS - 1 );
		$context = $request->get_param( 'context' );

		$report    = $request->get_param( 'id' );
		$reporting = new Reports( $start, $end, $context );

		$results = $reporting->get_data( $report );

		return self::SUCCESS_RESPONSE( [
			'start'   => $start,
			'end'     => $end,
			'results' => $results
		] );
	}

}