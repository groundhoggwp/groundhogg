<?php
namespace Groundhogg\DB;

use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Tags extends DB
{

    /**
     * Runtime associative array of ID => tag_object
     *
     * @var array
     */
    public $tag_cache = [];

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_tags';
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
        return 'tag';
    }

    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_insert/tag_relationship', [ $this, 'increase_contact_count' ], 10, 2 );
        add_action( 'groundhogg/db/post_delete/tag_relationship', [ $this, 'decrease_contact_count' ], 10 );
        add_action( 'groundhogg/db/pre_bulk_delete/tag_relationships', [ $this, 'bulk_decrease_tag_count' ], 10 );
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
                    $tags[] = $this->add( array( 'tag_name' => sanitize_text_field( $tag_id_or_string ) ) );
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

        return $this->insert( $args );
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

            $result = parent::delete( $id );

            if ( $result ) {
                unset( $this->tag_cache[ md5( $id ) ] );
            }

            return $result;

        } else {
            return false;
        }

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

    /**
     * Get tags in an array format that is select friendly.
     *
     * @return array
     */
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
     * @param $insert_id
     * @param $args
     */
    public function increase_contact_count( $insert_id = 0, $args = [] )
    {
        $tag_id = absint( $args[ 'tag_id' ] );

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
     * @param $insert_id
     * @param $args
     */
    public function decrease_contact_count( $args = [] )
    {
        if ( ! isset_not_empty( $args, 'tag_id' ) ){
            return;
        }

        $tag_id = absint( $args[ 'tag_id' ] );

        if ( ! $this->exists( $tag_id ) ) {
            return;
        }

        $tag = $this->get_tag( $tag_id );
        $tag->contact_count = intval( $tag->contact_count ) - 1;
        $this->update( $tag_id, array( 'contact_count' => $tag->contact_count ), $this->primary_key );
    }

    /**
     * Bulk decrease the contact count.
     *
     * @param $tag_ids
     */
    public function bulk_decrease_tag_count( $tag_ids ){

        if ( empty( $tag_ids ) ){
            return;
        }

        foreach ( $tag_ids as $id ){
            $this->decrease_contact_count( [ 'tag_id' => $id ] );
        }
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
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {

        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

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