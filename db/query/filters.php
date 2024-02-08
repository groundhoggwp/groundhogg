<?php

namespace Groundhogg\DB\Query;

use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\base64_json_decode;
use function Groundhogg\day_of_week;
use function Groundhogg\get_array_var;
use function Groundhogg\isset_not_empty;
use function Groundhogg\maybe_swap_dates;

class FilterException extends \Exception {

}

/**
 * Holder class for common filters
 */
class Filters {

	/**
	 * Registered filters
	 *
	 * @var array[]
	 */
	protected $filters = [];

	/**
	 * Register a filter callback which will modify the current query
	 *
	 * @param string   $type
	 * @param callable $filter_callback function that modifies the query
	 *
	 * @return bool
	 */
	public function register( string $type, callable $filter_callback ): bool {
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
	 * @throws FilterException
	 *
	 * @param Where $where
	 *
	 * @param array $filter
	 *
	 * @return false|string
	 */
	protected function parse_filter( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'type' => ''
		] );

		$type = $filter['type'];

		$handler = get_array_var( $this->filters, $type );

		// No filter handler available
		if ( ! $handler ) {
			throw new FilterException( sprintf( "%s is not a registered filter", $type ) );
		}

		if ( ! is_callable( $handler['filter_callback'] ) ) {
			throw new FilterException( sprintf( "%s does not have a valid callback", $type ) );
		}

