<?php

namespace Groundhogg\DB\Query;

use function Groundhogg\md5serialize;

class Query {

	/**
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * @var DB
	 */
	protected string $table = '';
	protected string $alias = '';
	protected string $select = '*';
	protected array $limits = [];
	protected int $offset = 0;
	protected string $order = 'DESC';
	protected string $orderby = '';
	protected string $groupby = '';
	protected bool $found_rows = false;

	protected array $joins = [];

	protected Where $where;

	/**
	 * @param $table      DB|string
	 */
	public function __construct( string $table, string $alias = '' ) {

		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $table;

		if ( empty( $alias ) ) {
			$alias = str_replace( $wpdb->prefix, '', $table );
		}

		$this->alias  = $alias;
		$this->where  = new Where( $this );
		$this->select = "$this->alias.*";
	}

	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Set the LIMIT
	 *
	 * @param ...$limits
	 *
	 * @return $this
	 */
	public function setLimit( ...$limits ) {
		$this->limits = array_filter( wp_parse_id_list( $limits ) );

		return $this;
	}

	/**
	 * Set the OFFSET
	 *
	 * @param $offset
	 *
	 * @return $this
	 */
	public function setOffset( $offset ) {
		$this->offset = absint( $offset );

		return $this;
	}

	/**
	 * Replace anything that can't be used as an SQL column key name
	 *
	 * @param $column
	 *
	 * @return array|string|string[]|null
	 */
	protected function _sanitize_column_key( $column ) {
		return preg_replace( '/[^A-Za-z0-9_.]/', '', $column );
	}

	/**
	 * Sanitizes what should be a column
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function sanitize_column( $maybe_column ) {

		if ( $maybe_column === '*' ) {
			return $maybe_column;
		}

		// Probably an aliased column
		if ( str_contains( $maybe_column, '.' ) ) {
			$alias  = $this->_sanitize_column_key( substr( $maybe_column, 0, strpos( $maybe_column, '.' ) ) );
			$column = $this->_sanitize_column_key( substr( $maybe_column, strpos( $maybe_column, '.' ) + 1 ) );

			return "$alias.$column";
		}

		// Should be a column
		return $this->_sanitize_column_key( $maybe_column );
	}

	/**
	 * Sanitizes an aggregate function like SUM(column)
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function maybe_sanitize_aggregate_column( $maybe_column ) {

		if ( ! preg_match( "/(COALESCE|COUNT|CAST|SUM|AVG|DATE|DISTINCT)\(/i", $maybe_column ) ) {
			return $this->sanitize_column( $maybe_column );
		}

		$column_regex = '([A-Za-z0-9_\.]+)';

		$aggregate_functions = [
			"/^COUNT\(DISTINCT\($column_regex\)\)/i"                            => function ( $matches ) {
				return sprintf( "COUNT(DISTINCT(%s))", $this->sanitize_column( $matches[1] ) );
			},
			"/^COUNT\(((?:[A-Za-z0-9_.]+)|\*)\)/i"                              => function ( $matches ) {
				return sprintf( "COUNT(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^DISTINCT\($column_regex\)/i"                                     => function ( $matches ) {
				return sprintf( "DISTINCT(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^SUM\($column_regex\)/i"                                          => function ( $matches ) {
				return sprintf( "SUM(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^AVG\($column_regex\)/i"                                          => function ( $matches ) {
				return sprintf( "AVG(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^COALESCE\($column_regex,\s*(?:'|\")?(\w+)(?:'|\")?\)/i"          => function ( $matches ) {
				$column = $this->sanitize_column( $matches[1] );
				$format = is_numeric( $matches[2] ) ? '%d' : '%s';

				return $this->db->prepare( "COALESCE($column, $format)", $matches[2] );
			},
			"/^CAST\($column_regex as (SIGNED|UNSIGNED|DATE|TIME|DATETIME)\)/i" => function ( $matches ) {
				return sprintf( "CAST(%s as %s)", $this->sanitize_column( $matches[1] ), strtoupper( $matches[2] ) );
			},
			"/^DATE\(FROM_UNIXTIME\($column_regex\)\)/i"                        => function ( $matches ) {
				return sprintf( "DATE(FROM_UNIXTIME(%s))", $this->sanitize_column( $matches[1] ) );
			},
		];

		foreach ( $aggregate_functions as $aggregate_regex => $callback ) {

			$column = preg_replace_callback( $aggregate_regex, $callback, $maybe_column, 1, $count );

			if ( $count ) {
				return $column;
			}
		}

		return $this->sanitize_column( $maybe_column );
	}

	/**
	 * Set the GROUP BY
	 *
	 * @param ...$columns
	 *
	 * @return $this
	 */
	public function setGroupby( ...$columns ) {
		$columns = array_map( function ( $col ) {
			return $this->sanitize_column( $col );
		}, $columns );

		$this->groupby = implode( ', ', $columns );

		return $this;
	}

