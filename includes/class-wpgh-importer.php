<?php

class WPGH_Importer
{

    /**
     * @var WPGH_DB_Contacts
     */
    public $db;


    public function __construct()
    {

        $this->db = WPGH()->contacts;

        add_action( 'wp_ajax_wpgh_import_contacts', array( $this, 'import' ) );
        add_action( 'wp_ajax_wpgh_export_contacts', array( $this, 'export' ) );

        if ( did_action( 'admin_enqueue_scripts' ) ){
            $this->scripts();
        } else {
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
        }

    }

    public function scripts()
    {
        wp_enqueue_script( 'papaparse', WPGH_ASSETS_FOLDER . 'lib/papa-parse/papaparse.js' );
        wp_enqueue_script( 'wpgh-import-export', WPGH_ASSETS_FOLDER . 'js/admin/import-export.js' );
        //wp_die( 'scripts' );
    }

    public function export()
    {

        global $wpdb;

        if ( ! current_user_can( 'gh_manage_contacts' ) )
            wp_die( 'You cannot manage contacts.' );

        if ( empty( $_POST[ 'tags' ] ) ){

            $contacts = WPGH()->contacts->get_contacts();

            wp_die( json_encode( $contacts ) );

        } else {

            $tags = WPGH()->tags->validate( $_POST[ 'tags' ] );

            $query = new WPGH_Contact_Query();

            $contacts = $query->query(array(
                'tags_include' => $tags
            ));

            wp_die( json_encode( $contacts ) );

        }

    }

    public function import()
    {

        if ( ! current_user_can( 'gh_manage_contacts' ) )
            wp_die( 'You cannot manage contacts.' );

        $contacts = $_POST[ 'data' ];

        //wp_die( json_encode( $contacts ) );

        foreach ( $contacts as $contact ){
            $contact = $this->generate( $contact );
        }

        wp_die( 'Finished.' );

    }

    /**
     * Example Args:
     * {
     *     "First": "foo",
     *     "Last": "bar"
     * }
     *
     * @param $args
     * @return WPGH_Contact
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
            wp_die( sprintf( 'Invalid data given: %s', json_encode( $data ) ) );
        }

        if ( isset( $args['owner'] ) ){
            $data[ 'owner_id' ] = intval( $args['owner'] );
            unset( $args[ 'owner' ] );
        }

        if ( $this->db->exists( $data[ 'email' ] ) ){
            $contact = new WPGH_Contact( $data[ 'email'] );
            $contact->update( $data );
        } else {
            $id = $this->db->add( $data );
            $contact = new WPGH_Contact( $id );
            $contact->update( array( 'optin_status' => WPGH_UNCONFIRMED ) );
        }

        if ( isset( $args[ 'tags' ] ) ){

            $tags = explode( ',',  $args[ 'tags' ]  );
            $tags = array_map( 'trim', $tags );
            $tags = WPGH()->tags->validate( $tags );
            $contact->add_tag( $tags );
            unset( $args[ 'tags' ] );

        }

        /* handle tags from the tag form. */
        if ( isset( $_POST[ 'tags' ] ) ){
//            $tags = explode( ',',  $args[ 'tags' ]  );
            $tags = array_map( 'intval', $_POST[ 'tags' ] );
            $tags = WPGH()->tags->validate( $tags );
            $contact->add_tag( $tags );
        }

        /*let's just quickly process the meta and get out for now */

        foreach ( $args as $key => $value ){
            $contact->update_meta( $key, sanitize_text_field( $value ) );
        }

        return $contact;

    }

    public function prepare( $args )
    {
        $sanitized_args = array();

        foreach ( $args as $key => $arg){

            $new_key = sanitize_key( str_replace( ' ', '_', strtolower( $key ) ) );

            $sanitized_args[ $new_key ] = sanitize_text_field( $arg );
        }

        return $sanitized_args;

    }


}