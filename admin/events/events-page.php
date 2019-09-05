<?php

namespace Groundhogg\Admin\Events;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\get_db;
use Groundhogg\Plugin;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

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
class Events_Page extends Admin_Page
{

    //UNUSED FUNCTIONS
    protected function add_ajax_actions() {}
    public function help() {}
    protected function add_additional_actions() {}
    public function scripts() {
        wp_enqueue_style( 'groundhogg-admin' );
    }

    public function get_slug()
    {
       return 'gh_events';
    }

    public function get_name()
    {
        return _x( 'Events', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'view_events';
    }

    public function get_item_type()
    {
        return 'event';
    }

    public function get_priority()
    {
        return 40;
    }

    protected function get_title_actions()
    {
        return [];
    }

    /**
     *  Sets the title of the page
     * @return string
     */
    public function get_title()
    {
        switch ( $this->get_current_action() ) {
            case 'view':
            default:
                return _x( 'Events', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * Cancels scheduled broadcast
     *
     * @return bool
     */
    public function process_cancel()
    {
        if ( !current_user_can( 'cancel_events' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $eid ) {
            Plugin::$instance->dbs->get_db( 'events' )->update(
                absint( $eid ),
                array(
                    'status' => 'cancelled'
                )
            );
        }
        $this->add_notice( 'cancelled', sprintf( _nx( '%d event cancelled', '%d events cancelled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

        //false return users to the main page
        return false;
    }

    /**
     * Clean up the events DB if something goes wrong.
     *
     * @return bool
     */
    public function process_cleanup()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        global $wpdb;

        $events = get_db( 'events' );

        $wpdb->query( "UPDATE {$events->get_table_name()} SET claim = '' WHERE claim <> ''" );
        $wpdb->query( "UPDATE {$events->get_table_name()} SET status = 'complete' WHERE status = 'in_progress'" );

        return false;
    }

    /**
     * Clean up the events DB if something goes wrong.
     *
     * @return bool
     */
    public function process_process_queue()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        $queue = Plugin::$instance->event_queue;

        Plugin::$instance->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $queue->run_queue(), $queue->get_last_execution_time() ) );

        if ( $queue->has_errors() ){
            Plugin::$instance->notices->add( 'queue-errors', sprintf( "%d events failed to complete. Please see the following errors.", count( $queue->get_errors() ) ), 'warning' );

            foreach ( $queue->get_errors() as $error ){
                Plugin::instance()->notices->add( $error );
            }
        }

        return false;
    }

    /**
     * Executes the event
     *
     * @return bool
     */
    public function process_execute()
    {
        if ( !current_user_can( 'execute_events' ) ) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $eid ) {
            Plugin::$instance->dbs->get_db( 'events' )->update(
                $eid,
                array(
                    'status' => 'waiting',
                    'time' => time()
                )
            );
        }

        $this->add_notice( 'scheduled', sprintf( _nx( '%d event rescheduled', '%d events rescheduled', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) ) );

        return false;
    }

    public function view()
    {
        if ( !current_user_can( 'view_events' ) ) {
            $this->wp_die_no_access();
        }

        if ( !class_exists( 'Events_Table' ) ) {
            include dirname( __FILE__ ) . '/events-table.php';
        }

        $events_table = new Events_Table();

        $events_table->views();
        ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <?php $events_table->prepare_items(); ?>
            <?php $events_table->display(); ?>
        </form>

        <?php
    }

}