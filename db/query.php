<?php

namespace Groundhogg\DB;

use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\maybe_implode_in_quotes;

class Where {

	/**
	 * List of all the clauses in this where statement, can be string or Where
	 *
	 * @var string[]|Where[]
	 */
	protected $clauses = [];

	/**
	 * How the clauses are evaluated ion relation to each other
	 *
	 * @var mixed|string
	 */
	protected $relation = 'AND';

	/**
	 * The query this Where is attached to
	 *
	 * @var Query
	 */
	protected $query;

	/**
	 * The main table for the current query
	 *
	 * @var DB
	 */
	protected $table;

	/**
	 * Whether this clause should be negated, NOT ( ... )
	 *
	 * @var false|mixed
	 */
	protected $negate = false;

	/**
	 * If this Where has no clauses
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->clauses );
	}

	/**
	 * *** MAGIC ***
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Constructor
	 *
	 * @param $query Query
	 * @param $relation
	 */
	public function __construct( $query, $relation = 'AND', $negate = false ) {
		$this->relation = $relation;
		$this->table    = $query->table;
		$this->query    = $query;
		$this->negate   = $negate;
	}

	/**
	 * Adds a clause to the list of clauses
	 *
	 * @param $clause
	 *
	 * @return $this
	 */
	public function addClause( $clause ) {

		if ( empty( $clause ) ){
			return $this;
		}

		$this->clauses[] = $clause;

		return $this;
	}

	/**
	 * Check if the column has been aliased already
	 *
	 * @param $column
	 *
	 * @return bool
	 */
	protected function isAliased( $column ) {
		return str_contains( $column, '.' );
	}

	/**
	 * Get the merge format for a specific column for $wpdb->prepare()
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return bool|mixed
	 */
	protected function getColumnFormat( $column, $value = false ) {
		$column_formats = $this->table->get_columns();

		if ( $this->isAliased( $column ) ) {
			$column = substr( $column, strpos( $column, '.' ) + 1 );
		}

		return get_array_var( $column_formats, $column, is_numeric( $value ) ? '%d' : '%s' );
	}

	/**
	 * Converts this clause and all sub clauses to a string
	 *
	 * @return string
	 */
	public function __toString() {

		if ( $this->isEmpty() ) {
			return '';
		}

		if ( count( $this->clauses ) > 1 ) {
			$clauses = '(' . implode( " $this->relation ", $this->clauses ) . ')';
		} else {
			$clauses = "{$this->clauses[0]}";
		}

		if ( $this->negate ) {
			$clauses = 'NOT ' . $clauses;
		}

		return $clauses;

	}

	/**
	 * Generic comparison wrapper for most statements
	 *
	 * @param $column
	 * @param $value
	 * @param $compare
	 *
	 * @return $this
	 */
	public function compare( $column, $value, $compare = '=' ) {

		switch ( strtoupper( $compare ) ) {
			case 'IN':
				$this->in( $column, $value );
				break;
			case 'NOT IN':
				$this->notIn( $column, $value );
				break;
			case 'LIKE':
				$this->like( $column, $value );
				break;
			case 'NOT LIKE':
				$this->notLike( $column, $value );
				break;
		}

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		if ( ! in_array( $compare, $this->table->get_allowed_comparisons() ) ) {
			return $this;
		}

		global $wpdb;

		$format = $this->getColumnFormat( $column, $value );

		$this->addClause( $wpdb->prepare( "$column $compare $format", $value ) );

		return $this;
	}

	/**
	 * a = b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function equals( $column, $value ) {
		return $this->compare( $column, $value );
	}

	/**
	 * a != b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function notEquals( $column, $value ) {
		return $this->compare( $column, $value, '!=' );
	}

	/**
	 * a <= c
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function lessThan( $column, $value ) {
		return $this->compare( $column, $value, '<' );
	}

	/**
	 * a <= b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function lessThanEqualTo( $column, $value ) {
		return $this->compare( $column, $value, '<=' );
	}

	/**
	 * a > b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function greaterThan( $column, $value ) {
		return $this->compare( $column, $value, '>' );
	}

	/**
	 * a >= b
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return $this
	 */
	public function greaterThanEqualTo( $column, $value ) {
		return $this->compare( $column, $value, '>=' );
	}

	/**
	 * IN (1,2,3)
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return $this
	 */
	public function in( $column, $values ) {

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		if ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) {
			$this->addClause( "$column IN ( $values )" );

			return $this;
		}

		$values = map_deep( $values, 'sanitize_text_field' );

		$values = map_deep( $values, 'esc_sql' );
		$values = maybe_implode_in_quotes( $values );

		$this->addClause( "$column IN ( $values )" );

