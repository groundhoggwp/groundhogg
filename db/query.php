<?php

namespace Groundhogg\DB;

use function Groundhogg\array_map_to_class;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\maybe_implode_in_quotes;
use function Groundhogg\preg_quote_except;

class Where {

	/**
	 * List of all the clauses in this where statement, can be string or Where
	 *
	 * @var string[]|Where[]
	 */
	protected $conditions = [];

	/**
	 * List of having statements
	 *
	 * @var string[]
	 */
	protected $havingConditions = [];

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
	 * The parent where relation
	 *
	 * @var mixed|string
	 */
	protected $parentRelation;

	/**
	 * If this Where has no clauses
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->conditions );
	}

	public function esc_like( $stuff ) {
		return $this->query->db->esc_like( $stuff );
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
	public function __construct( $query, $relation = 'AND', $negate = false, $parentRelation = 'AND' ) {
		$this->relation       = $relation;
		$this->table          = $query->table;
		$this->query          = $query;
		$this->negate         = $negate;
		$this->parentRelation = $parentRelation;
	}

	public function addHavingCondition( $condition ) {
		if ( empty( $condition ) ) {
			return $this;
		}

		$this->havingConditions[] = $condition;

		return $this;
	}

	/**
	 * Adds a clause to the list of clauses
	 *
	 * @param $condition
	 *
	 * @return $this
	 */
	public function addCondition( $condition ) {

		if ( empty( $condition ) ) {
			return $this;
		}

		$this->conditions[] = $condition;

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
	 * Sanitize a column
	 *
	 * @param $column
	 *
	 * @return string
	 */
	protected function sanitize_column( $column ) {

		$aggregate_functions = [
			'COALESCE',
			'DATE',
		];

		$aggregate_functions_regex = implode( '\(|', $aggregate_functions ) . '\(';

		if ( preg_match( "/$aggregate_functions_regex/i", $column ) ){
			return $this->query->sanitize_aggregate_column( $column );
		}

		return $this->query->sanitize_column( $column );
	}

	/**
	 * Get the merge format for a specific column for $wpdb->prepare()
	 *
	 * @param $column
	 * @param $value
	 *
	 * @return bool|mixed
	 */
	public function getColumnFormat( $column, $value = false ) {
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

		if ( count( $this->conditions ) > 1 ) {
			$conditions = '(' . implode( " $this->relation ", $this->conditions ) . ')';
		} else {
			$conditions = "{$this->conditions[0]}";
		}

		if ( $this->negate ) {
			$conditions = 'NOT ' . $conditions;
		}

		return $conditions;
	}

	/**
	 * Generic comparison wrapper for most statements
	 *
	 * @throws \Exception
	 *
	 * @param $value
	 * @param $compare
	 *
	 * @param $column
	 *
	 * @return $this
	 */
	public function compare( $column, $value, $compare = '=' ) {

		$column  = $this->sanitize_column( $column );
		$compare = $this->table->symbolize_comparison( $compare );

		if ( ! in_array( $compare, $this->table->get_allowed_comparisons() ) ) {
			throw new \Exception( "$compare is not an allowed comparison symbol" );
		}

		switch ( strtoupper( $compare ) ) {
			case 'IN':
				return $this->in( $column, $value );
			case 'NOT IN':
				return $this->notIn( $column, $value );
			case 'LIKE':
				return $this->like( $column, $value );
			case 'NOT LIKE':
				return $this->notLike( $column, $value );
		}

		global $wpdb;

		$format = $this->getColumnFormat( $column, $value );

		$this->addCondition( $wpdb->prepare( "$column $compare $format", $value ) );

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

		$column = $this->sanitize_column( $column );

		if ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) {
			$this->addCondition( "$column IN ( $values )" );

			return $this;
		}

		$values = array_values( $values );
		$values = map_deep( $values, 'sanitize_text_field' );
		$values = map_deep( $values, 'esc_sql' );

		if ( count( $values ) === 1 ) {
			return $this->equals( $column, $values[0] );
		}

		$values = maybe_implode_in_quotes( $values );

		$this->addCondition( "$column IN ( $values )" );

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

		$column = $this->sanitize_column( $column );

		if ( is_string( $values ) && str_starts_with( $values, 'SELECT' ) ) {
			$this->addCondition( "$column NOT IN ( $values )" );

			return $this;
		}

		$values = map_deep( $values, 'sanitize_text_field' );

		$values = map_deep( $values, 'esc_sql' );

		if ( count( $values ) === 1 ) {
			return $this->notEquals( $column, $values[0] );
		}

		$values = maybe_implode_in_quotes( $values );

		$this->addCondition( "$column NOT IN ( $values )" );

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

		$column = $this->sanitize_column( $column );

		$this->addCondition( $wpdb->prepare( "$column LIKE %s", $string ) );

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

		$column = $this->sanitize_column( $column );

		global $wpdb;

		$this->addCondition( $wpdb->prepare( "$column NOT LIKE %s", $string ) );

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

		$column = $this->sanitize_column( $column );

		global $wpdb;

		$this->addCondition( $wpdb->prepare( "$column BETWEEN $format AND $format", $a, $b ) );

		return $this;
	}

