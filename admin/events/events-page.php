<?php

namespace Groundhogg\Admin\Events;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

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
    public function scripts() {}

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

    public function get_priority(){
        return 40;
    }


    /**
     *  Sets the title of the page
     * @return string
     */
    public function get_title()
    {
        switch ($this->get_current_action()) {
            case 'view':
            default:
                return _x('Events', 'page_title', 'groundhogg');
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
        if (!current_user_can('cancel_events')) {
           $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $eid) {
            Plugin::$instance->dbs->get_db('events' )->update(
                $eid,
                array(
                    'status' => 'cancelled'
                )
            );
        }
        $this->add_notice('cancelled', sprintf(_nx('%d event cancelled', '%d events cancelled', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())));

        //false return users to the main page
        return false;
    }

    /**
     * Executes the event
     *
     * @return bool
     */
    public function process_execute()
    {
        if (!current_user_can('execute_events')) {
            $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $eid) {
            Plugin::$instance->dbs->get_db('events' )->update(
                $eid,
                array(
                    'status' => 'waiting',
                    'time' => time()
                )
            );
        }
        $this->add_notice('scheduled', sprintf(_nx('%d event rescheduled', '%d events rescheduled', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())));

        //false return users to the main page
        return false;
    }


    public function view()
    {
        if (!current_user_can('view_events')) {
            $this->wp_die_no_access();
        }

        if (!class_exists('Events_Table')) {
            include dirname(__FILE__) . '/events-table.php';
        }

        $events_table = new Events_Table();

        $events_table->views(); ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e('Search Events', 'groundhogg'); ?>
                    :</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button"
                       value="<?php _e('Search Events', 'groundhogg'); ?>">
            </p>
            <?php $events_table->prepare_items(); ?>
            <?php $events_table->display(); ?>
        </form>

        <?php
    }

}