		return $this;
	}

	/**
	 * NOT IN (1,2,3)
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return $this
	 */
	public function notIn( $column, $values ) {

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		if ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) {
			$this->addClause( "$column NOT IN ( $values )" );

			return $this;
		}

		$values = map_deep( $values, 'sanitize_text_field' );

		$values = map_deep( $values, 'esc_sql' );
		$values = maybe_implode_in_quotes( $values );

		$this->addClause( "$column NOT IN ( $values )" );

		return $this;
	}

	/**
	 * LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function like( $column, $string ) {
		global $wpdb;

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		$this->addClause( $wpdb->prepare( "$column LIKE %s", $string ) );

		return $this;
	}

	/**
	 * NOT LIKE %string%
	 *
	 * @param $column
	 * @param $string
	 *
	 * @return $this
	 */
	public function notLike( $column, $string ) {

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		global $wpdb;

		$this->addClause( $wpdb->prepare( "$column NOT LIKE %s", $string ) );

		return $this;
	}

	/**
	 * Compare if value is between two values
	 * value BETWEEN a AND b
	 *
	 * @param $column
	 * @param $a
	 * @param $b
	 *
	 * @return $this
	 */
	public function between( $column, $a, $b ) {

		$format = $this->getColumnFormat( $column, $a );

		if ( ! $this->isAliased( $column ) ) {
			$column = "{$this->table->alias}.$column";
		}

		global $wpdb;

		$this->addClause( $wpdb->prepare( "$column BETWEEN $format AND $format", $a, $b ) );

		return $this;
	}

	public function notEmpty( $column ){
		return $this->compare( $column, '', '!=' );
	}

	public function empty( $column ){
		return $this->compare( $column, '' );
	}

	/**
	 * Adds a sub where clause, in brackets
	 *
	 * @param $relation
	 *
	 * @return Where
	 */
	public function subWhere( $relation = 'OR' ) {
		$where = new Where( $this->query, $relation );
		$this->addClause( $where );

		return $where;
	}
}

class Query {

	/**
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * @var DB
	 */
	protected $table = '';
	protected $table_name = '';
	protected $alias = '';
	protected $select = '*';
	protected $limits = [];
	protected $offset = 0;
	protected $order = 'DESC';
	protected $orderby = '';
	protected $groupby = '';
	protected $found_rows = false;

	protected $joins = [];

	/**
	 * @var Where|null
	 */
	protected $where = null;

	/**
	 * @param $table      DB
	 */
	public function __construct( $table ) {

		if ( is_string( $table ) ) {
			$table = get_db( $table );
		}

		global $wpdb;
		$this->db         = $wpdb;
		$this->orderby    = $table->get_primary_key();
		$this->table      = $table;
		$this->table_name = $table->table_name;
		$this->alias      = $table->alias;
		$this->where      = new Where( $this );
	}

	public function __get( $name ) {
		return $this->$name;
	}

	public function setLimit( ...$limits ) {
		$this->limits = wp_parse_id_list( $limits );
	}

	public function setOffset( $offset ) {
		$this->offset = absint( $offset );
	}

	public function setGroupby( $groupby ) {
		$this->groupby = $groupby;
	}

	public function setOrderby( $orderby ) {
		$this->orderby = $orderby;
	}

	public function setOrder( $order ) {
		$order       = strtoupper( $order );
		$this->order = $order === 'ASC' ? 'ASC' : 'DESC';
	}

	public function select( ...$columns ) {

		$columns = array_map( function ( $col ) {

			if ( $this->table->has_column( $col ) ) {
				return "$this->alias.$col";
			}

			return $col;

		}, $columns );

		$this->select = implode( ', ', $columns );
	}

	public function setFoundRows( $val ) {
		$this->found_rows = boolval( $val );
	}

	protected function _select() {
		return trim( "SELECT {$this->_found_rows()} $this->select FROM {$this->_table_name()}" );
	}

	protected function _table_name() {
		return $this->table_name . ' ' . $this->alias;
	}

	protected function _alias() {
		return $this->alias ?: $this->table_name;
	}

	protected function _joins() {
		return empty( $this->joins ) ? '' : implode( ' ', $this->joins );
	}

	protected function _where() {

		if ( ! $this->where || $this->where->isEmpty() ) {
			return '';
		}

		return "WHERE $this->where";
	}

	protected function _limit() {
		$limits = implode( ', ', $this->limits );

		return ! empty( $this->limits ) ? "LIMIT $limits" : '';
	}

	protected function _offset() {
		return $this->offset ? "OFFSET $this->offset" : '';
	}

	protected function _orderby() {
		return $this->orderby ? "ORDER BY $this->orderby $this->order" : '';
	}

	protected function _groupby() {
		return $this->groupby ? "GROUP BY $this->groupby" : '';
	}


	protected function _found_rows() {
		return $this->found_rows ? "SQL_CALC_FOUND_ROWS" : '';
	}

	public function query() {
	}

