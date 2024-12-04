<?php

namespace Groundhogg\DB\Query;

use Groundhogg\Base_Object;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use function Groundhogg\array_map_to_class;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;

class Table_Query extends Query {

	/**
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * @var DB
	 */
	protected $db_table;

	/**
	 * @param $table      DB|string
	 */
	public function __construct( $table, $params = [] ) {

		if ( is_string( $table ) ) {
			$table = get_db( $table );
		}

		$this->db_table = $table;

		parent::__construct( $table->table_name, $table->alias );

		if ( ! empty( $params ) ) {
			$this->set_query_params( $params );
		}
	}

	/**
	 * Parse params from an array into the query
	 *
	 * @throws FilterException
	 *
	 * @param array $params
	 *
	 * @return Table_Query
	 */
	public function set_query_params( array $params ) {

		$params = wp_parse_args( $params, [
			'search_columns' => $this->db_table->get_searchable_columns(),
		] );

		foreach ( $params as $param => $value ){
			$param = strtolower( $param );

			// handle column
			if ( $this->db_table->has_column( $param ) ){

				if ( is_array( $value ) ){
					$this->where->in( $param, $value );
				} else {
					$this->where->equals( $param, $value );
				}

				continue;
			}

			switch ( $param ){
				case 's':
				case 'search':
				case 'term':
					if ( ! empty( $value ) ){
						$this->search( $value, wp_parse_list( $params['search_columns'] ) );
					}
					break;
				case 'filters':
				case 'include_filters':
					$this->parseFilters( $value );
					break;
				case 'exclude_filters':

					$exclude_query = new Table_Query( $this->db_table );
					$exclude_query->setSelect( $this->db_table->get_primary_key() );
					$exclude_query->parseFilters( $value );

					if ( ! $exclude_query->where->isEmpty() ) {
						$this->where()->notIn( $this->db_table->get_primary_key(), "$exclude_query" );
					}

					break;
				case 'include':
					$this->whereIn( $this->db_table->get_primary_key(), $value );
					break;
				case 'exclude':
					$this->whereNotIn( $this->db_table->get_primary_key(), $value );
					break;
				case 'before':

					if ( isset_not_empty( $params, 'range' ) ) {
						break;
					}

					$this->where->lessThanEqualTo( $this->db_table->get_date_key(), $this->db_table->maybe_convert_date_format_for_query( $value ) );
					break;
				case 'after':

					if ( isset_not_empty( $params, 'range' ) ) {
						break;
					}

					$this->where->greaterThanEqualTo( $this->db_table->get_date_key(), $this->db_table->maybe_convert_date_format_for_query( $value ) );
					break;
				case 'range':

					Filters::date_filter_handler( $this->db_table->get_date_key(), [
						'date_range' => $value,
						'before'     => get_array_var( $params, 'before' ),
						'after'      => get_array_var( $params, 'after' ),
						'days'       => get_array_var( $params, 'days' ),
					], $this->where(), $this->db_table->get_date_key_format() );

					break;
			}
		}

		return parent::set_query_params( $params );
	}

	/**
	 * Wrapper for setOrderby that handles ordering by a meta field
	 *
	 * @param ...$columns
	 *
	 * @return Table_Query
	 */
	public function setOrderby( ...$columns ) {

		// Handle ordering by meta
		$columns = array_map( function ( $column ){

			if ( ! is_string( $column ) || ! str_starts_with( $column, 'meta.' ) ){
				return $column;
			}

			$meta_key = sanitize_key( substr( $column, strpos( $column, '.' ) + 1 ) );

			try {
				$alias = $this->joinMeta( $meta_key );
			} catch ( \Exception $e ){
				return $column;
			}

			return "$alias.meta_value";

		}, $columns );

		return parent::setOrderby( ...$columns );
	}