	/**
	 * SET THE ORDER BY
	 *
	 * @param $orderby
	 *
	 * @return $this
	 */
	public function setOrderby( $orderby ) {
		$this->orderby = $this->maybe_sanitize_aggregate_column( $orderby );

		return $this;
	}

	/**
	 * Set the order to ASC or DESC
	 *
	 * @param $order
	 *
	 * @return $this
	 */
	public function setOrder( $order ) {
		$order       = strtoupper( $order );
		$this->order = $order === 'ASC' ? 'ASC' : 'DESC';

		return $this;
	}

	/**
	 * Select stuff!
	 *
	 * @param ...$columns
	 *
	 * @return $this
	 */
	public function setSelect( ...$columns ) {

		$columns = array_map( function ( $col ) {

			if ( $col === '*' ) {
				return $col;
			}

			// Back compat for distinct keyword
			if ( is_string( $col ) && str_starts_with( $col, 'DISTINCT' ) ) {
				[ 1 => $column ] = explode( ' ', $col );

				return "DISTINCT " . $this->sanitize_column( $column );
			}

			// Back compat aggregates with alias
			if ( is_string( $col ) && preg_match( '/ as /i', $col ) ) {
				// Fix case
				$col = preg_replace( '/ as /i', ' as ', $col );
				[ 0 => $aggregate, 1 => $alias ] = explode( ' as ', $col );
				$aggregate = $this->maybe_sanitize_aggregate_column( $aggregate );
				$alias     = $this->sanitize_column( $alias );

				return "$aggregate as $alias";
			}

			if ( is_array( $col ) && count( $col ) == 2 ) {
				[ 0 => $aggregate, 1 => $alias ] = $col;
				$aggregate = $this->maybe_sanitize_aggregate_column( $aggregate );
				$alias     = $this->sanitize_column( $alias );

				return "$aggregate as $alias";
			}

			return $this->maybe_sanitize_aggregate_column( $col );

		}, $columns );

		$this->select = implode( ', ', $columns );

		return $this;
	}

	/**
	 * Set SQL_FOUND_ROWS maybe
	 *
	 * @param bool $val
	 *
	 * @return $this
	 */
	public function setFoundRows( bool $val ) {
		$this->found_rows = $val;

		return $this;
	}

	protected function _select() {
		return "SELECT {$this->_found_rows()} $this->select FROM {$this->_table_name()}";
	}

	protected function _table_name() {
		return $this->table . ' ' . $this->alias;
	}

	/**
	 * Check if the column has been aliased already
	 *
	 * @param $column
	 *
	 * @return bool
	 */
	public static function isAliased( $column ) {
		return str_contains( $column, '.' );
	}

	protected function _alias() {
		return $this->alias ?: $this->table;
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
		$limits = trim( implode( ', ', $this->limits ) );

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

	/**
	 * @param $search
	 * @param $columns
	 *
	 * @return void
	 */
	public function search( $search, $columns = [] ) {

		if ( empty( $columns ) ) {
			return;
		}

		global $wpdb;

		$searchGroup = $this->where->subWhere( 'OR' );

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
	 * Add a join
	 *
	 * @throws \Exception
	 *
	 * @param $table
	 * @param $direction
	 *
	 * @return Join
	 */
	public function addJoin( $direction, $table ) {
		$join                        = new Join( $direction, $table, $this );
		$this->joins[ $join->alias ] = $join;

		return $join;
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
		return implode( ':', [
			$this->alias,
			$method,
			md5serialize( $this )
		] );
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
		do_action_ref_array( 'groundhogg/query/pre_get_results', [ $this ] );

		$cache_key   = $this->create_cache_key( __METHOD__ );
		$cache_value = wp_cache_get( $cache_key, 'groundhogg', false, $found );

		if ( $found ) {
			return $cache_value;
		}

		$items = $this->db->get_results( $this->get_select_sql() );

		wp_cache_set( $cache_key, $items, 'groundhogg' );

		return $items;
	}

	/**
	 * Get the count
	 *
	 * @return false|mixed|string|null
	 */
	public function count() {
		$this->setSelect( "COUNT(*)" );
		$this->setFoundRows( false );

		return absint( $this->get_var() );
	}

	/**
	 * Get a number
	 *
	 * @return false|mixed|string|null
	 */
	public function get_var( $x = 0, $y = 0 ) {

		$cache_key = $this->create_cache_key( __METHOD__ );

		$cache_value = wp_cache_get( $cache_key, 'groundhogg', false, $found );

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

		wp_cache_set( $cache_key, $result, 'groundhogg' );

		return $result;

	}

}