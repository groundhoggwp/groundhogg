<?php
/**
 * View Events
 *
 * Allow the user to view & edit the events
 *
 * @package     groundhogg
 * @subpackage  Includes/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPFN_Events_Page
{
    function __construct()
    {
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_events' ){

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    function get_events()
    {
        $events = isset( $_REQUEST['event'] ) ? $_REQUEST['event'] : null;

        if ( ! $events )
            return false;

        return is_array( $events )? array_map( 'intval', $events ) : array( intval( $events ) );
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
                _e( 'Add Event' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Event' , 'groundhogg' );
                break;
            default:
                _e( 'Events', 'groundhogg' );
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
            case 'cancel':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' events cancelled.' ); ?></p></div><?php

                break;

            case 'execute':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' events executed.' ); ?></p></div><?php

                break;

            case 're_execute':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' events re-executed.' ); ?></p></div><?php

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
            case 'cancel':

                if ( isset( $_POST ) )
                {
                    do_action( 'wpfn_cancel_event' );
                }

                break;

            case 'trash':

                foreach ( $this->get_events() as $id ) {
                    wpfn_update_event($id, 'event_status', 'trash');
                }

                do_action( 'wpfn_trash_events' );

                break;

            case 'cancel':

                foreach ( $this->get_events() as $id ){
                    wpfn_delete_event( $id );
                }

                do_action( 'wpfn_delete_events' );

                break;

            case 'restore':

                foreach ( $this->get_events() as $id )
                {
                    wpfn_update_event( $id, 'event_status', 'draft' );
                }

                do_action( 'wpfn_restore_events' );

                break;

            case 'edit':

                if ( isset( $_POST ) ){
                    do_action( 'wpfn_update_event', intval( $_GET[ 'event' ] ) );
                }

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_events() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }


    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-events' );
    }

    function table()
    {
        if ( ! class_exists( 'WPFN_Events_Table' ) ){
            include dirname( __FILE__ ) . '/class-events-table.php';
        }

        $events_table = new WPFN_Events_Table();

        $events_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Events ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Events ', 'groundhogg'); ?>">
            </p>
            <?php $events_table->prepare_items(); ?>
            <?php $events_table->display(); ?>
        </form>

        <?php
    }

    function edit()
    {
        include dirname( __FILE__ ) . '/event-editor.php';

    }

    function add()
    {
        include dirname( __FILE__ ) . '/add-event.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_events&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
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