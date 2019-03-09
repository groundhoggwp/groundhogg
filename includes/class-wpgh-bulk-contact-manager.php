<?php

/**
 * Importer
 *
 * This class contains function for importing and exporting contact information from the tools page.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Bulk_Contact_Manager
{

    /**
     * @var WPGH_DB_Contacts
     */
    public $db;

    /**
     * The time of importing.
     *
     * @var int
     */
    public $import_time;

    /**
     * The tag for the given import.
     *
     * @var int
     */
    public $import_tag;

    /**
     * The ID of the current import.
     *
     * @var int
     */
    public $import_id;

    /**
     * WPGH_Bulk_Contact_Manager constructor.
     */
    public function __construct()
    {

        $this->db = WPGH()->contacts;

        add_action( 'wp_ajax_wpgh_import_contacts', array( $this, 'import' ) );
        add_action( 'wp_ajax_wpgh_export_contacts', array( $this, 'export' ) );
        add_action( 'wp_ajax_wpgh_query_export_contacts', array( $this, 'query_export' ) );
        add_action( 'wp_ajax_wpgh_bulk_delete_contacts', array( $this, 'delete' ) );

        if ( did_action( 'admin_enqueue_scripts' ) ){
            $this->scripts();
        } else {
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
        }

    }

    /**
     * Enqueue the import-export js
     */
    public function scripts()
    {
        wp_enqueue_script( 'papaparse', WPGH_ASSETS_FOLDER . 'lib/papa-parse/papaparse.js' );
        wp_enqueue_script( 'wpgh-import-export', WPGH_ASSETS_FOLDER . 'js/admin/import-export.min.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/import-export.min.js' ) );
        //wp_die( 'scripts' );
    }

    /**
     * Export a list of contacts.
     */
    public function export()
    {
        if ( ! current_user_can( 'export_contacts' ) )
            wp_die( 'You cannot manage contacts.' );

        if ( empty( $_POST[ 'tags' ] ) ){

            $contacts = WPGH()->contacts->get_contacts();

        } else {

            $tags = WPGH()->tags->validate( $_POST[ 'tags' ] );

            $query = new WPGH_Contact_Query();

            $contacts = $query->query(array(
                'tags_include' => $tags
            ));

        }

        $keys = WPGH()->contact_meta->get_keys();

        $exclude_keys = [
            'ID',
            'email',
            'first_name',
            'last_name',
            'user_id',
            'owner_id',
            'optin_status',
            'date_created',
        ];

        foreach ( $contacts as $contact ){

            foreach ( $keys as $key ){

                if ( ! in_array( $key, $exclude_keys ) ) {
                    $contact->$key = WPGH()->contact_meta->get_meta($contact->ID, $key, true);
                }

            }

        }

        wp_die( json_encode( $contacts ) );

    }

    /**
     * Export contacts from the contact page
     */
    public function query_export()
    {
        if ( ! current_user_can( 'export_contacts' ) )
            wp_die( new WP_Error( 'INVALID_PERMISSIONS', __( 'You do not have permission export contacts.' ) ) );

        $c_query = new WPGH_Contact_Query();
        $args = get_transient( 'wpgh_contact_query_args' );

        if ( ! $args || ! is_array( $args ) ){
            wp_die( new WP_Error( 'NO_QUERY', __( 'no query was present.' ) ));
        }

        $contacts = $c_query->query( $args );

        $keys = WPGH()->contact_meta->get_keys();

        $exclude_keys = [
            'ID',
            'email',
            'first_name',
            'last_name',
            'user_id',
            'owner_id',
            'optin_status',
            'date_created',
        ];

        foreach ( $contacts as $contact ){

            foreach ( $keys as $key ){

                if ( ! in_array( $key, $exclude_keys ) ){

                    $contact->$key = WPGH()->contact_meta->get_meta( $contact->ID, $key, true );
                }

            }

        }

        wp_die( json_encode( $contacts ) );

    }

    /**
     * Bulk delete contacts
     */
    public function delete()
    {

        global $wpdb;

        if ( ! current_user_can( 'delete_contacts' ) )
            wp_die( 'You cannot manage contacts.' );

        if ( empty( $_POST[ 'tags' ] ) ){

            wp_die( __( 'Please select at least 1 tag.', 'groundhogg' ) );

        } else {

            $tags = WPGH()->tags->validate( $_POST[ 'tags' ] );

            $query = new WPGH_Contact_Query();

            $contacts = $query->query(array(
                'tags_include' => $tags
            ));

            foreach ( $contacts as $contact ){
                WPGH()->contacts->delete( $contact->ID );
            }

        }

        wp_die( sprintf( __( 'Deleted %d contacts.', 'groundhogg' ), count( $contacts )  ));

    }

    /**
     * Sets the import tag
     *
     * @return bool|int|mixed
     */
    public function get_import_tag()
    {
        if ( get_transient( 'wpgh_import_' . $this->import_id . '_tag' ) ){
            $this->import_tag = get_transient( 'wpgh_import_' . $this->import_id . '_tag'  );
        } else {
            $this->import_time = time();
            $this->import_tag = WPGH()->tags->add( array(
                'tag_name' => sprintf( '%s %s', __( 'Import' ), date_i18n( 'Y-m-d H:i:s', $this->import_time ) ),
            ) );
            set_transient( 'wpgh_import_' . $this->import_id . '_tag', $this->import_tag, HOUR_IN_SECONDS );
        }

        return $this->import_tag;
    }

    /**
     * Perform the importing of contacts
     */
    public function import()
    {

        if ( ! current_user_can( 'import_contacts' ) )
            wp_die( 'You cannot manage contacts.' );

        $data = $_POST[ 'data' ];
        $this->import_id = sanitize_key( $_POST[ 'import_id' ] );
        $this->get_import_tag();

        $completed = 0;
        $skipped = 0;

        foreach ( $data as $i => $contact ){

            $contact = $this->generate( $contact );

            if ( $contact && $contact->exists() ){
                $contact->add_tag( $this->import_tag );
                $completed += 1;
            } else {
                $skipped += 1;
            }

        }

        wp_die( json_encode( array( 'completed' => $completed, 'skipped' => $skipped ) ) );

    }

    /**
     * Example Args:
     * {
     *     "First": "foo",
     *     "Last": "bar"
     * }
     *
     * @param $args
     * @return WPGH_Contact|false
     */
    public function generate( $args )
    {

        $args = $this->prepare( $args );

        $data = array();

        /* Handle $args cases first */
        if ( isset( $args[ 'first_name' ] ) ){
            $data['first_name'] = sanitize_text_field( $args[ 'first_name' ] );
            unset( $args[ 'first_name' ] );
        }

        if ( isset( $args[ 'last_name' ] ) ){
            $data['last_name'] = sanitize_text_field( $args[ 'last_name' ] );
            unset( $args[ 'last_name' ] );
        }

        if ( isset( $args[ 'email' ] ) ){
            $data['email'] = sanitize_email( $args[ 'email' ] );
            unset( $args[ 'email' ] );
        }

        if ( ! is_email( $data['email'] ) ){
            return false;
        }

        if ( isset( $args['owner_id'] ) ){
            $data[ 'owner_id' ] = intval( $args['owner_id'] );
            unset( $args[ 'owner_id' ] );
        }

        if ( $this->db->exists( $data[ 'email' ] ) ){
            $contact = new WPGH_Contact( $data[ 'email'] );
            $contact->update( $data );
        } else {
            $id = $this->db->add( $data );
            $contact = new WPGH_Contact( $id );
        }

        if ( isset( $args[ 'optin_status' ] ) ){

            if ( intval( $args[ 'optin_status' ] )  === WPGH_UNSUBSCRIBED ){
                $contact->unsubscribe();
            } else {
                $contact->change_marketing_preference( intval( $args[ 'optin_status' ] ) );
            }

        }

        if ( isset( $args[ 'tags' ] ) ){

            $tags = explode( ',',  $args[ 'tags' ]  );
            $tags = array_map( 'trim', $tags );
            $contact->add_tag( $tags );
            unset( $args[ 'tags' ] );

        }

        /* handle tags from the tag form. */
        if ( isset( $_POST[ 'tags' ] ) ){
            $contact->add_tag( $_POST[ 'tags' ] );
        }

        /*let's just quickly process the meta and get out for now */
        foreach ( $args as $key => $value ){
            if ( ! empty( $value ) ){
                $contact->update_meta( $key, sanitize_text_field( $value ) );
            }
        }

        /* Special case for country code */
        if ( key_exists( 'country', $args ) ){

            /* Not a country code */
            if ( strlen( $args[ 'country' ] ) !== 2 ){

                $countries = wpgh_get_countries_list();
                $code = array_search( $args[ 'country' ], $countries );
                if ( $code ){
                    $contact->update_meta( 'country', $code );
                }

            }

        }

        //todo, not sure about this
        $contact->update_meta( 'last_optin', $this->import_time );

        return $contact;

    }

    /**
     * MAke sure all the arguments are clean
     *
     * @param $args
     * @return array
     */
    public function prepare( $args )
    {
        $sanitized_args = array();

        foreach ( $args as $key => $arg){

            $new_key = sanitize_key( str_replace( ' ', '_', strtolower( trim( $key ) ) ) );

            $sanitized_args[ $new_key ] = sanitize_text_field( trim( $arg ) );
        }

        return $sanitized_args;

    }


}