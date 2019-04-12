<?php

namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
class Broadcasts extends DB  {

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_broadcasts';
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
        return 'broadcast';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return [
            'ID'                => '%d',
            'object_id'         => '%d',
            'object_type'       => '%s',
            'scheduled_by'      => '%d',
            'send_time'         => '%d',
            'tags'              => '%s',
            'status'            => '%s',
            'date_scheduled'    => '%s',
        ];
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults() {
        return [
            'ID'                => 0,
            'object_id'         => 0,
            'object_type'       => 'email',
            'scheduled_by'      => 0,
            'send_time'         => 0,
            'tags'              => '',
            'status'            => 'scheduled',
            'date_scheduled'    => current_time( 'mysql' ),
        ];
    }

    /**
     * Given a data set, if tags are present make sure they end up serialized
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
     * Update a broadcast
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {
        $data = $this->serialize_tags( $data );

        $result = parent::update( $row_id, $data, $where );

        return $result;
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
        object_id bigint(20) unsigned NOT NULL,
        object_type VARCHAR(20) NOT NULL,
        scheduled_by bigint(20) unsigned NOT NULL,
        send_time bigint(20) unsigned NOT NULL,
        tags longtext NOT NULL,
        status VARCHAR(20) NOT NULL,
        date_scheduled datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}