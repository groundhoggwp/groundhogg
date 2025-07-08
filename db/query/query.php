<?php

namespace Groundhogg\DB\Query;

use function Groundhogg\md5serialize;

class Query {

	/**
	 * @var \wpdb
	 */
	protected $db;

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
	 * @param $table string
	 */
	public function __construct( $table, string $alias = '' ) {

		global $wpdb;
		$this->db = $wpdb;

		$this->table = is_a( $table, Query::class ) ? "($table)" : $table;

		if ( empty( $alias ) ) {
			$alias = str_replace( $wpdb->prefix, '', $table );
		}

		$this->alias  = $alias;
		$this->where  = new Where( $this );
		$this->select = "$this->alias.*";
	}

	/**
	 * Adds the DB prefix to a given table name if not present
	 *
	 * @param string $table_suffix
	 *
	 * @return string
	 */
	public static function maybe_prefix( string $table_suffix ) {
		global $wpdb;

		//  Shared tables in  multisite environment
		$sharedTables = [
			'users'    => $wpdb->users,
			'usermeta' => $wpdb->usermeta,
		];

		if ( array_key_exists( $table_suffix, $sharedTables ) ) {
			return $sharedTables[ $table_suffix ];
		}

		return $wpdb->prefix . $table_suffix;
	}

	/**
	 * Set query params from an array format
	 *
	 * @param array $params
	 *
	 * @return Query
	 */
	public function set_query_params( array $params ) {

		foreach ( $params as $param => $value ) {
			$param = strtolower( $param );
			switch ( $param ) {
				case 'select':
					if ( ! is_array( $value ) ) {
						$value = array_map( 'trim', explode( ',', $value ) );
					}

					$this->setSelect( ...$value );
					break;
				case 'limit':
					if ( is_array( $value ) ) {
						$this->setLimit( ...$value );
					} else {
						$this->setLimit( $value );
					}
					break;
				case 'orderby':
				case 'order_by':
					if ( is_array( $value ) ) {
						$this->setOrderby( ...$value );
					} else {
						$this->setOrderby( $value );
					}
					break;
				case 'order':
					$this->setOrder( $value );
					break;
				case 'offset':
					$this->setOffset( $value );
					break;
				case 'found_rows':
					$this->setFoundRows( $value );
					break;
			}
		}

		return $this;

	}

