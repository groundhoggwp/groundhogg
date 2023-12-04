<?php

namespace Groundhogg\DB;

use function Groundhogg\base64_json_decode;
use function Groundhogg\day_of_week;
use function Groundhogg\get_array_var;
use function Groundhogg\maybe_swap_dates;

/**
 * Holder class for common filters
 */
class Query_Filters {


	/**
	 * Registered filters
	 *
	 * @var array[]
	 */
	protected $filters = [];

	/**
	 * Register a filter callback which will return an SQL statement
	 *
	 * @param string   $type
	 * @param callable $filter_callback
	 *
	 * @return bool
	 */
	public function register_filter( string $type, callable $filter_callback ): bool {
		if ( ! $type || ! is_callable( $filter_callback ) ) {
			return false;
		}

		$this->filters[ $type ] = [
			'type'            => $type,
			'filter_callback' => $filter_callback,
		];

		return true;
	}

	/**
	 * Parse a single filter
	 *
	 * @param $filter
	 *
	 * @return false|string
	 */
	protected function parse_filter( $filter, $query ) {

		$filter = wp_parse_args( $filter, [
			'type' => ''
		] );

		$type = $filter['type'];

		$handler = get_array_var( $this->filters, $type );

		// No filter handler available
		if ( ! $handler || ! is_callable( $handler['filter_callback'] ) ) {
			return false;
		}

		return call_user_func( $handler['filter_callback'], $filter, $query );
	}

	/**
	 * @param $filters array[]
	 * @param $query   Query
	 *
	 * @return void
	 */
	public function parse_filters( $filters, $query, $negate = false ) {

		if ( ! is_array( $filters ) ) {
			$filters = base64_json_decode( $filters );
		}

		if ( ! $filters ) {
			return;
		}

		$ors = new Where( $query, 'OR', $negate );

		// Or Group
		foreach ( $filters as $filter_group ) {

			$ands = new Where( $query, 'AND' );

			// And Group
			foreach ( $filter_group as $filter ) {
				$this->parse_filter( $filter, $ands );
			}

			$ors->addClause( $ands );
		}

		$query->where( $ors );
	}