	public function search( $search, $columns = [] ) {
		global $wpdb;

		$searchGroup = $this->where->subWhere( 'OR' );

		$columns = array_filter( $columns, function ( $column ) {
			return $this->table->column_is_searchable( $column );
		} );

		if ( empty( $columns ) ) {
			$columns = $this->table->get_searchable_columns();
		}

		foreach ( $columns as $column ) {
			$searchGroup->like( $column, '%' . $wpdb->esc_like( $search ) . '%' );
		}
	}

	/**
	 * @param $column
	 * @param $value
	 * @param $compare
	 *
	 * @return Where
	 */
	public function where( $column = false, $value = null, $compare = '=' ) {

		if ( ! $column ) {
			return $this->where;
		}

		if ( is_a( $column, Where::class ) ) {
			return $this->where->addClause( $column );
		}

		return $this->where->compare( $column, $value, $compare );
	}

	public function whereIn( $column, $value ) {
		return $this->where->in( $column, $value );
	}

	public function whereNotIn( $column, $value ) {
		return $this->where->notIn( $column, $value );
	}

	/**
	 * @param $table        DB
	 * @param $on           string
	 * @param $primary_on   string
	 *
	 * @return string
	 */
	public function leftJoin( $table, $on, string $primary_on = '' ) {

		if ( empty( $primary_on ) ) {
			$primary_on = $this->table->primary_key;
		}

		$alias = $table->alias;

		$join = "LEFT JOIN $table->table_name $alias ON $this->alias.$primary_on = $alias.$on";

		$i = 0;
		while ( in_array( $join, $this->joins ) ) {
			$i ++;
			$join = "LEFT JOIN $table->table_name $alias$i ON $this->alias.$primary_on = $alias$i.$on";
		}

		$this->joins[] = $join;

		if ( $i > 0 ) {
			$alias = $alias . $i;
		}

		return $alias;
	}

	/**
	 * @param $meta_table Meta_DB
	 *
	 * @return string
	 */
	public function joinMeta( $meta_table = false ) {

		if ( ! $meta_table ) {
			$meta_table = $this->table->get_meta_table();
		}

		if ( is_string( $meta_table ) ) {
			$meta_table = get_db( $meta_table );
		}

		return $this->leftJoin( $meta_table, $meta_table->get_object_id_col() );
	}

	/**
	 * Delete using the current query
	 *
	 * @return bool|int|\mysqli_result|null
	 */
	public function delete() {

		$query = [
			"DELETE {$this->alias} FROM",
			"$this->table_name as $this->alias",
			$this->_joins(),
			$this->_where(),
//			$this->_orderby(),
//			$this->_limit()
		];

		$result = $this->db->query( implode( ' ', $query ) );

		if ( $result ) {
			$this->table->cache_set_last_changed();
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
		$column_formats = $this->table->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$fields = [];

		foreach ( $data as $column => $value ) {
			$fields[] = "`$column` = {$column_formats[$column]}";
		}

		$fields = $this->db->prepare( implode( ', ', $fields ), array_values( $data ) );

		$query = [
			'UPDATE',
			$this->_table_name(),
			$this->_joins(),
			'SET',
			$fields,
			$this->_where(),
			$this->_orderby()
		];


		$result = $this->db->query( implode( ' ', $query ) );

		if ( $result ) {
			$this->table->cache_set_last_changed();
		}

		return $result;
	}

	/**
	 * Get the cache key for the current query
	 *
	 * @param $method
	 *
	 * @return string
	 */
	protected function cache_key( $method ) {
		return $method . ":" . md5( serialize( [
				$this->select,
				$this->joins,
				$this->alias,
				$this->_where(),
				$this->limits,
				$this->offset,
				$this->order,
				$this->orderby,
				$this->groupby
			] ) );
	}

	/**
	 * Get the results of the current query
	 *
	 * @return object[]
	 */
	public function get_results() {

		$cache_key = $this->cache_key( __METHOD__ );

		$cache_value = $this->table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$query = [
			$this->_select(),
			$this->_joins(),
			$this->_where(),
			$this->_groupby(),
			$this->_orderby(),
			$this->_limit(),
			$this->_offset(),
		];

		$items = $this->db->get_results( implode( ' ', $query ) );

		$this->table->cache_set( $cache_key, $items );

		return $items;
	}

	/**
	 * Get the count
	 *
	 * @return false|mixed|string|null
	 */
	public function count() {
		$this->select = "COUNT($this->select)";

		return absint( $this->get_var() );
	}

	/**
	 * Get a number
	 *
	 * @return false|mixed|string|null
	 */
	public function get_var() {

		$cache_key = $this->cache_key( __METHOD__ );

		$cache_value = $this->table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$query = [
			$this->_select(),
			$this->_joins(),
			$this->_where(),
		];

		$result = $this->db->get_var( implode( ' ', $query ) );

		$this->table->cache_set( $cache_key, $result );

		return $result;

	}

}
