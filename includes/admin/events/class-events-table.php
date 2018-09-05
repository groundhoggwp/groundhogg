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

class WPFN_Events_Table extends WP_List_Table {

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
        return $columns;
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
            'contact' => array( 'contact', false ),
            'funnel' => array( 'funnel', false ),
            'step' => array( 'step', false ),
            'time' => array( 'time', false ),
        );
        return $sortable_columns;
    }
    /**
     * Get default column value.
     * @param object $item        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'contact':

            	$contact = new WPFN_Contact( intval( $item[ 'contact_id' ] ) );

            	if ( ! $contact )
		            return __( 'No Contact' );

                $html = "<span class='row-title'>" . $contact->get_email() . "</span>";
                return $html;
                break;
            case 'funnel':

            	$funnel_id = intval( $item['funnel_id'] );

            	if ( $funnel_id === WPFN_BROADCAST ) {
	            	$funnel_title = __( 'Broadcast' );
	            } else {
		            $funnel = wpfn_get_funnel_by_id( $funnel_id );
		            $funnel_title = $funnel->funnel_title;
	            }

	            return $funnel_title;

                break;
            case 'step':
	            $funnel_id = intval( $item['funnel_id'] );
	            $step_id = intval( $item['step_id'] );

	            if ( $funnel_id === WPFN_BROADCAST ) {
		            $broadcast = wpfn_get_broadcast_by_id( $step_id );
		            $email = wpfn_get_email_by_id( intval( $broadcast['email_id'] ) );
		            $step_title = $email->subject;
	            } else {
		            $step_title = wpfn_get_step_hndle( $step_id );
	            }

	            return $step_title;

	            break;
            case 'time':

            	$time = intval( $item[ 'time' ] );

            	$html = '';

            	if ( $time > time() )
	            {
	            	$html.= __( 'Scheduled For' );
	            } else {
		            $html.= __( 'Executed' );
	            }

                return $html . '<br><abbr title="' . date( DATE_ISO8601, $time ) . '">' . date('Y/m/d', $time ) . '</abbr>';
            default:
                return ! empty( $item[ $column_name ] ) ? print_r( $item[ $column_name ], true ) : '&#x2014;' ;
                break;
        }
    }

    /**
     * Get value for checkbox column.
     *
     * @param object $item A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $item['time']                // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk actions available on this table.
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {
        $actions = array(
	        'execute' => _x( 'Execute', 'List table bulk action', 'wp-funnels'),
	        're_execute' => _x( 'Re Execute', 'List table bulk action', 'wp-funnels' ),
	        'cancel' => _x( 'Cancel', 'List table bulk action', 'wp-funnels' ),
        );

        return apply_filters( 'wpfn_event_bulk_actions', $actions );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function get_views() {
        global $wpdb;
        $base_url = admin_url( 'admin.php?page=gh_events&view=status&status=' );

        $view = isset($_REQUEST['optin_status']) ? $_REQUEST['optin_status'] : 'all';

        $table_name = $wpdb->prefix . WPFN_EVENTS;

        $count = array(
            'waiting' => count($wpdb->get_results("SELECT status FROM $table_name WHERE status = 'waiting'")),
            'skipped' => count($wpdb->get_results("SELECT status FROM $table_name WHERE status = 'skipped'")),
            'cancelled' => count($wpdb->get_results("SELECT status FROM $table_name WHERE status = 'cancelled'")),
            'completed' => count($wpdb->get_results("SELECT status FROM $table_name WHERE status = 'completed'")),
        );

        return apply_filters( 'gh_event_views', array(
            'all' => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . admin_url( 'admin.php?page=gh_events' ) . "'>" . __( 'All <span class="count">('. array_sum($count) . ')</span>' ) . "</a>",
            'waiting' => "<a class='" . ($view === 'waiting' ? 'current' : '') . "' href='" . $base_url . "waiting" . "'>" . __( 'Waiting <span class="count">('.$count['unconfirmed'].')</span>' ) . "</a>",
            'skipped' => "<a class='" . ($view === 'skipped' ? 'current' : '') . "' href='" . $base_url . "skipped" . "'>" . __( 'Skipped <span class="count">('.$count['confirmed'].')</span>' ) . "</a>",
            'cancelled' => "<a class='" . ($view === 'cancelled' ? 'current' : '') . "' href='" . $base_url . "cancelled" . "'>" . __( 'Cancelled <span class="count">('.$count['opted_out'].')</span>' ) . "</a>",
            'completed' => "<a class='" . ($view === 'completed' ? 'current' : '') . "' href='" . $base_url . "completed" . "'>" . __( 'Completed <span class="count">('.$count['opted_out'].')</span>' ) . "</a>"
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
        global $wpdb; //This is used only if making any database queries
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 30;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        switch ( $this->get_view() )
        {
            case 'status':
                if ( isset( $_REQUEST['status'] ) ){
                    $sql = $wpdb->prepare(
                        "SELECT e.* FROM " . $wpdb->prefix . WPFN_EVENTS . " e
                        WHERE e.status = %s
                        ORDER BY e.time DESC" , $_REQUEST['status']
                    );
                }
                break;
            case 'contact':
	            if ( isset( $_REQUEST['contact'] ) ){
		            $sql = $wpdb->prepare(
			            "SELECT e.* FROM " . $wpdb->prefix . WPFN_EVENTS . " e
                        WHERE e.contact_id = %d
                        ORDER BY e.time DESC" , intval( $_REQUEST['contact'] )
		            );
	            }
	            break;
            case 'funnel':
	            if ( isset( $_REQUEST['funnel'] ) ){
		            $sql = $wpdb->prepare(
			            "SELECT e.* FROM " . $wpdb->prefix . WPFN_EVENTS . " e
                        WHERE e.funnel_id = %d
                        ORDER BY e.time DESC" , intval( $_REQUEST['funnel'] )
		            );
	            }
	            break;
	        case 'step':
		        if ( isset( $_REQUEST['step'] ) ){
			        $sql = $wpdb->prepare(
				        "SELECT e.* FROM " . $wpdb->prefix . WPFN_EVENTS . " e
                        WHERE e.funnel_id = %d
                        ORDER BY e.time DESC" , intval( $_REQUEST['step'] )
			        );
		        }
		        break;
            default:
	            $sql = "SELECT e.* FROM " . $wpdb->prefix . WPFN_EVENTS . " e ORDER BY e.time DESC";
                break;
        }

        $data = $wpdb->get_results( $sql, ARRAY_A );

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
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_created'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action links.
     *
     * @param object $item        Event being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row actions output for posts.
     */
    protected function handle_row_actions( $item, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $item['email'];

        $actions['inline hide-if-no-js'] = sprintf(
            '<a href="#" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $title ) ),
            __( 'Quick&nbsp;Edit' )
        );

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_events&event[]='. $item['ID'].'&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete Permanently' )
        );

        return $this->row_actions( $actions );
    }
}