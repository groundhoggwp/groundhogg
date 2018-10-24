<?php
/**
 * Broadcasts DB
 *
 * Stores information about broadcasts
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_DB_Contacts Class
 *
 * @since 2.1
 */
class WPGH_DB_Broadcasts extends WPGH_DB  {

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'broadcasts';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        $this->table_name  = $wpdb->prefix . 'gh_broadcasts';
        $this->primary_key = 'ID';
        $this->version     = '1.0';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'                => '%d',
            'email_id'          => '%d',
            'scheduled_by'      => '%d',
            'send_time'         => '%d',
            'tags'              => '%s',
            'status'            => '%s',
            'date_scheduled'    => '%s',
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
            'ID'                => 0,
            'email_id'          => 0,
            'scheduled_by'      => 0,
            'send_time'         => 0,
            'tags'              => '',
            'status'            => 'scheduled',
            'date_scheduled'    => current_time( 'mysql' ),
        );
    }

    /**
     * Given a data set, if tags are present make sure the end up serialized
     *
     * @param array $data
     * @return array
     */
    private function serialize_tags( $data = array() ){

        if ( isset( $data[ 'tags' ] ) ){

            $data[ 'tags' ] = maybe_serialize( $data[ 'tags' ] );

        }

        return $data;

    }

    /**
     * Given a data set, if tags are present make sure they end up unserialized
     *
     * @param null $obj
     * @return null
     */
    private function unserialize_tags( $obj = null )
    {
        if ( is_object( $obj ) && isset( $obj->tags ) ){
            $obj->tags = maybe_unserialize( $obj->tags );
        }

        return $obj;
    }

    /**
     * Add a broadcast
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );
        
        $args = $this->serialize_tags( $args );

        return $this->insert( $args, 'broadcast' );
    }

    /**
     * Insert a new broadcast
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
     * Update a broadcast
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {
        $data = $this->serialize_tags( $data );
        
        $result = parent::update( $row_id, $data, $where );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Delete a broadcast
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {

        if ( empty( $id ) ) {
            return false;
        }

        $broadcast = $this->get_broadcast_by( 'ID', $id );

        if ( $broadcast->ID > 0 ) {

            global $wpdb;

            $result = $wpdb->delete( $this->table_name, array( 'ID' => $broadcast->ID ), array( '%d' ) );

            if ( $result ) {
                $this->set_last_changed();
            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a broadcast exists
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
     * Retrieves the broadcast by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_broadcast( $id )
    {
        return  $this->get_broadcast_by( 'ID', $id );
    }

    /**
     * Retrieves a single broadcast from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or broadcast
     * @param  mixed  $value  The Customer ID or broadcast to search
     * @return mixed          Upon success, an object of the broadcast. Upon failure, NULL
     */
    public function get_broadcast_by( $field = 'ID', $value = 0 ) {

        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return $this->unserialize_tags( parent::get_by( $field, $value ) );
    }

    /**
     * Retrieve broadcasts from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_broadcasts( $data = array() ) {

        global  $wpdb;

        if ( ! is_array( $data ) )
            return false;

        $data = (array) $data;

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

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where ORDER BY send_time DESC" );

        if ( is_array( $results ) ){
            $results = array_map( array( $this, 'unserialize_tags' ), $results );
        }

        return $results;
    }


    /**
     * Count the total number of broadcasts in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_broadcasts( $args ) );

    }

    /**
     * Sets the last_changed cache key for broadcasts.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for broadcasts.
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
        email_id bigint(20) unsigned NOT NULL,
        scheduled_by bigint(20) unsigned NOT NULL,
        send_time bigint(20) unsigned NOT NULL,
        tags longtext NOT NULL,
        status VARCHAR(20) NOT NULL,
        date_scheduled datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}