	/**
	 * Given filters, modify the query accordingly
	 *
	 * @throws FilterException
	 *
	 * @param $filters
	 *
	 * @return void
	 */
	public function parseFilters( $filters ) {
		$this->db_table->parse_filters( $filters, $this->where );
	}

	protected $sanitize_columns = true;

	protected function toggleColumnSanitization() {

	}

	/**
	 * Sanitizes what should be a column
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function sanitize_column( $maybe_column ) {

		// Standard column
		if ( $this->db_table->has_column( $maybe_column ) ) {
			return "$this->alias.$maybe_column";
		}

		return parent::sanitize_column( $maybe_column );
	}

	/**
	 * HAve access to searchable columns
	 *
	 * @param $search
	 * @param $columns
	 *
	 * @return void
	 */
	public function search( $search, $columns = [] ) {
		global $wpdb;

		$searchGroup = $this->where->subWhere( 'OR' );

		$columns = array_filter( $columns, function ( $column ) {
			return $this->db_table->column_is_searchable( $column );
		} );

		if ( empty( $columns ) ) {
			$columns = $this->db_table->get_searchable_columns();
		}

		foreach ( $columns as $column ) {
			$searchGroup->like( $column, '%' . $wpdb->esc_like( $search ) . '%' );
		}
	}

	/**
	 *
	 * @throws \Exception
	 *
	 * @param bool         $table
	 * @param string|array $on
	 *
	 * @param string       $meta_key
	 *
	 * @return string
	 */
	public function joinMeta( $meta_key = '', $table = false, $on = '' ) {

		if ( is_a( $table, Meta_DB::class ) ) {
			$table_name         = $table->table_name;
			$table_alias_prefix = $table->alias;
			$meta_id_col        = $table->get_object_id_col();
			$table_id_col       = $on ?: $this->db_table->primary_key;
		} else if ( is_string( $table ) ) {
			$table_name         = $table;
			$table_alias_prefix = str_replace( $this->db->prefix, '', $table_name );
		} else if ( is_array( $table ) && count( $table ) === 2 ) {
			$table_name         = $table[0];
			$table_alias_prefix = $table[1];
		} else {
			$meta_table         = $this->db_table->get_meta_table();
			$table_name         = $meta_table->table_name;
			$table_alias_prefix = $meta_table->alias;
			$meta_id_col        = $meta_table->get_object_id_col();
			$table_id_col       = $on ?: $this->db_table->primary_key;
		}

		if ( ! isset( $meta_id_col ) ) {
			if ( is_string( $on ) && ! empty( $on ) ) {
				$meta_id_col  = $on;
				$table_id_col = $on;
			} else if ( is_array( $on ) && count( $on ) === 2 ) {
				[ 0 => $table_id_col, 1 => $meta_id_col ] = $on;
			} else {
				$table_id_col = $this->db_table->primary_key;
				$meta_id_col  = $this->db_table->get_object_id_col();
			}
		}

		$meta_table_alias = $table_alias_prefix . '_' . $this->_sanitize_column_key( $meta_key );

		// only join once per key
		if ( key_exists( $meta_table_alias, $this->joins ) ) {
			return $meta_table_alias;
		}

		$join = $this->addJoin( 'LEFT', [ $table_name, $meta_table_alias ] );
		$join->onColumn( $meta_id_col, $table_id_col );
		$join->conditions->equals( "$meta_table_alias.meta_key", $meta_key );

		if ( empty( $this->groupby ) ){
			$this->setGroupby( 'ID' );
		}

		return $meta_table_alias;
	}


	/**
	 * Delete using the current query
	 *
	 * @return bool|int|\mysqli_result|null
	 */
	public function delete() {

		$query = [
			"DELETE {$this->alias} FROM",
			"$this->table as $this->alias",
			$this->_joins(),
			$this->_where(),
//			$this->_orderby(),
//			$this->_limit()
		];

		$result = $this->db->query( implode( ' ', $query ) );

		if ( $result ) {
			$this->db_table->cache_set_last_changed();
		}

		return $result;
	}

