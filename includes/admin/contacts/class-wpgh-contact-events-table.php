<?php
/**
 * Contact Events table view
 *
 * This is an extension of the WP_List_Table, it shows the recent or future funnel history of a contact
 * Used in contact-editor.php
 *
 * Shows the name of the funnel, the name of the step, the run date and allows the user to cancel or run the event immediately.
 *
 * Because the data can be past or future, the actual data is set outside of the prepare items function in contact-editor.php
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

class WPGH_Contact_Events_Table extends WP_List_Table {

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
            'funnel'    => _x( 'Funnel', 'Column label', 'groundhogg' ),
            'step'      => _x( 'Step', 'Column label', 'groundhogg' ),
            'time'      => _x( 'Time', 'Column label', 'groundhogg' ),
            'actions'   => _x( 'Actions', 'Column label', 'groundhogg' )
        );
        return apply_filters( 'wpgh_contact_event_columns', $columns );
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
    protected function column_funnel( $event )
    {
        if ( $event->is_broadcast_event() ) {

            $funnel_title = __( 'Broadcast Email', 'groundhogg' );
            return sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_broadcasts&action=edit&broadcast=' . WPGH_BROADCAST ) ,$funnel_title);

        } else {

            $funnel_title = WPGH()->funnels->get_column_by( 'title', 'ID', $event->funnel_id );

            if ( ! $funnel_title ){

                return sprintf("<strong>(%s)</strong>", __( 'funnel deleted', 'groundhogg' ) );

            } else{

                return sprintf( "<a href='%s' target='_blank'>%s</a>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $event->funnel_id ) ,$funnel_title);

            }


        }

    }

    /**
     * @param $event WPGH_Event
     *
     * @return string
     */
    protected function column_step( $event )
    {
        if ($event->is_broadcast_event()) {

            $broadcast = WPGH()->broadcasts->get($event->step->ID);

            $email = new WPGH_Email($broadcast->email_id);

            $step_title = $email->subject;

            return sprintf("<a href='%s' target='_blank'>%s</a>", admin_url('admin.php?page=gh_emails&action=edit&email=' . $broadcast->email_id), $step_title);

        } else {

            if (!$event->step->title){
                return sprintf("<strong>(%s)</strong>", __( 'step deleted', 'groundhogg' ) );
            } else {
                return sprintf("<a href='%s' target='_blank'>%s</a>", admin_url('admin.php?page=gh_funnels&action=edit&funnel=' . $event->funnel_id . '#' . $event->step->ID), $event->step->title);
            }

        }
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_time( $event )
    {
        $p_time = intval( $event->time ) + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $p_time - $cur_time;
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = sprintf( "On %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
        } else {

            if ( $event->status === 'waiting' ){
                $time = sprintf( "In %s", human_time_diff( $p_time, $cur_time ) );
            } else {
                $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
            }

        }

        return '<abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_actions( $event )
    {
        $run = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=execute' ), 'execute' ) );
        $cancel = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=cancel' ), 'cancel' ) );

//        $html = "<div class=\"row-actions\">";

        $actions = array();

        if ( $event->time > time() ){
            $actions[] =  sprintf( "<span class=\"run\"><a href=\"%s\" class=\"run\">%s</a></span>", $run, __( 'Run Now', 'groundhogg' ) );
            $actions[] =  sprintf( "<span class=\"delete\"><a href=\"%s\" class=\"delete\">%s</a></span>", $cancel, __( 'Cancel', 'groundhogg' ) );
        } else {
            $actions[] = sprintf( "<span class=\"run\"><a href=\"%s\" class=\"run\">%s</a></span>", $run, __( 'Run Again', 'groundhogg' ) );
        }


//        $html .= "</div>";

        return $this->row_actions( $actions );
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
        global $wpdb; //This is used only if making any database queries
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 10;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $data = $this->data;

        if ( ! $data ){
            $data = array();
        }

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
        // If no sort, default to title.

        $a = (array) $a;
        $b = (array) $b;
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'time'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }
}