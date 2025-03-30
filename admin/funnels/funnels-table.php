<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Admin\Table;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Manager;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\check_lock;
use function Groundhogg\contact_filters_link;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\row_item_locked_text;
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
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {

		$columns = array(
			'cb'              => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'           => _x( 'Title', 'Column label', 'groundhogg' ),
			'steps'           => _x( 'Preview', 'Column label', 'groundhogg' ),
			'active_contacts' => _x( 'Waiting Contacts', 'Column label', 'groundhogg' ),
			'campaigns'       => _x( 'Campaigns', 'Column label', 'groundhogg' ),
			'author'          => _x( 'Author', 'Column label', 'groundhogg' ),
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
			'author'       => array( 'author', false ),
			'last_updated' => array( 'last_updated', false ),
			'date_created' => array( 'date_created', false )
		);

		return apply_filters( 'groundhogg_funnels_get_sortable_columns', $sortable_columns );
	}


	protected function column_title( $funnel ) {

		$subject = $funnel->get_title();
		$editUrl = $funnel->admin_link();

		if ( $this->get_view() === 'trash' ) {
			return "<strong class='row-title'>{$subject}</strong>";
		}

		row_item_locked_text( $funnel );

		$html = "<strong>";

		if ( $funnel->has_errors() ) {
			$html .= '<span>⚠️<div class="gh-tooltip top">Steps in this funnel might have issues.</div></span>';
		}

		$html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

		$html .= "</strong>";

		return $html;
	}

	/**
	 * @param $funnel Funnel
	 *
	 * @return string
	 */
	protected function column_active_contacts( $funnel ) {

		$eventQuery = new Table_Query( 'event_queue' );
		$eventQuery
			->where()
			->equals( 'funnel_id', $funnel->get_id() )
			->equals( 'event_type', Event::FUNNEL )
			->equals( 'status', Event::WAITING );

		$count_waiting = $eventQuery->count();

		return contact_filters_link( _nf( $count_waiting ), [
			[
				[
					'type'      => 'funnel_history',
					'status'    => Event::WAITING,
					'funnel_id' => $funnel->get_id(),
				]
			]
		], $count_waiting );
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
	 * @param $funnel Funnel
	 *
	 * @return string
	 */
	protected function column_author( Funnel $funnel ) {
		$user = get_userdata( intval( ( $funnel->author ) ) );
		if ( ! $user ) {
			return __( 'Unknown', 'groundhogg' );
		}
		$from_user = esc_html( $user->display_name );
		$queryUrl  = admin_page_url( 'gh_funnels', [
			'author' => $funnel->author
		] );

		return "<a href='$queryUrl'>$from_user</a>";
	}

	protected function column_steps( Funnel $funnel ) {
        $funnel->flow_preview( 10 );
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
					'archive'    => _x( 'Archive', 'List table bulk action', 'groundhogg' ),
					'export'     => _x( 'Export', 'List table bulk action', 'groundhogg' ),
				];
				break;

			case 'inactive':
				$actions = [
					'activate' => _x( 'Activate', 'List table bulk action', 'groundhogg' ),
					'archive'  => _x( 'Archive', 'List table bulk action', 'groundhogg' ),
					'export'   => _x( 'Export', 'List table bulk action', 'groundhogg' ),
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

				if ( ! check_lock( $item ) ) {
					$actions[] = [
						'class'   => 'trash',
						'display' => __( 'Deactivate' ),
						'url'     => action_url( 'deactivate', [ 'funnel' => $item->get_id() ] )
					];
				}

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

				if ( ! check_lock( $item ) ) {
					$actions[] = [
						'class'   => 'trash',
						'display' => __( 'Archive' ),
						'url'     => action_url( 'archive', [ 'funnel' => $item->get_id() ] )
					];
				}

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
