<?php
/**
 * Contacts DB class
 *
 * This class is for interacting with the superlinks' database table
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
class WPGH_DB_Superlinks extends WPGH_DB  {

        /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'superlinks';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        $this->table_name  = $wpdb->prefix . 'gh_superlinks';
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
            'ID'            => '%d',
            'name'          => '%s',
            'target'        => '%s',
            'tags'          => '%s',
            'clicks'        => '%d',
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
            'name'          => '',
            'target'        => '',
            'tags'          => '',
            'clicks'        => 0,
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
     * Add a superlink
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if ( empty( $args[ 'target' ] ) ){
            return false;
        }

        $args = $this->serialize_tags( $args );

        return $this->insert( $args, 'superlink' );
    }

    /**
     * Insert a new superlink
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
     * Update a superlink
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
     * Delete a superlink
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {

        if ( empty( $id ) ) {
            return false;
        }

        $superlink = $this->get_superlink_by( 'ID', $id );

        if ( $superlink->ID > 0 ) {

            global $wpdb;

            $result = $wpdb->delete( $this->table_name, array( 'ID' => $superlink->ID ), array( '%d' ) );

            if ( $result ) {
                $this->set_last_changed();
            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a superlink exists
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
     * Retrieves the superlink by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_superlink( $id )
    {
        return $this->get_superlink_by( 'ID', $id );
    }

    /**
     * Retrieves a single superlink from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or superlink
     * @param  mixed  $value  The Customer ID or superlink to search
     * @return mixed          Upon success, an object of the superlink. Upon failure, NULL
     */
    public function get_superlink_by( $field = 'ID', $value = 0 ) {

        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return $this->unserialize_tags( parent::get_by( $field, $value ) );
    }

    public function search($s = '')
    {
        $results = parent::search($s); // TODO: Change the autogenerated stub


        if ( is_array( $results ) ){

            $results = array_map( array( $this, 'unserialize_tags' ), $results );

        }

        return $results;

    }

    /**
     * Retrieve superlinks from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_superlinks( $data = array() ) {

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

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where" );

        if ( is_array( $results ) ){

            $results = array_map( array( $this, 'unserialize_tags' ), $results );

        }

        return $results;
    }


    /**
     * Count the total number of superlinks in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_superlinks( $args ) );

    }

    /**
     * Sets the last_changed cache key for superlinks.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for superlinks.
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
        name mediumtext NOT NULL,
        target mediumtext NOT NULL,
        tags longtext NOT NULL,
        clicks bigint(20) NOT NULL,
        PRIMARY KEY  (ID)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}