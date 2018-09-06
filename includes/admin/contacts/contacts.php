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
    function __construct()
    {
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_contacts' ){

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    function get_contacts()
    {
        $contacts = isset( $_REQUEST['contact'] ) ? $_REQUEST['contact'] : null;

        if ( ! $contacts )
            return false;

        return is_array( $contacts )? array_map( 'intval', $contacts ) : array( intval( $contacts ) );
    }

    function get_action()
    {
        if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    function get_title()
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

    function get_notice()
    {
        if ( isset( $_REQUEST['ids'] ) )
        {
            $ids = explode( ',', urldecode( $_REQUEST['ids'] ) );
            $count = count( $ids );
        }

        switch ( $this->get_previous_action() )
        {
            case 'spam':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' contacts marked as spam.' ); ?></p></div><?php

                break;

            case 'unspam':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' contacts marked as unconfirmed.' ); ?></p></div><?php

                break;

            case 'delete':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' contacts deleted.' ); ?></p></div><?php

                break;

            case 'edit':
                if ( isset( $_POST ) ){
                    ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Contact Updated.' ); ?></p></div><?php
                }
                break;
        }
    }

    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( isset( $_POST ) )
                {
                    do_action( 'wpgh_add_contact' );
                }

                break;

            case 'spam':

                foreach ( $this->get_contacts() as $id ) {
                    //todo revisit this as an unsubscribed contact can be marked and then unmarked as spam to set as unconfirmed.
                    wpgh_update_contact( $id, 'optin_status', WPGH_SPAM );
                }

                do_action( 'wpgh_trash_contacts' );

                break;

            case 'delete':

                foreach ( $this->get_contacts() as $id ){
                    wpgh_delete_contact( $id );
                }

                do_action( 'wpgh_delete_contacts' );

                break;

            case 'unspam':

                foreach ( $this->get_contacts() as $id )
                {
                    wpgh_update_contact( $id, 'contact_status', WPGH_UNCONFIRMED );
                }

                do_action( 'wpgh_restore_contacts' );

                break;

            case 'edit':

                if ( isset( $_POST ) ){
                    do_action( 'wpgh_update_contact', intval( $_GET[ 'contact' ] ) );
                }

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_contacts() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }


    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-contacts' );
    }

    function table()
    {
        if ( ! class_exists( 'WPGH_Contacts_Table' ) ){
            include dirname( __FILE__ ) . '/class-contacts-table.php';
        }

        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_script( 'wpgh-inline-edit-contacts', WPGH_ASSETS_FOLDER . '/js/admin/inline-edit-contacts.js' );

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

    function edit()
    {
        include dirname( __FILE__ ) . '/contact-editor.php';

    }

    function add()
    {
        include dirname( __FILE__ ) . '/add-contact.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_contacts&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            <?php $this->get_notice(); ?>
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