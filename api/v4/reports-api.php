<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Reports;
use Groundhogg\Utils\DateTimeHelper;
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

	/**
	 * Get custom report data for a singel custom report
	 *
	 * @throws \Groundhogg\DB\Query\FilterException
	 *
	 * @param $report
	 *
	 * @return array|array[]|bool|int|object|object[]|string|null
	 */
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
					$query->setOrderby( [ 'total', 'DESC' ] );

					$setGroupby = function ( Contact_Query &$query ) {
						$query->setGroupby( 'value' );
					};

					add_action( 'groundhogg/contact_query/pre_get_contacts', $setGroupby );

					$records = $query->get_results();

					remove_action( 'groundhogg/contact_query/pre_get_contacts', $setGroupby );

					foreach ( $records as &$record ) {
						$record->value = maybe_unserialize( $record->value ) ?: 'Empty';
						$record->count = $report['type'] === 'table' ? _nf( $record->total ) : $record->total;
					}

					return $records;

				case 'number':

					$report_type = sanitize_text_field( $report['value'] );
					$meta_key    = sanitize_key( $report['field'] );

					switch ( $report_type ) {
						case 'activity':
						case 'activity_sum_value':
						case 'activity_avg_value':

							$activityJoin = $query->addJoin( 'RIGHT', 'activity' );
							$activityJoin->onColumn( 'contact_id' );
							$activityJoin->conditions->equals( 'activity_type', sanitize_key( $report['activity'] ) );

							if ( $report_type === 'activity_sum_value' ) {
								$query->setSelect( [ "SUM({$activityJoin->alias}.value)", 'value' ] );

								return number_format_i18n( $query->get_var() );
							}

							if ( $report_type === 'activity_avg_value' ) {
								$query->setSelect( [ "AVG({$activityJoin->alias}.value)", 'value' ] );

								return number_format_i18n( $query->get_var() );
							}

							return number_format_i18n( $query->count() );

						case 'distinct':

							$alias = $query->joinMeta( $meta_key );
							$query->setSelect( [ "COUNT(DISTINCT($alias.meta_value))", 'unique_values' ] );
							$query->where()->isNotNull( "$alias.meta_value" );

							return number_format_i18n( $query->get_var() );

						case 'sum':
							$alias = $query->joinMeta( $meta_key );
							$query->setSelect( [ "SUM($alias.meta_value)", 'value' ] );
							$query->where()->isNotNull( "$alias.meta_value" );

							return number_format_i18n( $query->get_var() );

						case 'average':
							$alias = $query->joinMeta( $meta_key );
							$query->setSelect( [ "AVG($alias.meta_value)", 'value' ] );
							$query->where()->isNotNull( "$alias.meta_value" );
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

				$report_type = sanitize_text_field( $report['value'] );
				$meta_key    = sanitize_key( $report['field'] );

				switch ( $report_type ) {
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

						$metaQuery = new Table_Query( 'contactmeta' );
						$metaQuery->setSelect( 'COUNT(DISTINCT(meta_value))' );
						$metaQuery->where( 'meta_key', $meta_key )
						          ->isNotNull( 'meta_value' );
//						          ->notEmpty( 'meta_value' );

						if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
							$metaQuery->whereIn( 'contact_id', $sql );
						}

						return number_format_i18n( $metaQuery->get_var() );

					case 'sum':
					case 'average':

						$query->set_query_var( 'select', 'ID' );
						$sql = $query->get_sql();

						$where = [
							[ 'meta_key', '=', $meta_key ],
						];

						if ( ! empty( $report['filters'] ) || ! empty( $report['exclude_filters'] ) ) {
							$where[] = [ 'contact_id', 'IN', $sql ];
						}

						$result = get_db( 'contactmeta' )->query( [
							'select' => 'meta_value',
							'func'   => $report_type === 'sum' ? 'SUM' : 'AVG',
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
	 * Get the results of all custom reports
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
	 * Get the results of a single custom report
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

		$start = ( new DateTimeHelper( $request->get_param( 'after' ) ?: '7 days ago' ) )->modify( '00:00:00' );
		$end   = ( new DateTimeHelper( $request->get_param( 'before' ) ?: 'yesterday' ) )->modify( '23:59:59' );

		$params  = $request->get_param( 'params' );
		$reports = map_deep( $request->get_param( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start->getTimestamp(), $end->getTimestamp(), $params );

		if ( empty( $reports ) ) {
			return self::ERROR_404( 'error', 'report not found' );
		}

		$results = [];

		foreach ( $reports as $report_id ) {
			$results[ $report_id ] = $reporting->get_data( $report_id );
		}

		return self::SUCCESS_RESPONSE( [
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

		$start = ( new DateTimeHelper( $request->get_param( 'after' ) ?: '7 days ago' ) )->modify( '00:00:00' );
		$end   = ( new DateTimeHelper( $request->get_param( 'before' ) ?: 'yesterday' ) )->modify( '23:59:59' );

		$params    = $request->get_param( 'params' ) ?: [];
		$report    = sanitize_key( $request->get_param( 'id' ) );
		$reporting = new Reports( $start->getTimestamp(), $end->getTimestamp(), $params );

		$results = $reporting->get_data( $report );

		return self::SUCCESS_RESPONSE( [
			'report' => $results
		] );
	}

}