		return call_user_func( $handler['filter_callback'], $filter, $where, $where->query );
	}

	/**
	 * Parse a given filter set based on the registered filters
	 *
	 * @throws FilterException
	 *
	 * @param Where          $where
	 * @param bool           $negate  Whether this is NOT IN or IN
	 *
	 * @param array[]|string $filters could be base 64 json encoded
	 *
	 * @return void
	 */
	public function parse_filters( $filters, Where $where, bool $negate = false ) {

		if ( ! is_array( $filters ) ) {
			$filters = base64_json_decode( $filters );
		}

		if ( ! $filters ) {
			return;
		}

		$ors = $where->subWhere( 'OR', $negate );

		// Or Group
		foreach ( $filters as $filter_group ) {

			$ands = $ors->subWhere( 'AND' );

			// And Group
			foreach ( $filter_group as $filter ) {
				$this->parse_filter( $filter, $ands );
			}
		}
	}

	/**
	 * Given a date range, create a before & and after
	 *
	 * @param array $filter
	 *
	 * @return DateTimeHelper[]
	 */
	public static function get_before_and_after_from_date_range( $filter, $format = false ) {

		$filter = wp_parse_args( $filter, [
			'date_range' => 'any',
			'after'      => '', // typically Y-m-d formatted string
			'before'     => '', // typically Y-m-d formatted string
		] );

		$after  = new DateTimeHelper(); // now
		$before = new DateTimeHelper(); // now

		switch ( $filter['date_range'] ) {
			default:
			case 'any':
				// what to do here?
				$after->setTimestamp( 0 );
				$before->modify( '+99 years' );
				break;
			case 'today':
				$after->modify( 'today 00:00:00' );
				$before->modify( 'today 23:59:59' );
				break;
			case 'yesterday':
				$after->modify( 'yesterday 00:00:00' );
				$before->modify( 'yesterday 23:59:59' );
				break;
			case 'tomorrow':
				$after->modify( 'tomorrow 00:00:00' );
				$before->modify( 'tomorrow 23:59:59' );
				break;
			case 'this_week':
				$start_of_week = day_of_week( get_option( 'start_of_week' ) );

				if ( $after->format( 'l' ) !== $start_of_week ) {
					$after->modify( sprintf( 'last %s 00:00:00', $start_of_week ) );
				}

				$before = ( clone $after )->modify( '+7 days 23:59:59' );

				break;

			case 'this_month':
				$after->modify( 'first day of this month 00:00:00' );
				$before->modify( 'last day of this month 23:59:59' );
				break;
			case 'this_year':
				$after->modify( 'first day of January this year 00:00:00' );
				$before->modify( 'last day of December this year 23:59:59' );
				break;
			case '24_hours':
				$after->modify( '24 hours ago' );
				break;
			case 'next_24_hours':
				$after->modify( 'now' );
				$before->modify( '+24 hours' );
				break;
			case '7_days':
				$after->modify( '7 days ago' );
				break;
			case 'next_7_days':
				$after->modify( 'now' );
				$before->modify( '+ 7 days' );
				break;
			case '14_days':
				$after->modify( '14 days ago' );
				break;
			case 'next_14_days':
				$before->modify( '+14 days' );
				break;
			case '30_days':
				$after->modify( '30 days ago' );
				break;
			case 'next_30_days':
				$before->modify( '+30 days' );
				break;
			case '60_days':
				$after->modify( '60 days ago' );
				break;
			case 'next_60_days':
				$before->modify( '+60 days' );
				break;
			case '90_days':
				$after->modify( '90 days ago' );
				break;
			case 'next_90_days':
				$before->modify( '+90 days' );
				break;
			case '365_days':
				$after->modify( '365 days ago' );
				break;
			case 'next_365_days':
				$before->modify( '+365 days' );
				break;
			case 'before':
				$after->setTimestamp( 0 );
				$before->modify( $filter['before'] );

				// todo maybe set to EOD?
				break;
			case 'after':
				$after->modify( $filter['after'] );
				$after->modify( '00:00:00' );
				$before->modify( '+99 years' );
				break;
			case 'between':
				$before = new DateTimeHelper( $filter['before'] );
				// set before time to EOD
				$before->modify( '23:59:59' );

				$after  = new DateTimeHelper( $filter['after'] );
				// Set to SOD
				$after->modify( '00:00:00' );
				break;
		}

		if ( $format ) {
			switch ( $format ) {
				case 'mysql':
				case 'ymdhis':
					return [
						'before' => $before->ymdhis(),
						'after'  => $after->ymdhis()
					];
				case 'date':
				case 'ymd':
					return [
						'before' => $before->ymd(),
						'after'  => $after->ymd()
					];
				case 'unix':
				case 'timestamp':
					return [
						'before' => $before->getTimestamp(),
						'after'  => $after->getTimestamp()
					];
				default:
					if ( method_exists( $before, $format ) ) {
						return [
							'before' => call_user_func( [ $before, $format ] ),
							'after'  => call_user_func( [ $after, $format ] ),
						];
					}

					return [
						'before' => $before->format( $format ),
						'after'  => $after->format( $format )
					];
			}
		}

		return [
			'before' => $before,
			'after'  => $after
		];
	}

	/**
	 * Handler for date related query filter clauses
	 *
	 * @param string          $column the table column
	 * @param array           $filter the filter args
	 * @param Where           $where
	 * @param string|callable $format a callback for DateTimeHelper or a custom format string
	 *
	 * @return void
	 */
	public static function date_filter_handler( string $column, array $filter, Where $where, $format = '' ) {

		if ( empty( $format ) ) {
			$format = 'ymdhis';
		}

		$filter = wp_parse_args( $filter, [
			'date_range' => 'any',
		] );

		if ( $filter['date_range'] === 'any' ){
			return;
		}

		try {

			[ 'before' => $before, 'after' => $after ] = self::get_before_and_after_from_date_range( $filter );

			if ( method_exists( $before, $format ) ) {
				$before = call_user_func( [ $before, $format ] );
				$after  = call_user_func( [ $after, $format ] );
			} else {
				$before = $before->format( $format );
				$after  = $after->format( $format );
			}
		} catch ( \Exception $exception ) {
			return;
		}

		switch ( $filter['date_range'] ) {
			default:
			case '24_hours':
			case '7_days':
			case '14_days':
			case '30_days':
			case '60_days':
			case '90_days':
			case '365_days':
			case 'next_24_hours':
			case 'next_7_days':
			case 'next_14_days':
			case 'next_30_days':
			case 'next_60_days':
			case 'next_90_days':
			case 'next_365_days':
			case 'today':
			case 'yesterday':
			case 'tomorrow':
			case 'this_week':
			case 'this_month':
			case 'this_year':
			case 'between':
				$where->between( $column, $after, $before );
				break;
			case 'before':
				$where->lessThan( $column, $after );
				break;
			case 'after':
				$where->greaterThan( $column, $after );
				break;
		}
	}

	/**
	 *  Formats before and after as Y-m-d H:i:s
	 *
	 * @param string $column
	 * @param array  $filter
	 * @param Where  $where
	 *
	 * @return void
	 */
	public static function mysqlDateTime( string $column, array $filter, Where $where ) {
		self::date_filter_handler( $column, $filter, $where, 'ymdhis' );
	}

	/**
	 * Formats before and after as Y-m-d
	 *
	 * @param $column string
	 * @param $filter array
	 * @param $where  Where
	 *
	 * @return void
	 */
	public static function mysqlDate( string $column, array $filter, Where $where ) {
		self::date_filter_handler( $column, $filter, $where, 'ymd' );
	}


	/**
	 * @param string $column
	 * @param array  $filter
	 * @param Where  $where
	 *
	 * @return void
	 */
	public static function timestamp( string $column, array $filter, Where $where ) {
		self::date_filter_handler( $column, $filter, $where, 'getTimestamp' );
	}

	/**
	 * Simple number comparison filter
	 *
	 * @param $column
	 * @param $filter
	 * @param $where Where
	 *
	 * @return void
	 */
	public static function number( $column, $filter, Where $where ) {

		[ 'value' => $value, 'compare' => $compare ] = wp_parse_args( $filter, [
			'compare' => '',
			'value'   => 0
		] );

		// Convert to float or int to be on the safe side
		if ( is_string( $value ) ){
			if ( str_contains( $value, ',' ) ){
				$value = floatval( $value );
			} else {
				$value = intval( $value );
			}
		}

		if ( is_float( $value ) ){
			$where->setColumnFormat( $column, '%f' );
		} else {
			$where->setColumnFormat( $column, '%d' );
		}

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
	 * Filter by meta
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function meta_filter( $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'meta' => ''
		] );

		if ( empty( $filter['meta'] ) ) {
			return;
		}

		$alias = $where->query->joinMeta( sanitize_key( $filter['meta'] ) );
		Filters::string( "$alias.meta_value", $filter, $where );
	}

	/**
	 * Will check if the custom field is one of the supplied options
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function is_one_of_filter( $column, $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'compare' => 'in',
			'options' => []
		] );

		if ( empty( $filter['options'] ) ) {
			return;
		}

		if ( $filter['compare'] === 'in' ) {
			$where->in( $column, $filter['options'] );
		} else {
			$where->notIn( $column, $filter['options'] );
		}
	}

	/**
	 * Custom field checkboxes and multi-selects have selected options stored as a serialized array
	 * This will see if all the options are selected within that serialized array
	 *
	 * @param       $filter
	 * @param Where $where
	 *
	 * @return void
	 */
	public static function custom_field_has_all_selected( $column, $filter, Where $where ) {

		$filter = wp_parse_args( $filter, [
			'compare' => 'all_in',
			'options' => []
		] );

		if ( empty( $filter['options'] ) ) {
			return;
		}

		switch ( $filter['compare'] ) {
			default:
			case 'all_checked':
			case 'all_in':
				foreach ( $filter['options'] as $option ) {
					$where->like( $column, '%"' . $where->query->db->esc_like( $option ) . '"%' );
				}
				break;
			case 'not_checked':
			case 'all_not_in':
				foreach ( $filter['options'] as $option ) {
					$where->notLike( $column, '%"' . $where->query->db->esc_like( $option ) . '"%' );
				}
				break;
		}
	}

	/**
	 * Do a string comparison
	 *
	 * @param $column
	 * @param $filter
	 * @param $where Where
	 *
	 * @return void
	 */
	public static function string( $column, $filter, Where $where ) {
		global $wpdb;

		[ 'value' => $value, 'compare' => $compare ] = wp_parse_args( $filter, [
			'compare' => '',
			'value'   => 0
		] );

		$value = sanitize_text_field( $value );

		$where->setColumnFormat( $column, '%s' );

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
				$where->addCondition( "$column = ''" );
				break;
			case 'not_empty':
				$where->addCondition( "$column != ''" );
				break;
			case 'regex':
				$where->addCondition( $wpdb->prepare( "$column REGEXP BINARY %s", $value ) );
				break;
			case 'less_than':
			case '<':
				$where->lessThan( $column, $value );
				break;
			case 'greater_than':
			case '>':
				$where->greaterThan( $column, $value );
				break;
			case 'greater_than_or_equal_to':
			case '>=':
				$where->greaterThanEqualTo( $column, $value );
				break;
			case 'less_than_or_equal_to':
			case '<=':
				$where->lessThanEqualTo( $column, $value );
				break;
		}
	}

	/**
	 * Handle the filter for a custom field
	 *
	 * @param       $filter
	 * @param Where $where
	 * @param       $field
	 *
	 * @return void
	 */
	public static function custom_field_filter_handler( $filter, Where $where, $field ) {
		// Use most recent available key?
		$meta_key       = $field['name'];
		$filter['meta'] = $meta_key;

		$alias             = $where->query->joinMeta( $meta_key );
		$meta_value_column = "$alias.meta_value";

		switch ( $field['type'] ) {
			default:
			case 'text':
			case 'textarea':
			case 'url':
			case 'tel':
			case 'custom_email':
			case 'html':
				self::string( $meta_value_column, $filter, $where );
				break;
			case 'number':
				self::number( "CAST($meta_value_column as UNSIGNED)", $filter, $where );
				break;
			case 'date':
				self::mysqlDate( "CAST($meta_value_column as DATE)", $filter, $where );
				break;
			case 'datetime':
				self::mysqlDateTime( "CAST($meta_value_column as DATETIME)", $filter, $where );
				break;
			case 'time':
				// todo this is wrong
				self::mysqlDateTime( "CAST($meta_value_column as TIME)", $filter, $where );
				break;
			case 'radio':
				self::is_one_of_filter( $meta_value_column, $filter, $where );
				break;
			case 'checkboxes':
				self::custom_field_has_all_selected( $meta_value_column, $filter, $where );
				break;
			case 'dropdown':
				if ( isset_not_empty( $field, 'multiple' ) ) {
					self::custom_field_has_all_selected( $meta_value_column, $filter, $where );
				} else {
					self::is_one_of_filter( $meta_value_column, $filter, $where );
				}
				break;
		}
	}

	/**
	 * Automatically registers fields given properties
	 *
	 * @param array $fields
	 *
	 * @return void
	 */
	public function register_from_properties( array $fields ) {

		foreach ( $fields as $field ) {
			$this->register( $field['id'], function ( $filter, Where $where ) use ( $field ) {
				self::custom_field_filter_handler( $filter, $where, $field );
			} );
		}

	}
}
