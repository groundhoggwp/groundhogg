<?php
namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Funnels DB
 *
 * Store information about funnels
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Funnels extends DB  {

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_funnels';
    }

    /**
     * Get the DB primary key
     *
     * @return string
     */
    public function get_primary_key()
    {
        return 'ID';
    }

    /**
     * Get the DB version
     *
     * @return mixed
     */
    public function get_db_version()
    {
        return '2.0';
    }

    /**
     * Get the object type we're inserting/updating/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'funnel';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'            => '%d',
            'author'        => '%d',
            'title'         => '%s',
            'status'        => '%s',
            'date_created'  => '%s',
            'last_updated'  => '%s',
        );
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults() {
        return array(
            'ID'            => 0,
            'author'        => 0,
            'title'         => '',
            'status'        => 'inactive',
            'date_created'  => current_time( 'mysql' ),
            'last_updated'  => current_time( 'mysql' ),
        );
    }

    /**
     * Add a funnel
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if ( empty( $args[ 'title' ] ) ){
            return false;
        }

        return $this->insert( $args );
    }

    /**
     * Retrieves the funnel by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_funnel( $id )
    {
        return $this->get_funnel_by( 'ID', $id );
    }

    /**
     * Retrieves a single funnel from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or funnel
     * @param  mixed  $value  The Customer ID or funnel to search
     * @return mixed          Upon success, an object of the funnel. Upon failure, NULL
     */
    public function get_funnel_by( $field = 'ID', $value = 0 ) {

        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return parent::get_by( $field, $value );
    }


    /**
     * Retrieve funnels from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_funnels( $data = array() ) {

        global  $wpdb;

        if ( ! is_array( $data ) )
            return false;

        $data = (array) $data;

        $extra = '';

        if ( isset( $data[ 'search' ] ) ){

            $extra .= sprintf( " AND (%s)", $this->generate_search( $data[ 'search' ] ) );

        }

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        $where = $this->generate_where( $data );

        if ( empty( $where ) ){

            $where = "1=1";

        }

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where $extra ORDER BY $this->primary_key DESC" );

        return $results;
    }

    /**
     * Count the total number of funnels in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_funnels( $args ) );

    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {

        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title text NOT NULL,
        status varchar(20) NOT NULL,
        author bigint(20) unsigned NOT NULL,
        active_contacts bigint(20) unsigned NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        $wpdb->query( "ALTER TABLE $this->table_name AUTO_INCREMENT = 2" );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}