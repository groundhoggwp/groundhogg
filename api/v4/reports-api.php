<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact_Query;
use Groundhogg\Reports;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use function Groundhogg\array_find;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\get_db;

class Reports_Api extends Base_Api {

	public function register_routes() {
		register_rest_route( self::NAME_SPACE, '/reports', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/reports/(?P<id>[-\w]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/custom-reports', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_custom_reports' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/custom-reports/(?P<id>[-\w]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_single_custom_report' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );
	}

	function get_custom_report_data( $report ) {

		$query = new Contact_Query( [
			'filters' => $report['filters']
		] );

		switch ( $report['type'] ) {
			case 'pie_chart':
			case 'table':
				$query->set_query_var( 'select', 'ID' );
				$sql = $query->get_sql();

				$where = [
					[ 'meta_key', '=', $report['field'] ],
					[ 'meta_value', '!=', '' ],
				];

				if ( ! empty( $report['filters'] ) ) {
					$where[] = [ 'contact_id', 'IN', $sql ];
				}

				$records = get_db( 'contactmeta' )->query( [
					'select'  => 'meta_value as value, COUNT(*) as count',
					'where'   => $where,
					'groupby' => 'value',
					'orderby' => 'count'
				] );

				foreach ( $records as &$record ) {
					$record->value = maybe_unserialize( $record->value );
				}

				return $records;

			case 'number':

				switch ( $report['value'] ) {
					case 'sum':
					case 'average':

						// todo

					default:
					case 'contacts':
						return $query->count();

				}
		}

		return false;
	}

	/**
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function read_custom_reports( WP_REST_Request $request ) {

		$reports = get_option( 'gh_custom_reports', [] );

		foreach ( $reports as &$report ) {
			$report['data'] = $this->get_custom_report_data( $report );
		}

		return self::SUCCESS_RESPONSE( [
			'items' => $reports
		] );

	}

	/**
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function read_single_custom_report( WP_REST_Request $request ) {

		$reports = get_option( 'gh_custom_reports', [] );
		$report  = array_find( $reports, function ( $report ) use ( $request ) {
			return $report['id'] == $request->get_param( 'id' );
		} );

		$report['data'] = $this->get_custom_report_data( $report );

		return self::SUCCESS_RESPONSE( [
			'item' => $report
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
		$start = $request->get_param( 'start' );
		$end   = $request->get_param( 'end' );

		$params  = $request->get_param( 'params' );
		$reports = map_deep( $request->get_param( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start, $end, $params );

//		if ( empty( $reports ) ) {
//			return self::ERROR_404( 'error', 'report not found' );
//		}

		$custom_reports = get_option( 'gh_custom_reports', [] );

		$results = [];

		foreach ( $reports as $report_id ) {

			if ( wp_is_uuid( $report_id ) ) {
				$report = array_find( $custom_reports, function ( $report ) use ( $report_id ) {
					return $report['id'] == $report_id;
				} );

				$data = $this->get_custom_report_data( $report );
			} else {
				$data = $reporting->get_data_3_0( $report_id );
			}

			$results[ $report_id ] = $data;
		}

		return self::SUCCESS_RESPONSE( [
			'start'       => $start,
			'end'         => $end,
			'report_data' => $results,
			'diff'        => human_time_diff( $reporting->start->getTimestamp(), $reporting->end->getTimestamp() )
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
