<?php
/**
 * Step Meta DB
 *
 * Allows for the use of metadata api usage
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

class WPGH_DB_Step_Meta extends WPGH_DB {

    /**
     * Get things started
     *
     * @access  public
     * @since   2.6
     */
    public function __construct() {
        global $wpdb;

        if ( wpgh_should_if_multisite() ){
            $this->table_name  = $wpdb->prefix . 'gh_stepmeta';
        } else {
            $this->table_name  = $wpdb->base_prefix . 'gh_stepmeta';
        }

        $this->primary_key = 'meta_id';
        $this->version     = '1.0';

        add_action( 'plugins_loaded', array( $this, 'register_table' ) );
        add_action( 'installing_groundhogg', array( $this, 'register_table' ) );
        add_action( 'wpgh_delete_step', array( $this, 'delete_step_meta_on_delete' ) );

    }

    /**
     * Get table columns and data types
     *
     * @access  public
     * @since   1.7.18
     */
    public function get_columns() {
        return array(
            'meta_id'     => '%d',
            'step_id'     => '%d',
            'meta_key'    => '%s',
            'meta_value'  => '%s',
        );
    }

    /**
     * Register the table with $wpdb so the metadata api can find it
     *
     * @access  public
     * @since   2.6
     */
    public function register_table() {
        global $wpdb;
        $wpdb->stepmeta = $this->table_name;
        $wpdb->tables[] = 'gh_stepmeta';
    }

    /**
     * Clean up contact Meta if steps gets delete
     *
     * @param $id int the ID of the step
     * @return false|int
     */
    public function delete_step_meta_on_delete( $id ){

        global $wpdb;

        $result = $wpdb->delete( $this->table_name, array( 'step_id' => $id ), array( '%d' ) );

        return $result;

    }

    /**
     * Retrieve step meta field for a step.
     *
     * For internal use only. Use EDD_Contact->get_meta() for public usage.
     *
     * @param   int    $step_id   Contact ID.
     * @param   string $meta_key      The meta key to retrieve.
     * @param   bool   $single        Whether to return a single value.
     * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
     *
     * @access  private
     * @since   2.6
     */
    public function get_meta( $step_id = 0, $meta_key = '', $single = false ) {
        $step_id = $this->sanitize_step_id( $step_id );
        if ( false === $step_id ) {
            return false;
        }

        return get_metadata( 'step', $step_id, $meta_key, $single );
    }

    /**
     * Add meta data field to a step.
     *
     * For internal use only. Use EDD_Contact->add_meta() for public usage.
     *
     * @param   int    $step_id   Contact ID.
     * @param   string $meta_key      Metadata name.
     * @param   mixed  $meta_value    Metadata value.
     * @param   bool   $unique        Optional, default is false. Whether the same key should not be added.
     * @return  bool                  False for failure. True for success.
     *
     * @access  private
     * @since   2.6
     */
    public function add_meta( $step_id = 0, $meta_key = '', $meta_value, $unique = false ) {
        $step_id = $this->sanitize_step_id( $step_id );
        if ( false === $step_id ) {
            return false;
        }

        return add_metadata( 'step', $step_id, $meta_key, $meta_value, $unique );
    }

    /**
     * Update step meta field based on Contact ID.
     *
     * For internal use only. Use EDD_Contact->update_meta() for public usage.
     *
     * Use the $prev_value parameter to differentiate between meta fields with the
     * same key and Contact ID.
     *
     * If the meta field for the step does not exist, it will be added.
     *
     * @param   int    $step_id   Contact ID.
     * @param   string $meta_key      Metadata key.
     * @param   mixed  $meta_value    Metadata value.
     * @param   mixed  $prev_value    Optional. Previous value to check before removing.
     * @return  bool                  False on failure, true if success.
     *
     * @access  private
     * @since   2.6
     */
    public function update_meta( $step_id = 0, $meta_key = '', $meta_value, $prev_value = '' ) {
        $step_id = $this->sanitize_step_id( $step_id );
        if ( false === $step_id ) {
            return false;
        }

        return update_metadata( 'step', $step_id, $meta_key, $meta_value, $prev_value );
    }

    /**
     * Remove metadata matching criteria from a step.
     *
     * For internal use only. Use EDD_Contact->delete_meta() for public usage.
     *
     * You can match based on the key, or key and value. Removing based on key and
     * value, will keep from removing duplicate metadata with the same key. It also
     * allows removing all metadata matching key, if needed.
     *
     * @param   int    $step_id   Contact ID.
     * @param   string $meta_key      Metadata name.
     * @param   mixed  $meta_value    Optional. Metadata value.
     * @return  bool                  False for failure. True for success.
     *
     * @access  private
     * @since   2.6
     */
    public function delete_meta( $step_id = 0, $meta_key = '', $meta_value = '' ) {
        return delete_metadata( 'step', $step_id, $meta_key, $meta_value );
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.6
     */
    public function create_table() {

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE {$this->table_name} (
		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		step_id bigint(20) unsigned NOT NULL,
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY step_id (step_id),
		KEY meta_key (meta_key)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

    /**
     * Given a step ID, make sure it's a positive number, greater than zero before inserting or adding.
     *
     * @since  2.6
     * @param  int|string $step_id A passed step ID.
     * @return int|bool                The normalized step ID or false if it's found to not be valid.
     */
    private function sanitize_step_id( $step_id ) {
        if ( ! is_numeric( $step_id ) ) {
            return false;
        }

        $step_id = (int) $step_id;

        // We were given a non positive number
        if ( absint( $step_id ) !== $step_id ) {
            return false;
        }

        if ( empty( $step_id ) ) {
            return false;
        }

        return absint( $step_id );

    }

}