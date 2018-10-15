<?php
/**
 * Contacts DB class
 *
 * This class is for interacting with the funnels' database table
 *
 * @package     EDD
 * @subpackage  Classes/DB Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey (Modified From EDD)
 * @license     http://opensource.org/licenses/gpl-3.0 GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_DB_Contacts Class
 *
 * @since 2.1
 */
class WPGH_DB_Funnels extends WPGH_DB  {

        /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'funnels';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        $this->table_name  = $wpdb->prefix . 'gh_funnels';
        $this->primary_key = 'ID';
        $this->version     = '1.0';
        
        add_action( 'wpgh_post_insert_event', array( $this, 'calculate_active_contacts' ) );
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
            'active_contacts' => '%d',
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
            'active_contacts' => 0,
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

        return $this->insert( $args, 'funnel' );
    }

    /**
     * Insert a new funnel
     *
     * @access  public
     * @since   2.1
     * @return  int
     */
    public function insert( $data, $type = '' ) {
        $result = parent::insert( $data, $type );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Calculate the number of contacts for a specific funnel
     * 
     * @param $wpdb_insert_id int ID
     */
    public function calculate_active_contacts( $wpdb_insert_id )
    {
        $event = WPGH()->events->get( $wpdb_insert_id );
        $count = WPGH()->events->count( array( 'funnel_id' => $event->funnel_id, 'start' => strtotime( '30 days ago' ) ) );
        $this->update( $event->funnel_id, array( 'active_contacts' => $count ) );
    }

    /**
     * Update a funnel
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {

        $result = parent::update( $row_id, $data, $where );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Delete a funnel
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {

        if ( empty( $id ) ) {
            return false;
        }

        $funnel = $this->get_funnel_by( 'ID', $id );

        if ( $funnel->ID > 0 ) {

            global $wpdb;

            $result = $wpdb->delete( $this->table_name, array( 'ID' => $funnel->ID ), array( '%d' ) );

            if ( $result ) {
                $this->set_last_changed();
            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a funnel exists
     *
     * @access  public
     * @since   2.1
     */
    public function exists( $value = 0, $field = 'ID' ) {

        $columns = $this->get_columns();
        if ( ! array_key_exists( $field, $columns ) ) {
            return false;
        }

        return (bool) $this->get_column_by( 'ID', $field, $value );

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
     * Sets the last_changed cache key for funnels.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for funnels.
     *
     * @access public
     * @since  2.8
     */
    public function get_last_changed() {
        if ( function_exists( 'wp_cache_get_last_changed' ) ) {
            return wp_cache_get_last_changed( $this->cache_group );
        }

        $last_changed = wp_cache_get( 'last_changed', $this->cache_group );
        if ( ! $last_changed ) {
            $last_changed = microtime();
            wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
        }

        return $last_changed;
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title text NOT NULL,
        status varchar(20) NOT NULL,
        author bigint(20) unsigned NOT NULL,
        active_contacts bigint(20) unsigned NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        $wpdb->query( "ALTER TABLE $this->table_name AUTO_INCREMENT = 2" );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}