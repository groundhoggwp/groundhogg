<?php
/**
 * Activity DB
 *
 * Stores information about a contact's site activity.
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
class WPGH_DB_Activity extends WPGH_DB  {

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'activity';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        if ( wpgh_should_if_multisite() ){
            $this->table_name  = $wpdb->prefix . 'gh_activity';
        } else {
            $this->table_name  = $wpdb->base_prefix . 'gh_activity';
        }

        $this->primary_key = 'ID';
        $this->version     = '1.0';

        add_action( 'wpgh_post_delete_contact',  array( $this, 'contact_deleted' ) );
        add_action( 'wpgh_delete_funnel',   array( $this, 'funnel_deleted' ) );
        add_action( 'wpgh_delete_step',     array( $this, 'step_deleted' ) );
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
            'timestamp'     => '%d',
            'funnel_id'     => '%d',
            'step_id'       => '%d',
            'contact_id'    => '%d',
            'event_id'      => '%d',
            'email_id'      => '%d',
            'activity_type' => '%s',
            'referer'       => '%s',
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
            'timestamp'     => 0,
            'funnel_id'     => 0,
            'step_id'       => 0,
            'contact_id'    => 0,
            'event_id'      => 0,
            'email_id'      => 0,
            'activity_type' => '',
            'referer'      => '',
        );
    }

    /**
     * Add a activity
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['timestamp'] ) ) {
            return false;
        }

        return $this->insert( $args, 'activity' );
    }

    /**
     * Insert a new activity
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
     * Update activity
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
     * Delete activity
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {
        $result = parent::delete( $id );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Retrieve activity like the given args
     *
     * @access  public
     * @since   2.1
     */
    public function get_activity( $data = array(), $order = 'timestamp' ) {

        global  $wpdb;

        if ( ! is_array( $data ) )
            return false;

        $other = '';

        /* allow for special handling of time based search */
        if ( isset( $data[ 'start' ] ) ){

            $other .= sprintf( " AND timestamp >= %d", $data[ 'start' ] );

        }

        /* allow for special handling of time based search */
        if ( isset( $data[ 'end' ] ) ){

            $other .= sprintf( " AND timestamp <= %d", $data[ 'end' ] );

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

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where $other ORDER BY $order DESC" );

        return $results;

    }

    /**
     * Count the number of rows
     *
     * @param array $args
     * @return int
     */
    public function count( $args = array() )
    {

        return count( $this->get_activity( $args ) );

    }

    /**
     * Check to see if activity like the objet supplied exists
     *
     * @access  public
     * @since   2.1
     */
    public function activity_exists( $data = array() ) {

        $results = $this->get_activity( $data );

        return ! empty( $results );

    }

    /**
     * Helper function to bulk delete events in the event associated things happen.
     *
     * @param array $args
     * @return false|int
     */
    public function bulk_delete( $data = array(), $where= array( '%d' ) )
    {
        global $wpdb;

        $column_formats = $this->get_columns();
        $data = array_intersect_key( $data, $column_formats );

        $result = $wpdb->delete( $this->table_name, $data );

        return $result;
    }

    /**
     * Delete events for a contact that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function contact_deleted( $id ){
        return $this->bulk_delete(  array( 'contact_id' => $id ) );
    }

    /**
     * Delete events for a funnel that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function funnel_deleted( $id ){
        return $this->bulk_delete(  array( 'funnel_id' => $id ) );
    }

    /**
     * Delete events for a step that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function step_deleted( $id ){
        return $this->bulk_delete(  array( 'step_id' => $id ) );
    }

    /**
     * Sets the last_changed cache key for activitys.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for activitys.
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

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        activity_type VARCHAR(20) NOT NULL,
        event_id bigint(20) unsigned NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        referer text NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY funnel_id (funnel_id),
        KEY step_id (step_id),
        KEY event_id (event_id)
		) $charset_collate;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}