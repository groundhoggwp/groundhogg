<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Admin\Table;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Manager;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\scheduled_time_column;


/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
 *
 * @since       0.1
 * @subpackage  Includes/Emails
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

class Funnels_Table extends Table {

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
			'active_contacts' => _x( 'Waiting Contacts', 'Column label', 'groundhogg' ),
			'campaigns'       => _x( 'Campaigns', 'Column label', 'groundhogg' ),
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
				$html .= " &#x2014; " . "<span class='post-state'>" . _x( 'Inactive', 'status', 'groundhogg' ) . "</span>";
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
	 * @param Funnel $funnel
	 */
	protected function column_campaigns( $funnel ) {
		$campaigns = $funnel->get_related_objects( 'campaign' );

		return implode( ', ', array_map( function ( $campaign ) {
			return html()->e( 'a', [
				'href' => add_query_arg( [
					'related' => [ 'ID' => $campaign->ID, 'type' => 'campaign' ]
				], $_SERVER['REQUEST_URI'] ),
			], $campaign->get_name() );
		}, $campaigns ) );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $funnel      A singular item (one full row's worth of data).
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


		switch ( $this->get_view() ) {
			case 'active':
				$actions = [
					'deactivate' => _x( 'Deactivate', 'List table bulk action', 'groundhogg' ),
					'archive'    => _x( 'Archive', 'List table bulk action', 'groundhogg' )
				];
				break;

			case 'inactive':
				$actions = [
					'activate' => _x( 'Activate', 'List table bulk action', 'groundhogg' ),
					'archive'  => _x( 'Archive', 'List table bulk action', 'groundhogg' )
				];
				break;
			case 'archived':
				$actions = [
					'delete'  => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
					'restore' => _x( 'Restore', 'List table bulk action', 'groundhogg' )
				];
				break;
		}

		return apply_filters( 'groundhogg_email_bulk_actions', $actions );
	}

	function get_table_id() {
		return 'funnels';
	}

	function get_db() {
		return get_db( 'funnels' );
	}

	protected function parse_item( $item ) {
		return new Funnel( $item );
	}

	/**
	 * @param $item Funnel
	 * @param $column_name
	 * @param $primary
	 *
	 * @return array
	 */
	protected function get_row_actions( $item, $column_name, $primary ) {
		$actions = [];

		switch ( $this->get_view() ) {
			default:
				$actions[] = [ 'class' => 'edit', 'display' => __( 'Edit' ), 'url' => $item->admin_link() ];
				$actions[] = [
					'class'   => 'report',
					'display' => __( 'Report' ),
					'url'     => admin_page_url( 'gh_reporting', [
						'tab'    => 'funnels',
						'funnel' => $item->get_id(),
					] )
				];
				$actions[] = [
					'class'   => 'duplicate',
					'display' => __( 'Duplicate' ),
					'url'     => action_url( 'duplicate', [ 'funnel' => $item->get_id() ] )
				];
				$actions[] = [
					'class'   => 'export',
					'display' => __( 'Export' ),
					'url'     => $item->export_url()
				];
				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Deactivate' ),
					'url'     => action_url( 'deactivate', [ 'funnel' => $item->get_id() ] )
				];
				break;
			case 'inactive':
				$actions[] = [ 'class' => 'edit', 'display' => __( 'Edit' ), 'url' => $item->admin_link() ];
				$actions[] = [
					'class'   => 'duplicate',
					'display' => __( 'Duplicate' ),
					'url'     => action_url( 'duplicate', [ 'funnel' => $item->get_id() ] )
				];
				$actions[] = [
					'class'   => 'export',
					'display' => __( 'Export' ),
					'url'     => $item->export_url()
				];
				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Archive' ),
					'url'     => action_url( 'archive', [ 'funnel' => $item->get_id() ] )
				];
				break;
			case 'archived':
				$actions[] = [
					'class'   => 'restore',
					'display' => __( 'Restore' ),
					'url'     => action_url( 'restore', [ 'funnel' => $item->get_id() ] )
				];
				$actions[] = [
					'class'   => 'trash',
					'display' => __( 'Delete' ),
					'url'     => action_url( 'delete', [ 'funnel' => $item->get_id() ] )
				];
				break;
		}

		return $actions;
	}

	protected function get_views_setup() {
		return [
			[
				'view'    => 'active',
				'display' => __( 'Active', 'groundhogg' ),
				'query'   => [ 'status' => 'active' ],
			],
			[
				'view'    => 'inactive',
				'display' => __( 'Inactive', 'groundhogg' ),
				'query'   => [ 'status' => 'inactive' ]
			],
			[
				'view'    => 'archived',
				'display' => __( 'Archived', 'groundhogg' ),
				'query'   => [ 'status' => 'archived' ],
			],
		];
	}

	function get_default_query() {
		return [];
	}
}
