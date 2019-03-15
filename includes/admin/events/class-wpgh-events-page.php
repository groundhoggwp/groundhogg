<?php
/**
 * View Events
 *
 * Allow the user to view & edit the events
 * This allows one to manage all the events associated with funnels, broadcasts, and funnels.
 * This was included as a page for the convenience of the end user. Although only advanced users will use it probably.
 *
 * @package     Admin
 * @subpackage  Admin/Events
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Events_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;
    public $order = 40;

    function __construct()
    {

        add_action( 'admin_menu', array( $this , 'register' ), $this->order );
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_events' ){

            add_action( 'init' , array( $this, 'process_action' )  );
            $this->notices = WPGH()->notices;

        }
    }


    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            _x( 'Events', 'page_title', 'groundhogg' ),
            _x( 'Events', 'page_title', 'groundhogg' ),
            'view_events',
            'gh_events',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));

    }

    public function help()
    {
        //todo
    }

    function get_events()
    {
        $events = isset( $_REQUEST['event'] ) ? $_REQUEST['event'] : null;

        if ( ! $events )
            return [];

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
            default:
                _ex( 'Events', 'page_title', 'groundhogg' );
        }
    }

    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() )
            return;

        $base_url = remove_query_arg( array( '_wpnonce' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'cancel':

                if ( ! current_user_can( 'cancel_events' ) ){
                    wp_die( WPGH()->roles->error( 'cancel_events' ) );
                }

                foreach ( $this->get_events() as $eid ){

                    WPGH()->events->update(
                        $eid,
                        array(
                            'status' => 'cancelled'
                        )
                    );

                }

                $this->notices->add( 'cancelled', sprintf( _nx( '%d event cancelled', '%d events cancelled', count( $this->get_events() ), 'notice', 'groundhogg' ), count( $this->get_events() ) ) );

                do_action( 'wpgh_cancel_events' );

                break;

            case 'execute':

                if ( ! current_user_can( 'execute_events' ) ){
                    wp_die( WPGH()->roles->error( 'execute_events' ) );
                }

                foreach ( $this->get_events() as $eid )
                {

                    WPGH()->events->update(
                        $eid,
                        array(
                            'status' => 'waiting',
                            'time'   => time()
                        )
                    );
                }

                do_action( 'wpgh_execute_events' );

                $this->notices->add( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_events() ), 'notice', 'groundhogg' ), count( $this->get_events() ) ) );

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
        if ( ! current_user_can( 'view_events' ) ){
            wp_die( WPGH()->roles->error( 'view_events' ) );
        }

        if ( ! class_exists( 'WPGH_Events_Table' ) ){
            include dirname(__FILE__) . '/class-wpgh-events-table.php';
        }

        $events_table = new WPGH_Events_Table();

        $events_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Events', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Events', 'groundhogg'); ?>">
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
            <?php $this->notices->notices(); ?>
            <hr class="wp-header-end">
            <?php $this->table(); ?>
        </div>
        <?php
    }
}