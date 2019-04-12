<?php
namespace Groundhogg\DB;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SMS DB
 *
 * Store sms messages
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.2
 */
class SMS extends DB
{


    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_sms';
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
     * Get the object type we're inserting/updateing/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'sms';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'      => '%d',
            'title'   => '%s',
            'message' => '%s',
            'author'  => '%d',
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
            'ID'      => 0,
            'title'   => '',
            'message' => '',
            'author'  => get_current_user_id(),
        );
    }

    /**
     * Add a sms
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['title'] ) ) {
            return false;
        }

        return $this->insert( $args );
    }

    /**
     * Retrieves the sms by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_sms( $id )
    {
        return $this->get_sms_by( 'ID', $id );
    }

    /**
     * Retrieves a single sms from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or email
     * @param  mixed  $value  The Customer ID or email to search
     * @return mixed          Upon success, an object of the sms. Upon failure, NULL
     */
    public function get_sms_by( $field = 'ID', $value = 0 ) {
        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        if ( 'ID' == $field ) {
            // Make sure the value is numeric to avoid casting objects, for example,
            // to int 1.
            if ( ! is_numeric( $value ) ) {
                return false;
            }

            $value = intval( $value );

            if ( $value < 1 ) {
                return false;
            }

        }

        if ( ! $value ) {
            return false;
        }

        $results = $this->get_by( $field, $value );

        if ( empty( $results ) ) {
            return false;
        }

        return $results;
    }

    /**
     * Retrieve smss from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_all_sms() {

        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY $this->primary_key DESC" );

        return $results;

    }

    /**
     * GET SMS messages ready for select/dropdown
     *
     * @return array
     */
    public function get_sms_select() {
        global $wpdb;
        $results = $this->get_all_sms();
        $smses = [];
        foreach ( $results as $sms ){
            $smses[ $sms->ID ] = sprintf( "%s", $sms->title );
        }

        return $smses;
    }

    /**
    * Retrieve messages from the database
    *
    * @access  public
    * @since   2.1
    */
    public function get_smses( $data = array() ) {

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
     * Count the total number of smss in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {
        return count( $this->get_smses( $args ) );
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
        message text NOT NULL,
        author bigint(20) unsigned NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}