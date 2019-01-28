<?php
/**
 * Email DB
 *
 * Store user emails
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
class WPGH_DB_Emails extends WPGH_DB  {

    /**
     * The metadata type.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $meta_type = 'email';

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'emails';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        if ( wpgh_should_if_multisite() ){
            $this->table_name  = $wpdb->prefix . 'gh_emails';
        } else {
            $this->table_name  = $wpdb->base_prefix . 'gh_emails';
        }

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
            'subject'       => '%s',
            'pre_header'    => '%s',
            'content'       => '%s',
            'author'        => '%d',
            'from_user'     => '%d',
            'status'        => '%s',
            'last_updated'  => '%s',
            'date_created'  => '%s',
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
            'subject'       => '',
            'pre_header'    => '',
            'content'       => '',
            'author'        => 0,
            'from_user'     => 0,
            'status'        => 'draft',
            'last_updated'  => current_time( 'mysql' ),
            'date_created'  => current_time( 'mysql' ),
        );
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

        return $this->insert( $args, 'email' );
    }

    /**
     * Insert a new email
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
     * Update a email
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = 'ID' ) {
        $result = parent::update( $row_id, $data, $where );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Delete a email
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {

        if ( empty( $id ) ) {
            return false;
        }

        $email = $this->get_email_by( 'ID', $id );

        if ( $email->ID > 0 ) {

            global $wpdb;

            $result = $wpdb->delete( $this->table_name, array( 'ID' => $email->ID ), array( '%d' ) );

            if ( $result ) {
                $this->set_last_changed();
            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a email exists
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
     * Retrieves the email by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_email( $id )
    {
        return $this->get_email_by( 'ID', $id );
    }

    /**
     * Retrieves a single email from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or email
     * @param  mixed  $value  The Customer ID or email to search
     * @return mixed          Upon success, an object of the email. Upon failure, NULL
     */
    public function get_email_by( $field = 'ID', $value = 0 ) {

        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return parent::get_by( $field, $value );
    }

    /**
     * Retrieve emails from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_emails( $data = array() ) {

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
     * Count the total number of emails in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_emails( $args ) );

    }

    /**
     * Sets the last_changed cache key for emails.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for emails.
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
        content longtext NOT NULL,
        subject text NOT NULL,
        pre_header text NOT NULL,
        from_user bigint(20) unsigned NOT NULL,
        author bigint(20) unsigned NOT NULL,   
        status VARCHAR(20) NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}