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


class WPGH_Events_Page
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

        return is_array( $events )? array_map( 'intval', $events ): array( intval ( $events ) );
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

    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() || ! current_user_can( 'gh_manage_events' ) )
            return;

        $base_url = remove_query_arg( array( '_wpnonce' ), wp_get_referer() );

        global $wpdb;

        switch ( $this->get_action() )
        {
            case 'cancel':

                foreach ( $this->get_events() as $eid ){

                    $wpdb->update(
                        $wpdb->prefix . WPGH_EVENTS,
                        array(
                            'status' => 'cancelled'
                        ),
                        array(
                            'ID'          => $eid,
                        ),
                        array( '%s' ),
                        array(
                            '%d',
                        )
                    );
                }

                wpgh_add_notice( 'cancelled', sprintf( "%d %s", count( $this->get_events() ) ,__( "events cancelled", 'groundhogg' ) ) );

                do_action( 'wpgh_cancel_events' );

                break;

            case 'execute':

                foreach ( $this->get_events() as $eid )
                {

                    $wpdb->update(
                        $wpdb->prefix . WPGH_EVENTS,
                        array(
                            'status' => 'waiting',
                            'time'   => time()
                        ),
                        array(
                            'ID'          => $eid,
                        ),
                        array( '%s' ),
                        array(
                            '%d',
                        )
                    );
                }

                do_action( 'wpgh_execute_events' );

                wpgh_add_notice( 'scheduled', sprintf( "%d %s", count( $this->get_events() ) ,__( "events rescheduled", 'groundhogg' ) ) );

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

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
        if ( ! class_exists( 'WPGH_Events_Table' ) ){
            include dirname( __FILE__ ) . '/class-events-table.php';
        }

        $events_table = new WPGH_Events_Table();

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

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1>
            <?php wpgh_notices(); ?>
            <hr class="wp-header-end">
            <?php $this->table(); ?>
        </div>
        <?php
    }
}