<?php

namespace Groundhogg\Admin\Superlinks;

use Groundhogg\Plugin;
use WP_List_Table;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Superlinks Table
 *
 * This is the Superlinks table, has basic actions and shows basic info about a superlink.
 *
 * @package     Admin
 * @subpackage  Admin/Supperlinks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */



// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Superlinks_Table extends WP_List_Table {
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
            'cb'            => '<input type="checkbox" />', // Render a checkbox instead of text.
            'name'          => _x( 'Name', 'Column label', 'groundhogg' ),
            'source'        => _x( 'Source Url', 'Column label', 'groundhogg' ),
            'replacement'   => _x( 'Replacement Code', 'Column label', 'groundhogg' ),
            'tags'          => _x( 'Tags', 'Column label', 'groundhogg' ),
            //'clicks' => _x( 'Clicks', 'Column label', 'groundhogg' ),
            'target'        => _x( 'Target Url', 'Column label', 'groundhogg' ),
        );
        return $columns;
    }
    /**
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'name'          => array( 'name', false ),
//            'target'    => array( 'target', false ),
            'replacement'   => array( 'replacement', false ),
            'tags'          => array( 'tags', false ),
            'clicks'        => array( 'clicks', false ),
        );
        return $sortable_columns;
    }

    protected function column_name( $superlink )
    {
        $editUrl = admin_url( 'admin.php?page=gh_superlinks&action=edit&superlink=' . $superlink->ID );
        $html = "<a class='row-title' href='$editUrl'>" . esc_html( $superlink->name ) . "</a>";
        return $html;
    }

    protected function column_target( $superlink )
    {
        return '<a target="_blank" href="' . esc_url_raw( $superlink->target ) . '">' . esc_url( $superlink->target ) . '</a>';
    }

    protected function column_replacement( $superlink )
    {
        return sprintf( '<input type="text" value="%s" onfocus="this.select()" readonly>', '{superlink.' . $superlink->ID . '}');
    }

    protected function column_source( $superlink )
    {
        return sprintf( '<input style="max-width: 100%%;" class="regular-text" type="text" value="%s" onfocus="this.select()" readonly><p><a target="_blank" href="%s">%s</a></p>', site_url( 'superlinks/link/' . $superlink->ID ), site_url( 'superlinks/link/' . $superlink->ID ), site_url( 'superlinks/link/' . $superlink->ID ) );
    }

    protected function column_tags( $superlink )
    {
        $tags = array();

        foreach ( $superlink->tags as $i => $tag_id ){


            if ( Plugin::$instance->dbs->get_db('tags')->exists( $tag_id ) ){
                $tag = Plugin::$instance->dbs->get_db('tags')->get( $tag_id );
                $tags[ $i ] = '<a href="'. admin_url( 'admin.php?page=gh_contacts&view=tag&tag=' . $tag_id ) . '">' . $tag->tag_name . '</a>';
            }
        }

        return implode( ', ', $tags );
    }

    /**
     * Get default column value.
     * @param object $superlink        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $superlink, $column_name ) {

        return print_r( $superlink->$column_name, true );

    }
    /**
     * @param object $superlink A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $superlink ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $superlink->ID               // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * @return array An associative array containing all the bulk steps.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
        );

        return apply_filters( 'wpgh_superlink_bulk_actions', $actions );
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

        $args = [];

        if ( isset( $_REQUEST[ 's' ] ) ){
            $args[ 'search' ] = $_REQUEST[ 's' ];
        }

        $args = wp_unslash( $args );
        $data = Plugin::instance()->dbs->get_db('superlinks')->query($args);


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
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'ID'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action superlinks.
     *
     * @param object $item        Contact being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row steps output for posts.
     */
    protected function handle_row_actions( $superlink, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $superlink->name;

        $actions['edit'] = sprintf(
            '<a href="%s" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            admin_url( 'admin.php?page=gh_superlinks&action=edit&superlink=' . $superlink->ID ),
            esc_attr( sprintf( __( 'Edit' ), $title ) ),
            __( 'Edit' )
        );

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_superlinks&superlink='. $superlink->ID . '&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete' )
        );

        return $this->row_actions( $actions );
    }
}