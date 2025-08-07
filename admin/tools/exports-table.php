<?php

namespace Groundhogg\Admin\Tools;

use WP_List_Table;
use wpdb;
use function Groundhogg\_nf;
use function Groundhogg\count_csv_rows;
use function Groundhogg\file_access_url;
use function Groundhogg\files;

/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @since       0.1
 * @subpackage  Modules/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @package     groundhogg
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Exports_Table extends WP_List_Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'export',     // Singular name of the listed records.
			'plural'   => 'exports',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = array(
			'cb'   => '<input type="checkbox" />', // Render a checkbox instead of text.
			'file' => _x( 'File', 'Column label', 'groundhogg' ),
			'rows' => _x( 'Rows', 'Column label', 'groundhogg' ),
			'date' => _x( 'Date Exported', 'Column label', 'groundhogg' ),
		);

		return $columns;
	}

	/**
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'file' => array( 'file', false ),
			'rows' => array( 'rows', false ),
			'date' => array( 'date', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Get the rule title
	 *
	 * @param $export array
	 *
	 * @return string
	 */
	protected function column_file( $export ) {
		$download_url = $export['file_url'];

		$html = "<strong>";
		$html .= "<a class='row-title' href='$download_url'>{$export['file']}</a>";
		$html .= "</strong>";

		return $html;
	}

	/**
	 * Get the rule type
	 *
	 * @param $export array
	 *
	 * @return mixed
	 */
	protected function column_rows( $export ) {
		return _nf( $export['rows'] );
	}

	/**
	 * Show the points to add.
	 *
	 * @param $export array
	 *
	 * @return string
	 */
	protected function column_date( $export ) {
		$strdate = date_i18n( 'Y-m-d H:i:s', intval( $export['date'] ) );

		return '<abbr title="' . $strdate . '">' . $strdate . '</abbr>';
	}

	/**
	 * Get default column value.
	 *
	 * @param object $export      A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $export, $column_name ) {

		return print_r( $export[ $column_name ], true );

	}

	/**
	 * @param object $export A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $export ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$export['file']               // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
		);

		return apply_filters( 'wpgh_exports_bulk_actions', $actions );
	}

	/**
	 * Prepares the list of items for displaying.
	 * @global $wpdb wpdb
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


		$data = [];

		if ( file_exists( files()->get_csv_exports_dir() ) ) {

			$scanned_directory = array_diff( scandir( files()->get_csv_exports_dir() ), [
				'..',
				'.'
			] );


			foreach ( $scanned_directory as $filename ) {

				$filepath = files()->get_csv_exports_dir( $filename );

				$file = [
					'file'      => $filename,
					'file_path' => $filepath,
					'file_url'  => file_access_url( '/exports/' . $filename, true ),
					'date'      => filemtime( $filepath ),
					'rows'      => count_csv_rows( $filepath ),
				];

				$data[] = $file;

			}
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
		$orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'file'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );

		return ( 'desc' === $order ) ? $result : - $result;
	}

	/**
	 * Generates and displays row action rule.
	 *
	 * @param array  $export      Contact being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $export, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		$actions['export'] = sprintf(
			'<a href="%s" class="edit" aria-label="%s">%s</a>',
			/* translators: %s: title */
			$export['file_url'],
			esc_attr( 'Export' ),
			__( 'Export' )
		);

		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
			wp_nonce_url( admin_url( 'admin.php?page=gh_tools&tab=export&action=delete&export=' . $export['file'] ) ),
			/* translators: %s: title */
			esc_attr( 'Delete Permanently' ),
			__( 'Delete export' )
		);

		return $this->row_actions( $actions );
	}
}
