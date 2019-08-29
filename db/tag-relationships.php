<?php
namespace Groundhogg\DB;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * tag relationships DB
 *
 * Store the relationships between tags and contacts
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Tag_Relationships extends DB
{
    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'contact_tag_relationships';

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_tag_relationships';
    }

    /**
     * Get the DB primary key
     *
     * @return string
     */
    public function get_primary_key()
    {
        return 'tag_id';
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
        return 'tag_relationship';
    }

    /**
     * Clean up after tag/contact is deleted.
     */
    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
        add_action( 'groundhogg/db/post_delete/tag', [ $this, 'tag_deleted' ] );
        parent::add_additional_actions();
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'tag_id'             => '%d',
            'contact_id'         => '%d',
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
            'tag_id'             => 0,
            'contact_id'         => 0,
        );
    }

    /**
     * Add a tag relationship
     *
     * @access  public
     * @since   2.1
     */
    public function add( $tag_id = 0, $contact_id = 0 ) {

        if ( ! $tag_id || ! $contact_id ){
            return false;
        }

        $data = array(
            'tag_id' => absint( $tag_id ),
            'contact_id' => absint( $contact_id )
        );

        $result = $this->insert( $data );

        return $result;
    }

    /**
     * A tag was delete, delete all tag relationships
     *
     * @param $tag_id
     *
     * @return bool
     */
    public function tag_deleted( $tag_id ){
        return $this->delete( [ 'tag_id' => $tag_id ] );
    }

    /**
     * A contact was deleted, delete all tag relationships
     *
     * @param $contact_id
     *
     * @return bool
     */
    public function contact_deleted( $contact_id ){

        $tags = $this->get_tags_by_contact( $contact_id );

        if ( empty( $tags ) ){
            return false;
        }

        do_action( 'groundhogg/db/pre_bulk_delete/tag_relationships', wp_parse_id_list( $tags ) );

        return $this->delete( [ 'contact_id' => $contact_id ] );

    }

    /**
     * Delete a tag relationship
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $args = array() ) {

        global $wpdb;

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $args );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys = array_keys( $data );
        $column_formats = array_merge( array_flip( $data_keys ), $column_formats );

        if ( false === $wpdb->delete( $this->table_name, $data, $column_formats ) ) {
            return false;
        }

        do_action( 'groundhogg/db/post_delete/tag_relationship', $args );

        return true;

    }

    /**
     * Checks if a tag relationship exists
     *
     * @access  public
     * @since   2.1
     */
    public function exists( $tag_id = 0, $contact_id = 0 ) {

        if ( empty( $tag_id ) || empty( $contact_id ) ) {
            return false;
        }

        if ( ! WPGH()->tags->exists( $tag_id ) || ! WPGH()->contacts->exists( $contact_id, 'ID' ) ) {

            return false;

        }

        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE contact_id = %s AND tag_id = %s", $contact_id, $tag_id ) );

        return (bool) $result;

    }


    /**
     * Retrieve tags from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_relationships( $value, $column='contact_id', $return="tag_id" )        {

        if ( empty( $value ) || ! is_numeric( $value ) )
            return false;

        global $wpdb;

        $results = $wpdb->get_col("SELECT $return FROM $this->table_name WHERE $column = $value ORDER BY $this->primary_key DESC" );

        if ( empty( $results ) ) {
            return false;
        }

        return $results;
    }

    /**
     * Get a list of tags associated with a particular contact
     *
     * @param int $contact_id
     * @return array|bool|null|object
     */
    public function get_tags_by_contact( $contact_id=0 ){
        return $this->get_relationships( $contact_id, 'contact_id', 'tag_id' );
    }


    /**
     * Get a list of contacts associated with a particular tag
     *
     * @param int $tag_id
     * @return array|bool|null|object
     */
    public function get_contacts_by_tag( $tag_id = 0 ){
        return $this->get_relationships( $tag_id, 'tag_id', 'contact_id' );

    }

    /**
     * Sets the last_changed cache key for tags.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for tags.
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE " . $this->table_name . " (
		tag_id bigint(20) unsigned NOT NULL,
		contact_id bigint(20) unsigned NOT NULL,
		PRIMARY KEY (tag_id,contact_id),
		KEY tag_id (tag_id),
		KEY contact_id (contact_id)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}