	public function notEmpty( $column ) {
		return $this->compare( $column, '', '!=' );
	}

	public function empty( $column ) {
		return $this->compare( $column, '' );
	}

	/**
	 * Adds a sub where clause, in brackets
	 *
	 * @param $relation
	 *
	 * @return Where
	 */
	public function subWhere( $relation = 'OR', $negate = false ) {
		$where = new Where( $this->query, $relation, $negate, $this->relation );
		$this->addCondition( $where );

		return $where;
	}

	/**
	 * Wrapper for $wpdb->prepare
	 *
	 * @param ...$args
	 *
	 * @return string|null
	 */
	public function prepare( ...$args ) {
		return $this->query->db->prepare( ...$args );
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
	 * @param $table      DB|string
	 */
	public function __construct( $table ) {

		if ( is_string( $table ) ) {
			$table = get_db( $table );
		}

		global $wpdb;
		$this->db = $wpdb;
//		$this->orderby    = $table->get_primary_key();
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

	/**
	 * Sanitizes what should be a column
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function sanitize_column( $maybe_column ) {

		// Standard column
		if ( $this->table->has_column( $maybe_column ) ) {
			return "$this->alias.$maybe_column";
		}

		// Probably an aliased column
		if ( str_contains( $maybe_column, '.' ) ) {
			$alias  = sanitize_key( substr( $maybe_column, 0, strpos( $maybe_column, '.' ) ) );
			$column = sanitize_key( substr( $maybe_column, strpos( $maybe_column, '.' ) + 1 ) );

			return "$alias.$column";
		}

		// Should be a column
		return sanitize_key( $maybe_column );
	}

	/**
	 * Sanitizes an aggregate function like SUM(column)
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function sanitize_aggregate_column( $maybe_column ) {

		$aggregate_functions = [
			'COUNT(%s)',
			'SUM(%s)',
			'AVG(%s)',
			'COALESCE(%s)',
			'DATE(FROM_UNIXTIME(%s))'
		];

		if ( in_array( $maybe_column, [ 'COUNT(*)', 'COUNT(ID)' ] ) ){
			return $maybe_column;
		}

		foreach ( $aggregate_functions as $aggregate_function ) {

			$aggregate_function_regex = sprintf( preg_quote_except( $aggregate_function ), '([.a-z,0-9_\-]+)' );

			if ( ! preg_match( "/$aggregate_function_regex/i", $maybe_column, $matches ) ) {
				continue;
			}

			$args   = array_map( 'trim', explode( ',', $matches[1] ) );
			$column = $args[0];
			unset( $args[0] );

			$column = $this->sanitize_column( $column );

			if ( empty( $args ) ){
				return sprintf( $aggregate_function, $column );
			}

			$formats = implode( ', ', array_map( function ( $arg ){
				return is_numeric( $arg ) ? '%d' : '%s';
			}, $args ) );

			return $this->db->prepare( sprintf( $aggregate_function, $column . ', ' . $formats ), ...$args );
		}

		return $this->sanitize_column( $maybe_column );
	}

	public function setGroupby( ...$columns ) {
		$columns = array_map( function ( $col ) {

			if ( $col === '*' ) {
				return $col;
			}

			return $this->sanitize_column( $col );

		}, $columns );

		$this->groupby = implode( ', ', $columns );
	}

	public function setOrderby( $orderby ) {
		$this->orderby = $this->sanitize_column( $orderby );
	}

	public function setOrder( $order ) {
		$order       = strtoupper( $order );
		$this->order = $order === 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Select stuff!
	 *
	 * @param ...$columns
	 *
	 * @return void
	 */
	public function setSelect( ...$columns ) {

		$columns = array_map( function ( $col ) {

			if ( $col === '*' ) {
				return $col;
			}

			// Back compat for distinct keyword
			if ( is_string( $col ) && str_contains( $col, 'DISTINCT' ) ) {
				[ 1 => $column ] = explode( ' ', $col );

				return "DISTINCT " . $this->sanitize_column( $column );
			}

			// Back compat aggregates with alias
			if ( is_string( $col ) && preg_match( '/ as /i', $col ) ) {
				// Fix case
				$col = preg_replace( '/ as /i', ' as ', $col );
				[ 0 => $aggregate, 1 => $alias ] = explode( ' as ', $col );
				$aggregate = $this->sanitize_aggregate_column( $aggregate );
				$alias     = $this->sanitize_column( $alias );

				return "$aggregate as $alias";
			}

			if ( is_array( $col ) && count( $col ) == 2 ) {
				[ 0 => $aggregate, 1 => $alias ] = $col;
				$aggregate = $this->sanitize_aggregate_column( $aggregate );
				$alias     = $this->sanitize_column( $alias );

				return "$aggregate as $alias";
			}

			return $this->sanitize_aggregate_column( $col );

		}, $columns );

		$this->select = implode( ', ', $columns );
	}

	public function setFoundRows( $val ) {
		$this->found_rows = boolval( $val );
	}

	protected function _select() {
		return "SELECT {$this->_found_rows()} $this->select FROM {$this->_table_name()}";
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

		if ( $this->groupby ) {
			return "GROUP BY $this->groupby";
		}

		// multiple meta clauses, requires group by
		if ( count( array_filter( $this->joins, function ( $join ) {
				return preg_match( '/meta/', $join );
			} ) ) > 1 ) {
			return "GROUP BY {$this->alias}.{$this->table->primary_key}";
		}

		return '';
	}


	protected function _found_rows() {
		return $this->found_rows ? "SQL_CALC_FOUND_ROWS" : '';
	}

	/**
	 * Wrapper for get results
	 *
	 * @return object[]
	 */
	public function query() {
		return $this->get_results();
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
			return $this->where->addCondition( $column );
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
	 * Left join an external table that's not always a GH DB
	 *
	 * @param $table_name array|string array if also passing alias
	 * @param $on         mixed the column to join on
	 * @param $primary_on string the column of the primary table to join on
	 * @param $joinOnce   bool|string true if the table should not be joined more than once, string to only join for specific keys, for example a meta key
	 *
	 * @return string the alias of the table
	 */
	public function leftJoinExternalTable( $table_name, $on, $primary_on = '', $joinOnce = false ) {

		if ( empty( $primary_on ) ) {
			$primary_on = $this->table->primary_key;
		}

		// Alias was provided <3
		if ( is_array( $table_name ) ) {
			[ 0 => $table_name, 1 => $alias ] = $table_name;
		} else {
			// Need a table alias to make this easy to read
			$alias = str_replace( $this->db->prefix, $table_name, $table_name );
		}

		$join = "LEFT JOIN $table_name $alias ON $this->alias.$primary_on = $alias.$on";

		// Already joined this table
		if ( $joinOnce === true && in_array( $join, $this->joins ) ) {
			return $alias;
		}

		// Already joined this table for a specific key
		if ( is_string( $joinOnce ) && key_exists( $joinOnce, $this->joins ) ) {
			return $alias;
		}

		$i = 0;
		while ( in_array( $join, $this->joins ) ) {
			$i ++;
			$join = "LEFT JOIN $table_name $alias$i ON $this->alias.$primary_on = $alias$i.$on";
		}

		if ( $i > 0 ) {
			$alias = $alias . $i;
		}

		if ( is_string( $joinOnce ) ) {
			$this->joins[ $joinOnce ] = $join;
		} else {
			$this->joins[] = $join;
		}

		return $alias;
	}

	/**
	 * @param DB          $table
	 * @param mixed       $on
	 * @param string      $primary_on
	 * @param bool|string $joinOnce
	 *
	 * @return string
	 */
	public function leftJoinTable( DB $table, $on, string $primary_on = '', $joinOnce = false ) {
		return $this->leftJoinExternalTable( [ $table->table_name, $table->alias ], $on, $primary_on, $joinOnce );
	}

	protected static $metaJoinSuffix = 0;

	/**
	 *
	 * @param string       $meta_key
	 * @param bool         $table
	 * @param string|array $on
	 *
	 * @return string
	 */
	public function joinMeta( $meta_key = '', $table = false, $on = '' ) {

		if ( is_a( $table, Meta_DB::class ) ) {
			$table_name         = $table->table_name;
			$table_alias_prefix = $table->alias;
			$meta_id_col        = $table->get_object_id_col();
			$table_id_col       = $on ?: $this->table->primary_key;
		} else if ( is_string( $table ) ) {
			$table_name         = $table;
			$table_alias_prefix = str_replace( $this->db->prefix, '', $table_name );
		} else if ( is_array( $table ) && count( $table ) === 2 ) {
			$table_name         = $table[0];
			$table_alias_prefix = $table[1];
		} else {
			$meta_table         = $this->table->get_meta_table();
			$table_name         = $meta_table->table_name;
			$table_alias_prefix = $meta_table->alias;
			$meta_id_col        = $meta_table->get_object_id_col();
			$table_id_col       = $on ?: $this->table->primary_key;
		}

		if ( ! isset( $meta_id_col ) ) {
			if ( is_string( $on ) && ! empty( $on ) ) {
				$meta_id_col  = $on;
				$table_id_col = $on;
			} else if ( is_array( $on ) && count( $on ) === 2 ) {
				[ 0 => $table_id_col, 1 => $meta_id_col ] = $on;
			} else {
				$table_id_col = $this->table->primary_key;
				$meta_id_col  = $this->table->get_object_id_col();
			}
		}

		$meta_table_alias = $table_alias_prefix . '_' . $meta_key;

		// only join once per key
		if ( key_exists( $meta_table_alias, $this->joins ) ) {
			return $meta_table_alias;
		}

		$join = $this->db->prepare( "LEFT JOIN $table_name $meta_table_alias ON {$this->alias}.$table_id_col = $meta_table_alias.$meta_id_col AND $meta_table_alias.meta_key = %s", $meta_key );

		$this->joins[ $meta_table_alias ] = $join;

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

		$cache_key = $this->cache_key( __METHOD__ );

		$cache_value = $this->table->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$items = $this->db->get_results( $this->get_select_sql() );

		$this->table->cache_set( $cache_key, $items );

		return $items;
	}

	/**
	 * Map the items to a specific class
	 * Wrapper for get_results
	 *
	 * @return object[]
	 */
	public function get_objects( $as = '' ) {
		if ( ! class_exists( $as ) ) {
			return [];
		}

		$items = $this->get_results();

		array_map_to_class( $items, $as );

		return $items;
	}

	/**
	 * Get the count
	 *
	 * @return false|mixed|string|null
	 */
	public function count() {
		$this->setSelect( "COUNT(*)" );

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
