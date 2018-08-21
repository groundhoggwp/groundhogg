<?php
/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @package     groundhogg
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

class WPFN_Superlinks_Table extends WP_List_Table {
    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'superlink',     // Singular name of the listed records.
            'plural'   => 'superlinks',    // Plural name of the listed records.
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
            'name'    => _x( 'Name', 'Column label', 'groundhogg' ),
            'replacement'    => _x( 'Replacement Code', 'Column label', 'groundhogg' ),
            'target'    => _x( 'Target Url', 'Column label', 'groundhogg' ),
            'tags'   => _x( 'Tags', 'Column label', 'groundhogg' ),
            'clicks' => _x( 'Clicks', 'Column label', 'groundhogg' ),
        );
        return $columns;
    }
    /**
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'name'    => array( 'name', false ),
//            'target'    => array( 'target', false ),
            'replacement'    => array( 'replacement', false ),
            'tags' => array( 'tags', false ),
            'clicks' => array( 'clicks', false ),
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
            case 'name':
                $editUrl = admin_url( 'admin.php?page=gh_superlinks&action=edit&superlink_id=' . $item['ID'] );
                $html  = '<div id="inline_' .$item['ID'] . '" class="hidden">';
                $html .= '  <div class="name">' . $item['name'] . '</div>';
                $html .= '  <div class="target">' . $item['target'] . '</div>';
                $html .= '  <div class="replacement">' . '{superlink.' . $item['ID'] . '}</div>';
                $html .= '  <div class="tags">' . implode(', ', maybe_unserialize( $item[ 'tags' ] ) ) . '</div>';
                $html .= '  <div class="clicks">' . $item['clicks'] . '</div>';
                $html .= '</div>';
                $html .= "<a class='row-title' href='$editUrl'>{$item[ $column_name ]}</a>";
                return $html;
                break;
            case 'target':
                return '<a target="_blank" href="' . esc_url_raw( $item['target'] ) . '">' . esc_url( $item['target'] ) . '</a>';
                break;
            case 'replacement':
                return '{superlink.' . $item['ID'] . '}';
                break;
            case 'tags':
                $tags = maybe_unserialize( $item['tags'] );

                $html = '';

                foreach ( $tags as $i => $tag_id ){
                    $tags[$i] = '<a href="'.admin_url('admin.php?page=gh_contacts&view=tag&tag_id='.$tag_id).'">' . wpfn_get_tag_name( $tag_id ). '</a>';
                }

                return implode( ', ', $tags );
                break;
            case 'clicks':
                return ! empty( $item['clicks'] ) ? $item['clicks'] : '0';
            default:
                return print_r( $item[ $column_name ], true );
                break;
        }
    }
    /**
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
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
        );

        return apply_filters( 'wpfn_superlink_bulk_actions', $actions );
    }
    /**
     * Handle bulk actions.
     *
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     *
     * @see $this->prepare_items()
     */
    protected function process_bulk_action() {
        // Detect when a bulk action is being triggered.
        global $wpdb;

        $doaction = $this->current_action();
        $sendback = remove_query_arg( array( 'deleted' ), wp_get_referer() );

        if ($doaction && isset($_REQUEST['superlink'])) {
            $ids = $_REQUEST['superlink'];
            switch ( $this->current_action() ){
                case 'delete':
                    $deleted = 0;
                    if(!empty($ids)) {
                        foreach ($ids as $id) {
                            wpfn_delete_superlink( intval( $id ) );
                            $deleted++;
                        }
                    }
                    $sendback = add_query_arg( array( 'notice' => 'deleted', 'superlinks' => urlencode( implode( ',', $ids ) ) ) , $sendback);
                    break;
                default:
            }

            wp_redirect( $sendback );
            exit();
        }
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
        /*
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        /*
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * three other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array( $columns, $hidden, $sortable );
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        /*
         * GET THE DATA!
         *
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * package slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
         * our dummy data.
         *
         * In a real-world situation, this is probably where you would want to
         * make your actual database query. Likewise, you will probably want to
         * use any posted sort or pagination data to build a custom query instead,
         * as you'll then be able to use the returned query data immediately.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         */
        $table_name = $wpdb->prefix . WPFN_SUPER_LINKS;

        if ( isset( $_REQUEST['s'] ) ){
            $data = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM $table_name WHERE name LIKE %s ORDER BY ID DESC", '%' . $wpdb->esc_like( $_REQUEST['s'] ) . '%' ),
                ARRAY_A
            );
        } else {
            $data = $wpdb->get_results(
                "SELECT * FROM $table_name ORDER BY ID DESC", ARRAY_A
            );
        }


        /*
         * Sort the data
         */
        usort( $data, array( $this, 'usort_reorder' ) );


        /*
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        /*
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count( $data );
        /*
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to do that.
         */
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
        /*
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
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
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'ID'; // WPCS: Input var ok.
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
        $title = $item['name'];

        $actions['edit'] = sprintf(
            '<a href="%s" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            admin_url( 'admin.php?page=gh_superlinks&action=edit&superlink_id=' . $item['ID'] ),
            esc_attr( sprintf( __( 'Edit' ), $title ) ),
            __( 'Edit' )
        );

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_superlinks&tad_id='. $item['ID'].'&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete' )
        );

        return $this->row_actions( $actions );
    }
}