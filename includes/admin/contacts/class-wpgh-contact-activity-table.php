<?php
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
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPGH_Contact_Activity_Table extends WP_List_Table {

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
        $this->single_row_columns( new WPGH_Event( $item->ID ) );
        echo '</tr>';
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_email( $event )
    {

        if ( $event->is_broadcast_event() ) {

            $email = $event->step->email;

        } else {

            $email = new WPGH_Email( $event->step->get_meta( 'email_id' ) );

        }

        return sprintf(  "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $email->ID ), $email->subject );
    }

    /**
     * @param $event WPGH_Event
     *
     * @return string
     */
    protected function column_open( $event )
    {

        $activity = WPGH()->activity->get_activity( array(
            'funnel_id'     => $event->funnel_id,
            'step_id'       => $event->step->ID,
            'activity_type' => 'email_opened',
            'contact_id'    => $event->contact->ID,
//            'event_id'      => $event->ID
        ) );

//        print_r( $activity );

        if( empty( $activity ) ){
            return '&#x2014;';
        }

        $activity = array_shift( $activity );

        $p_time = intval( $activity->timestamp ) + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $p_time - $cur_time;
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = sprintf( "On %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
        } else {
            $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
        }

        return '<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';

    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_click( $event )
    {

        $activity = WPGH()->activity->get_activity( array(
            'funnel_id'     => $event->funnel_id,
            'step_id'       => $event->step->ID,
            'activity_type' => 'email_link_click',
            'contact_id'    => $event->contact->ID,
//            'event_id'      => $event->ID
        ) );

        if( empty( $activity ) ){
            return '&#x2014;';
        }

        $activity = array_shift( $activity );

        return '<a target="_blank" href="' . esc_url( $activity->referer ) . '">' . esc_url( $activity->referer ) . '</a>';

    }

    /**
     * Prepares the list of items for displaying.
     * @global wpdb $wpdb
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

        $events_table = WPGH()->events->table_name;
        $steps_table = WPGH()->steps->table_name;

        $id = intval( $_REQUEST[ 'contact' ] );

        $data = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*,s.step_type FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.contact_id = %d AND e.status = %s AND ( s.step_type = %s OR e.funnel_id = %d )
                        ORDER BY time DESC"
            , $id, 'complete', 'send_email', WPGH_BROADCAST )
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