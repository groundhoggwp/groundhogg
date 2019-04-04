<?php
/**
 * Tags Table
 *
 * @package     Admin
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPGH_Tags_Table extends WP_List_Table {
    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'tag',     // Singular name of the listed records.
            'plural'   => 'tags',    // Plural name of the listed records.
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
            'tag_name'    => _x( 'Name', 'Column label', 'groundhogg' ),
            'tag_description'   => _x( 'Description', 'Column label', 'groundhogg' ),
            'contact_count' => _x( 'Count', 'Column label', 'groundhogg' ),
        );
        return $columns;
    }
    /**
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'tag_name'    => array( 'tag_name', false ),
            'tag_description' => array( 'tag_description', false ),
            'contact_count' => array( 'contact_count', false ),
        );
        return $sortable_columns;
    }

    protected function extra_tablenav($which)
    {
        ?>
        <div class="alignleft gh-actions">
            <a class="button action" href="<?php echo add_query_arg( 'recount_contacts', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>"><?php _ex( 'Recount Contacts', 'action','groundhogg' ); ?></a>
        </div>
        <?php
    }

    protected function column_tag_name( $tag )
    {
        $editUrl = admin_url( 'admin.php?page=gh_tags&action=edit&tag=' . $tag->tag_id );
        $html = "<a class='row-title' href='$editUrl'>" . esc_html( $tag->tag_name ) . "</a>";
        return $html;
    }

    protected function column_contact_count( $tag )
    {
        $count = $tag->contact_count;
        return $count ? '<a href="'.admin_url('admin.php?page=gh_contacts&tags_include=' . $tag->tag_id ).'">'. $count .'</a>' : '0';
    }

    protected function column_tag_description( $tag )
    {
        return ! empty( $tag->tag_description ) ? $tag->tag_description : '&#x2014;';
    }

    /**
     * Get default column value.
     * @param object $tag        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $tag, $column_name ) {

        return print_r( $tag->$column_name, true );

    }
    /**
     * @param object $tag A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $tag ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $tag->tag_id               // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * @return array An associative array containing all the bulk elements.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
        );

        return apply_filters( 'wpgh_contact_tag_bulk_actions', $actions );
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

        if ( isset( $_REQUEST['s'] ) ){
            $data = WPGH()->tags->search( $_REQUEST[ 's' ] );
        } else {
            $data = WPGH()->tags->get_tags();
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
        $a = (array) $a;
        $b = (array) $b;

        // If no sort, default to title.
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'tag_id'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action superlinks.
     *
     * @param object $tag        Contact being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row elements output for posts.
     */
    protected function handle_row_actions( $tag, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $tag->tag_name;

        $actions[ 'id' ] = 'ID: ' . $tag->tag_id;

        $actions['edit'] = sprintf(
            '<a href="%s" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            admin_url( 'admin.php?page=gh_tags&action=edit&tag=' . $tag->tag_id ),
            esc_attr( sprintf( __( 'Edit' ), $title ) ),
            __( 'Edit' )
        );

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_tags&tag='. $tag->tag_id . '&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete' )
        );

        return $this->row_actions( $actions );
    }
}