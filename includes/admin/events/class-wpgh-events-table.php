<?php
/**
 * Events Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @package     wp-funnels
 * @subpackage  Modules/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPGH_Events_Table extends WP_List_Table {

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
            'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
            'contact'    => _x( 'Contact', 'Column label', 'wp-funnels' ),
            'funnel'   => _x( 'Funnel', 'Column label', 'wp-funnels' ),
            'step' => _x( 'Step', 'Column label', 'wp-funnels' ),
            'time' => _x( 'Time', 'Column label', 'wp-funnels' ),
        );

        return apply_filters( 'wpgh_event_columns', $columns );
    }
    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'contact' => array( 'contact_id', false ),
            'funnel' => array( 'funnel_id', false ),
            'step' => array( 'step_id', false ),
            'time' => array( 'time', false ),
        );
        return apply_filters( 'wpgh_event_sortable_columns', $sortable_columns );
    }

    public function single_row($item)
    {
        echo '<tr>';
        $this->single_row_columns( new WPGH_Event( $item->ID ) );
        echo '</tr>';
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_contact( $event )
    {


        if ( ! $event->contact->exists() )
            return sprintf( "<strong>(%s)</strong>",  __( 'contact deleted', 'groundhogg' ) );

        $html = sprintf( "<a class='row-title' href='%s'>%s</a>",
            admin_url( 'admin.php?page=gh_events&view=contact&contact=' . $event->contact->ID ),
            $event->contact->email
        );

        return $html;
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_funnel( $event )
    {
        $funnel_id = intval( $event->funnel_id );

        if ( $event->is_broadcast_event() ) {
            $funnel_title = __( 'Broadcast Email', 'groundhogg' );
        } else {
            $funnel = WPGH()->funnels->get( $funnel_id );
            $funnel_title = ( $funnel )? $funnel->title : sprintf( '(%s)', __( 'deleted' ) ) ;
        }

        return sprintf( "<a href='%s'>%s</a>",
            admin_url( 'admin.php?page=gh_events&view=funnel&funnel=' . $event->funnel_id ),
            $funnel_title);
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_step( $event )
    {

        if ( $event->is_broadcast_event() ) {

            $step_title = $event->step->email->subject;

        } else {

            $step_title = $event->step->title;

        }

        if ( ! $step_title )
            return sprintf( "<strong>%s</strong>", __( '(step deleted)' ) );

        return sprintf( "<a href='%s'>%s</a>",
            admin_url( 'admin.php?page=gh_events&view=step&step=' . $event->step->ID ),
            $step_title );

    }

    protected function extra_tablenav($which)
    {
        ?>
        <div class="alignleft actions">
            <a class="button button-secondary action" href="<?php echo add_query_arg( 'process_queue', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>"><?php _e( 'Process Events', 'groundhogg' ); ?></a>
        </div>
        <?php
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_time( $event )
    {
        $p_time = intval( $event->time ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        $cur_time = (int) current_time( 'timestamp' );

        $time_diff = $p_time - $cur_time;

        $status = $event->status;

        switch ( $status ){
            case 'waiting':
                $time_prefix = __( 'Will run' );
                break;
            case 'cancelled':
                $time_prefix = __( 'Cancelled' );
                break;
            case 'skipped':
                $time_prefix = __( 'Skipped' );
                break;
            case 'complete':
                $time_prefix = __( 'Processed' );
                break;
        }

        if ( $time_diff < 0 ){
            /* The event has passed */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = date_i18n( 'Y/m/d \@ h:i A', intval( $p_time ) );
            } else {
                $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
            }
        } else {
            /* the event is scheduled */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = sprintf( "on %s", date_i18n( 'Y/m/d \@ h:i A', intval( $p_time )  ) );
            } else {
                $time = sprintf( "in %s", human_time_diff( $p_time, $cur_time ) );
            }
        }

        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
    }

    /**
     * Get default column value.
     * @param WPGH_Event $event        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $event, $column_name ) {

        do_action( 'wpgh_events_custom_column', $event, $column_name );

        return '';

    }

    /**
     * Get value for checkbox column.
     *
     * @param object $event A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $event ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $event->ID           // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk elements available on this table.
     * @return array An associative array containing all the bulk elements.
     */
    protected function get_bulk_actions() {
        $actions = array(
	        'execute' => _x( 'Run', 'List table bulk action', 'wp-funnels'),
	        'cancel' => _x( 'Cancel', 'List table bulk action', 'wp-funnels' ),
        );

        return apply_filters( 'wpgh_event_bulk_actions', $actions );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function get_views()
    {
        $base_url = admin_url( 'admin.php?page=gh_events&view=status&status=' );

        $view = $this->get_view();

        $count = array(
            'waiting'   => WPGH()->events->count( array( 'status' => 'waiting' ) ),
            'skipped'   => WPGH()->events->count( array( 'status' => 'skipped' ) ),
            'cancelled' => WPGH()->events->count( array( 'status' => 'cancelled' ) ),
            'completed' => WPGH()->events->count( array( 'status' => 'complete' ) )
        );

        return apply_filters( 'gh_event_views', array(
            'all'       => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . admin_url( 'admin.php?page=gh_events' ) . "'>" . __( 'All <span class="count">('. array_sum($count) . ')</span>' ) . "</a>",
            'waiting'   => "<a class='" . ($view === 'waiting' ? 'current' : '') . "' href='" . $base_url . "waiting" . "'>" . __( 'Waiting <span class="count">('.$count['waiting'].')</span>' ) . "</a>",
            'skipped'   => "<a class='" . ($view === 'skipped' ? 'current' : '') . "' href='" . $base_url . "skipped" . "'>" . __( 'Skipped <span class="count">('.$count['skipped'].')</span>' ) . "</a>",
            'cancelled' => "<a class='" . ($view === 'cancelled' ? 'current' : '') . "' href='" . $base_url . "cancelled" . "'>" . __( 'Cancelled <span class="count">('.$count['cancelled'].')</span>' ) . "</a>",
            'completed' => "<a class='" . ($view === 'completed' ? 'current' : '') . "' href='" . $base_url . "complete" . "'>" . __( 'Completed <span class="count">('.$count['completed'].')</span>' ) . "</a>"
        ) );
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

        $per_page = 30;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        switch ( $this->get_view() )
        {
            case 'status':
                if ( isset( $_REQUEST['status'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'status' => $_REQUEST[ 'status' ]
                    ));
                }
                break;
            case 'contact':
	            if ( isset( $_REQUEST['contact'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'contact_id' => $_REQUEST[ 'contact' ]
                    ));
	            }
	            break;
            case 'funnel':
	            if ( isset( $_REQUEST['funnel'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'funnel_id' => $_REQUEST[ 'funnel' ]
                    ));
	            }
	            break;
	        case 'step':
		        if ( isset( $_REQUEST['step'] ) ){
                    $data = WPGH()->events->get_events( array(
                        'step_id' => $_REQUEST[ 'step' ]
                    ));
		        }
		        break;
            default:
                $data = WPGH()->events->get_events();
                break;
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

    /**
     * Generates and displays row action superlinks.
     *
     * @param object $event        Event being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row elements output for posts.
     */
    protected function handle_row_actions( $event, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();

        switch ( $event->status ){
            case 'waiting':
                $actions['execute'] = sprintf(
                    '<a href="%s" class="edit" aria-label="%s">%s</a>',
                    /* translators: %s: title */
                    esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=execute' ) ) ),
                    esc_attr( __( 'Execute' ) ),
                    __( 'Run Now' )
                );
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    esc_url( wp_nonce_url(admin_url('admin.php?page=gh_events&event='. $event->ID .'&action=cancel') ) ),
                    /* translators: %s: title */
                    esc_attr( __( 'Cancel' ) ),
                    __( 'Cancel' )
                );
                break;
            default:
                $actions['re_execute'] = sprintf(
                    '<a href="%s" class="edit" aria-label="%s">%s</a>',
                    /* translators: %s: title */
                    esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=execute' ) ) ),
                    esc_attr( __( 'Re-execute' ) ),
                    __( 'Run Again' )
                );
                break;

        }

        if ( $event->contact->exists() ){
            $actions[ 'view' ] = sprintf( "<a class='edit' href='%s'>%s</a>",
                admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $event->contact->ID ),
                esc_attr( __( 'View Contact' ) ),
                __( 'View Contact' )
            );
        }


        return $this->row_actions( apply_filters( 'wpgh_event_row_actions', $actions, $event, $column_name ) );
    }
}