	/**
	 * Update using the current query
	 *
	 * @param $data
	 *
	 * @return bool|int|\mysqli_result|null
	 */
	public function update( $data ) {

		// Initialise column format array
		$column_formats = $this->db_table->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$fields = [];

		foreach ( $data as $column => $value ) {

			if ( $this->column_is_safe( $value ) ){
				$fields[] ="`$column` = $value";
			} else {
				$fields[] = $this->db->prepare( "`$column` = {$column_formats[$column]}", $value );
			}
		}

		$fields = implode( ', ', $fields );

		$query = [
			'UPDATE',
			$this->_table_name(),
			$this->_joins(),
			'SET',
			$fields,
			$this->_where(),
			$this->_orderby(),
			$this->_limit()
		];

		$result = $this->db->query( implode( ' ', $query ) );

		if ( $result ) {
			$this->db_table->cache_set_last_changed();
		}

		return $result;
	}

	public function __serialize(): array {
		return [
			$this->select,
			$this->joins,
			$this->alias,
			$this->where,
			$this->limits,
			$this->offset,
			$this->order,
			$this->orderby,
			$this->groupby
		];
	}

	/**
	 * Get the cache key for the current query
	 *
	 * @param $method
	 *
	 * @return string
	 */
	protected function create_cache_key( $method ) {
		return $method . ":" . md5( serialize( $this ) );
	}

	/**
	 * Get SQL for a select statement
	 *
	 * @return string
	 */
	public function get_select_sql() {
		return implode( ' ', [
			$this->_select(),
			$this->_joins(),
			$this->_where(),
			$this->_groupby(),
			$this->_orderby(),
			$this->_limit(),
			$this->_offset(),
		] );
	}

	public function __toString(): string {
		return $this->get_select_sql();
	}

	/**
	 * Get the results of the current query
	 *
	 * @return object[]
	 */
	public function get_results() {

		/**
		 * Before getting the results from a specific query
		 */
		do_action_ref_array( "groundhogg/{$this->db_table->get_object_type()}/pre_get_results", [ &$this ] );

		$cache_key   = $this->create_cache_key( __METHOD__ );
		$cache_value = $this->db_table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$items = $this->db->get_results( $this->get_select_sql() );

		$this->db_table->cache_set( $cache_key, $items );

		return $items;
	}

	/**
	 * Return the number of found rows for a query
	 *
	 * @return int
	 */
	public function get_found_rows(){

		$cache_key   = $this->create_cache_key( __METHOD__ );
		$cache_value = $this->db_table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$rows = (int) $this->db->get_var( 'SELECT FOUND_ROWS()' );

		$this->db_table->cache_set( $cache_key, $rows );

		return $rows;
	}

	/**
	 * Map the items to a specific class
	 * Wrapper for get_results
	 *
	 * @param string $as pass a specific class, by default uses whatever is provided by the table
	 *
	 * @return Base_Object[]
	 */
	public function get_objects( string $as = '' ) {

		$items = $this->get_results();

		// We should do this here because subsequent queries during object creation might impact
		if ( $this->found_rows ){
			$this->get_found_rows();
		}

		if ( $as && class_exists( $as ) ) {
			array_map_to_class( $items, $as );
		} else {
			$items = array_map( [ $this->db_table, 'create_object' ], $items );
		}

		return $items;
	}

	/**
	 * Get a number
	 *
	 * @return false|mixed|string|null
	 */
	public function get_var( $x = 0, $y = 0 ) {

		$cache_key = $this->create_cache_key( __METHOD__ );

		$cache_value = $this->db_table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$query = [
			$this->_select(),
			$this->_joins(),
			$this->_where(),
//			$this->_groupby(),
		];

		$result = $this->db->get_var( implode( ' ', $query ), $x, $y );

		$this->db_table->cache_set( $cache_key, $result );

		return $result;
	}

}
