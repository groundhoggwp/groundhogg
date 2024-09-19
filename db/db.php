<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Contact;
use Groundhogg\DB\Query\FilterException;
use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\DB\Query\Where;
use Groundhogg\DB_Object;
use Groundhogg\DB_Object_With_Meta;
use Groundhogg\Plugin;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\Cli\doing_cli;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\is_option_enabled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\maybe_implode_in_quotes;
use function Groundhogg\preg_quote_except;
use function Groundhogg\swap_array_keys;

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
	 * @var The easy query alias
	 */
	public $alias;
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

		// Special handler for merging contacts!
		add_action( 'groundhogg/contact/merged', [ $this, 'contact_merged' ], 10, 2 );
	}

	/**
	 * When a contact is merged handle this by default
	 *
	 * @param $contact Contact
	 * @param $other   Contact
	 */
	public function contact_merged( $contact, $other ) {

		// Has contact_id but is not a meta table because that is done in code.
		if ( key_exists( 'contact_id', $this->get_columns() ) && ! key_exists( 'meta_id', $this->get_columns() ) ) {
			$this->update( [
				'contact_id' => $other->get_id(),
			],
			[
				'contact_id' => $contact->get_id()
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
		 * @param DB     $db
		 */
		$this->table_name = apply_filters( 'groundhogg/db/render_table_name', $table_name, $this );
		$this->alias      = $this->get_object_type() . 's';
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

	protected function add_additional_actions() {
	}

	/**
	 * Check if the site is global multisite enabled
	 *
	 * @deprecated
	 * @return bool
	 *
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
	 * @param $object object
	 */
	public function create_object( $object ) {
		$meta_table = $this->get_meta_table();

		if ( $meta_table ) {
			return new DB_Object_With_Meta( $this, $meta_table, $object );
		}

		return new DB_Object( $this, $object );
	}

	/**
	 * Get the associated meta table
	 *
	 * @return Meta_DB
	 */
	public function get_meta_table() {
		return Plugin::instance()->dbs->get_meta_db_by_object_type( $this->get_object_type() );
	}

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

		foreach ( $this->get_searchable_columns() as $column ) {
			$where_args[ $column ] = "%" . $wpdb->esc_like( $s ) . "%";
		}

		return $this->generate_where( $where_args, "OR" );
	}

	/**
	 * Whitelist of columns
	 *
	 * @access  public
	 * @since   2.1
	 * @return  array
	 */
	public function get_columns() {
		return [];
	}

	public function has_column( string $column ) {
		return array_key_exists( $column, $this->get_columns() );
	}

	public function column_is_searchable( $column ) {
		return in_array( $column, $this->get_searchable_columns() );
	}

	/**
	 * @return int[]|string[]
	 */
	public function get_searchable_columns() {
		return array_keys( array_filter( $this->get_columns(), function ( $f ) {
			return $f === '%s';
		} ) );
	}

	/**
	 * Whether this table has an auto incrementing ID column
	 *
	 * @return bool
	 */
	public function is_auto_increment() {

		$sql = $this->create_table_sql_command();

		// Not configured, assume true for backwards compatibility
		if ( empty( $sql ) ) {
			return true;
		}

		return str_contains( $sql, 'AUTO_INCREMENT' );
	}

	/**
	 * Create a where clause given an array
	 *
	 * @param array  $args
	 * @param string $relationship
	 *
	 * @return string
	 */
	public function generate_where( $args = array(), $relationship = "AND" ) {

		$where = array();
		if ( ! empty( $args ) && is_array( $args ) ) {
			foreach ( $args as $key => $value ) {

				if ( is_array( $value ) ) {
					$where[] = "$key IN (" . maybe_implode_in_quotes( $value ) . ")";
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

		return implode( " {$relationship} ", $where );

	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @access  public
	 * @since   2.1
	 * @return  object
	 */
	public function get( $row_id ) {
		return $this->get_by( $this->primary_key, $row_id );
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @access  public
	 * @since   2.1
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );

		$cache_key   = "get_by:$column:$row_id";
		$cache_value = $this->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$results = apply_filters( 'groundhogg/db/get/' . $this->get_object_type(), $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ) ) );

		$this->cache_set( $cache_key, $results );

		return $results;
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_column( $column, $row_id ) {
		return $this->get_column_by( $column, $this->primary_key, $row_id );
	}

	public function get_unique_column_values( $column ) {
		return wp_list_pluck( $this->query( [
			'select' => "DISTINCT $column",
			'limit'  => 100,
		] ), $column );
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @access  public
	 * @since   2.1
	 * @return  string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		$cache_key   = "get_column_by:$column:$column_where:$column_value";
		$cache_value = $this->cache_get( $cache_key, $found );

		if ( $found ) {
			return $cache_value;
		}

		$column_format = get_array_var( $this->get_columns(), $column_where, '%s' );

		$results = apply_filters( 'groundhogg/db/get_column/' . $this->get_object_type(),
			$wpdb->get_var(
				$wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = $column_format LIMIT 1;", $column_value ) ) );

		$this->cache_set( $cache_key, $results );

		return $results;
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
	 * @since   2.1
	 * @return  array
	 */
	public function get_column_defaults() {
		return [];
	}

	/**
	 * Storage for when batch inserting
	 *
	 * @var array
	 */
	protected $batch_inserts = [];
	protected $batch_formats = [];
	protected $batch_columns = [];


	/**
	 * Add an insert statment for a batch insert
	 *
	 * @param $data mixed
	 *
	 * @return void
	 */
	public function batch_insert( $data ) {

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'groundhogg/db/pre_insert/' . $this->get_object_type(), $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data           = array_intersect_key( $data, $column_formats );
		$column_formats = array_intersect_key( $column_formats, $data );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$data = apply_filters( 'groundhogg/db/pre_insert/' . $this->get_object_type(), $data, $column_formats );

		// Remove primary key if auto incrementing primary key
		if ( $this->is_auto_increment() ) {
			unset( $data[ $this->primary_key ] );
			unset( $column_formats[ $this->primary_key ] );
		}

		if ( empty( $this->batch_columns ) ) {
			$this->batch_columns = array_keys( $data );
		}
		if ( empty( $this->batch_formats ) ) {
			$this->batch_formats = array_values( $column_formats );
		}
		$this->batch_inserts[] = array_values( $data );
	}

	/**
	 * Perform a batch insert
	 *
	 * @return false|int
	 */
	public function commit_batch_insert() {

		// Nothing to batch insert
		if ( empty( $this->batch_inserts ) ) {
			return false;
		}

		global $wpdb;

		$INSERTS = [];
		$FORMATS = implode( ', ', $this->batch_formats );

		foreach ( $this->batch_inserts as $_insert ) {
			$INSERTS[] = $wpdb->prepare( '(' . $FORMATS . ')', $_insert );
		}

		$INSERTS = implode( ', ', $INSERTS );
		$COLUMNS = implode( ', ', $this->batch_columns );

		$wpdb->query( "INSERT INTO $this->table_name ( $COLUMNS ) VALUES $INSERTS" );

		$this->cache_set_last_changed();

		// Clear batch stuff
		$this->batch_columns = [];
		$this->batch_formats = [];
		$this->batch_inserts = [];

		if ( ! $wpdb->rows_affected ) {
			return false;
		}

		return $wpdb->rows_affected;
	}

	/**
	 * Insert a new row
	 *
	 * @access  public
	 * @since   2.1
	 * @return  int
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

		// Remove primary key if auto incrementing primary key
		if ( $this->is_auto_increment() ) {
			unset( $data[ $this->primary_key ] );
			unset( $column_formats[ $this->primary_key ] );
		}

		$wpdb->insert( $this->table_name, $data, $column_formats );

		$wpdb_insert_id = $wpdb->insert_id;

		$this->cache_set_last_changed();

		do_action( 'groundhogg/db/post_insert/' . $this->get_object_type(), $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Sets the last_changed cache key for contacts.
	 *
	 * @access public
	 * @since  2.8
	 */
	public function cache_set_last_changed() {

		// Use our own cache instead
		if ( ! is_option_enabled( 'gh_use_object_cache' ) ) {
			self::$cache[ $this->get_cache_group() ]['last_changed'] = microtime();

			return;
		}

		wp_cache_set_last_changed( $this->get_cache_group() );
	}

	/**
	 * Retrieves the value of the last_changed cache key for contacts.
	 *
	 * @access public
	 * @since  2.8
	 */
	public function cache_get_last_changed() {

		// Use our own cache instead
		if ( ! is_option_enabled( 'gh_use_object_cache' ) ) {
			if ( $this->_exists( 'last_changed', $this->get_cache_group() ) ) {
				return self::$cache[ $this->get_cache_group() ]['last_changed'];
			} else {
				$last_changed                                            = microtime();
				self::$cache[ $this->get_cache_group() ]['last_changed'] = $last_changed;

				return $last_changed;
			}
		}

		return wp_cache_get_last_changed( $this->get_cache_group() );
	}

	/**
	 * Utility function
	 *
	 * @param $key
	 * @param $group
	 *
	 * @return bool
	 */
	protected function _exists( $key, $group ) {
		return isset( self::$cache[ $group ] ) && ( isset( self::$cache[ $group ][ $key ] ) || array_key_exists( $key, self::$cache[ $group ] ) );
	}


	/**
	 * Get the results from the cache
	 *
	 * @param $cache_key string
	 * @param $found     bool if a result was found
	 *
	 * @return false|mixed
	 */
	public function cache_get( $cache_key, &$found = null ) {
		$last_changed = $this->cache_get_last_changed();
		$cache_key    = "$cache_key:$last_changed";

		// Use our own cache instead
		if ( ! is_option_enabled( 'gh_use_object_cache' ) ) {

			if ( $this->_exists( $cache_key, $this->get_cache_group() ) ) {

				$data  = self::$cache[ $this->get_cache_group() ][ $cache_key ];
				$found = true;

				if ( is_object( $data ) ) {
					return clone $data;
				} else {
					return $data;
				}
			}

			$found = false;

			return false;
		}

		return wp_cache_get( $cache_key, $this->get_cache_group(), false, $found );
	}

	/**
	 * Set the value in the cache
	 *
	 * @param $cache_key
	 * @param $data
	 *
	 * @return bool
	 */
	public function cache_set( $cache_key, $data ) {
		$last_changed = $this->cache_get_last_changed();
		$cache_key    = "$cache_key:$last_changed";

		// Use our own cache instead
		if ( ! is_option_enabled( 'gh_use_object_cache' ) ) {

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			self::$cache[ $this->get_cache_group() ][ $cache_key ] = $data;


			return false;
		}

		return wp_cache_set( $cache_key, $data, $this->get_cache_group(), MINUTE_IN_SECONDS );
	}

	/**
	 * Clears the cache group
	 */
	public function clear_cache() {

		if ( ! is_option_enabled( 'gh_use_object_cache' ) ) {
			unset( self::$cache[ $this->get_cache_group() ] );
		} else {
			wp_cache_flush_group( $this->get_cache_group() );
		}
	}

	/**
	 * Clears the whole cache, all groups and keys
	 */
	public static function clear_whole_cache() {
		self::$cache = [];
	}

	/**
	 * Get the cache group
	 *
	 * @return string
	 */
	public function get_cache_group() {
		return 'groundhogg/db/' . $this->get_object_type();
	}

	/**
	 * Update a row
	 *
	 * @access  public
	 * @since   2.1
	 * @return  bool
	 */
	public function update( $row_id_or_where = 0, $data = [], $where = [] ) {

		// Nothing to update
		if ( empty( $data ) ) {
			return true;
		}

		global $wpdb;

		if ( is_string( $row_id_or_where ) || is_numeric( $row_id_or_where ) ) {
			$where = [ $this->get_primary_key() => $row_id_or_where ];
		} else if ( is_array( $row_id_or_where ) ) {
			$where = $row_id_or_where;
		}

		// Don't know who to update
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

		// Empty data at this point, return false.
		if ( empty( $data ) || false === $wpdb->update( $this->table_name, $data, $where, $column_formats ) ) {
			return false;
		}

		$this->cache_set_last_changed();

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

		$this->cache_set_last_changed();

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

		$this->cache_set_last_changed();

		return $result;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @access  public
	 *
	 * @since   2.1
	 *
	 * @param mixed $id
	 *
	 * @return  bool
	 */
	public function delete( $id = null ) {

		global $wpdb;

		$by_primary = false;

		if ( is_numeric( $id ) ) {
			$id         = absint( $id );
			$where      = [
				$this->primary_key => $id
			];
			$by_primary = true;
		} else if ( is_array( $id ) ) {
			$where = $id;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// White list columns
		$where = array_intersect_key( $where, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $where );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		do_action( 'groundhogg/db/pre_delete/' . $this->get_object_type(), $by_primary ? $id : $where, $column_formats, $this );
		do_action( 'groundhogg/db/pre_delete', $this->get_object_type(), $by_primary ? $id : $where, $column_formats, $this );

		if ( false === $wpdb->delete( $this->table_name, $where, $column_formats ) ) {
			return false;
		}

		$this->cache_set_last_changed();

		do_action( 'groundhogg/db/post_delete/' . $this->get_object_type(), $by_primary ? $id : $where, $column_formats, $this );
		do_action( 'groundhogg/db/post_delete', $this->get_object_type(), $by_primary ? $id : $where, $column_formats, $this );

		return true;
	}

	/**
	 * Whether the table has at least 1 row
	 *
	 * @return bool
	 */
	public function is_empty() {
		$rows = $this->query( [ 'limit' => 1 ] );

		return empty( $rows );
	}

	/**
	 * Checks if a broadcast exists
	 *
	 * @access  public
	 * @since   2.1
	 */
	public function exists( $value = 0, $field = false ) {

		if ( is_array( $value ) ) {

			$query          = $value;
			$query['limit'] = 1;

		} else {
			if ( ! $field ) {
				$field = $this->get_primary_key();
			}

			if ( ! array_key_exists( $field, $this->get_columns() ) ) {
				return false;
			}

			$query = [
				$field  => $value,
				'limit' => 1
			];
		}

		$exists = $this->query( $query );

		return ! empty( $exists );
	}

	/**
	 * Parses query vars when as they can be passed in a variety of formats
	 *
	 * @param $data array
	 *
	 * @return array
	 */
	public function parse_query_vars( $data = [] ) {

		global $wpdb;

		// parsed allready
		if ( isset_not_empty( $data, '_was_parsed' ) ) {
			return $data;
		}

		$query_vars = wp_parse_args( $data, [
			'operation' => 'SELECT',
			'where'     => [],
			'limit'     => false,
			'offset'    => false,
			'orderby'   => $this->get_primary_key(),
			'order'     => 'desc',
			'select'    => '*',
			'search'    => false,
			'func'      => false,
		] );

		$where = [ 'relationship' => 'AND' ];

		// Parse data and turn into an advanced query search instead
		foreach ( $data as $key => $val ) {

			if ( empty( $val ) ) {
				continue;
			}

			switch ( $key ) {
				case 'where':
					$where = array_merge( $where, $val );
					break;
				case 's':
				case 'search':
				case 'term':
					$query_vars['search'] = $val;
					break;
				case 'include':
					$where[] = [ 'col' => $this->get_primary_key(), 'val' => $val, 'compare' => 'IN' ];
					break;
				case 'exclude':
					$where[] = [ 'col' => $this->get_primary_key(), 'val' => $val, 'compare' => 'NOT IN' ];
					break;
				case 'before':
					$where[] = [ 'col' => $this->get_date_key(), 'val' => $val, 'compare' => '<=' ];
					break;
				case 'after':
					$where[] = [ 'col' => $this->get_date_key(), 'val' => $val, 'compare' => '>=' ];
					break;
				case 'child' :
				case 'related' :

					$val = swap_array_keys( $val, [
						'child_id' => 'ID',
						'child_type' => 'type',
						'object_id' => 'ID',
						'object_type' => 'type',
					]);

					$relationships = get_db( 'object_relationships' );
					$where[]       = [
						'col'     => $this->get_primary_key(),
						'compare' => 'IN',
						'val'     => $wpdb->prepare( "SELECT primary_object_id FROM {$relationships->table_name} WHERE secondary_object_id = %d AND secondary_object_type = '%s' AND primary_object_type = '%s'", $val['ID'], $val['type'], $this->get_object_type() )
					];

					break;
				case 'parent' :

					$val = swap_array_keys( $val, [
						'parent_id' => 'ID',
						'parent_type' => 'type',
						'object_id' => 'ID',
						'object_type' => 'type',
					]);

					$relationships = get_db( 'object_relationships' );
					$where[]       = [
						'col'     => $this->get_primary_key(),
						'compare' => 'IN',
						'val'     => $wpdb->prepare( "SELECT secondary_object_id FROM {$relationships->table_name} WHERE primary_object_id = %d AND primary_object_type = '%s' AND secondary_object_type = '%s'", $val['ID'], $val['type'], $this->get_object_type() )
					];
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
				case 'func':
					$query_vars['func'] = strtoupper( $val );
					break;
				case 'include_filters':

					// Parse the filters
//					$where[] = $this->parse_filters( $val );

					break;
				case 'exclude_filters':
					// Parse the filters
//					$where[] = 'NOT ( ' . $this->parse_filters( $val ) . ')';
					break;
				default:
					if ( in_array( $key, $this->get_allowed_columns() ) ) {

						if ( is_array( $val ) ) {

							// Compare and val defined explicitly
							if ( array_key_exists( 'compare', $val ) && array_key_exists( 'val', $val ) ) {
								$where[] = [ 'col' => $key, 'val' => $val['val'], 'compare' => $val['compare'] ];
								break;
							}

							// Compare is provided as first item in array of 2
							if ( count( $val ) === 2 && in_array( $val[0], $this->get_allowed_comparisons() ) ) {
								$where[] = [ 'col' => $key, 'val' => $val[1], 'compare' => $val[0] ];
								break;
							}
						}

						// Select Clause
						if ( is_string( $val ) && strpos( $val, 'SELECT' ) !== false ) {
							$where[] = [ 'col' => $key, 'val' => $val, 'compare' => 'IN' ];
							break;
						}

						// Basic column clause
						$where[] = [ 'col' => $key, 'val' => $val, 'compare' => is_array( $val ) ? 'IN' : '=' ];

					} else {
						// Pass along
						$query_vars[ $key ] = $val;
					}

					break;
			}
		}

		$query_vars['where']       = $where;
		$query_vars['_was_parsed'] = true;

		return $query_vars;
	}

	/**
	 * @var Filters
	 */
	protected $query_filters;

	/**
	 * Wrapper for Filters::parse_filters()
	 *
	 * @throws FilterException
	 *
	 * @param Where $where
	 * @param array|string $filters
	 *
	 * @return void
	 */
	public function parse_filters( $filters, Where $where ) {
		$this->maybe_register_filters();
		$this->query_filters->parse_filters( $filters, $where );
	}

	protected function maybe_register_filters() {

		if ( $this->query_filters ) {
			return;
		}

		$filters = new Filters();

		foreach ( $this->get_columns() as $column => $format ) {

			switch ( $format ) {
				case '%s':

					if ( str_starts_with( $column, 'date' ) ) {

						$filters->register( $column, function ( $filter, $where ) use ( $column ) {
							Filters::mysqlDateTime( $column, $filter, $where );
						} );

						break;
					}

					$filters->register( $column, function ( $filter, $where ) use ( $column ) {
						Filters::string( $column, $filter, $where );
					} );

					break;
				case '%d':

					if ( in_array( $column, [ 'time', 'timestamp', 'time_scheduled' ] ) ) {

						$filters->register( $column, function ( $filter, $where ) use ( $column ) {
							Filters::timestamp( $column, $filter, $where );
						} );

						break;
					}

					$filters->register( $column, function ( $filter, $where ) use ( $column ) {
						Filters::number( $column, $filter, $where );
					} );
					break;
			}

		}

		// Campaigns filter
		$filters->register( 'campaigns', function ( $filter, Where $where ) {

			$filter = wp_parse_args( $filter, [
				'campaigns' => [],
			] );

			$campaigns = wp_parse_id_list( $filter['campaigns'] );

			foreach ( $campaigns as $campaign ) {

				$join = $where->query->addJoin( 'LEFT', [ get_db( 'object_relationships' )->table_name, 'campaign_' . $campaign ] );
				$join->onColumn( 'primary_object_id' )
				     ->equals( "$join->alias.primary_object_type", $this->get_object_type() )
				     ->equals( "$join->alias.secondary_object_type", 'campaign' )
				     ->equals( "$join->alias.secondary_object_id", $campaign );

				$where->equals( "$join->alias.secondary_object_id", $campaign );
			}

			$where->query->setGroupby( 'ID' );
		} );

		$this->query_filters = $filters;
	}

	/**
	 * @var Table_Query
	 */
	protected $current_query;

	/**
	 * @throws FilterException
	 *
	 * @param string|false $ORDER_BY
	 * @param bool         $from_cache
	 *
	 * @param array        $data
	 *
	 * @return array|bool|null|object
	 */
	public function query( $query_vars = [], $ORDER_BY = '', $from_cache = true ) {

		if ( $ORDER_BY ) {
			$query_vars['orderby'] = $ORDER_BY;
		}

		$query_vars = wp_parse_args( $query_vars, [
			'operation'      => 'SELECT',
			'data'           => [],
			'where'          => [],
//			'limit'          => false,
//			'offset'         => false,
			'orderby'        => $this->get_primary_key(),
			'search_columns' => $this->get_searchable_columns(),
			'order'          => 'desc', // ASC || DESC
//			'select'         => '*',
//			'search'         => false,
//			'func'           => false, // COUNT | AVG | SUM
//			'groupby'        => false,
//			'meta_query'     => [],
			'found_rows'     => false,
		] );

		$operation = $query_vars['operation'];

		$query               = new Table_Query( $this );
		$this->current_query = $query;

		$moreWhere = [];
		$searched  = false;

		// Parse data and turn into an advanced query search instead
		foreach ( $query_vars as $key => $val ) {

			if ( empty( $val ) ) {
				continue;
			}

			switch ( strtolower( $key ) ) {
				case 'select':

					if ( ! is_array( $val ) ) {
						$val = array_map( 'trim', explode( ',', $val ) );
					}

					$query->setSelect( ...$val );
					break;
				case 'func':
					$func = strtoupper( $val );

					if ( $func === 'COUNT' ) {
						$operation = 'COUNT';
						break;
					}

					$operation = 'VAR';
					$select    = "{$func}({$query_vars['select']})";
					$query->setSelect( $select );
					break;
				case 'distinct':
					if ( $query_vars['select'] !== '*' ) {
						$query->setSelect( "DISTINCT {$query_vars['select']}" );
					}
					break;
				case 'where':
					$moreWhere = array_merge( $moreWhere, $val );
					break;
				case 's':
				case 'search':
				case 'term':
					if ( $searched ) {
						break;
					}
					$query->search( $val, wp_parse_list( $query_vars['search_columns'] ) );
					$searched = true;
					break;
				case 'include':
					$query->whereIn( $this->get_primary_key(), $val );
					break;
				case 'exclude':
					$query->whereNotIn( $this->get_primary_key(), $val );
					break;
				case 'before':
					$query->where()->lessThanEqualTo( $this->get_date_key(), $val );
					break;
				case 'after':
					$query->where()->greaterThanEqualTo( $this->get_date_key(), $val );
					break;
				case 'child' : // if it has a child
				case 'related' : // if it has a child

				$val = swap_array_keys( $val, [
					'child_id' => 'ID',
					'child_type' => 'type',
					'object_id' => 'ID',
					'object_type' => 'type',
				]);

				$join = $query->addJoin( 'LEFT', 'object_relationships' );
					$join->onColumn( 'primary_object_id' );

					$query->where( "$join->alias.secondary_object_id", $val['ID'] );
					$query->where( "$join->alias.secondary_object_type", $val['type'] );
					$query->where( "$join->alias.primary_object_type", $this->get_object_type() );

					break;
				case 'parent' : // if it has a parent

					$val = swap_array_keys( $val, [
						'parent_id' => 'ID',
						'parent_type' => 'type',
						'object_id' => 'ID',
						'object_type' => 'type',
					]);

					$join = $query->addJoin( 'LEFT', 'object_relationships' );
					$join->onColumn( 'secondary_object_id' );

					$query->where( "$join->alias.primary_object_id", $val['ID'] );
					$query->where( "$join->alias.primary_object_type", $val['type'] );
					$query->where( "$join->alias.secondary_object_type", $this->get_object_type() );

					break;


				case 'count':
					$operation = 'COUNT';
					break;
				case 'limit':

					if ( is_array( $val ) ) {
						$query->setLimit( ...$val );
					} else {
						$query->setLimit( $val );
					}

					break;
				case 'orderby':
				case 'order_by':
					if ( is_array( $val ) ) {
						$query->setOrderby( ...$val );
					} else {
						$query->setOrderby( $val );
					}
					break;
				case 'order':
				case 'ORDER':
					$query->setOrder( $val );
					break;
				case 'offset':
					$query->setOffset( $val );
					break;
				case 'groupby':
				case 'group_by':
					$query->setGroupby( $val );
					break;
				case 'filters':
				case 'include_filters':

					$this->parse_filters( $val, $query->where() );

					break;
				case 'exclude_filters':

					$exclude_query = new Table_Query( $this );
					$exclude_query->setSelect( $this->get_primary_key() );
					$this->parse_filters( $val, $exclude_query->where() );

					if ( ! $exclude_query->where->isEmpty() ) {
						$query->where()->notIn( $this->get_primary_key(), "$exclude_query" );
					}

					break;
				case 'found_rows':
					$query->setFoundRows( $val );
					break;
				case 'meta_query':

					foreach ( $val as $meta_query ) {

						$meta_query = swap_array_keys( $meta_query, [
							'val'  => 'value',
							'comp' => 'compare'
						] );

						[ 'key' => $key, 'value' => $value, 'compare' => $compare ] = $meta_query;

						$alias = $query->joinMeta( $key );

						$query->where( "$alias.meta_key", $key );
						$query->where( "$alias.meta_value", $value, $compare );
					}

					break;
				default:

					if ( ! in_array( $key, $this->get_allowed_columns() ) ) {
						break;
					}

					if ( is_array( $val ) ) {

						// Compare and val defined explicitly
						if ( array_key_exists( 'compare', $val ) && array_key_exists( 'val', $val ) ) {
							$query->where( $key, $val['val'], $val['compare'] );
							break;
						}

						// Compare is provided as first item in array of 2
						if ( count( $val ) === 2 && in_array( $val[0], $this->get_allowed_comparisons() ) ) {
							$query->where( $key, $val[1], $val[0] );
							break;
						}
					}

					// Select Clause
					if ( is_string( $val ) && strpos( $val, 'SELECT' ) !== false ) {
						$query->whereIn( $key, $val );
						break;
					}

					if ( is_array( $val ) ) {
						$query->whereIn( $key, $val );
						break;
					}

					switch ( $val ) {
						case 'NOT_EMPTY':
							$query->where()->notEmpty( $key );
							break 2;
						case 'EMPTY':
							$query->where()->empty( $key );
							break 2;
					}

					$query->where( $key, $val );

					break;
			}
		}

		foreach ( $moreWhere as $key => $row ) {

			if ( $key === 'relationship' ) {
				continue;
			}

			$row = swap_array_keys( $row, [
				'key'  => 'column',
				0      => 'column',
				'col'  => 'column',
				1      => 'compare',
				'comp' => 'compare',
				'val'  => 'value',
				2      => 'value'
			] );

			[ 'column' => $column, 'compare' => $compare, 'value' => $value ] = $row;

			if ( ! in_array( $column, $this->get_allowed_columns() ) ) {
				continue;
			}

			$query->where( $column, $value, $compare );
		}

		switch ( strtoupper( $operation ) ) {
			default:
			case 'SELECT':

				$results = $query->get_results();
				break;
			case 'COUNT':

				$results = $query->count();
				break;
			case 'UPDATE':

				$results = $query->update( $query_vars['data'] );
				break;
			case 'DELETE':

				$results = $query->delete();
				break;
			case 'VAR':

				$results = $query->get_var();
				break;
		}

		return $results;
	}

	public function found_rows() {

		if ( $this->current_query ) {
			return $this->current_query->get_found_rows();
		}

		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
	}

	public $last_query = '';

	public $last_error = '';

	/**
	 * New and improved query function to access DB in more complex and interesting ways.
	 *
	 * @param array $query_vars
	 * @param bool  $from_cache
	 *
	 * @return object[]|array[]|int
	 */
	public function advanced_query( $query_vars = [], $from_cache = true ) {

		$query_vars = $this->parse_query_vars( $query_vars );

		ksort( $query_vars );

		$operation = $query_vars['operation'];
		$cache_key = "query:" . md5( serialize( $query_vars ) );

		// Only fetch cached results for SELECT queries
		if ( $operation === 'SELECT' ) {

			$cache_value = $this->cache_get( $cache_key, $found );

			if ( $found && $from_cache !== false ) {
				return $cache_value;
			}
		}

		$sql = $this->get_sql( $query_vars );

		global $wpdb;

		switch ( $query_vars['operation'] ) {
			default:
			case 'SELECT':

				$func = strtolower( get_array_var( $query_vars, 'func' ) );

				switch ( $func ) {
					case 'count':
					case 'sum':
					case 'avg':
						$method = 'get_var';
						break;
					default:
						$method = 'get_results';
						break;
				}

				break;
			case 'UPDATE':
			case 'DELETE':
				$method = 'query';
				break;
		}

		$results = call_user_func( [ $wpdb, $method ], $sql );

		$this->last_query = $wpdb->last_query;
		$results          = apply_filters( 'groundhogg/db/query/' . $this->get_object_type(), $results, $query_vars );

		$this->cache_set( $cache_key, $results );

		// Clear cache after changing table results
		if ( in_array( $query_vars['operation'], [ 'UPDATE', 'DELETE' ] ) ) {
			$this->cache_set_last_changed();
		}

		return $results;
	}

	/**
	 * Generate the SQL Statement
	 *
	 * @param array $query_vars
	 *
	 * @return string
	 */
	public function get_sql( $query_vars = [] ) {

		$query_vars = $this->parse_query_vars( $query_vars );

		// Actual start
		$query_vars = wp_parse_args( $query_vars, [
			'operation'      => 'SELECT',
			'data'           => [],
			'where'          => [],
			'limit'          => false,
			'offset'         => false,
			'orderby'        => $this->get_primary_key(),
			'order'          => 'desc', // ASC || DESC
			'select'         => '*',
			'search'         => false,
			'search_columns' => [],
			'func'           => false, // COUNT | AVG | SUM
			'groupby'        => false,
			'meta_query'     => [],
			'found_rows'     => false,
		] );

		if ( ! empty( $query_vars['meta_query'] ) ) {

//			var_dump( $query_vars['meta_query']);

			$meta_query = new \WP_Meta_Query( $query_vars['meta_query'] );

			$meta_table = $this->get_meta_table();
			$meta_table->maybe_resolve_table_conflict();

			$meta_query_sql = $meta_query->get_sql( $this->get_object_type(), $this->table_name, 'ID' );

//			var_dump( $meta_query_sql );

			$meta_table->maybe_resolve_table_conflict();
		}

		// Build Where Statement
		$where = get_array_var( $query_vars, 'where', [] );

		if ( $query_vars['search'] ) {
			$search = [ 'relationship' => 'OR' ];

			$search_columns = ! empty( $query_vars['search_columns'] ) ? wp_parse_list( $query_vars['search_columns'] ) : $this->get_searchable_columns();

			foreach ( $search_columns as $column ) {

				if ( ! $this->column_is_searchable( $column ) ) {
					continue;
				}

				$search[] = [
					'col'     => $column,
					'val'     => '%' . esc_sql( $query_vars['search'] ) . '%',
					'compare' => 'LIKE'
				];
			}

			$where[] = $search;
		}

		$where = empty( $where ) ? '1=1' : $this->build_advanced_where_statement( $where );

		if ( empty( $where ) ) {
			$where = '1=1';
		}

		if ( isset( $meta_query_sql ) && $meta_query_sql ) {
			$where .= ' ' . $meta_query_sql['where'];
		}

		$func = false;

		switch ( $query_vars['operation'] ) {
			default:
			case 'SELECT':

				$select = get_array_var( $query_vars, 'select', '*' );

				if ( is_array( $select ) ) {
					$select = array_intersect( $select, $this->get_allowed_columns() );
					$select = implode( ',', $select );
				}

				$distinct = isset_not_empty( $query_vars, 'distinct' ) ? 'DISTINCT' : '';

				if ( $query_vars['func'] ) {
					$func   = strtoupper( $query_vars['func'] );
					$select = sprintf( '%s( %s %s)', $func, $distinct, $select );
				}

				$found_rows = $query_vars['found_rows'] && $query_vars['limit'] ? 'SQL_CALC_FOUND_ROWS' : '';

				$operation = "SELECT $found_rows $select FROM {$this->get_table_name()}";

				if ( isset( $meta_query_sql ) && $meta_query_sql ) {
					$operation .= ' ' . $meta_query_sql['join'];
				}

				break;

			case 'DELETE':

				$operation = "DELETE FROM {$this->get_table_name()}";

				break;

			case 'UPDATE':

				global $wpdb;

				$data = $query_vars['data'];

				// Initialise column format array
				$column_formats = $this->get_columns();

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

				$fields = $wpdb->prepare( implode( ', ', $fields ), array_values( $data ) );

				$operation = "UPDATE {$this->get_table_name()} SET $fields";

				break;
		}

		$limit   = $query_vars['limit'] ? sprintf( 'LIMIT %d', absint( $query_vars['limit'] ) ) : '';
		$offset  = $query_vars['offset'] ? sprintf( 'OFFSET %d', absint( $query_vars['offset'] ) ) : '';
		$orderby = $query_vars['orderby'] && ( in_array( $query_vars['orderby'], $this->get_allowed_columns() ) || strpos( $query_vars['select'], $query_vars['orderby'] ) !== false ) ? sprintf( 'ORDER BY %s', $query_vars['orderby'] ) : '';
		$groupby = $query_vars['groupby'] && ( in_array( $query_vars['groupby'], $this->get_allowed_columns() ) || strpos( $query_vars['select'], $query_vars['groupby'] ) !== false ) ? sprintf( 'GROUP BY %s', $query_vars['groupby'] ) : '';

		$order = '';

		if ( $orderby ) {
			$query_vars['order'] = strtoupper( $query_vars['order'] );
			$order               = in_array( $query_vars['order'], [ 'ASC', 'DESC' ] ) ? $query_vars['order'] : '';
		}

		$clauses = [
			'where'   => $where,
			'groupby' => $groupby,
			'orderby' => $orderby,
			'order'   => $order,
			'limit'   => $limit,
			'offset'  => $offset,
		];

		if ( $func ) {
			unset( $clauses['limit'] );
			unset( $clauses['orderby'] );
			unset( $clauses['order'] );
		}

		$clauses = apply_filters( 'groundhogg/db/sql_query_clauses', $clauses, $query_vars );

		$clauses = implode( ' ', array_filter( $clauses ) );

		$sql = "$operation WHERE $clauses";

		return apply_filters( 'groundhogg/db/sql_query', $sql, $query_vars );
	}

	/**
	 * Maybe symbolized the comparison
	 *
	 * @param $str
	 *
	 * @return bool|mixed
	 */
	public function symbolize_comparison( $str ) {

		$symbols = [
			'equals'                   => '=',
			'not_equals'               => '!=',
			'less_than'                => '<',
			'greater_than'             => '>',
			'more_than'                => '>',
			'less_than_or_equal_to'    => '<=',
			'greater_than_or_equal_to' => '>=',
			'in'                       => 'IN',
			'not_in'                   => 'NOT IN',
			'like'                     => 'LIKE',
			'not_like'                 => 'NOT LIKE',
			'rlike'                    => 'RLIKE',
		];

		if ( in_array( $str, $symbols ) ) {
			return $str;
		}

		return get_array_var( $symbols, $str );
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

		$parsed_clauses = [];

		foreach ( $where as $i => $unparsed_clause ) {

			if ( ! is_array( $unparsed_clause ) ) {

				if ( is_int( $i ) && is_string( $unparsed_clause ) ) {
					$parsed_clauses[] = $unparsed_clause;
					continue;
				}

				// Assume first order
				$value = $unparsed_clause;
				$col   = $i;

				if ( ! in_array( $col, $this->get_allowed_columns() ) ) {
					continue;
				}

				if ( is_numeric( $value ) ) {
					$parsed_clauses[] = $wpdb->prepare( "$col = %d", $value );
				} else {
					$parsed_clauses[] = $wpdb->prepare( "$col = %s", $value );
				}

			} else if ( isset_not_empty( $unparsed_clause, 'relationship' ) ) {

				$parsed_clauses[] = '(' . $this->build_advanced_where_statement( $unparsed_clause ) . ')';

			} else {

				$unparsed_clause = wp_parse_args( $unparsed_clause, [
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
					if ( isset_not_empty( $unparsed_clause, $from ) ) {
						$unparsed_clause[ $to ] = $unparsed_clause[ $from ];
					}
				}

				if ( ! in_array( $unparsed_clause['compare'], $this->get_allowed_comparisons() ) ) {
					$unparsed_clause['compare'] = $this->symbolize_comparison( $unparsed_clause['compare'] );

					if ( ! $unparsed_clause['compare'] ) {
						continue;
					}
				}

				if ( in_array( $unparsed_clause['col'], $this->get_allowed_columns() ) ) {

					$value = $unparsed_clause['val'];

					if ( is_array( $value ) && ! in_array( $unparsed_clause['compare'], [ 'IN', 'NOT IN' ] ) ) {
						$unparsed_clause['compare'] = 'IN';
					}

					switch ( $unparsed_clause['compare'] ) {
						default:
						case '=':
						case '!=':
						case '>':
						case '>=':
						case '<':
						case '<=':
						case '<>':
						case 'LIKE':
							if ( is_numeric( $value ) ) {
								$parsed_clauses[] = $wpdb->prepare( "{$unparsed_clause[ 'col' ]} {$unparsed_clause[ 'compare' ]} %d", $value );
							} else {
								$parsed_clauses[] = $wpdb->prepare( "{$unparsed_clause[ 'col' ]} {$unparsed_clause[ 'compare' ]} %s", $value );
							}
							break;
						case 'RLIKE':

							if ( is_numeric( $value ) ) {
								$parsed_clauses[] = $wpdb->prepare( "{$unparsed_clause[ 'col' ]} {$unparsed_clause[ 'compare' ]} %d", $value );
							} else {
								$value            = str_replace( '\\', '\\\\', preg_quote_except( $value, [
									'=',
									':'
								], '@' ) );
								$parsed_clauses[] = "{$unparsed_clause[ 'col' ]} {$unparsed_clause[ 'compare' ]} '$value'";
							}

							break;
						case 'IN':
						case 'NOT IN':

							if ( is_array( $value ) ) {
								$value = map_deep( $value, 'sanitize_text_field' );
								$value = maybe_implode_in_quotes( $value );
							}

							$parsed_clauses[] = "{$unparsed_clause[ 'col' ]} {$unparsed_clause['compare']} ({$value})";


							break;
					}

				}

			}

		}

		return implode( " {$relationship} ", $parsed_clauses );
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
			'NOT IN',
		];
	}

	/**
	 * @return string
	 */
	public function get_date_key() {
		return 'date_created';
	}

	/**
	 * Get the sum of a column
	 *
	 * @param $column
	 * @param $args
	 *
	 * @return array|array[]|bool|int|object|object[]|null
	 */
	public function sum( $column, $args ) {
		unset( $args['offset'] );
		unset( $args['limit'] );
		unset( $args['LIMIT'] );

		$args['select'] = $column;
		$args['func']   = 'SUM';

		return $this->query( $args );
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
		unset( $args['number'] );

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

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) && ! doing_cli() ) {
			exit;
		}

		delete_option( $this->table_name . '_db_version' );

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS " . $this->table_name );

	}

	/**
	 * Retrieve the date created via an SQL query
	 *
	 * @throws \Exception
	 *
	 * @return \DateTimeInterface
	 */
	public function get_date_created() {

		global $wpdb;

		$results = $wpdb->get_results( "SELECT create_time FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = '{$wpdb->dbname}' AND table_name = '{$this->table_name}';" );
		$date    = $results[0]->create_time;

		return new DateTimeHelper( $date );
	}

	/**
	 * Drop a column
	 *
	 * @param $column
	 *
	 * @return void
	 */
	public function drop_column( $column ) {
		global $wpdb;
		$wpdb->query( "ALTER TABLE {$this->table_name} DROP COLUMN $column" );
	}

	/**
	 * Drop an index
	 *
	 * @param array $indexes
	 *
	 * @return void
	 */
	public function drop_indexes( array $indexes ) {
		foreach ( $indexes as $index ){
			$this->drop_index( $index );
		}
	}

	/**
	 * Drop an index
	 *
	 * @param array $indexes
	 *
	 * @return void
	 */
	public function drop_index( string $index ) {

		global $wpdb;
		$wpdb->query( "DROP INDEX $index ON {$this->table_name};" );
	}

	/**
	 * Create a new index
	 *
	 * @param string $name
	 * @param array  $columns
	 *
	 * @return void
	 */
	public function create_index( string $name, array $columns ){
		global $wpdb;
		$wpdb->query( sprintf( "CREATE INDEX $name ON {$this->table_name} (%s);", implode(',', $columns ) ) );
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
	 * @since  2.4
	 * @return bool Returns if the contacts table was installed and upgrade routine run
	 */
	public function installed() {
		return self::table_exists( $this->table_name );
	}

	/**
	 * Check if the given table exists
	 *
	 * @since  2.4
	 *
	 * @param string $table The table name
	 *
	 * @return bool          If the table name exists
	 */
	public static function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

	/**
	 * The column exists
	 *
	 * @param $column_name
	 *
	 * @return bool
	 */
	public function column_exists( $column_name ){
		global $wpdb;

		if ( in_array( $column_name, $wpdb->get_col( "DESC $this->table_name", 0 ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The command to create a table
	 *
	 * @return string
	 */
	public function create_table_sql_command() {
		return '';
	}

	/**
	 * Create the DB
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $this->create_table_sql_command() );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Delete orphaned meta
	 */
	public function delete_orphaned_meta() {
		do_action( 'groundhogg/db/delete_orphaned_meta/' . $this->get_object_type(), $this );
	}
}
