<?php

/**
 * DB Parent Class
 *
 * This class is the foundation for all DB activities in Groundhogg. With the exception of several new functions
 * such as generate_where, generate_search and search, this class was mostly borrowed from EDD with several mods and the original copyright belongs to Pippin...
 *
 * @package     Includes
 * @subpackage  Includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GH base class
 *
 * @package     GH
 * @subpackage  Classes/GH DB
 * @copyright   Copyright (c) 2018, Adrian Tobey (modified from EDD_DB)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       0.1
 */
abstract class WPGH_DB {

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
     * Get the charset
     *
     * @return string
     */
    public function get_charset_collate()
    {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }


    /**
     * Create a where clause given an array
     *
     * @param array $args
     * @param string $operator
     * @return string
     */
    public function generate_where( $args = array(), $operator = "AND" ){

        $where = array();
        if (!empty($args) && is_array($args)) {
            foreach ($args as $key => $value) {

                if ( is_string( $value ) ){
                    $value = "'" . $value . "'";
                }

                if ( strpos( $value, '%' ) !== false ){
                    $where[] = $key . " LIKE " . $value;
                } else {
                    $where[] = $key . " = " . $value;
                }
            }
        }

        return implode( " {$operator} ", $where );

    }

    /**
     * Search the records
     *
     * @param string $s
     *
     * @return array
     */
    public function search( $s ='' )
    {
        global $wpdb;

        $where = $this->generate_search( $s );

        return $wpdb->get_results(
            "SELECT * FROM $this->table_name WHERE $where ORDER BY $this->primary_key DESC"
        );
    }

    /**
     * Generates the search WHERE Clause
     * @param $s
     *
     * @return string
     */
    public function generate_search( $s ='' )
    {
        global $wpdb;

        $where_args = array();

        foreach ( $this->get_columns() as $column => $type ){
            if ( $type === '%s' ){
                $where_args[ $column ] = "%" . $wpdb->esc_like( $s ) . "%";
            }
        }

        $where = $this->generate_where( $where_args, "OR" );

        return $where;
    }

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {}

    /**
     * Whitelist of columns
     *
     * @access  public
     * @since   2.1
     * @return  array
     */
    public function get_columns() {
        return array();
    }

    /**
     * Default column values
     *
     * @access  public
     * @since   2.1
     * @return  array
     */
    public function get_column_defaults() {
        return array();
    }

    /**
     * Retrieve a row by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  object
     */
    public function get( $row_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
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
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ) );
    }

    /**
     * Retrieve a specific column's value by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  string
     */
    public function get_column( $column, $row_id ) {
        global $wpdb;
        $column = esc_sql( $column );
        return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
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
        return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) );
    }

    /**
     * Insert a new row
     *
     * @access  public
     * @since   2.1
     * @return  int
     */
    public function insert( $data, $type = '' ) {
        global $wpdb;

        // Set default values
        $data = wp_parse_args( $data, $this->get_column_defaults() );

        do_action( 'wpgh_pre_insert_' . $type, $data );

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys = array_keys( $data );
        $column_formats = array_merge( array_flip( $data_keys ), $column_formats );

        $wpdb->insert( $this->table_name, $data, $column_formats );
        $wpdb_insert_id = $wpdb->insert_id;

        do_action( 'wpgh_post_insert_' . $type, $wpdb_insert_id, $data );

        return $wpdb_insert_id;
    }

    /**
     * Update a row
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {

        global $wpdb;

        $row_id = absint( $row_id );

        if( empty( $row_id ) ) {
            return false;
        }

        if( empty( $where ) ) {
            $where = $this->primary_key;
        }

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys = array_keys( $data );
        $column_formats = array_merge( array_flip( $data_keys ), $column_formats );


        if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) {
            return false;
        }

        return true;
    }

    /**
     * Mass update records
     *
     * @param $data array
     * @param $where array
     *
     * @return bool;
     */
    public function mass_update( $data, $where )
    {
        global $wpdb;

        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );
        $where = array_change_key_case( $where );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );
        $where = array_intersect_key( $where, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data
//        $data_keys = array_keys( $data );
//        $column_formats = array_merge( array_flip( $data_keys ), );

        if ( false === $wpdb->update( $this->table_name, $data, $where ) ) {
            return false;
        }

        return true;
    }

    /**
     * Delete a row identified by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function delete( $row_id = 0 ) {

        global $wpdb;

        // Row ID must be positive integer
        $row_id = absint( $row_id );

        if( empty( $row_id ) ) {
            return false;
        }

        if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given table exists
     *
     * @since  2.4
     * @param  string $table The table name
     * @return bool          If the table name exists
     */
    public function table_exists( $table ) {
        global $wpdb;
        $table = sanitize_text_field( $table );

        return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
    }

    /**
     * Check if the table was ever installed
     *
     * @since  2.4
     * @return bool Returns if the contacts table was installed and upgrade routine run
     */
    public function installed() {
        return $this->table_exists( $this->table_name );
    }

    /**
     * Drops the table
     */
    public function drop(){

        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

        delete_option( $this->table_name . '_db_version' );

        global $wpdb;

        $wpdb->query( "DROP TABLE IF EXISTS " . $this->table_name );

    }

    /**
     * Update the DB if required
     */
    public function update_db()
    {
        if ( ! $this->installed() || get_option( $this->table_name . '_db_version' ) !== $this->version ) {
            $this->create_table();
        }
    }

    /**
     * Create the DB
     */
    abstract public function create_table();


}