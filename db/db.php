<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Contact;
use function Groundhogg\get_array_var;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DB Parent Class
 *
 * This class is the foundation for all DB activities in Groundhogg. With the exception of several new functions
 * such as generate_where, generate_search and search, this class was mostly borrowed from EDD with several mods and the original copyright belongs to Pippin...
 *
 * @since       File available since Release 0.1
 * @subpackage  Includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class DB {

	/**
	 * @var array Store results about a query temporarily.
	 */
	protected static $cache = [];
	/**
	 * The name of our database table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public $db_suffix;
	/**
	 * The name of our database table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public $table_name;
	/**
	 * The version of our database table
	 *
	 * @access  public
	 * @since   2.1
	 */
	public $version;
	/**
	 * The name of the primary column
	 *
	 * @access  public
	 * @since   2.1
	 */
	public $primary_key;
	/**
	 * @var string
	 */
	public $charset;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function __construct() {

		$this->db_suffix = $this->get_db_suffix();

		$this->render_table_name();

		$this->primary_key = $this->get_primary_key();
		$this->version     = $this->get_db_version();
		$this->charset     = $this->get_charset_collate();

		$this->add_additional_actions();

		/**
		 * Register the table...
		 */
		$this->register_table();

		// If the blog is switched, re-render the table name to provide the correct table name.
		add_action( 'switch_blog', [ $this, 'render_table_name' ] );
		add_action( 'groundhogg/contact/merge', [ $this, 'contacts_merged' ] );
	}

	/**
	 * Handle the updating of IDs when a contact is merged.
	 *
	 * @param $primary Contact
	 * @param $other Contact
	 */
	public function contacts_merged( $primary, $other ) {
		if ( array_key_exists( 'contact_id', $this->get_columns() ) ) {
			$this->mass_update( [
				'contact_id' => $primary->get_id()
			], [
				'contact_id' => $other->get_id()
			] );
		} else if ( array_key_exists( 'object_id', $this->get_columns() ) ) {
			$this->mass_update( [
				'object_id' => $primary->get_id()
			], [
				'object_id'   => $other->get_id(),
				'object_type' => 'contact'
			] );
		}
	}

	/**
	 * Build the table name from the wpdb
	 */
	public function render_table_name() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->db_suffix;

		/**
		 * Filter the table name...
		 *
		 * @param string $table_name
		 * @param DB $db
		 */
		$this->table_name = apply_filters( 'groundhogg/db/render_table_name', $table_name, $this );
	}

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	abstract public function get_db_suffix();

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Check if the site is global multisite enabled
	 *
	 * @return bool
	 *
	 * @deprecated
	 */
	private function is_global_multisite() {
		return false;
	}

	/**
	 * Get the DB primary key
	 *
	 * @return string
	 */
	abstract public function get_primary_key();

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	abstract public function get_db_version();

	/**
	 * Get the charset
	 *
	 * @return string
	 */
	public function get_charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	/**
	 * Get the charset
	 *
	 * @return string
	 */
	public function get_charset() {
		global $wpdb;

		return $wpdb->charset;
	}

	/**
	 * Get the charset
	 *
	 * @return string
	 */
	public function get_collate() {
		global $wpdb;

		return $wpdb->collate;
	}

	/**
	 * Option to add additional actions following construct.
	 */
	protected function add_additional_actions() {
	}

	/**
	 * Register the table with $wpdb so the metadata api can find it
	 *
	 * @access  public
	 * @since   2.6
	 */
	public function register_table() {
		global $wpdb;
		$wpdb->__set( 'gh_' . $this->get_object_type() . 's', $this->get_table_name() );
		$wpdb->tables[] = $this->get_db_suffix();
	}

	/**
	 * Get the object type we're inserting/updating/deleting.
	 *
	 * @return string
	 */
	abstract public function get_object_type();

	/**
	 * Gets the max index length
	 */
	public function get_max_index_length() {
		return $this->get_charset() === 'utf8mb4' ? 191 : 255;
	}

	/**
	 * Flush the cache...
	 */
	public static function flush_cache() {
		self::$cache = [];
	}

	/**
	 * @return string
	 */
	public function get_id_key() {
		return 'ID';
	}

	/**
	 * Search the records
	 *
	 * @param string $s
	 *
	 * @return array
	 */
	public function search( $s = '' ) {
		global $wpdb;

		$where = $this->generate_search( $s );

		return $wpdb->get_results(
			"SELECT * FROM $this->table_name WHERE $where ORDER BY $this->primary_key DESC"
		);
	}

	/**
	 * Generates the search WHERE Clause
	 *
	 * @param $s
	 *
	 * @return string
	 */
	public function generate_search( $s = '' ) {
		global $wpdb;

		$where_args = array();

		foreach ( $this->get_columns() as $column => $type ) {
			if ( $type === '%s' ) {
				$where_args[ $column ] = "%" . $wpdb->esc_like( $s ) . "%";
			}
		}

		$where = $this->generate_where( $where_args, "OR" );

		return $where;
	}

	/**
	 * Whitelist of columns
	 *
	 * @access  public
	 * @return  array
	 * @since   2.1
	 */
	public function get_columns() {
		return [];
	}

	/**
	 * Create a where clause given an array
	 *
	 * @param array $args
	 * @param string $operator
	 *
	 * @return string
	 */
	public function generate_where( $args = array(), $operator = "AND" ) {

		$where = array();
		if ( ! empty( $args ) && is_array( $args ) ) {
			foreach ( $args as $key => $value ) {

				if ( is_array( $value ) ) {

					$ORS = [];

					foreach ( $value as $item ) {

						if ( is_numeric( $item ) ) {
							$ORS[] = $item;
						} else if ( is_string( $item ) ) {
							$ORS[] = "'" . $item . "'";
						}

					}

					if ( empty( $ORS ) ) {
						$ORS[] = 0;
					}

					$where[] = "$key IN (" . implode( ',', $ORS ) . ")";

				} else {
					if ( is_string( $value ) ) {
						$value = "'" . $value . "'";
					}

					if ( strpos( $value, '%' ) !== false ) {
						$where[] = $key . " LIKE " . $value;
					} else {
						$where[] = $key . " = " . $value;
					}
				}
			}
		}

		return implode( " {$operator} ", $where );

	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @access  public
	 * @return  object
	 * @since   2.1
	 */
	public function get( $row_id ) {
		global $wpdb;

		return apply_filters( 'groundhogg/db/get/' . $this->get_object_type(), $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) ) );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @access  public
	 * @return  object
	 * @since   2.1
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );

		return apply_filters( 'groundhogg/db/get/' . $this->get_object_type(), $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ) ) );
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @access  public
	 * @return  string
	 * @since   2.1
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );

		return apply_filters( 'groundhogg/db/get_column/' . $this->get_object_type(), $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) ) );
	}

	/**
	 * Add a email
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function add( $data = array() ) {

		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		return $this->insert( $args );
	}

	/**
	 * Default column values
	 *
	 * @access  public
	 * @return  array
	 * @since   2.1
	 */
	public function get_column_defaults() {
		return [];
	}

	/**
	 * Insert a new row
	 *
	 * @access  public
	 * @return  int
	 * @since   2.1
	 */
	public function insert( $data ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'groundhogg/db/pre_insert/' . $this->get_object_type(), $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$data = apply_filters( 'groundhogg/db/pre_insert/' . $this->get_object_type(), $data, $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );
		$wpdb_insert_id = $wpdb->insert_id;

		if ( $wpdb_insert_id ) {
			$this->set_last_changed();
		}

		do_action( 'groundhogg/db/post_insert/' . $this->get_object_type(), $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Sets the last_changed cache key for contacts.
	 *
	 * @access public
	 * @since  2.8
	 */
	public function set_last_changed() {
		wp_cache_set( 'last_changed', microtime(), $this->get_cache_group() );
	}

	/**
	 * Get the cache group
	 *
	 * @return string
	 */
	public function get_cache_group() {
		return 'gh_' . $this->get_object_type() . 's';
	}

	/**
	 * Update a row
	 *
	 * @access  public
	 * @return  bool
	 * @since   2.1
	 */
	public function update( $row_id = 0, $data = [], $where = [] ) {

		global $wpdb;

		if ( ! is_array( $row_id ) ){
			$row_id = absint( $row_id );
			$where = [ $this->get_primary_key() => $row_id ];
		}

		if ( empty( $where ) ) {
			return false;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		do_action( 'groundhogg/db/pre_update/' . $this->get_object_type(), $where, $data );

		$data = apply_filters( 'groundhogg/db/pre_update/' . $this->get_object_type(), $data, $where );

		if ( false === $wpdb->update( $this->table_name, $data, $where, $column_formats ) ) {
			return false;
		}

		$this->set_last_changed();

		do_action( 'groundhogg/db/post_update/' . $this->get_object_type(), $where );

		return true;
	}

	/**
	 * Mass update records
	 *
	 * @param $data  array
	 * @param $where array
	 *
	 * @return bool;
	 */
	public function mass_update( $data, $where ) {
		global $wpdb;

		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data  = array_change_key_case( $data );
		$where = array_change_key_case( $where );

		// White list columns
		$data  = array_intersect_key( $data, $column_formats );
		$where = array_intersect_key( $where, $column_formats );

		do_action( 'groundhogg/db/pre_mass_update/' . $this->get_object_type(), $data );

		$data = apply_filters( 'groundhogg/db/mass_update/' . $this->get_object_type(), $data, $column_formats );

		if ( false === $wpdb->update( $this->table_name, $data, $where ) ) {
			return false;
		}

		do_action( 'groundhogg/db/post_mass_update/' . $this->get_object_type(), $data );

		return true;
	}

	/**
	 * Helper function to bulk delete events in the event associated things happen.
	 *
	 * @param array $args
	 *
	 * @return false|int
	 */
	public function bulk_delete( $where = [] ) {
		global $wpdb;

		if ( empty( $where ) ) {
			return false;
		}

		$column_formats = $this->get_columns();
		$where          = array_intersect_key( $where, $column_formats );

		do_action( 'groundhogg/db/pre_bulk_delete/' . $this->get_object_type(), $where );

		$result = $wpdb->delete( $this->table_name, $where );

		do_action( 'groundhogg/db/post_bulk_delete/' . $this->get_object_type(), $where );

		return $result;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 * @return  bool
	 * @since   2.1
	 */
	public function delete( $row_id = 0 ) {

		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		do_action( 'groundhogg/db/pre_delete/' . $this->get_object_type(), $row_id );

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
			return false;
		}

		$this->set_last_changed();

		do_action( 'groundhogg/db/post_delete/' . $this->get_object_type(), $row_id );

		return true;
	}

	/**
	 * Checks if a broadcast exists
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function exists( $value = 0, $field = false ) {

		$columns = $this->get_columns();

		if ( is_array( $value ) ) {

			$exists = $this->query( $value );

			return ! empty( $exists );

		} else {
			if ( ! $field ) {
				$field = $this->get_primary_key();
			}

			if ( ! array_key_exists( $field, $columns ) ) {
				return false;
			}

			return (bool) $this->get_column_by( $this->get_primary_key(), $field, $value );
		}
	}

	/**
	 * @param array $data
	 * @param string|false $ORDER_BY
	 * @param bool $from_cache
	 *
	 * @return array|bool|null|object
	 */
	public function query( $data = [], $ORDER_BY = '', $from_cache = true ) {
		if ( isset_not_empty( $data, 'where' ) ) {
			return $this->advanced_query( $data, $from_cache );
		}

		// fix orderBy to orderby
		if ( isset_not_empty( $data, 'orderBy' ) ){
			$data[ 'orderby' ] = $data[ 'orderBy' ];
			unset( $data[ 'orderBy' ] );
		}

		$query_vars = wp_parse_args( $data, [
			'where'   => [],
			'limit'   => false,
			'offset'  => false,
			'orderby' => $this->get_primary_key(),
			'order'   => 'desc',
			'select'  => '*',
			'search'  => false,
		] );

		$where = [ 'relationship' => 'AND' ];

		// Parse data and turn into an advanced query search instead
		foreach ( $data as $key => $val ) {

			if ( empty( $val ) ) {
				continue;
			}

			switch ( $key ) {
				case 's':
				case 'search':

					$search = [ 'relationship' => 'OR' ];

					foreach ( $this->get_columns() as $column => $type ) {
						if ( $type === '%s' ) {
							$search[] = [ 'col' => $column, 'val' => $val, 'compare' => 'RLIKE' ];
						}
					}

					$where[] = $search;

					break;
				case 'before':
					$where[] = [ 'col' => $this->get_date_key(), 'val' => $val, 'compare' => '<=' ];
					break;
				case 'after':
					$where[] = [ 'col' => $this->get_date_key(), 'val' => $val, 'compare' => '>=' ];
					break;
				case 'count':
					$query_vars['func'] = 'count';
					break;
				case 'limit':
				case 'LIMIT':
					$query_vars['limit'] = $val;
					break;
				case 'ORDER_BY':
				case 'ORDERBY':
				case 'orderby':
				case 'order_by':
					$query_vars['orderby'] = $val;
					break;
				case 'order':
				case 'ORDER':
					$query_vars['order'] = $val;
					break;
				default:
					if ( in_array( $key, $this->get_allowed_columns() ) ) {
						$where[] = [ 'col' => $key, 'val' => $val, 'compare' => '=' ];
					}

					break;
			}

		}

		if ( $ORDER_BY ) {
			$query_vars['orderby'] = $ORDER_BY;
		}

		$query_vars['where'] = $where;

		return $this->advanced_query( $query_vars, $from_cache );
	}

	/**
	 * New and improved query function to access DB in more complex and interesting ways.
	 *
	 * @param array $query_vars
	 * @param bool $from_cache
	 *
	 * @return object[]|array[]|int
	 */
	public function advanced_query( $query_vars = [], $from_cache = true ) {

		$key = md5( serialize( $query_vars ) );

		$last_changed = $this->get_last_changed();

		$cache_key   = "query:$key:$last_changed";
		$cache_value = wp_cache_get( $cache_key, $this->get_cache_group() );

		if ( $cache_value && $from_cache !== false ) {
			return $cache_value;
		}

		$sql = $this->get_sql( $query_vars );

		global $wpdb;

		$func = strtolower( get_array_var( $query_vars, 'func' ) );

		switch ( $func ) {
			case 'count':
				$results = absint( $wpdb->get_var( $sql ) );
				break;
			case 'sum':
			case 'avg':
				$results = $wpdb->get_var( $sql );
				break;
			default:
				$results = $wpdb->get_results( $sql );
				break;
		}

		$results = apply_filters( 'groundhogg/db/query/' . $this->get_object_type(), $results, $query_vars );

		wp_cache_add( $cache_key, $cache_value, $this->get_cache_group() );

		return $results;
	}

	/**
	 * Retrieves the value of the last_changed cache key for contacts.
	 *
	 * @access public
	 * @since  2.8
	 */
	public function get_last_changed() {
		if ( function_exists( 'wp_cache_get_last_changed' ) ) {
			return wp_cache_get_last_changed( $this->get_cache_group() );
		}

		$last_changed = wp_cache_get( 'last_changed', $this->get_cache_group() );
		if ( ! $last_changed ) {
			wp_cache_set( 'last_changed', $last_changed, $this->get_cache_group() );
		}

		return $last_changed;
	}

	/**
	 * Generate the SQL Statement
	 *
	 * @param array $query_vars
	 *
	 * @return string
	 */
	public function get_sql( $query_vars = [] ) {
		// Actual start
		$query_vars = wp_parse_args( $query_vars, [
			'where'   => [],
			'limit'   => false,
			'offset'  => false,
			'orderby' => $this->get_primary_key(),
			'order'   => 'desc', // ASC || DESC
			'select'  => '*',
			'search'  => false,
			'func'    => false, // COUNT | AVG | SUM
			'groupby' => false,
		] );

		// Build Where Statement
		$where = get_array_var( $query_vars, 'where', [] );

		if ( $query_vars['search'] ) {
			$search = [ 'relationship' => 'OR' ];

			foreach ( $this->get_columns() as $column => $type ) {
				if ( $type === '%s' ) {
					$search[] = [ 'col' => $column, 'val' => esc_sql( $query_vars['search'] ), 'compare' => 'RLIKE' ];
				}
			}

			$where[] = $search;
		}

		$where = empty( $where ) ? '1=1' : $this->build_advanced_where_statement( $where );
		if ( empty( $where ) ) {
			$where = '1=1';
		}

		// Build SELECT statement
		$select = get_array_var( $query_vars, 'select', '*' );

		if ( is_array( $select ) ) {
			$select = array_intersect( $select, $this->get_allowed_columns() );
			$select = implode( ',', $select );
		}

		$distinct = isset_not_empty( $query_vars, 'distinct' ) ? 'DISTINCT' : '';

		if ( $query_vars['func'] ) {
			$select = sprintf( '%s( %s %s)', strtoupper( $query_vars['func'] ), $distinct, $select );
		}

		$limit   = $query_vars['limit'] ? sprintf( 'LIMIT %d', absint( $query_vars['limit'] ) ) : '';
		$offset  = $query_vars['offset'] ? sprintf( 'OFFSET %d', absint( $query_vars['offset'] ) ) : '';
		$orderby = $query_vars['orderby'] && in_array( $query_vars['orderby'], $this->get_allowed_columns() ) ? sprintf( 'ORDER BY %s', $query_vars['orderby'] ) : '';
		$groupby = $query_vars['groupby'] && in_array( $query_vars['groupby'], $this->get_allowed_columns() ) ? sprintf( 'GROUP BY %s', $query_vars['groupby'] ) : '';

		$query_vars['order'] = strtoupper( $query_vars['order'] );
		$order               = in_array( $query_vars['order'], [ 'ASC', 'DESC' ] ) ? $query_vars['order'] : '';

		$clauses = [
			'where'   => $where,
			'groupby' => $groupby,
			'orderby' => $orderby,
			'order'   => $order,
			'limit'   => $limit,
			'offset'  => $offset,
		];

		$clauses = implode( ' ', array_filter( $clauses ) );

		$sql = "SELECT {$select} FROM {$this->get_table_name()} WHERE $clauses";

		return $sql;
	}

	/**
	 * Build the where clause statement using the new structure. Recursive
	 *
	 * @param $where array
	 *
	 * @return string
	 */
	public function build_advanced_where_statement( $where ) {
		global $wpdb;

		// Normalize 'relation' => 'relationship'
		if ( isset_not_empty( $where, 'relation' ) ) {
			$where['relationship'] = $where['relation'];
			unset( $where['relation'] );
		}

		$where = wp_parse_args( $where, [
			'relationship' => 'AND'
		] );

		$relationship = in_array( $where['relationship'], $this->get_allowed_relationships() ) ? strtoupper( $where['relationship'] ) : 'AND';

		unset( $where['relationship'] );

		$clause = [];

		foreach ( $where as $i => $condition ) {

			if ( ! is_array( $condition ) ) {
				// Assume first order ==

				$value = $condition;
				$col   = $i;

				if ( is_numeric( $value ) ) {
					$clause[] = $wpdb->prepare( "$col = %d", $value );
				} else {
					$clause[] = $wpdb->prepare( "$col = %s", $value );
				}

			} else if ( isset_not_empty( $condition, 'relationship' ) ) {

				$clause[] = $this->build_advanced_where_statement( $condition );

			} else {

				$condition = wp_parse_args( $condition, [
					'col'     => '',
					'val'     => '',
					'compare' => '='
				] );

				$normalize_keys = [
					'value'  => 'val',
					'key'    => 'col',
					'column' => 'col',
					'comp'   => 'compare',
					0        => 'col',
					1        => 'compare',
					2        => 'val'
				];

				foreach ( $normalize_keys as $from => $to ) {
					if ( isset_not_empty( $condition, $from ) ) {
						$condition[ $to ] = $condition[ $from ];
					}
				}

				if ( in_array( $condition['col'], $this->get_allowed_columns() ) && in_array( $condition['compare'], $this->get_allowed_comparisons() ) ) {

					$value = $condition['val'];

					if ( is_array( $value ) ) {

						if ( empty( $value ) ){
							continue;
						}

						$condition['compare'] = 'IN';
						$value                = map_deep( $value, 'sanitize_text_field' );

						$value = map_deep( $value, function ( $i ) {

							$i = esc_sql( $i );

							if ( is_numeric( $i ) ) {
								return absint( $i );
							} else if ( is_string( $i ) ) {
								return "'{$i}'";
							}

							return false;
						} );

						$value = sprintf( "(%s)", implode( ',', $value ) );

						$clause[] = "{$condition[ 'col' ]} IN {$value}";

					} else {

						if ( is_numeric( $value ) ) {
							$clause[] = $wpdb->prepare( "{$condition[ 'col' ]} {$condition[ 'compare' ]} %d", $value );
						} else {
							$clause[] = $wpdb->prepare( "{$condition[ 'col' ]} {$condition[ 'compare' ]} %s", $value );
						}

					}

				}

			}

		}

		return implode( " {$relationship} ", $clause );
	}

	/**
	 * Allowed relationships
	 *
	 * @return array
	 */
	public function get_allowed_relationships() {
		return [ 'AND', 'OR' ];
	}

	/**
	 * @return array
	 */
	public function get_allowed_columns() {
		return array_keys( $this->get_columns() );
	}

	public function get_allowed_comparisons() {
		return [
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'<>',
			'LIKE',
			'RLIKE',
			'IN',
		];
	}

	/**
	 * @return string
	 */
	public function get_date_key() {
		return 'date_created';
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @access  public
	 * @return  string
	 * @since   2.1
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		return apply_filters( 'groundhogg/db/get_column/' . $this->get_object_type(), $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) ) );
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	public function count( $args = [] ) {
		unset( $args['offset'] );
		unset( $args['limit'] );
		unset( $args['LIMIT'] );

		if ( isset_not_empty( $args, 'where' ) ) {
			$args['func'] = 'count';
		} else {
			$args['count'] = true;
		}

		return $this->query( $args );
	}

	/**
	 * Drops the table
	 */
	public function drop() {

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			exit;
		}

		delete_option( $this->table_name . '_db_version' );

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS " . $this->table_name );

	}

	/**
	 * Empty the table
	 */
	public function truncate() {
		global $wpdb;

		$wpdb->query( "DELETE FROM " . $this->table_name );
	}

	/**
	 * Update the DB if required
	 */
	public function update_db() {
		if ( ! $this->installed() || get_option( $this->table_name . '_db_version' ) !== $this->version ) {
			$this->create_table();
		}
	}

	/**
	 * Check if the table was ever installed
	 *
	 * @return bool Returns if the contacts table was installed and upgrade routine run
	 * @since  2.4
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}

	/**
	 * Check if the given table exists
	 *
	 * @param string $table The table name
	 *
	 * @return bool          If the table name exists
	 * @since  2.4
	 *
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	/**
	 * Create the DB
	 */
	abstract public function create_table();

}