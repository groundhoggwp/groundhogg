<?php

namespace Groundhogg\Admin\SMS;

use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use Groundhogg\SMS;
use Groundhogg\Plugin;
use WP_List_Table;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SMS Table
 *
 * This is the SMS table, has basic actions and shows basic info about an SMS message.
 *
 * @package     Admin
 * @subpackage  Admin/SMS
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.2
 */


// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class SMS_Table extends WP_List_Table {
    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'sms',     // Singular name of the listed records.
            'plural'   => 'sms',    // Plural name of the listed records.
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
            'title'         => _x( 'Title', 'Column label', 'groundhogg' ),
            'message'       => _x( 'Message', 'Column label', 'groundhogg' ),
            'author'        => _x( 'Author', 'Column label', 'groundhogg' ),
        );
        return $columns;
    }
    /**
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'title'   => array( 'title', false ),
            'author'  => array( 'author', false ),
        );
        return $sortable_columns;
    }

    /**
     * @param $sms SMS
     * @return string
     */
    protected function column_title( $sms )
    {
        $editUrl = admin_url( 'admin.php?page=gh_sms&action=edit&sms=' . $sms->get_id() );
        $html = "<a class='row-title' href='$editUrl'>" . esc_html( $sms->get_title() ) . "</a>";
        return $html;
    }

    /**
     * @param $sms SMS
     * @return mixed|void
     */
    protected function column_message( $sms )
    {
    	return apply_filters( 'the_content', wp_trim_words( $sms->get_message(), 20 ) );
    }

    /**
     * @param $sms SMS
     * @return string
     */
	protected function column_author( $sms )
	{
		$user = get_userdata( intval( ( $sms->get_author() ) ) );
		$from_user = esc_html( $user->display_name );
		$queryUrl = admin_url( 'admin.php?page=gh_sms&view=author&author=' . $user->ID );
		return "<a href='$queryUrl'>$from_user</a>";
	}

    /**
     * Get default column value.
     * @param  $sms SMS        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $sms, $column_name ) {
        return print_r( $sms->$column_name, true );

    }
    /**
     * @param  $sms SMS A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $sms ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $sms->get_id()               // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * @return array An associative array containing all the bulk steps.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
        );

        return apply_filters( 'wpgh_sms_bulk_actions', $actions );
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

        $columns  = $this->get_columns();
        $hidden   = array(); // No hidden columns
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page = absint( get_url_var( 'limit', get_screen_option( 'per_page' ) ) );
        $paged   = $this->get_pagenum();
        $offset  = $per_page * ( $paged - 1 );
        $search  = get_url_var( 's' );
        $order   = get_url_var( 'order', 'DESC' );
        $orderby = get_url_var( 'orderby', 'ID' );

        $args = array(
            'search'  => $search,
            'limit'   => $per_page,
            'offset'  => $offset,
            'order'   => $order,
            'orderby' => $orderby,
        );

        $events = get_db( 'sms' )->query( $args );
        $total = get_db( 'sms' )->count( $args );

        $this->items = $events;

        // Add condition to be sure we don't divide by zero.
        // If $this->per_page is 0, then set total pages to 1.
        $total_pages = $per_page ? ceil( (int) $total / (int) $per_page ) : 1;

        $this->set_pagination_args( array(
            'total_items' => $total,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
        ) );
    }

    /**
     * Generates and displays row action smss.
     *
     * @param $sms  SMS  Contact being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row steps output for posts.
     */
    protected function handle_row_actions( $sms, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $sms->get_title();

        $actions['edit'] = sprintf(
            '<a href="%s" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            admin_url( 'admin.php?page=gh_sms&action=edit&sms=' . $sms->get_id() ),
            esc_attr( sprintf( __( 'Edit' ), $title ) ),
            __( 'Edit' )
        );

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_sms&sms='. $sms->get_id() . '&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete' )
        );

        return $this->row_actions( $actions );
    }

    public function single_row( $item ) {
        echo '<tr>';
        $this->single_row_columns( new SMS( $item->ID ) );
        echo '</tr>';
    }

}