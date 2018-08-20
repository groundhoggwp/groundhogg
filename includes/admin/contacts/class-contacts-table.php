<?php
/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @package     wp-funnels
 * @subpackage  Modules/Contacts
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

class WPFN_Contacts_Table extends WP_List_Table {

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'contact',     // Singular name of the listed records.
            'plural'   => 'contacts',    // Plural name of the listed records.
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
            'email'    => _x( 'Email', 'Column label', 'wp-funnels' ),
            'first_name'   => _x( 'First Name', 'Column label', 'wp-funnels' ),
            'last_name' => _x( 'Last Name', 'Column label', 'wp-funnels' ),
            'user_id' => _x( 'User ID', 'Column label', 'wp-funnels' ),
            'owner' => _x( 'Owner', 'Column label', 'wp-funnels' ),
            'date_created' => _x( 'Date Created', 'Column label', 'wp-funnels' ),
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
            'email'    => array( 'email', false ),
            'first_name' => array( 'first_name', false ),
            'last_name' => array( 'last_name', false ),
            'user_id' => array( 'user_id', false ),
            'owner' => array( 'owner', false ),
            'date_created' => array( 'date_created', false )
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
            case 'email':
                $editUrl = admin_url( 'admin.php?page=contacts&action=edit&contact=' . $item['ID'] );
                $html  = '<div id="inline_' .$item['ID']. '" class="hidden">';
                $html .= '  <div class="email">' .$item['email']. '</div>';
                $html .= '  <div class="first_name">' .$item['first_name']. '</div>';
                $html .= '  <div class="last_name">' .$item['last_name']. '</div>';
                $html .= '</div>';
                $html .= "<a class='row-title' href='$editUrl'>{$item[ $column_name ]}</a>";
                return $html;
                break;
            case 'user_id':
                return $item['user_id'] ? '<a href="'.admin_url('user-edit.php?user_id='.$item['user_id']).'">'.$item['user_id'].'</a>' :  '&#x2014;';
                break;
            case 'owner':
                $owner = get_userdata( $item['owner_id'] );
                return ! empty( $item['owner_id'] ) ? '<a href="'.admin_url('admin.php?page=contacts&view=owner&owner=' .$item['owner_id'] ).'">'. $owner->user_login .'</a>' :  '&#x2014;';
                break;
            case 'date_created':
                return date('d/M/Y g:i a', strtotime($item['date_created']));
            default:
                return print_r( $item[ $column_name ], true );
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
            $item['ID']                // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk actions available on this table.
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'wp-funnels' ),
            'export' => _x( 'Export', 'List table bulk action', 'wp-funnels' ),
            'apply_tag' => _x( 'Apply Tag', 'List table bulk action', 'wp-funnels'),
            'remove_tag' => _x( 'Remove Tag', 'List table bulk action', 'wp-funnels')
        );

        return apply_filters( 'wpfn_contact_bulk_actions', $actions );
    }
    /**
     * Handle bulk actions.
     * @see $this->prepare_items()
     */
    protected function process_bulk_action() {
        // Detect when a bulk action is being triggered.
        global $wpdb;
        $doaction = $this->current_action();
        $sendback = remove_query_arg( array('trashed', 'untrashed', 'deleted', 'locked', 'ids'), wp_get_referer() );

        if ($doaction && isset($_REQUEST['contact'])) {
            $ids = $_REQUEST['contact'];

            switch ( $this->current_action() ){
                case 'export':
                    //todo
                    break;
                case 'delete':
                    $deleted = 0;
                    if(!empty($ids)) {
                        foreach ($ids as $id) {
                            wpfn_delete_contact($id);
                            $deleted++;
                        }
                    }
                    $sendback = add_query_arg('deleted', $deleted, $sendback);
                    break;
                case 'apply_tag':
                    //todo
                    break;
                case 'remove_tag':
                    //todo
                    break;
            }

            wp_redirect($sendback);
            exit();
        }
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function get_views() {
        global $wpdb;
        $base_url = admin_url( 'admin.php?page=contacts&view=optin_status&optin_status=' );

        $view = isset($_REQUEST['optin_status']) ? $_REQUEST['optin_status'] : 'all';

        $table_name = $wpdb->prefix . WPFN_CONTACTS;

        $count = array(
            'unconfirmed' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = 0")),
            'confirmed' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = 1")),
            'opted_out' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = 2")),
        );

        return apply_filters( 'contact_views', array(
            'all' => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . $base_url . "all" . "'>" . __( 'All <span class="count">('.array_sum($count).')</span>' ) . "</a>",
            'unconfirmed' => "<a class='" . ($view === 'unconfirmed' ? 'current' : '') . "' href='" . $base_url . "unconfirmed" . "'>" . __( 'Unconfirmed <span class="count">('.$count['unconfirmed'].')</span>' ) . "</a>",
            'confirmed' => "<a class='" . ($view === 'confirmed' ? 'current' : '') . "' href='" . $base_url . "confirmed" . "'>" . __( 'Confirmed <span class="count">('.$count['confirmed'].')</span>' ) . "</a>",
            'opted_out' => "<a class='" . ($view === 'opted_out' ? 'current' : '') . "' href='" . $base_url . "opted_out" . "'>" . __( 'Unsubscribed <span class="count">('.$count['opted_out'].')</span>' ) . "</a>"
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

        $this->process_bulk_action();

        $search = isset( $_REQUEST['s'] )? $wpdb->prepare( "AND ( c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%" ) : '';

        switch ( $this->get_view() )
        {
            case 'optin_status':
                if ( isset( $_REQUEST['optin_status'] ) ){
                    switch ( $_REQUEST['optin_status'] ){
                        case 'unconfirmed':
                            $view = 0;
                            break;
                        case 'confirmed':
                            $view = 1;
                            break;
                        case 'opted_out':
                            $view = 2;
                            break;
                        default:
                            $view = 0;
                            break;
                    }
                    $sql = $wpdb->prepare(
                        "SELECT c.* FROM " . $wpdb->prefix . WPFN_CONTACTS . " c
                        WHERE c.optin_status = %d $search
                        ORDER BY c.date_created DESC" , $view
                    );
                }
                break;
            case 'tag':
                if ( isset( $_REQUEST[ 'tag_id'] ) ){
                    $tag_id = $_GET['tag_id'];
                    $sql = $wpdb->prepare(
                        "SELECT t.*, c.* FROM "
                        .$wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS . " t "
                        . " LEFT JOIN " .$wpdb->prefix . WPFN_CONTACTS . " c ON t.contact_id = c.ID 
                        WHERE t.tag_id = %d $search
                        ORDER BY c.date_created DESC"
                    , $tag_id);
                }
                break;
            case 'report':

                $sql = "SELECT e.contact_id, c.*
                FROM " . $wpdb->prefix . WPFN_EVENTS ." e 
                LEFT JOIN " . $wpdb->prefix . WPFN_CONTACTS . " c ON e.contact_id = c.ID 
                WHERE (1=1 ";
                if ( isset( $_REQUEST['status'] ) ) {
                    $status = $_REQUEST['status'];
                    $sql .= $wpdb->prepare(' AND e.status = %s', $status);
                }
                if ( isset( $_REQUEST['funnel'] ) ){
                    $funnel = intval ( $_REQUEST['funnel'] );
                    $sql .= $wpdb->prepare( ' AND e.funnel_id = %d', $funnel );
                }
                if ( isset(  $_REQUEST['step'] ) ){
                    $step = intval ( $_REQUEST['step'] );
                    $sql .= $wpdb->prepare( ' AND e.step_id = %d', $step );
                }
                if ( isset( $_REQUEST['start'] ) ){
                    $start = intval ( $_REQUEST['start'] );
                    $sql .= $wpdb->prepare( ' AND %d <= e.time', $start );
                }
                if ( isset( $_REQUEST['end'] ) ){
                    $end = intval ( $_REQUEST['end'] );
                    $sql .= $wpdb->prepare( ' AND e.time <= %d', $end );
                }

                $sql .= ") $search
                ORDER BY c.date_created DESC";
                break;
            case 'owner':
                if ( isset( $_REQUEST['owner'] ) ){
                    $owner = intval( $_REQUEST['owner'] );
                    $sql = $wpdb->prepare( "SELECT c.* FROM " . $wpdb->prefix . WPFN_CONTACTS . " c
                    WHERE c.owner_id = %d $search
                    c.date_created DESC", $owner );
                }
                break;
            default:
                $sql = "SELECT c.* FROM " . $wpdb->prefix . WPFN_CONTACTS . " c
                WHERE 1=1 $search
                ORDER BY c.date_created DESC";
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
     * @param object $item        Contact being acted upon.
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
            wp_nonce_url(admin_url('admin.php?page=contacts&contact[]='. $item['ID'].'&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete Permanently' )
        );

        return $this->row_actions( $actions );
    }
}