	public function __get( $name ) {
		return $this->$name;
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
	public function sanitize_column( string $maybe_column ) {

		if ( $this->column_is_safe( $maybe_column ) ) {
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

	protected array $safe_columns = [
		'*'        => true,
		'COUNT(*)' => true,
		'RAND()'   => true,
	];

	/**
	 * Support explicitly defining safe column formats
	 *
	 * @param string $col
	 *
	 * @return $this
	 */
	public function add_safe_column( string $col ) {
		$this->safe_columns[ $col ] = true;

		return $this;
	}

	/**
	 * Test whether a column string is safe to use in the query
	 *
	 * @param string $col
	 *
	 * @return bool
	 */
	public function column_is_safe( string $col ) {
		return key_exists( $col, $this->safe_columns ) && $this->safe_columns[ $col ];
	}

	/**
	 * Sanitizes an aggregate function like SUM(column)
	 *
	 * @param $maybe_column
	 *
	 * @return string
	 */
	public function maybe_sanitize_aggregate_column( string $maybe_column ) {

		if ( $this->column_is_safe( $maybe_column ) ) {
			return $maybe_column;
		}

		if ( ! preg_match( "/(COALESCE|COUNT|CAST|SUM|AVG|DATE|DISTINCT|LOWER|UPPER)\(/i", $maybe_column ) ) {
			return $this->sanitize_column( $maybe_column );
		}

		$column_regex = '([A-Za-z0-9_\.]+)';

		$aggregate_functions = [
			"/^COUNT\(DISTINCT\($column_regex\)\)/i"                                                 => function ( $matches ) {
				return sprintf( "COUNT(DISTINCT(%s))", $this->sanitize_column( $matches[1] ) );
			},
			"/^COUNT\((?:$column_regex\.\*)\)/i"                                                     => function ( $matches ) {
				return "COUNT(*)";
			},
			"/^COUNT\($column_regex\)/i"                                                             => function ( $matches ) {
				return sprintf( "COUNT(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^COUNT\(\*\)/i"                                                                        => function ( $matches ) {
				return "COUNT(*)";
			},
			"/^DISTINCT\($column_regex\)/i"                                                          => function ( $matches ) {
				return sprintf( "DISTINCT(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^SUM\($column_regex\)/i"                                                               => function ( $matches ) {
				return sprintf( "SUM(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^AVG\($column_regex\)/i"                                                               => function ( $matches ) {
				return sprintf( "AVG(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^MAX\($column_regex\)/i"                                                               => function ( $matches ) {
				return sprintf( "MAX(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^LOWER\($column_regex\)/i"                                                             => function ( $matches ) {
				return sprintf( "LOWER(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^UPPER\($column_regex\)/i"                                                             => function ( $matches ) {
				return sprintf( "UPPER(%s)", $this->sanitize_column( $matches[1] ) );
			},
			"/^COALESCE\($column_regex,\s*(?:'|\")?(\w*)(?:'|\")?\)/i"                               => function ( $matches ) {
				$column = $this->sanitize_column( $matches[1] );
				$format = is_numeric( $matches[2] ) ? '%d' : '%s';

				return $this->db->prepare( "COALESCE($column, $format)", $matches[2] );
			},
			"/^DATE_FORMAT\($column_regex,\s*(?:'|\")?([^'\"]+)(?:'|\")?\)/i"                        => function ( $matches ) {
				$column = $this->sanitize_column( $matches[1] );
				$format = is_numeric( $matches[2] ) ? '%d' : '%s';

				return $this->db->prepare( "DATE_FORMAT($column, $format)", $matches[2] );
			},
			"/^CAST\($column_regex as (SIGNED|UNSIGNED|DATE|TIME|INT|DATETIME|DECIMAL\([^)]+\))\)/i" => function ( $matches ) {
				return sprintf( "CAST(%s as %s)", $this->sanitize_column( $matches[1] ), strtoupper( $matches[2] ) );
			},
			"/^DATE\(FROM_UNIXTIME\($column_regex\)\)/i"                                             => function ( $matches ) {
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
	 * @param mixed ...$columns
	 *
	 * @return $this
	 */
	public function setOrderby( ...$columns ) {

		// Basic usage
		if ( count( $columns ) === 1 && is_string( $columns[0] ) ) {
			$this->orderby = $this->maybe_sanitize_aggregate_column( $columns[0] );

			return $this;
		}

		// Columns passed
		$columns = array_map( function ( $col ) {

			// column and order
			if ( is_array( $col ) && count( $col ) === 2 ) {
				[ 0 => $column, 1 => $order ] = $col;

				$column = $this->maybe_sanitize_aggregate_column( $column );
				$order  = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

				return "$column $order";
			}

			return trim( "{$this->maybe_sanitize_aggregate_column( $col )} {$this->order}" );
		}, $columns );

		$this->orderby = implode( ', ', $columns );
		$this->order   = ''; // set the order to '' because we're storing it in orderby now

		return $this;
	}

	/**
	 * Set the order to ASC or DESC
	 *
	 * @deprecated use setOrderby with an array instead
	 *
	 * @param $order
	 *
	 * @return $this
	 */
	public function setOrder( $order ) {

		// Prevent setting the order when complex orderby is used
		if ( empty( $order ) || count( explode( ',', $this->orderby ) ) > 1 ) {
			$this->order = '';

			return $this;
		}

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

				return "DISTINCT(" . $this->sanitize_column( $column ) . ')';
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
	 * @param string $column the column
	 * @param Query $query the associated query
	 *
	 * @return bool
	 */
	public static function isAliased( $column, $query = null ) {

		$aliasPrefix = '.';

		if ( $query instanceof Query ) {
			$aliasPrefix = $query->alias . $aliasPrefix;
		}

		return str_contains( $column, $aliasPrefix );
	}

	/**
	 * If a column is already aliased, return the column
	 * Otherwise add the alias
	 *
	 * @param $column string
	 *
	 * @return string
	 */
	public function maybePrefixAlias( $column ) {
		if ( self::isAliased( $column, $this ) ) {
			return $column;
		}

		return "$this->alias.$column";
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

	/**
	 * ::$order will be empty if setOrderby() was used and provided a complex order by clause
	 *
	 * @return string
	 */
	protected function _orderby() {
		$this->orderby = trim( $this->orderby );

		return ! empty( $this->orderby ) ? "ORDER BY $this->orderby $this->order" : '';
	}

	protected function _groupby() {
		return ! empty( $this->groupby ) ? "GROUP BY $this->groupby" : '';
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
	 * Return the number of found rows for a query
	 *
	 * @return int
	 */
	public function get_found_rows() {

		$cache_key   = $this->create_cache_key( __METHOD__ );
		$cache_value = wp_cache_get( $cache_key, 'groundhogg', false, $found );

		if ( $found ) {
			return $cache_value;
		}

		$rows = (int) $this->db->get_var( 'SELECT FOUND_ROWS()' );

		wp_cache_set( $cache_key, $rows, 'groundhogg' );

		return $rows;
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

	public static function coalesceZero( string $col ) {
		return "COALESCE($col, 0)";
	}

	public static function cast2decimal( string $col, int $precision, int $scale ) {
		return "CAST($col as DECIMAL($precision, $scale))";
	}

	public static function cast2int( string $col ) {
		return "CAST($col as INT)";
	}

	public static function cast2signed( string $col ) {
		return "CAST($col as SIGNED)";
	}

	public static function cast2unsigned( string $col ) {
		return "CAST($col as UNSIGNED)";
	}

	public static function cast2date( string $col ) {
		return "CAST($col as DATE)";
	}

	public static function cast2datetime( string $col ) {
		return "CAST($col as DATETIME)";
	}

	public static function cast2time( string $col ) {
		return "CAST($col as TIME)";
	}

}
