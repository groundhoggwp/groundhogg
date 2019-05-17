<?php

namespace Groundhogg\Admin\Contacts\Tables;

use function Groundhogg\scheduled_time;
use \WP_List_Table;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Email;

/**
 * Activity table view
 *
 * This is an extension of the WP_List_Table, it shows the recent email activity of a contact at the bottom of the contact record
 * Shows the subject line of the email sent, the date it was opened and the link they clicked if they click a link
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WP_List_Table, contact-editor.php
 * @since       File available since Release 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Contact_Activity_Table extends WP_List_Table {

    /**
     * @var array
     */
    public $data;

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'event',     // Singular name of the listed records.
            'plural'   => 'events',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }
    /**
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {
        $columns = array(
            'email'    => _x( 'Email', 'Column label', 'groundhogg' ),
            'open'      => _x( 'Opened', 'Column label', 'groundhogg' ),
            'click'      => _x( 'Clicked', 'Column label', 'groundhogg' ),
        );
        return apply_filters( 'wpgh_contact_activity_columns', $columns );
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    public function single_row( $item ) {
        echo '<tr>';
        $this->single_row_columns( new Event( $item->ID ) );
        echo '</tr>';
    }

    /**
     * @param $event Event
     * @return string
     */
    protected function column_email( $event )
    {
        $email = $event->get_email();

        if ( ! $email || ! $email->exists() ){
            return false;
        }

        return sprintf(  "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $email->get_id() ), $email->get_subject_line() );
    }

    /**
     * @param $event Event
     *
     * @return string
     */
    protected function column_open( $event )
    {

        $activity = Plugin::$instance->dbs->get_db('activity')->query( [
            'event_id'      => $event->get_id(),
            'step_id'       => $event->get_step_id() ,
            'activity_type' => 'email_opened',
            'contact_id'    => $event->get_contact_id(),
        ] );

        if( empty( $activity ) ){
            return '&#x2014;';
        }

        $activity = array_shift( $activity );
        $time = absint( $activity->timestamp );

        $s_time = scheduled_time( $time );

        $html = '<abbr title="' . date_i18n( DATE_ISO8601, intval( $time ) ) . '">' . $s_time . '</abbr>';
        $html .= sprintf( '<br><i>(%s %s)', date_i18n( 'h:i A', $event->get_contact()->get_local_time( $time ) ), __( 'local time' ) ) . '</i>'; //todo

        return $html;

    }

    /**
     * @param $event Event
     * @return string
     */
    protected function column_click( $event )
    {

        $activity = Plugin::$instance->dbs->get_db('activity')->query( [
            'event_id'      => $event->get_id(),
            'step_id'       => $event->get_step_id() ,
            'activity_type' => 'email_link_click',
            'contact_id'    => $event->get_contact_id(),
        ] );

        if( empty( $activity ) ){
            return '&#x2014;';
        }

        $activity = array_shift( $activity );

        return '<a target="_blank" href="' . esc_url( $activity->referer ) . '">' . esc_url( $activity->referer ) . '</a>';

    }

    public function display_tablenav($which)
    {
        if ( $which === 'top' ):
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>">
                <?php $this->extra_tablenav( $which ); ?>
                <br class="clear" />
            </div>
        <?php
        endif;
    }

    /**
     * Prepares the list of items for displaying.
     * @global $wpdb \wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 10;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        global $wpdb;

        $events_table = Plugin::$instance->dbs->get_db('events')->get_table_name();
        $steps_table  = Plugin::$instance->dbs->get_db('steps')->get_table_name();

        $id = intval( $_REQUEST[ 'contact' ] );

        $data = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*,s.step_type FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        ORDER BY time DESC"
            , $id, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION )
        );

        /*
         * Sort the data
         */
        usort( $data, array( $this, 'usort_reorder' ) );

        $current_page = $this->get_pagenum();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                     // WE have to calculate the total number of items.
            'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
            'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
        ) );
    }

    /**
     * Callback to allow sorting of example data.
     *
     * @param string $a First value.
     * @param string $b Second value.
     *
     * @return int
     */
    protected function usort_reorder( $a, $b ) {
        $a = (array) $a;
        $b = (array) $b;
        // If no sort, default to title.
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'time'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }
}