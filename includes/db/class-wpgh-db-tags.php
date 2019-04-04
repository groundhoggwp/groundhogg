<?php
/**
 * Tags DB
 *
 * Store tags
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_DB_Tags extends WPGH_DB
{
    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'contact_tags';

    /**
     * Runtime associative array of ID => tag_object
     *
     * @var array
     */
    public $tag_cache = [];

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        $this->db_name = 'gh_tags';
        $this->table_name();

        $this->primary_key = 'tag_id';
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
            'tag_id'             => '%d',
            'tag_name'           => '%s',
            'tag_slug'           => '%s',
            'tag_description'    => '%s',
            'contact_count'      => '%d',
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
            'tag_name'           => '',
            'tag_slug'           => '',
            'tag_description'    => '',
            'contact_count'      => 0,
        );
    }

    /**
     * Given a list of tags, make sure that the tags exist, if they don't add/or remove them
     *
     * @param array $maybe_tags
     * @return array $tags
     */
    public function validate( $maybe_tags=array() ){

        $tags = array();

        if ( ! is_array( $maybe_tags ) ){
            $maybe_tags = array( $maybe_tags );
        }

        foreach ( $maybe_tags as $i => $tag_id_or_string ) {

            if ( is_numeric( $tag_id_or_string ) ){

                $tag_id = intval( $tag_id_or_string );

                if ( $this->exists( $tag_id ) ) {
                    $tags[] = $tag_id;
                }

            } else if ( is_string( $tag_id_or_string ) ){

                $slug = sanitize_title( $tag_id_or_string );

                if ( $this->exists( $slug, 'tag_slug' ) ) {

                    $tag = $this->get_tag_by( 'tag_slug', $slug );

                    $tags[] = $tag->tag_id;

                } else {

                    $tags[] = $this->add( array( 'tag_name' => $tag_id_or_string ) );

                }
            }
        }

        return $tags;
    }

    /**
     * Add a tag
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['tag_name'] ) ) {
            return false;
        }

        $args[ 'tag_slug' ] = sanitize_title( $args[ 'tag_name' ] );
        if ( $this->exists( $args[ 'tag_slug' ], 'tag_slug' ) ){
            $tag = $this->get_tag_by( 'tag_slug', $args[ 'tag_slug' ] );
            return $tag->tag_id;
        }

        return $this->insert( $args, 'tag' );
    }

    /**
     * Insert a new tag
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
     * Update a tag
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
     * Delete a tag
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {

        if ( empty( $id ) ) {
            return false;
        }

        $tag = $this->get_tag( $id );

        if ( $tag->tag_id > 0 ) {

            global $wpdb;

            /* delete the actual tag */
            $result = $wpdb->delete( $this->table_name, array( 'tag_id' => $tag->tag_id ), array( '%d' ) );

            if ( $result ) {
                $this->set_last_changed();
                unset( $this->tag_cache[ md5( $id ) ] );
                do_action( 'wpgh_delete_tag', $tag->tag_id );
            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a tag exists
     *
     * @access  public
     * @since   2.1
     */
    public function exists( $value = 0, $field = 'tag_id' ) {

        $columns = $this->get_columns();
        if ( ! array_key_exists( $field, $columns ) ) {
            return false;
        }

        if ( $field === 'tag_id' ){
            $tag = $this->get( $value );
        } else {
            $tag = $this->get_tag_by( $field, $value );
        }

        return ! empty( $tag ) ;

    }

    /**
     * Retrieves the tag by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_tag( $id )
    {
        $id = absint( $id );
        $cache_key = md5( $id );

        if ( key_exists( $cache_key, $this->tag_cache ) ){
            return $this->tag_cache[ $cache_key ];
        }

        $tag = $this->get_tag_by( 'tag_id', $id );
        $this->tag_cache[ $cache_key ] = $tag;
        return $tag;
    }

    /**
     * Retrieves a single tag from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or email
     * @param  mixed  $value  The Customer ID or email to search
     * @return mixed          Upon success, an object of the tag. Upon failure, NULL
     */
    public function get_tag_by( $field = 'tag_id', $value = 0 ) {
        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        if ( 'tag_id' == $field ) {
            // Make sure the value is numeric to avoid casting objects, for example,
            // to int 1.
            if ( ! is_numeric( $value ) ) {
                return false;
            }

            $value = intval( $value );

            if ( $value < 1 ) {
                return false;
            }

        } else if ( 'tag_slug' == $field ) {
            if ( ! is_string( $value ) ) {
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
     * Retrieve tags from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_tags() {

        global $wpdb;

        $results = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY $this->primary_key DESC" );

        return $results;

    }

    public function get_tags_select() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY $this->primary_key DESC" );

        $tags = [];

        foreach ( $results as $tag ){
            $tags[ $tag->tag_id ] = sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count );
        }

        return $tags;
    }

    /**
     * Increase the contact tag count
     *
     * @param $tag_id
     */
    public function increase_contact_count( $tag_id )
    {

        if ( ! $this->exists( $tag_id ) ) {
            return;
        }

        $tag = $this->get_tag( $tag_id );
        $tag->contact_count = intval( $tag->contact_count ) + 1;
        $this->update( $tag_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
    }

    /**
     * Decrease the contact tag count
     *
     * @param $tag_id
     */
    public function decrease_contact_count( $tag_id )
    {

        if ( ! $this->exists( $tag_id ) ) {
            return;
        }

        $tag = $this->get_tag( $tag_id );
        $tag->contact_count = intval( $tag->contact_count ) - 1;
        $this->update( $tag_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
    }

    /**
     * Count the total number of tags in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_tags() );

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

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE " . $this->table_name . " (
        tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        tag_slug varchar(50) NOT NULL,
        tag_name mediumtext NOT NULL,
        tag_description text NOT NULL,
        contact_count bigint(20) unsigned NOT NULL,
        PRIMARY KEY (tag_id),
        UNIQUE KEY tag_slug (tag_slug)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}