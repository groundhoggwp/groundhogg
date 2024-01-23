<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact_Query;
use Groundhogg\Reports;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
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

	function get_report_data( $report ) {

		$report = wp_parse_args( $report, [
			'filters'         => [],
			'exclude_filters' => [],
		] );

		$query = new Contact_Query( [
			'filters'         => $report['filters'],
			'exclude_filters' => $report['exclude_filters']
		] );

		try {
			// Attempt to do it the modern way

			switch ( $report['type'] ) {
				case 'pie_chart':
				case 'table':

					$alias = $query->joinMeta( $report['field'] );
					$query->setSelect( [ "$alias.meta_value", 'value' ], [ 'COUNT(*)', 'total' ] );
					$query->where()->isNotNull( "$alias.meta_value" );
					$query->setGroupby( 'value' );
					$query->setOrderby( 'total' );
					$records = $query->get_results();

					foreach ( $records as &$record ) {
						$record->value = maybe_unserialize( $record->value );
						$record->count = $report['type'] === 'table' ? _nf( $record->total ) : $record->total;
					}

					return $records;

				case 'number':

					switch ( $report['value'] ) {
						case 'activity':

							$activityJoin = $query->addJoin( 'RIGHT', 'activity' );
							$activityJoin->onColumn( 'contact_id' );
							$activityJoin->conditions->equals( 'activity_type', $report['activity'] );

							return number_format_i18n( $query->count() );

						case 'distinct':

							$alias = $query->joinMeta( $report['field'] );
							$query->setSelect( [ "$alias.meta_value", 'value' ] );
							$query->setGroupby( 'value' );
							$records = $query->get_results();

							return number_format_i18n( count( $records ) );

						case 'sum':
							$alias = $query->joinMeta( $report['field'] );
							$query->setSelect( [ "SUM($alias.meta_value)", 'value' ] );
							$result = $query->get_var();

							return number_format_i18n( $result );

						case 'average':
							$alias = $query->joinMeta( $report['field'] );
							$query->setSelect( [ "AVG($alias.meta_value)", 'value' ] );
							$result = $query->get_var();

							return number_format_i18n( $result );

						default:
						case 'contacts':
							return number_format_i18n( $query->count() );
					}
			}


		} catch ( \Exception $exception ) {

			// Reset the query
			$query = new Contact_Query( [
				'filters'         => $report['filters'],
				'exclude_filters' => $report['exclude_filters']
			] );
		}

		switch ( $report['type'] ) {
			case 'pie_chart':
			case 'table':

				$query->set_query_var( 'select', 'ID' );
				$sql = $query->get_sql();

				$where = [
					[ 'meta_key', '=', $report['field'] ],
					[ 'meta_value', '!=', '' ],
				];

				if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
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
					$record->count = number_format_i18n( $record->count );
				}

				return $records;
			case 'number':

				switch ( $report['value'] ) {
					case 'activity':

						$query->set_query_var( 'select', 'ID' );
						$sql = $query->get_sql();

						$where = [
							[ 'activity_type', '=', $report['activity'] ],
						];

						if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
							$where[] = [ 'contact_id', 'IN', $sql ];
						}

						$result = get_db( 'activity' )->count( [
							'where' => $where,
						] );

						return number_format_i18n( $result );

					case 'distinct':

						$query->set_query_var( 'select', 'ID' );
						$sql = $query->get_sql();

						$where = [
							[ 'meta_key', '=', $report['field'] ],
						];

						if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
							$where[] = [ 'contact_id', 'IN', $sql ];
						}

						$result = get_db( 'contactmeta' )->count( [
							'select'   => 'meta_value',
							'distinct' => true,
							'where'    => $where,
						] );

						return number_format_i18n( $result );

					case 'sum':
					case 'average':

						$query->set_query_var( 'select', 'ID' );
						$sql = $query->get_sql();

						$where = [
							[ 'meta_key', '=', $report['field'] ],
						];

						if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
							$where[] = [ 'contact_id', 'IN', $sql ];
						}

						$result = get_db( 'contactmeta' )->query( [
							'select' => 'meta_value',
							'func'   => $report['value'] === 'sum' ? 'SUM' : 'AVG',
							'where'  => $where,
						] );

						return number_format_i18n( $result );

					default:
					case 'contacts':
						return number_format_i18n( $query->count() );

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
			$report['data'] = $this->get_report_data( $report );
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

		$report['data'] = $this->get_report_data( $report );

		return self::SUCCESS_RESPONSE( [
			'item' => $report
		] );

	}

	public function read_permissions_callback() {
		return current_user_can( 'view_reports' );
	}

	/**
	 * Verify the reporting data is valid
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
