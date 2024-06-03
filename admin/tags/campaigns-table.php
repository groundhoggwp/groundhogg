<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Admin\Table;
use Groundhogg\Campaign;
use WP_List_Table;
use function Groundhogg\action_url;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tags Table
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Campaigns_Table extends Table {
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
			'cb'          => '<input type="checkbox" />', // Render a checkbox instead of text.
			'name'        => _x( 'Name', 'Column label', 'groundhogg' ),
			'description' => _x( 'Description', 'Column label', 'groundhogg' ),
			'visibility'  => _x( 'Visibility', 'Column label', 'groundhogg' ),
//			'contacts' => _x( 'Count', 'Column label', 'groundhogg' ),
		);

		return apply_filters( 'groundhogg/admin/campaigns/table/get_columns', $columns );
	}

	/**
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', false ),
//			'tag_description' => array( 'tag_description', false ),
//			'contacts' => array( 'contacts', false ),
		);

		return apply_filters( 'groundhogg/admin/campaigns/table/sortable_columns', $sortable_columns );
	}

	/**
	 * @param $campaign Campaign
	 *
	 * @return string
	 */
	protected function column_name( $campaign ) {
		return html()->e( 'a', [
			'class' => 'row-title',
			'href'  => $campaign->admin_link()
		], esc_html( $campaign->get_name() ) );
	}

	/**
	 * @param $campaign Campaign
	 *
	 * @return string
	 */
	protected function column_description( $campaign ) {
		return ! empty( $campaign->get_description() ) ? $campaign->get_description() : '&#x2014;';
	}

	/**
	 * @param Campaign $campaign
	 *
	 * @return string
	 */
	protected function column_visibility( $campaign ) {
		return $campaign->is_public() ? __( 'Public' ) : __( 'Hidden' );
	}

	/**
	 * Get default column value.
	 *
	 * @param object $tag         A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return void
	 */
	protected function column_default( $tag, $column_name ) {
		do_action( "groundhogg/admin/campaigns/table/{$column_name}", $tag );
	}


	function get_table_id() {
		return 'campaigns';
	}

	function get_db() {
		return get_db( 'campaigns' );
	}

	protected function parse_item( $item ) {
		return new Campaign( $item );
	}

	/**
	 * @param $item Campaign
	 * @param $column_name
	 * @param $primary
	 *
	 * @return array[]
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {
		return [
			[
				'display' => 'ID: ' . $item->get_id(),
				'url'     => false
			],
			[
				'class'   => 'edit',
				'display' => __( 'Edit' ),
				'url'     => $item->admin_link()
			],
			[
				'class'   => 'view',
				'display' => __( 'View Archive' ),
				'url'     => managed_page_url( sprintf( '/campaigns/%s', $item->get_slug() ) )
			],
			[
				'class'   => 'trash',
				'display' => __( 'Delete' ),
				'url'     => action_url( 'delete', [ 'campaign' => $item->get_id() ] )
			]
		];
	}

	protected function get_views_setup() {
		return [];
	}

	function get_default_query() {
		return [];
	}
}