	/**
	 * Build a standard date filter clause
	 *
	 * @param array $filter_vars
	 *
	 * @return \DateTime[]
	 */
	public static function get_before_and_after_from_date_range( $filter_vars ) {

		$filter_vars = wp_parse_args( $filter_vars, [
			'date_range' => 'any',
			'after'      => '',
			'before'     => '',
		] );

		$after  = new \DateTime( 'today', wp_timezone() );
		$before = new \DateTime( 'now', wp_timezone() );

		// Future date range
		if ( str_starts_with( $filter_vars['date_range'], 'f_' ) ) {
			$after->modify( 'now' );
			$before->modify( '+99 years' );
		}

		switch ( $filter_vars['date_range'] ) {
			default:
			case 'any':
				$after  = $after->setTimestamp( 1 );
				$before = time();
				break;
			case 'f_any':
			case 'today':
				break;
			case 'f_today':
				$before->modify( 'tomorrow' );
				break;
			case 'this_week':
				$start_of_week = day_of_week( get_option( 'start_of_week' ) );

				if ( $before->format( 'l' ) !== $start_of_week ) {
					$after->modify( sprintf( 'last %s', $start_of_week ) );
				}

				break;
			case 'f_this_week':
				$start_of_week = day_of_week( get_option( 'start_of_week' ) );

				if ( $after->format( 'l' ) !== $start_of_week ) {
					$before->modify( sprintf( 'next %s', $start_of_week ) );
				}

				break;
			case 'this_month':
				$after->modify( 'first day of this month' );
				break;
			case 'f_this_month':
				$after->modify( 'first day of next month' );
				break;
			case 'this_year':
				$after->modify( 'first day of January this year' );
				break;
			case 'f_this_year':
				$after->modify( 'first day of January next year' );
				break;
			case '24_hours':
				$after->modify( '24 hours ago' );
				break;
			case 'f_24_hours':
				$after->modify( '+24 hours' );
				break;
			case '7_days':
				$after->modify( '7 days ago' );
				break;
			case 'f_7_days':
				$after->modify( '+7 days' );
				break;
			case '30_days':
				$after->modify( '30 days ago' );
				break;
			case 'f_30_days':
				$after->modify( '+30 days' );
				break;
			case '60_days':
				$after->modify( '60 days ago' );
				break;
			case 'f_60_days':
				$after->modify( '+60 days' );
				break;
			case '90_days':
				$after->modify( '90 days ago' );
				break;
			case 'f_90_days':
				$after->modify( '+90 days' );
				break;
			case '365_days':
				$after->modify( '365 days ago' );
				break;
			case 'f_365_days':
				$after->modify( '+365 days' );
				break;
			case 'before':
				$before->modify( $filter_vars['before'] );
				$after->setTimestamp( 1 );
				break;
			case 'after':
				$after->modify( $filter_vars['after'] );
				$before->modify( 'now' );
				break;
			case 'between':
				$tbefore = $filter_vars['before'];
				$tafter  = $filter_vars['after'];

				maybe_swap_dates( $tbefore, $tafter );

				$after->modify( $tafter );
				$before->modify( $tbefore );
				break;
		}

		return [
			'before' => $before,
			'after'  => $after
		];
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $where Where
	 *
	 * @return void
	 */
	public static function mysqlDate( $column, $filter, $where ) {

		$filter = wp_parse_args( $filter, [
			'date_range' => '24_hours',
		] );

		[ 'before' => $before, 'after' => $after ] = self::get_before_and_after_from_date_range( $filter );

		switch ( $filter['date_range'] ) {
			default:
			case '24_hours':
			case '7_days':
			case '30_days':
			case '60_days':
			case '90_days':
			case '365_days':
			case 'after':
				$where->greaterThan( $column, $after->format( 'Y-m-d H:i:s' ) );
				break;
			case 'before':
				$where->lessThan( $column, $after->format( 'Y-m-d H:i:s' ) );
				break;
			case 'between':
			case 'today':
				$where->between( $column, $after->format( 'Y-m-d H:i:s' ), $before->format( 'Y-m-d H:i:s' ) );
				break;
		}
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $where Where
	 *
	 * @return void
	 */
	public static function timestamp( $column, $filter, $where ) {

		$filter = wp_parse_args( $filter, [
			'date_range' => '24_hours',
		] );

		[ 'before' => $before, 'after' => $after ] = self::get_before_and_after_from_date_range( $filter );

		switch ( $filter['date_range'] ) {
			default:
			case '24_hours':
			case '7_days':
			case '30_days':
			case '60_days':
			case '90_days':
			case '365_days':
			case 'after':
				$where->greaterThan( $column, $after->getTimestamp() );
				break;
			case 'before':
				$where->lessThan( $column, $after->getTimestamp() );
				break;
			case 'between':
			case 'today':
				$where->between( $column, $after->getTimestamp(), $before->getTimestamp() );
				break;
		}
	}

	/**
	 * Simple number comparison filter
	 *
	 * @param $column
	 * @param $filter
	 * @param Where
	 *
	 * @return void
	 */
	public static function number( $column, $filter, $where ) {

		[ 'value' => $value, 'compare' => $compare ] = wp_parse_args( $filter, [
			'compare' => '',
			'value'   => 0
		] );

		switch ( $compare ) {
			default:
			case 'equals':
				$where->equals( $column, $value );
				break;
			case 'not_equals':
				$where->notEquals( $column, $value );
				break;
			case 'less_than':
				$where->lessThan( $column, $value );
				break;
			case 'greater_than':
				$where->greaterThan( $column, $value );
				break;
			case 'greater_than_or_equal_to':
				$where->greaterThanEqualTo( $column, $value );
				break;
			case 'less_than_or_equal_to':
				$where->lessThanEqualTo( $column, $value );
				break;
		}
	}

	/**
	 * @param $column
	 * @param $filter
	 * @param $where Where
	 *
	 * @return void
	 */
	public static function string( $column, $filter, $where ) {
		global $wpdb;

		[ 'value' => $value, 'compare' => $compare ] = wp_parse_args( $filter, [
			'compare' => '',
			'value'   => 0
		] );

		$value = sanitize_text_field( $value );

		switch ( $compare ) {
			default:
			case 'equals':
			case '=':
				$where->equals( $column, $value );
				break;
			case '!=':
			case 'not_equals':
				$where->notEquals( $column, $value );
				break;
			case 'contains':
				$where->like( $column, '%' . $wpdb->esc_like( $value ) . '%' );
				break;
			case 'not_contains':
				$where->notLike( $column, '%' . $wpdb->esc_like( $value ) . '%' );
				break;
			case 'starts_with':
			case 'begins_with':
				$where->like( $column, $wpdb->esc_like( $value ) . '%' );
				break;
			case 'does_not_start_with':
				$where->notLike( $column, $wpdb->esc_like( $value ) . '%' );
				break;
			case 'ends_with':
				$where->like( $column, '%' . $wpdb->esc_like( $value ) );
				break;
			case 'does_not_end_with':
				$where->notLike( $column, '%' . $wpdb->esc_like( $value ) );
				break;
			case 'empty':
				$where->addClause( "$column = ''" );
				break;
			case 'not_empty':
				$where->addClause( "$column != ''" );
				break;
			case 'regex':
				$where->addClause( $wpdb->prepare( "$column REGEXP BINARY %s", $value ) );
				break;
			case 'less_than':
				$where->lessThan( $column, $value );
				break;
			case 'greater_than':
				$where->greaterThan( $column, $value );
				break;
			case 'greater_than_or_equal_to':
				$where->greaterThanEqualTo( $column, $value );
				break;
			case 'less_than_or_equal_to':
				$where->lessThanEqualTo( $column, $value );
				break;
		}
	}

	public function meta( $query ) {

	}
}
