<?php

namespace Groundhogg;

class Contact_Query_With_Filters {

	protected static $filters = [];

	public function __construct() {
		add_filter( 'groundhogg/contact_query/where_clauses', [ $this, 'parse_where_clauses' ], 10, 2 );
		add_filter( 'groundhogg/contact_query/request_join', [ $this, 'parse_request_join' ], 10, 2 );

		$this->setup_default_filters();

		do_action( 'groundhogg/contact_query_with_filters/init', $this );
	}

	/**
	 * Generate the join statement
	 *
	 * @param $join
	 * @param $query_vars
	 *
	 * @return array
	 */
	public function parse_request_join( $join, $query_vars ) {

		$filters = get_array_var( $query_vars, 'filters' );

		if ( ! $filters ) {
			return $join;
		}

		foreach ( $filters as $filter ) {

			$type = get_array_var( $filter, 'type' );

			if ( ! $type || ! $this->has_filter( $type ) ) {
				continue;
			}

			$join_request = $this->get_join_request( $type, $filter['args'] );

			// avoid joining the same table twice...
			if ( ! in_array( $join_request, $join ) ) {
				$join[] = $join_request;
			}

		}

		return $join;
	}

	/**
	 * Given the fields add the where clauses to the query
	 *
	 * @param $where      string[]
	 * @param $query_vars mixed[]
	 *
	 * @return string[]
	 */
	public function parse_where_clauses( $where, $query_vars ) {

		$filters = get_array_var( $query_vars, 'filters' );

		if ( ! $filters ) {
			return $where;
		}

		foreach ( $filters as $filter ) {

			if ( ! $this->has_filter( $filter['type'] ) ) {
				continue;
			}

			$where[ uniqid( 'filter_' ) ] = $this->get_where_clause( $filter['type'], $filter['args'] );

		}

		return $where;
	}

	public function has_filter( $filter ) {
		return isset_not_empty( self::$filters, $filter );
	}

	/**
	 * Generate the where clause for the given filter.
	 *
	 * @param string $type
	 * @param array  $args
	 *
	 * @return string
	 */
	public function get_where_clause( $type = '', $args = [] ) {

		if ( ! $this->has_filter( $type ) ) {
			return '';
		}

		return call_user_func( self::$filters[ $type ]['where'], $args, $type );
	}

	/**
	 * Generate the where clause for the given filter.
	 *
	 * @param string $type
	 * @param array  $args
	 *
	 * @return string
	 */
	public function get_join_request( $type = '', $args = [] ) {

		if ( ! $this->has_filter( $type ) || ! isset_not_empty( self::$filters[ $type ]['join'] ) ) {
			return '';
		}

		return call_user_func( self::$filters[ $type ]['join'], $args, $type );
	}

	/**
	 * Add a filter for the contact query
	 * The filter require a function to parse when given details
	 * The SQL callback will provide an SQL statement given the return value of the parse callback
	 *
	 * @param $type           string
	 * @param $where_callback
	 * @param $join_callback
	 */
	public function add_filter( $type, $where_callback, $join_callback = false ) {

		// We will not prevent filters from overriding each other...
		if ( is_string( $type ) && is_callable( $where_callback ) ) {

			self::$filters[ $type ] = [
				'type'  => $type,
				'where' => $where_callback,
				'join'  => is_callable( $join_callback ) ? $join_callback : false,
			];
		}
	}

	/**
	 * List of standard string conditions
	 *
	 * @return array
	 */
	public static function standard_string_compares() {
		return [
			'eq', // equals
			'neq', // not equals
			'sw', // starts with
			'ew', // ends with
			'cnts', // contains
			'in', // in
			'nin', // not in
			'empty', // empty
			'filled', // filled
		];
	}

	public static function standard_string_compare_callbacks() {
		return [
			'eq'     => function ( $value ) {
				return "= " . ( is_numeric( $value ) ? $value : "'{$value}'" );
			},
			'neq'    => function ( $value ) {
				return "!= " . ( is_numeric( $value ) ? $value : "'{$value}'" );
			},
			'sw'     => function ( $value ) {
				return "LIKE '{$value}%'";
			},
			'ew'     => function ( $value ) {
				return "LIKE '%{$value}'";
			},
			'cnts'   => function ( $value ) {
				return "LIKE '%{$value}%'";
			},
			'in'     => function ( $values ) {
				return "IN (" . ( is_array( $values ) ? implode( ',', array_map( function ( $value ) {
					return is_numeric( $value ) ? $value : "'{$value}'";
				}, $values ) ) : "{$values}" );
			},
			'nin'    => function ( $values ) {
				return "NOT IN (" . ( is_array( $values ) ? implode( ',', array_map( function ( $value ) {
					return is_numeric( $value ) ? $value : "'{$value}'";
				}, $values ) ) : "{$values}" );
			},
			'empty'  => function ( $value ) {
				return "= ''";
			},
			'filled' => function ( $value ) {
				return "!= ''";
			},
		];
	}

	/**
	 * Get build the SQL query
	 *
	 * @param $value
	 * @param $compare
	 *
	 * @return mixed
	 */
	public static function construct_standard_string_compare( $value, $compare ) {
		return call_user_func( self::standard_string_compare_callbacks()[ $compare ], $value );
	}

	/**
	 * Parse standard string filters
	 * Use for:
	 *  - First
	 *  - Last
	 *  - Email
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function parse_standard_string_args_callback( $args = [] ) {
		global $wpdb;

		$args['compare'] = in_array( $args['compare'], self::standard_string_compares() ) ? $args['compare'] : 'eq';
		$args['value']   = $wpdb->esc_like( sanitize_text_field( $args['value'] ) );

		return $args;
	}

	/**
	 * Standard function which will return an sql statement to filter contacts
	 * based on a
	 *
	 * @param array  $args
	 * @param string $type the type which was mapped to this code.
	 *
	 * @return string
	 */
	public function standard_string_filter_callback( $args = [], $type = '' ) {

		$args = $this->parse_standard_string_args_callback( $args );

		$type_to_field_map = [
			'first'         => 'first_name',
			'first_name'    => 'first_name',
			'last'          => 'last_name',
			'last_name'     => 'last_name',
			'email'         => 'email',
			'email_address' => 'email',
		];

		$sql = "`{$type_to_field_map[$type]}` " . self::construct_standard_string_compare( $args['value'], $args['compare'] );

//		var_dump( $sql );

		return $sql;
	}

	protected function setup_default_filters() {

		$filters = [
			[
				'type'  => 'first',
				'where' => [ $this, 'standard_string_filter_callback' ]
			],
			[
				'type'  => 'last',
				'where' => [ $this, 'standard_string_filter_callback' ]
			],
			[
				'type'  => 'email',
				'where' => [ $this, 'standard_string_filter_callback' ]
			]
		];

		foreach ( $filters as $filter ) {
			$this->add_filter( $filter['type'], $filter['where'], get_array_var( $filters, 'join', false ) );
		}

	}

}
