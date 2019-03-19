<?php
/**
 * Contact DB
 *
 * Store contact info
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
class WPGH_DB_Contacts extends WPGH_DB  {

    /**
     * The metadata type.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $meta_type = 'contact';

    /**
     * The name of the date column.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $date_key = 'date_created';

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'contacts';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        $this->db_name = 'gh_contacts';
        $this->table_name();

        $this->primary_key = 'ID';
        $this->version     = '1.0';

        add_action( 'profile_update', array( $this, 'update_contact_email_on_user_update' ), 10, 2 );

    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'             => '%d',
            'email'          => '%s',
            'first_name'     => '%s',
            'last_name'      => '%s',
            'user_id'        => '%d',
            'owner_id'       => '%d',
            'optin_status'   => '%d',
            'date_created'   => '%s',
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
            'ID'             => 0,
            'email'          => '',
            'first_name'     => '',
            'last_name'      => '',
            'user_id'        => 0,
            'owner_id'       => 0,
            'optin_status'   => 0,
            'date_created'   => current_time( 'mysql' ),
        );
    }

    /**
     * Add a contact
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['email'] ) ) {
            return false;
        }

        /* Make sure lowercase. */
        $args[ 'email' ] = strtolower( $args[ 'email' ] );

        if( $this->exists( $args['email'], 'email' ) ) {
            // update an existing contact

            $contact = $this->get_contact_by( 'email', $args[ 'email' ] );
            $this->update( $contact->ID, $data );

            $result = $contact->ID;

        } else {

            $result = $this->insert( $args, 'contact' );

            do_action( 'wpgh_contact_created', $result );

        }

        return $result;

    }

    /**
     * Insert a new contact
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
     * Update a contact
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {

        if ( isset( $data[ 'email' ] ) ){
            $data[ 'email' ] = strtolower( $data[ 'email' ] );
        }

        $result = parent::update( $row_id, $data, $where );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Delete a contact
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $_id_or_email = false ) {

        if ( empty( $_id_or_email ) ) {
            return false;
        }

        $column   = is_email( $_id_or_email ) ? 'email' : 'ID';
//        $contact = $this->get_contact_by( $column, $_id_or_email );

        if ( $this->exists( $_id_or_email, $column ) ) {

            global $wpdb;

            $contact = $this->get_contact_by( $column, $_id_or_email );

            do_action( 'wpgh_pre_delete_contact', $contact->ID );

            /* delete tag relationships */
            $result = $wpdb->delete( $this->table_name, array( 'ID' => $contact->ID ), array( '%d' ) );


            if ( $result ) {

                $this->set_last_changed();

                do_action( 'wpgh_post_delete_contact', $contact->ID );

            }

            return $result;

        } else {
            return false;
        }

    }

    /**
     * Checks if a contact exists
     *
     * @access  public
     * @since   2.1
     */
    public function exists( $value = '', $field = 'email' ) {

        $columns = $this->get_columns();

        if ( ! array_key_exists( $field, $columns ) ) {
            return false;
        }

        $contact = $this->get_contact_by( $field, $value );

        return ! empty( $contact );

    }

    /**
     * Updates the email address of a contact record when the email on a user is updated
     *
     * @access  public
     * @since   2.4
     */
    public function update_contact_email_on_user_update( $user_id = 0, $old_user_data = '' ) {

        $contact = new WPGH_Contact( $user_id, true );

        if( ! $contact ) {
            return false;
        }

        $user = get_userdata( $user_id );

        if( ! empty( $user ) && $user->user_email !== $contact->email ) {

            if( ! $this->get_contact_by( 'email', $user->user_email ) ) {

                $success = $this->update( $contact->ID, array( 'user_id' => $user_id ) );
                $success = $this->update( $contact->ID, array( 'email' => $user->user_email ) );

                if( $success ) {
                    // Update some payment meta if we need to

                    do_action( 'wpgh_update_contact_email_on_user_update', $user, $contact );

                }

            }

        }

    }

    /**
     * Retrieves a single contact from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or email
     * @param  mixed  $value  The Customer ID or email to search
     * @return mixed          Upon success, an object of the contact. Upon failure, NULL
     */
    public function get_contact_by( $field = 'ID', $value = 0 ) {
        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return parent::get_by( $field, $value );
    }

    /**
     * Retrieve contacts from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_contacts( $args = array() ) {
        $args = $this->prepare_contact_query_args( $args );
        $args['count'] = false;

        $query = new WPGH_Contact_Query( '', $this );

        return $query->query( $args );
    }


    /**
     * Count the total number of contacts in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {
        $args = $this->prepare_contact_query_args( $args );
        $args['count'] = true;
        $args['offset'] = 0;

        $query   = new WPGH_Contact_Query( '', $this );
        $results = $query->query( $args );

        return $results;
    }

    /**
     * Prepare query arguments for `WPGH_Contact_Query`.
     *
     * This method ensures that old arguments transition seamlessly to the new system.
     *
     * @access protected
     * @since  2.8
     *
     * @param array $args Arguments for `WPGH_Contact_Query`.
     * @return array Prepared arguments.
     */
    protected function prepare_contact_query_args( $args ) {
        if ( ! empty( $args['ID'] ) ) {
            $args['include'] = $args['ID'];
            unset( $args['ID'] );
        }

        if ( ! empty( $args['user_id'] ) ) {
            $args['users_include'] = $args['user_id'];
            unset( $args['user_id'] );
        }

        if ( ! empty( $args['date'] ) ) {
            $date_query = array( 'relation' => 'AND' );

            if ( is_array( $args['date'] ) ) {
                $date_query[] = array(
                    'after'     => date( 'Y-m-d 00:00:00', strtotime( $args['date']['start'] ) ),
                    'inclusive' => true,
                );
                $date_query[] = array(
                    'before'    => date( 'Y-m-d 23:59:59', strtotime( $args['date']['end'] ) ),
                    'inclusive' => true,
                );
            } else {
                $date_query[] = array(
                    'year'  => date( 'Y', strtotime( $args['date'] ) ),
                    'month' => date( 'm', strtotime( $args['date'] ) ),
                    'day'   => date( 'd', strtotime( $args['date'] ) ),
                );
            }

            if ( empty( $args['date_query'] ) ) {
                $args['date_query'] = $date_query;
            } else {
                $args['date_query'] = array(
                    'relation' => 'AND',
                    $date_query,
                    $args['date_query'],
                );
            }

            unset( $args['date'] );
        }

        return $args;
    }

    /**
     * Sets the last_changed cache key for contacts.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for contacts.
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
		email varchar(50) NOT NULL,
		first_name mediumtext NOT NULL,
		last_name mediumtext NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		owner_id bigint(20) unsigned NOT NULL,
		optin_status int unsigned NOT NULL,
		date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY (ID),
		UNIQUE KEY email (email),
		KEY user (user_id)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}