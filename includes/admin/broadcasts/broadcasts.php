<?php
/**
 * View Broadcasts
 *
 * Allow the user to view & edit the broadcasts
 *
 * @package     groundhogg
 * @subpackage  Includes/Broadcasts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPFN_Broadcasts_Page
{
    function __construct()
    {
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_broadcasts' ){

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    function get_broadcasts()
    {
        $broadcasts = isset( $_REQUEST['broadcast'] ) ? $_REQUEST['broadcast'] : null;

        if ( ! $broadcasts )
            return false;

        return is_array( $broadcasts )? array_map( 'intval', $broadcasts ) : array( intval( $broadcasts ) );
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
                _e( 'Schedule Broadcast' , 'groundhogg' );
                break;
            default:
                _e( 'Broadcasts', 'groundhogg' );
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

                ?><div class="notice notice-success is-dismissible"><p><?php _e( $count .' broadcasts cancelled.' ); ?></p></div><?php

                break;

            case 'add':

                ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Broadcast scheduled.' ); ?></p></div><?php

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
                    do_action( 'wpfn_add_broadcast' );
                }

                break;

            case 'cancel':
                foreach ( $this->get_broadcasts() as $id ){
                    $s = wpfn_update_broadcast( $id, 'broadcast_status', 'cancelled' );

                    if ( ! $s )
                        wp_die( __( 'Could not cancel.', 'groundhogg' ) );

                    wpfn_dequeue_funnel_step_events( WPFN_BROADCAST, $id );
                }
                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_broadcasts() ){
            $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_broadcasts() ) ), $base_url );
        }

        wp_redirect( $base_url );
        die();
    }


    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-broadcasts' );
    }

    function table()
    {
        if ( ! class_exists( 'WPFN_Broadcasts_Table' ) ){
            include dirname( __FILE__ ) . '/class-broadcasts-table.php';
        }

        $broadcasts_table = new WPFN_Broadcasts_Table();

        $broadcasts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Broadcasts ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Broadcasts ', 'groundhogg'); ?>">
            </p>
            <?php $broadcasts_table->prepare_items(); ?>
            <?php $broadcasts_table->display(); ?>
        </form>

        <?php
    }

    function add()
    {
        include dirname(__FILE__) . '/add-broadcast.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_broadcasts&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            <?php $this->get_notice(); ?>
            <?php if( isset( $_POST['send_test'] ) ): ?>
                <div class="notice notice-success is-dismissible"><p><?php _e( 'Sent test broadcast.', 'groundhogg' ); ?></p></div>
            <?php endif; ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }
}