<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
use WP_List_Table;
use wpdb;
use function Groundhogg\count_csv_rows;
use function Groundhogg\file_access_url;
use function Groundhogg\get_request_var;

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

class Imports_Table extends WP_List_Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'import',     // Singular name of the listed records.
			'plural'   => 'imports',    // Plural name of the listed records.
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
			'date' => _x( 'Date Uploaded', 'Column label', 'groundhogg' ),
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
	 * @param $import array
	 *
	 * @return string
	 */
	protected function column_file( $import ) {

		if ( ! current_user_can( 'import_contacts' ) ) {
			return sprintf( '<strong>%s</strong>', esc_html( $import['file'] ) );
		}

		return sprintf( '<strong><a class="row-title" href="%s">%s</a></strong>', esc_url( $this->get_map_url( $import ) ), esc_html( $import['file'] ) );
	}

	/**
	 * Link to the map step for an import
	 *
	 * @param array $import
	 *
	 * @return string
	 */
	protected function get_map_url( $import ) {
		return add_query_arg( [
			'page'   => 'gh_tools',
			'tab'    => 'import',
			'action' => 'map',
			'import' => rawurlencode( $import['file'] ),
		], admin_url( 'admin.php' ) );
	}

	/**
	 * Get the rule type
	 *
	 * @param $import array
	 *
	 * @return mixed
	 */
	protected function column_rows( $import ) {
		return number_format_i18n( $import['rows'] );
	}

	/**
	 * Show the points to add.
	 *
	 * @param $import array
	 *
	 * @return string
	 */
	protected function column_date( $import ) {
		$strdate = date_i18n( 'Y-m-d H:i:s', intval( $import['date'] ) );

		return '<abbr title="' . esc_attr( $strdate ) . '">' . esc_html( $strdate ) . '</abbr>';
	}

	/**
	 * Get default column value.
	 *
	 * @param object $import      A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $import, $column_name ) {
		return esc_html( $import[ $column_name ] ?? '' );
	}

	/**
	 * @param object $import A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $import ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			esc_attr( $this->_args['singular'] ),
			esc_attr( $import['file'] )
		);
	}

	/**
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
		);

		return apply_filters( 'wpgh_imports_bulk_actions', $actions );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
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

		if ( file_exists( Plugin::$instance->utils->files->get_csv_imports_dir() ) ) {

			$scanned_directory = array_diff( scandir( Plugin::$instance->utils->files->get_csv_imports_dir() ), [
				'..',
				'.'
			] );

			foreach ( $scanned_directory as $filename ) {

				$filepath = Plugin::$instance->utils->files->get_csv_imports_dir( $filename );

				if ( ! is_file( $filepath ) ) {
					continue;
				}

				$data[] = [
					'file'      => $filename,
					'file_path' => $filepath,
					'file_url'  => file_access_url( '/imports/' . $filename, true ),
					'date'      => filemtime( $filepath ),
					'rows'      => null,
				];
			}
		}

		// Counting rows reads the whole file, so only do it for every file when sorting by rows
		$count_rows = function ( $import ) {
			if ( $import['rows'] === null ) {
				$import['rows'] = count_csv_rows( $import['file_path'] );
			}

			return $import;
		};

		if ( $this->get_orderby() === 'rows' ) {
			$data = array_map( $count_rows, $data );
		}

		/*
		 * Sort the data
		 */
		usort( $data, array( $this, 'usort_reorder' ) );

		$current_page = $this->get_pagenum();

		$total_items = count( $data );

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->items = array_map( $count_rows, $data );

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
		$orderby = $this->get_orderby();
		$result  = strnatcmp( (string) $a[ $orderby ], (string) $b[ $orderby ] );

		return $this->get_order() === 'asc' ? $result : - $result;
	}

	/**
	 * The column to sort by, newest uploads first by default
	 *
	 * @return string
	 */
	protected function get_orderby() {
		$orderby = get_request_var( 'orderby', 'date' );

		return in_array( $orderby, [ 'file', 'rows', 'date' ], true ) ? $orderby : 'date';
	}

	/**
	 * @return string asc or desc
	 */
	protected function get_order() {
		// with no column picked, the default sort (date) is newest first
		$default = get_request_var( 'orderby' ) ? 'asc' : 'desc';

		return get_request_var( 'order', $default ) === 'asc' ? 'asc' : 'desc';
	}

	/**
	 * Generates and displays row action rule.
	 *
	 * @param array  $import      Contact being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $import, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();

		if ( current_user_can( 'import_contacts' ) ) {
			$actions['import'] = sprintf(
				'<a href="%s" class="edit">%s</a>',
				esc_url( $this->get_map_url( $import ) ),
				esc_html__( 'Import', 'groundhogg' )
			);
		}

		if ( current_user_can( 'download_imports' ) ) {
			$actions['download'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $import['file_url'] ),
				esc_html__( 'Download', 'groundhogg' )
			);
		}

		if ( current_user_can( 'delete_imports' ) ) {
			$actions['delete'] = sprintf(
				'<a href="%s" class="submitdelete">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin.php?page=gh_tools&tab=import&action=delete&import=' . rawurlencode( $import['file'] ) ) ) ),
				esc_html__( 'Delete Import', 'groundhogg' )
			);
		}

		return $this->row_actions( $actions );
	}
}
