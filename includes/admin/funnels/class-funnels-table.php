<?php
/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
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

class WPFN_Funnels_Table extends WP_List_Table {

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'funnel',     // Singular name of the listed records.
            'plural'   => 'funnels',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }
    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {
        $columns = array(
            'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
            'title'    => _x( 'Title', 'Column label', 'groundhogg' ),
            'active_contacts'   => _x( 'Active Contacts', 'Column label', 'groundhogg' ),
            'last_updated' => _x( 'Last Updated', 'Column label', 'groundhogg' ),
            'date_created' => _x( 'Date Created', 'Column label', 'groundhogg' ),
        );
        return $columns;
    }
    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'title'    => array( 'title', false ),
            'active_contacts' => array( 'active_contacts', false ),
            'last_updated' => array( 'last_updated', false ),
            'date_created' => array( 'date_created', false )
        );
        return $sortable_columns;
    }

    /**
     * Get the views for the emails, all, ready, unready, trash
     *
     * @return array
     */
    protected function get_views()
    {
        $views =  array();

        $views['all'] = "<a class='" .  print_r( ( $this->get_view() === 'all' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=all' ) . "'>" . __( 'All' ) . " <span class='count'>(" . wpfn_count_funnel_items() . ")</span>" . "</a>";

        $views['active'] = "<a class='" .  print_r( ( $this->get_view() === 'active' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=active' ) . "'>" . __( 'Active' ) . " <span class='count'>(" . wpfn_count_funnel_items( 'funnel_status', 'active' ) . ")</span>" . "</a>";

        $views['inactive'] = "<a class='" .  print_r( ( $this->get_view() === 'inactive' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=inactive' ) . "'>" . __( 'Inactive' ) . " <span class='count'>(" . wpfn_count_funnel_items( 'funnel_status', 'inactive' ) . ")</span>" . "</a>";

        $views['archived'] = "<a class='" .  print_r( ( $this->get_view() === 'archived' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=archived' ) . "'>" . __( 'Archived' ) . " <span class='count'>(" . wpfn_count_funnel_items( 'funnel_status', 'archived' ) . ")</span>" . "</a>";

        return apply_filters(  'wpfn_funnel_views', $views );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    /**
     * Get default row actions...
     *
     * @param $id int an item ID
     * @return array a list of actions
     */
    protected function get_row_actions( $id )
    {
        if ( $this->get_view() === 'archived' )
        {
            return array(
                "<span class='restore'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=restore&funnel='. $id ), 'restore'  ). "'>" . __( 'Restore' ) . "</a></span>",
                "<span class='delete'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=archived&action=delete&funnel='. $id ), 'delete'  ). "'>" . __( 'Delete Permanently' ) . "</a></span>",
            );
        } else {
            return apply_filters( 'wpfn_email_row_actions', array(
                "<span class='edit'><a href='" . admin_url( 'admin.php?page=gh_funnels&action=edit&funnel='. $id ). "'>" . __( 'Build' ) . "</a></span>",
                "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=archive&funnel='. $id ), 'archive' ). "'>" . __( 'Archive' ) . "</a></span>",
            ));
        }
    }

    /**
     * For more detailed insight into how columns are handled, take a look at
     * WP_List_Table::single_row_columns()
     *
     * @param object $item        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'title':
                $subject = ( ! $item[ 'funnel_title' ] )? '(' . __( 'no title' ) . ')' : $item[ 'funnel_title' ] ;
                $editUrl = admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $item['ID'] );

                if ( $this->get_view() === 'archived' ){
                    $html = "<strong>{$subject}</strong>";
                } else {
                    $html = "<strong>";

                    $html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

                    if ( $item['funnel_status'] === 'inactive' ){
                        $html .= " — " . "<span class='post-state'>(" . __( 'Inactive', 'groundhogg' ) . ")</span>";
                    }
                }
                $html .= "</strong>";

                $html .= $this->row_actions( $this->get_row_actions( $item['ID'] ) );


                return $html;

                break;
            case 'active_contacts':
                global $wpdb;

                $table_name = $wpdb->prefix . WPFN_EVENTS;

                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "
                         SELECT COUNT(*) FROM $table_name
                         WHERE funnel_id = %d AND status = %s
                        ",
                        $item['ID'], 'waiting'
                    )
                );

                $queryUrl = admin_url( 'admin.php?page=gh_contacts&view=report&status=waiting&funnel=' . $item['ID'] );
                return "<a href='$queryUrl'>$count</a>";
            case 'date_created':
                return __( 'Created' ) . '<br><abbr title="' . $item['date_created'] . '">' . date('Y/m/d', strtotime($item['date_created'])) . '</abbr>';
                break;
            case 'last_updated':
                return __( 'Updated' ) . '<br><abbr title="' . $item['last_updated'] . '">' . date('Y/m/d', strtotime($item['last_updated'])) . '</abbr>';
                break;
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
     *
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {

        if ( $this->get_view() === 'archived' )
        {
            $actions = array(
                'delete' => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
                'restore' => _x( 'Restore', 'List table bulk action', 'groundhogg' )
            );

        } else {
            $actions = array(
                'archive' => _x( 'Archive', 'List table bulk action', 'groundhogg' )
            );
        }

        return apply_filters( 'wpfn_email_bulk_actions', $actions );
    }

    /**
     * Prepares the list of items for displaying.
     *
     * REQUIRED! This is where you prepare your data for display. This method will
     *
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

        $per_page = 20;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $table_name = $wpdb->prefix . WPFN_FUNNELS;

        $query = "SELECT * FROM $table_name WHERE ";

        if ( isset( $_REQUEST[ 's' ] ) ){

            $pattern = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST[ 's' ] ) ) . '%' ;
            $query .= $wpdb->prepare( "(funnel_title LIKE %s) AND ", $pattern );

        }

        if ( $this->get_view() === 'archived' ){

            $query .= $wpdb->prepare( '( funnel_status = %s )', 'archived' );

        } else if ( $this->get_view() === 'active' ) {

            $query .= $wpdb->prepare( '( funnel_status = %s )', 'active' );

        } else if ( $this->get_view() === 'inactive' ) {

            $query .= $wpdb->prepare( '( funnel_status = %s )', 'inactive' );

        } else {

            $query .= $wpdb->prepare( '( funnel_status = %s OR funnel_status = %s OR funnel_status = %s )', 'active', 'inactive', '' );

        }

        $data = $wpdb->get_results( $query, ARRAY_A );

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
}