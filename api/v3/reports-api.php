<?php

namespace Groundhogg\Api\V3;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use Groundhogg\Reports;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\show_groundhogg_branding;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

use Groundhogg\Admin\Dashboard\Widgets;

class Reports_Api extends Base {

	public function register_routes() {
//		$callback = $this->get_auth_callback();


		register_rest_route( self::NAME_SPACE, '/reports', [
			[
				'methods'  => WP_REST_Server::READABLE,
//				'permission_callback' => $callback,
				'callback' => [ $this, 'get_report' ],
//				'args'     => [
//					'report'       => [
//						'required' => true
//					],
//					'range'        => [
//						'required' => true
//					],
//					'chart_format' => [
//						'required' => false
//					]
//				]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/reports', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
//				'permission_callback' => $callback,
				'callback' => [ $this, 'get_report_react' ],
				'args'     => [
//					'report'       => [
//						'required' => true
//					],
//					'range'        => [
//						'required' => true
//					],
//					'chart_format' => [
//						'required' => false
//					]
				]
			],
		] );

		register_rest_route( self::NAME_SPACE, '/reports/dashboard', [
			[
				'methods'  => WP_REST_Server::READABLE,
//				'permission_callback' => $callback,
				'callback' => [ $this, 'get_dashboard_reports' ],
				'args'     => [
					'reports' => [
						'required' => true,
					],
					'range'   => [
						'required' => true
					],
				]
			],
		] );

	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_dashboard_reports( WP_REST_Request $request ) {

		if ( ! current_user_can( 'view_reports' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$reports = $request->get_param( 'reports' );

		if ( ! $reports ) {
			return self::ERROR_401( 'no_report', 'The given report does not exist.' );
		}

		// Todo remove dummy data, get some real stuff in here
		// get_report_data( 'report_id', 'range' )

		$report_data = [
			'number'   => 192,
			'previous' => [
				'percent'   => '25%',
				'direction' => 'up',
				'color'     => 'green'
			],
			'compare'  => 'vs. Previous 30 Days'
		];

		return self::SUCCESS_RESPONSE( [
			'report' => $report_data
		] );

	}

	/**
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_report( WP_REST_Request $request ) {

		if ( ! current_user_can( 'view_reports' ) ) {
			return self::ERROR_INVALID_PERMISSIONS();
		}

		$report = $request->get_param( 'report' );

		if ( ! $report ) {
			return self::ERROR_401( 'no_report', 'The given report does not exist.' );
		}

		$get_from_widget = $request->get_param( 'chart_format' );

		if ( ! filter_var( $get_from_widget, FILTER_VALIDATE_BOOLEAN ) ) {
			$data = Plugin::$instance->reporting->get_report( $report )->get_data();
		} else {
			// this is most definitely a hack, do better next time.
			$widgets = new Dashboard_Widgets();
			$widgets->setup_widgets();
			$widget = $widgets->get_widget( $report );
			$data   = [];

			if ( method_exists( $widget, 'get_chart_data' ) ) {
				$data = $widget->get_chart_data();
			}
		}

		$response = [
			'data' => $data,
//            'start' => [ 'U' => $report->get_start_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_start_time() ) ],
//            'end' => [ 'U' => $report->get_end_time(), 'MYSQL' => date( 'Y-m-d H:i:s', $report->get_end_time() ) ],
		];

		return self::SUCCESS_RESPONSE( $response );


	}


//	/**
//	 *
//	 *
//	 * @param WP_REST_Request $request
//	 *
//	 * @return WP_Error|WP_REST_Response
//	 */
//	public function get_report_react( WP_REST_Request $request ) {
//
//
//		$start_date = $request->get_param( 'start_date' );
//
//		if ( is_string( $start_date ) ) {
//			$start_date = strtotime( $start_date );
//		}
//
//		$end_date = $request->get_param( 'end_date' );
//
//		/**
//		 * Get extra Data for fetching the contact
//		 */
//		$data = $request->get_param( 'data' );
//
//		$report_id = $request->get_param( 'id' );
//
//
//		$reporting = new Reports( $start_date, $end_date, $data );
//
//		return self::SUCCESS_RESPONSE( $reporting->get_data( $report_id ) );
//
//	}





	/**
	 *
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_report_react( WP_REST_Request $request ) {






		$start_date = $request->get_param( 'start_date' );

		if ( is_string( $start_date ) ) {
			$start_date = strtotime( $start_date );
		}

		$end_date = $request->get_param( 'end_date' );

		/**
		 * Get extra Data for fetching the contact
		 */
		$data = $request->get_param( 'data' );

		$report_id = $request->get_param( 'id' );

		$reports = $request->get_param( 'reports' );


		$reporting = new Reports( $start_date, $end_date , $data );


		$results = [] ;
		foreach ( $reports as $report_id ) {
			$results[ $report_id ] = $reporting->get_data( $report_id );
		}

		return self::SUCCESS_RESPONSE( $results);

	}



}