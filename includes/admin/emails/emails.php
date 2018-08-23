<?php
/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPFN_Emails_Page
{
    function __construct()
    {
        if ( $_GET[ 'page' ] === 'gh_emails' ){

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    function get_emails()
    {
        $emails = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : null;

        if ( ! $emails )
            return false;

        return is_array( $emails )? array_map( 'intval', $emails ) : array( intval( $emails ) );
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
                _e( 'Add Email' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Email' , 'groundhogg' );
                break;
            default:
                _e( 'Emails', 'groundhogg' );
        }
    }

    function get_notice()
    {
        $ids = explode( ',', urldecode( $_REQUEST['ids'] ) );

        $count = count( $ids );

        switch ( $this->get_previous_action() )
        {
            case 'trash':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' emails trashed.' ); ?></p></div><?php

                break;

            case 'delete':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' emails deleted.' ); ?></p></div><?php

                break;

            case 'restore':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' emails restored.' ); ?></p></div><?php

                break;

            case 'edit':
                if ( isset( $_POST ) ){
                    ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Email Updated.' ); ?></p></div><?php
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
                    do_action( 'wpfn_add_email' );
                }
                break;

            case 'trash':

                foreach ( $this->get_emails() as $id ) {
                    wpfn_update_email($id, 'email_status', 'trash');
                }

                do_action( 'wpfn_trash_emails' );

                break;

            case 'delete':

                foreach ( $this->get_emails() as $id ){
                    wpfn_delete_email( $id );
                }

                do_action( 'wpfn_delete_emails' );

                break;

            case 'restore':

                foreach ( $this->get_emails() as $id )
                {
                    wpfn_update_email( $id, 'email_status', 'draft' );
                }

                do_action( 'wpfn_restore_emails' );

                break;

            case 'edit':

                if ( isset( $_POST ) ){
                    do_action( 'wpfn_update_email', intval( $_GET[ 'email' ] ) );
                }

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_emails() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }


    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() );
    }

    function table()
    {
        if ( ! class_exists( 'WPFN_Emails_Table' ) ){
            include dirname( __FILE__ ) . '/class-emails-table.php';
        }

        $emails_table = new WPFN_Emails_Table();

        $emails_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Emails ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Emails ', 'groundhogg'); ?>">
            </p>
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>

        <?php
    }

    function edit()
    {
        include dirname( __FILE__ ) . '/email-editor.php';

    }

    function add()
    {
        include dirname( __FILE__ ) . '/add-email.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
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