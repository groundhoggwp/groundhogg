<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\is_option_enabled;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use \WP_List_Table;
use Groundhogg\Contact_Query;
use Groundhogg\Admin\Funnels\Funnels_Page;
use Groundhogg\Manager;

use function Groundhogg\get_request_var;
use function Groundhogg\scheduled_time_column;


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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Funnels_Table extends WP_List_Table {

	protected $default_view = 'active';

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
	 * bulk steps or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {

		$columns = array(
			'cb'              => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'           => _x( 'Title', 'Column label', 'groundhogg' ),
			'active_contacts' => _x( 'Active Contacts', 'Column label', 'groundhogg' ),
			'last_updated'    => _x( 'Last Updated', 'Column label', 'groundhogg' ),
			'date_created'    => _x( 'Date Created', 'Column label', 'groundhogg' ),
		);

		if ( $this->get_view() !== 'active' ) {
			unset( $columns['active_contacts'] );
		}

		return apply_filters( 'groundhogg_funnels_get_columns', $columns );
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
			'title'        => array( 'title', false ),
//			'active_contacts' => array( 'active_contacts', false ),
			'last_updated' => array( 'last_updated', false ),
			'date_created' => array( 'date_created', false )
		);

		return apply_filters( 'groundhogg_funnels_get_sortable_columns', $sortable_columns );
	}

	/**
	 * Get the views for the emails, all, ready, unready, trash
	 *
	 * @return array
	 */
	protected function get_views() {
		$views = array();

		$count = array(
			'active'   => get_db( 'funnels' )->count( array( 'status' => 'active' ) ),
			'inactive' => get_db( 'funnels' )->count( array( 'status' => 'inactive' ) ),
			'archived' => get_db( 'funnels' )->count( array( 'status' => 'archived' ) )
		);

		// If there are no scheduled broadcasts, go to the sent view
		if ( $count['active'] === 0 && $this->get_view() === 'active' ) {
			$this->default_view = 'inactive';
		}

		$views['active']   = "<a class='" . print_r( ( $this->get_view() === 'active' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&status=active' ) . "'>" . _x( 'Active', 'view', 'groundhogg' ) . " <span class='count'>(" . _nf( $count['active'] ) . ")</span>" . "</a>";
		$views['inactive'] = "<a class='" . print_r( ( $this->get_view() === 'inactive' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&status=inactive' ) . "'>" . _x( 'Inactive', 'view', 'groundhogg' ) . " <span class='count'>(" . _nf( $count['inactive'] ) . ")</span>" . "</a>";
		$views['archived'] = "<a class='" . print_r( ( $this->get_view() === 'archived' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&status=archived' ) . "'>" . _x( 'Archived', 'view', 'groundhogg' ) . " <span class='count'>(" . _nf( $count['archived'] ) . ")</span>" . "</a>";

		return apply_filters( 'groundhogg_funnel_views', $views );
	}

	/**
	 * Get the current view
	 *
	 * @return string
	 */
	protected function get_view() {
		return sanitize_text_field( get_url_var( 'status', $this->default_view ) );
	}

	/**
	 * Get default row steps...
	 *
	 * @param $funnel Funnel
	 *
	 * @return string a list of steps
	 */
	protected function handle_row_actions( $funnel, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();
		$id      = $funnel->get_id();

		$editUrl = admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel->ID );

		$editUrlClassic = add_query_arg( [
			'version' => 1
		], $editUrl );

		if ( $this->get_view() === 'archived' ) {
			$actions['restore'] = "<span class='restore'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=restore&funnel=' . $id ), 'restore' ) . "'>" . _x( 'Restore', 'action', 'groundhogg' ) . "</a></span>";
			$actions['delete']  = "<span class='delete'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=archived&action=delete&funnel=' . $id ), 'delete' ) . "'>" . _x( 'Delete Permanently', 'action', 'groundhogg' ) . "</a></span>";
		} else {

			$actions['edit'] = "<span class='edit'><a href='" . $editUrl . "'>" . __( 'Build' ) . "</a></span>";

			if ( $funnel->is_active() ) {
				$actions['report'] = "<a href='" . esc_url( admin_page_url( 'gh_reporting', [
						'tab'    => 'funnels',
						'funnel' => $id,
					] ) ) . "'>" . __( 'Report', 'groundhogg' ) . "</a>";
			}

			$actions['duplicate'] = "<span class='duplicate'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&action=duplicate&funnel=' . $id ), 'duplicate' ) . "'>" . _x( 'Duplicate', 'action', 'groundhogg' ) . "</a></span>";
			$actions['export']    = "<span class='export'><a href='" . $funnel->export_url() . "'>" . _x( 'Export', 'action', 'groundhogg' ) . "</a></span>";
			$actions['trash']     = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=archive&funnel=' . $id ), 'archive' ) . "'>" . __( 'Archive', 'action', 'groundhogg' ) . "</a></span>";
		}

		return $this->row_actions( apply_filters( 'groundhogg_funnel_row_actions', $actions, $funnel, $column_name ) );
	}

	protected function column_title( $funnel ) {
		$subject = ( ! $funnel->title ) ? '(' . __( 'no title' ) . ')' : $funnel->title;

		$editUrl = admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel->ID );

//		if ( is_option_enabled( 'gh_use_classic_builder' ) ) {
//			$editUrl = add_query_arg( [
//				'version' => 1
//			], $editUrl );
//		}

		if ( $this->get_view() === 'archived' ) {
			$html = "<strong>{$subject}</strong>";
		} else {
			$html = "<strong>";

			$html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

			if ( $funnel->status === 'inactive' ) {
				$html .= " &#x2014; " . "<span class='post-state'>(" . _x( 'Inactive', 'status', 'groundhogg' ) . ")</span>";
			}
		}
		$html .= "</strong>";

		return $html;
	}

	/**
	 * @param $funnel Funnel
	 *
	 * @return string
	 */
	protected function column_active_contacts( $funnel ) {

		$query = new Contact_Query();

		$query_args = [
			'report' => array(
				'funnel' => $funnel->get_id(),
				'status' => 'waiting'
			)
		];


		$count = _nf( $query->query( array_merge( [ 'count' => true ], $query_args ) ) );

		$queryUrl = admin_page_url( 'gh_contacts', $query_args );

		return "<a href='$queryUrl'>$count</a>";
	}

	/**
	 * @param $funnel Funnel
	 *
	 * @return string
	 */
	protected function column_last_updated( $funnel ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $funnel->last_updated ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * @param $funnel Funnel
	 *
	 * @return string
	 */
	protected function column_date_created( $funnel ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $funnel->date_created ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $funnel A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $funnel, $column_name ) {

		do_action( 'groundhogg_funnels_custom_column', $funnel, $column_name );

		return '';

	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param  $funnel Funnel A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $funnel ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$funnel->get_id()                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		if ( $this->get_view() === 'archived' ) {
			$actions = array(
				'delete'  => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
				'restore' => _x( 'Restore', 'List table bulk action', 'groundhogg' )
			);

		} else {
			$actions = array(
				'archive' => _x( 'Archive', 'List table bulk action', 'groundhogg' )
			);
		}

		return apply_filters( 'groundhogg_email_bulk_actions', $actions );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * REQUIRED! This is where you prepare your data for display. This method will
	 *
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
		$paged    = $this->get_pagenum();
		$offset   = $per_page * ( $paged - 1 );
		$search   = trim( sanitize_text_field( get_url_var( 's' ) ) );
		$order    = get_url_var( 'order', 'DESC' );
		$orderby  = get_url_var( 'orderby', 'ID' );

		$where = [
			'relationship' => "AND",
			[ 'col' => 'status', 'val' => $this->get_view(), 'compare' => '=' ],
		];

		$args = array(
			'where'   => $where,
			'search'  => $search,
			'limit'   => $per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);

		$events = get_db( 'funnels' )->query( $args );
		$total  = get_db( 'funnels' )->count( $args );

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
	 * @param object $item
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Funnel( $item->ID ) );
		echo '</tr>';
	}
}