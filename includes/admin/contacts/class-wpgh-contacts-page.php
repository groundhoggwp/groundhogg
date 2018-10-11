<?php
/**
 * View Contacts
 *
 * Allow the user to view & edit the contacts
 *
 * @package     groundhogg
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Contacts_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

    public function __construct()
    {
        add_action('wp_ajax_wpgh_inline_save_contacts', array( $this, 'save_inline' ) );

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_contacts' ){

            add_action( 'init' , array( $this, 'process_action' )  );

            $this->notices = WPGH()->notices;

        }
    }

    /**
     * Get the affected contacts
     *
     * @return array|bool
     */
    private function get_contacts()
    {
        $contacts = isset( $_REQUEST['contact'] ) ? $_REQUEST['contact'] : null;

        if ( ! $contacts )
            return false;

        return is_array( $contacts )? array_map( 'intval', $contacts ) : array( intval( $contacts ) );
    }

    /**
     * Get the current action
     *
     * @return bool
     */
    private function get_action()
    {
        if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * Get the previous action
     *
     * @return mixed
     */
    private function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    /**
     * Get the screen title
     */
    private function get_title()
    {
        switch ( $this->get_action() ){
            case 'add':
                _e( 'Add Contact' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Contact' , 'groundhogg' );
                break;
            default:
                _e( 'Contacts', 'groundhogg' );
        }
    }

    /**
     * Process the given action
     */
    public function process_action()
    {

        if ( ! $this->get_action() || ! $this->verify_action() || ! current_user_can( 'gh_manage_contacts' ) )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( ! empty( $_POST ) )
                {
                    $this->add_contact();
                }

                break;

            case 'edit':

                if ( ! empty( $_POST ) ){

                    $this->update_contact();

                }

                break;

            case 'spam':

                foreach ( $this->get_contacts() as $id ) {

                    $contact = new WPGH_Contact( $id );
                    $args = array( 'optin_status' => WPGH_SPAM );
                    $contact->update( $args );

                    $ip_address = $contact->get_meta('ip_address' );

                    if ( $ip_address ) {
                        $blacklist = get_option( 'blacklist_keys' );
                        $blacklist .= "\n" . $ip_address;
                        $blacklist = sanitize_textarea_field( $blacklist );
                        update_option( 'blacklist_keys', $blacklist );
                    }

                    do_action( 'wpgh_contact_marked_as_spam', $id );
                }

	            $this->notices->add(
		            esc_attr( 'spammed' ),
		            sprintf( "%s %d %s",
			            __( 'Marked', 'groundhogg' ),
			            count( $this->get_contacts() ),
			            __( 'contacts as spam', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_spam_contacts' );

                break;

            case 'delete':

                foreach ( $this->get_contacts() as $id ){

                    do_action( 'wpgh_deleted_contact', $id );
                    WPGH()->contacts->delete( $id );

                }

	            $this->notices->add(
		            esc_attr( 'deleted' ),
		            sprintf( "%s %d %s",
			            __( 'Deleted', 'groundhogg' ),
			            count( $this->get_contacts() ),
			            __( 'Contacts', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_delete_contacts' );

                break;

            case 'unspam':

                foreach ( $this->get_contacts() as $id ) {
                    $contact = new WPGH_Contact( $id );
                    $args = array( 'optin_status' => WPGH_UNCONFIRMED );
                    $contact->update( $args );
                }

	            $this->notices->add(
		            esc_attr( 'unspam' ),
		            sprintf( "%s %d %s",
			            __( 'Approved', 'groundhogg' ),
			            count( $this->get_contacts() ),
			            __( 'Contacts', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_unspam_contacts' );

                break;

        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_contacts() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }

    /**
     * Create a contact via the admin area
     */
    private function add_contact()
    {
        do_action( 'wpgh_admin_add_contact_before' );

        if ( ! isset( $_POST['email'] ) ){
            $this->notices->add( 'NO_EMAIL', __( "Please enter a valid email address", 'groundhogg' ), 'error' );
            return;
        }

        if ( isset( $_POST[ 'first_name' ] ) )
            $args['first_name'] = sanitize_text_field( $_POST[ 'first_name' ] );

        if ( isset( $_POST[ 'last_name' ] ) )
            $args['last_name'] = sanitize_text_field( $_POST[ 'last_name' ] );

        if ( isset( $_POST[ 'email' ] ) )
            $args['email'] = sanitize_email( $_POST[ 'email' ] );

        if ( ! is_email( $args['email'] ) ){
            $this->notices->add( 'BAD_EMAIL', __( "Please enter a valid email address", 'groundhogg' ), 'error' );
            return;
        }

        if ( isset( $_POST['owner'] ) ){
            $args[ 'owner_id' ] = intval( $_POST['first_name'] );
        }

        $id = WPGH()->contacts->add( $args );

        $contact = new WPGH_Contact( $id );

        if ( isset( $_POST[ 'primary_phone' ] ) ){

            $contact->update_meta( 'primary_phone', sanitize_text_field( $_POST[ 'primary_phone' ] ) );

        }

        if ( isset( $_POST[ 'primary_phone_extension' ] ) ){

            $contact->update_meta( 'primary_phone', sanitize_text_field( $_POST[ 'primary_phone_extension' ] ) );

        }

        if ( isset( $_POST[ 'notes' ] ) ){
            $contact->add_note( $_POST[ 'notes' ] );
        }

        if ( isset( $_POST[ 'tags' ] ) ) {
            $contact->add_tag( $_POST[ 'tags' ] );
        }

        $this->notices->add( 'created', __( "Contact created!", 'groundhogg' ), 'success' );

        do_action( 'wpgh_admin_add_contact_after', $id );

        wp_redirect( admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $id ) );
        die();
    }

    /**
     * Update the contact via the admin screen
     */
    private function update_contact()
    {

        $id = intval( $_GET[ 'contact' ] );

        if ( ! $id ){
            return;
        }

        $contact = new WPGH_Contact( $id );

        do_action( 'wpgh_admin_update_contact_before', $id );

        //todo security check

        /* Save the meta first... as actual fields might overwrite it later... */
        $cur_meta = WPGH()->contact_meta->get_meta( $id );

        $posted_meta = $_POST[ 'meta' ];

        foreach ( $cur_meta as $key => $value ){

            if ( isset( $posted_meta[ $key ] ) ){

                $contact->update_meta( $key, sanitize_text_field( $posted_meta[ $key ] ) );

            } else {

                $contact->delete_meta( $key );

            }
        }

        /* add new meta */
        if ( isset( $_POST[ 'newmetakey' ] ) && isset( $_POST[ 'newmetavalue' ] ) ){

            $new_meta_keys = $_POST[ 'newmetakey' ];
            $new_meta_vals = $_POST[ 'newmetavalue' ];

            foreach ( $new_meta_keys as $i => $new_meta_key ){
                $contact->update_meta( sanitize_key( $new_meta_key ), sanitize_text_field( $new_meta_vals[ $i ] ) );
            }

        }

        /* Update Main Contact Information */
        $args = array();

        if ( isset( $_POST[ 'unsubscribe' ] ) ) {

            $args[ 'optin_status' ] = WPGH_UNSUBSCRIBED;

            do_action( 'wpgh_preference_unsubscribe', $id );

            $this->notices->add(
                esc_attr( 'unsubscribed' ),
                __( 'This contact will no longer receive email communication', 'groundhogg' ),
                'info'
            );
        }

        if ( isset( $_POST[ 'email' ] ) ) {
            $args[ 'email' ] = sanitize_email(  $_POST[ 'email' ] );
        }

        if ( isset( $_POST['first_name'] ) ){
            $args[ 'first_name' ] = sanitize_text_field( $_POST['first_name'] );
        }

        if ( isset( $_POST['last_name'] ) ){
            $args[ 'last_name' ] = sanitize_text_field( $_POST['last_name'] );
        }

        if ( isset( $_POST['owner'] ) ){
            $args[ 'owner_id' ] = intval( $_POST['first_name'] );
        }

        $contact->update( $args );

        if ( isset( $_POST['primary_phone'] ) ){
            $contact->update_meta( 'primary_phone', sanitize_text_field( $_POST['primary_phone'] ) );
        }

        if ( isset( $_POST['primary_phone_extension'] ) ){
            $contact->update_meta( 'primary_phone_extension', sanitize_text_field( $_POST['primary_phone_extension'] ) );
        }

        if ( isset( $_POST[ 'notes' ] ) ){
            $contact->update_meta( 'notes', sanitize_textarea_field( $_POST['notes'] ) );
        }

        if ( isset( $_POST[ 'lead_source' ] ) ){
            $contact->update_meta( 'lead_source', esc_url_raw( $_POST['lead_source'] ) );
        }

        if ( isset( $_POST[ 'page_source' ] ) ){
            $contact->update_meta( 'page_source', esc_url_raw( $_POST['page_source'] ) );
        }

        if ( isset( $_POST[ 'tags' ] ) ){

            $tags = WPGH()->tags->validate( $_POST['tags' ] );

            $cur_tags = $contact->tags;
            $new_tags = $tags;

            $delete_tags = array_diff( $cur_tags, $new_tags );
            if ( ! empty( $delete_tags ) ) {
                $contact->remove_tag( $delete_tags );
            }

            $add_tags = array_diff( $new_tags, $cur_tags );
            if ( ! empty( $add_tags ) ){

                print_r( $add_tags );

                $result = $contact->add_tag( $add_tags );

                if ( ! $result ){
                    $this->notices->add( 'bad-tag', 'Hmm, looks like we couldn\'t add the new tags...' );
                }
            }
        }

        $this->notices->add( 'update', __( "Contact updated!", 'groundhogg' ), 'success' );

        do_action( 'wpgh_admin_update_contact_after', $id );

    }

    /**
     * Save the contact during inline edit
     */
    public function save_inline()
    {
        if ( ! wp_doing_ajax() )
            wp_die( 'should not be calling this function' );

        //todo security check

        $id             = (int) $_POST['ID'];

        $contact = new WPGH_Contact( $id );

        do_action( 'wpgh_inline_update_contact_before', $id );

        $args[ 'email' ] = sanitize_email( $_POST['email'] );
        $args[ 'first_name' ] = sanitize_text_field( $_POST['first_name'] );
        $args[ 'last_name' ] = sanitize_text_field( $_POST['last_name'] );
        $args[ 'owner_id' ] = intval( $_POST['owner' ] );

        $err = array();

        if( !$args[ 'email' ] ) {
            $err[] = 'Email can not be blank';
        } else if ( ! is_email( $args[ 'email' ] ) ) {
            $err[] = 'Invalid email address';
        }

        if( !$args[ 'first_name' ] ) {
            $err[] = 'First name can not be blank';
        }

        if( $err ) {
            echo implode(', ', $err);
            exit;
        }

        $contact->update( $args );

        $tags = WPGH()->tags->validate( $_POST['tags' ] );

//        wp_die( print_r( $tags ) );

        $cur_tags = $contact->tags;
        $new_tags = $tags;

        $delete_tags = array_diff( $cur_tags, $new_tags );
        if ( ! empty( $delete_tags ) ) {
            $contact->remove_tag( $delete_tags );
        }

        $add_tags = array_diff( $new_tags, $cur_tags );
        if ( ! empty( $add_tags ) ){
            $contact->add_tag( $add_tags );

        }

        do_action( 'wpgh_inline_update_contact_after', $id );

        if ( ! class_exists( 'WPGH_Contacts_Table' ) ) {
            include_once 'class-wpgh-contacts-table.php';
        }

        $contactTable = new WPGH_Contacts_Table;
        $contactTable->single_row( WPGH()->contacts->get( $id ) );

        wp_die();
    }

    /**
     * Verify that the current user can perform the action
     *
     * @return bool
     */
    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) && ! isset( $_REQUEST[ '_edit_contact_nonce' ] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_edit_contact_nonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-contacts' );
    }

    /**
     * Display the contact table
     */
    function table()
    {
        if ( ! class_exists( 'WPGH_Contacts_Table' ) ){
            include dirname(__FILE__) . '/class-wpgh-contacts-table.php';
        }

        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_script( 'wpgh-inline-edit-contacts', WPGH_ASSETS_FOLDER . '/js/admin/inline-edit-contacts.js' );
        wp_enqueue_style( 'wpgh-inline-edit-contacts', WPGH_ASSETS_FOLDER . '/css/admin/contacts.css'  );

        $contacts_table = new WPGH_Contacts_Table();

        $contacts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Contacts ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Contacts ', 'groundhogg'); ?>">
            </p>
            <?php $contacts_table->prepare_items(); ?>
            <?php $contacts_table->display(); ?>
            <?php
            if ( $contacts_table->has_items())
                $contacts_table->inline_edit();
            ?>
        </form>

        <?php
    }

    /**
     * Display the edit screen
     */
    function edit()
    {
        include dirname( __FILE__ ) . '/contact-editor.php';

    }

    /**
     * Display the add screen
     */
    function add()
    {
        include dirname( __FILE__ ) . '/add-contact.php';
    }

    /**
     * Display the title and dependent action include the appropriate page content
     */
    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_contacts&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            <?php $this->notices->notices(); ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }
}