<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Broadcast;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\contact_filters_link;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_uri;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\scheduled_time_column;

/**
 * The table for Broadcasts
 *
 * This just displays all the broadcast information in a WP_List_Table
 * Columns display basic information about the broadcast including send time
 * and basic reporting.
 *
 * @since       File available since Release 0.1
 * @see         WP_List_Table
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Broadcasts_Table extends WP_List_Table {

	protected $default_view = 'scheduled';

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'broadcast',     // Singular name of the listed records.
			'plural'   => 'broadcasts',    // Plural name of the listed records.
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
			'cb'             => '<input type="checkbox" />', // Render a checkbox instead of text.
			'object_id'      => _x( 'Email/SMS', 'Column label', 'groundhogg' ),
			'send_time'      => _x( 'Send Date', 'Column label', 'groundhogg' ),
			'campaigns'      => _x( 'Campaigns', 'Column label', 'groundhogg' ),
			'sending_to'     => _x( 'Remaining', 'Column label', 'groundhogg' ),
			'stats'          => _x( 'Stats', 'Column label', 'groundhogg' ),
			'from_user'      => _x( 'Scheduled By', 'Column label', 'groundhogg' ),
			'date_scheduled' => _x( 'Date Scheduled', 'Column label', 'groundhogg' ),
		);

		switch ( $this->get_view() ) {
			case 'pending':
				unset( $columns['stats'] );
				unset( $columns['sending_to'] );

				$columns['time'] = _x( 'Time Remaining', 'Column label', 'groundhogg' );
				break;
			case 'scheduled':
				unset( $columns['stats'] );
				break;
			default:
			case 'sent':
				unset( $columns['sending_to'] );
				break;
			case 'sending':
				break;
			case 'cancelled':
				unset( $columns['send_time'] );
				unset( $columns['sending_to'] );
				unset( $columns['stats'] );
				unset( $columns['query'] );
				break;
		}

		/**
		 * Filter the columns
		 *
		 * @param $columns array the columns for the given view
		 * @param $view    string the current view of the table
		 */
		return apply_filters( 'groundhogg/admin/broadcasts/table/columns', $columns, $this->get_view() );
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
			'object_id'      => array( 'object_id', false ),
			'from_user'      => array( 'scheduled_by', false ),
			'send_time'      => array( 'send_time', false ),
			'date_scheduled' => array( 'date_scheduled', false )
		);

		/**
		 * Filter the columns
		 *
		 * @param $columns array the columns for the given view
		 * @param $view    string the current view of the table
		 */
		return apply_filters( 'groundhogg/admin/broadcast/table/sortable_columns', $sortable_columns, $this->get_view() );
	}

	/**
	 * Get the views for the broadcasts, all, ready, unready, trash
	 *
	 * @return array
	 */
	protected function get_views() {
		$count = [
			'pending'   => get_db( 'broadcasts' )->count( [ 'status' => 'pending' ] ),
			'scheduled' => get_db( 'broadcasts' )->count( [ 'status' => 'scheduled' ] ),
			'sending'   => get_db( 'broadcasts' )->count( [ 'status' => 'sending' ] ),
			'sent'      => get_db( 'broadcasts' )->count( [ 'status' => 'sent' ] ),
			'cancelled' => get_db( 'broadcasts' )->count( [ 'status' => 'cancelled' ] ),
		];

		$titles = [
			'pending'   => _x( 'Pending', 'view', 'groundhogg' ),
			'scheduled' => _x( 'Scheduled', 'view', 'groundhogg' ),
			'sending'   => _x( 'Sending', 'view', 'groundhogg' ),
			'sent'      => _x( 'Sent', 'view', 'groundhogg' ),
			'cancelled' => _x( 'Cancelled', 'view', 'groundhogg' ),
		];

		// If there are no scheduled broadcasts, go to the sent view
		if ( $count['scheduled'] == 0 && $this->get_view() === 'scheduled' ) {
			$this->default_view = 'sent';
		}

		$views = [];

		foreach ( $count as $c => $num ) {

			if ( $num === 0 ) {
				continue;
			}

			$view_content = $titles[ $c ];
			$view_content .= " <span class='count'>(" . _nf( $num ) . ")</span>";

			$views[ $c ] = html()->e( 'a', [
				'href'  => admin_page_url( 'gh_broadcasts', [ 'status' => $c ] ),
				'class' => $this->get_view() === $c ? 'current' : ''
			], $view_content );

		}

		return apply_filters( 'groundhogg/admin/broadcasts/table/get_views', $views );
	}

	/**
	 * Get the current view
	 *
	 * @return mixed
	 */
	protected function get_view() {
		return get_url_var( 'status', $this->default_view );
	}

	/**
	 * @param object $item convert $item to broadcast object
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Broadcast( $item->ID ) );
		echo '</tr>';
	}

	/**
	 * Get default row steps...
	 *
	 * @param $broadcast Broadcast
	 * @param $column_name
	 * @param $primary
	 *
	 * @return string a list of steps
	 */
	protected function handle_row_actions( $broadcast, $column_name, $primary ) {

		if ( $primary !== $column_name || $this->get_view() === 'cancelled' ) {
			return '';
		}

		$actions = [];

		if ( $broadcast->status_is( 'sent' ) || $broadcast->status_is( 'sending' ) ) {
			$actions['report'] = html()->e( 'a', [
				'href' => admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->get_id()
				] )
			], _x( 'Report', 'action', 'groundhogg' ) );
		}

		$actions['edit'] = html()->e( 'a', [
			'href' => admin_page_url( $broadcast->is_email() ? 'gh_emails' : 'gh_sms', [ 'action' => 'edit', $broadcast->get_broadcast_type() => $broadcast->get_object_id() ] )
		], $broadcast->is_email() ? esc_html__( 'Edit email', 'groundhogg' ) : esc_html__( 'Edit SMS' , 'groundhogg' ) );

		if ( $broadcast->is_email() ){
			$actions[] = html()->a( '#', esc_html__( 'Preview', 'groundhogg' ), [ 'class' => 'gh-email-preview', 'data-id' => $broadcast->get_object_id() ] );
		}

		// Add query action
		$query = $broadcast->get_query();

		if ( ! empty( $query ) && is_array( $query ) ) {
			$query['is_searching'] = 'on';

			$actions['query'] = html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', $query )
			], esc_html__( 'Recipients', 'groundhogg' ) );
		}

		if ( ! $broadcast->is_sent() ) {
			$actions['trash'] = html()->e( 'a', [
				'href' => action_url( 'cancel', [
					'broadcast' => $broadcast->get_id(),
				] ),
			], esc_html__( 'Cancel', 'groundhogg' ) );
		}

		return $this->row_actions( apply_filters( 'groundhogg/admin/broadcasts/table/handle_row_actions', $actions, $broadcast, $column_name ) );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_object_id( $broadcast ) {

		if ( $broadcast->is_sent() ) {
			return html()->e( 'a', [
				'class' => 'row-title',
				'href'  => admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->get_id()
				] )
			], $broadcast->get_title() );
		}

		return html()->e( 'span', [ 'class' => 'row-title' ], $broadcast->get_title() );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_from_user( $broadcast ) {
		$user      = get_userdata( $broadcast->get_scheduled_by_id() );
		$from_user = esc_html( $user->display_name );
		$queryUrl  = admin_url( 'admin.php?page=gh_broadcasts&scheduled_by=' . $broadcast->get_scheduled_by_id() );

		return "<a href='$queryUrl'>$from_user</a>";
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_sending_to( $broadcast ) {

		$num = $broadcast->count_pending_events();

		if ( ! $num ) {
			return '&#x2014;';
		}

		$link = admin_page_url( 'gh_contacts', [
			'report' => [
				'type'   => Event::BROADCAST,
				'step'   => $broadcast->get_id(),
				'status' => Event::WAITING
			]
		] );

		return sprintf( "<a href='%s'>%s</a>",
			$link,
			number_format_i18n( $num )
		);
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_stats( $broadcast ) {

		$stats = $broadcast->get_report_data();

		$html = "";

		$html .= sprintf(
			"%s: <strong>%s</strong><br/>",
			esc_html_x( "Sent", 'stats', 'groundhogg' ),
			contact_filters_link( _nf( $stats['sent'] ), [
				[
					[
						'type'         => 'broadcast_received',
						'broadcast_id' => $broadcast->get_id()
					]
				]
			], $stats['sent'] > 0 )
		);

		// Can only check opens if email. No open tracking for SMS
		if ( $broadcast->is_email() ) {

			$html .= sprintf(
				"%s: <strong>%s</strong><br/>",
				esc_html_x( "Opened", 'stats', 'groundhogg' ),
				contact_filters_link( _nf( $stats['opened'] ), [
					[
						[
							'type'         => 'broadcast_opened',
							'broadcast_id' => $broadcast->get_id()
						]
					]
				], $stats['opened'] > 0 )
			);
		}

		$html .= sprintf(
			"%s: <strong>%s</strong><br/>",
			esc_html_x( "Clicked", 'stats', 'groundhogg' ),
			contact_filters_link( _nf( $stats['clicked'] ), [
				[
					[
						'type'         => 'broadcast_link_clicked',
						'broadcast_id' => $broadcast->get_id(),
						'is_sms'       => $broadcast->is_sms()
					]
				]
			], $stats['clicked'] > 0 ),
		);

		return $html;
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_send_time( $broadcast ) {

		$prefix = $broadcast->is_sent() ? esc_html__( 'Sent', 'groundhogg' ) : esc_html__( 'Sending', 'groundhogg' );

		return $prefix . ' ' . scheduled_time_column( $broadcast->get_send_time() );
	}

	/**
	 * Shows the time remaining for the broadcast to complete scheduling
	 *
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_time( $broadcast ) {

		$time_remaining = $broadcast->get_estimated_scheduling_time_remaining();

		if ( $time_remaining === false ) {
			return esc_html__( 'Estimating...', 'groundhogg' );
		}

		$complete = $broadcast->get_percent_scheduled();

		/* translators: 1: the percentage scheduled 2: the time remaining */
		return sprintf( esc_html__( '%1$d%% scheduled with %2$s remaining', 'groundhogg' ), $complete, human_time_diff( time(), time() + $time_remaining ) );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_date_scheduled( $broadcast ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $broadcast->get_date_scheduled() ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_process_schedule( $broadcast ) {
		$confirm_link = admin_page_url( 'gh_broadcasts', [
			'action'    => 'preview',
			'broadcast' => $broadcast->get_id(),
		] );

		return html()->e( 'a', [
			'href'  => $confirm_link,
			'class' => 'button'
		], esc_html__( 'Finish Scheduling' , 'groundhogg' ) );
	}

	/**
	 * @param Broadcast $broadcast
	 */
	protected function column_campaigns( $broadcast ) {
		$campaigns = $broadcast->get_related_objects( 'campaign' );

		return implode( ', ', array_map( function ( $campaign ) {
			return html()->e( 'a', [
				'href' => add_query_arg( [
					'related' => [ 'ID' => $campaign->ID, 'type' => 'campaign' ]
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				], get_request_uri() ),
			], esc_html( $campaign->get_name() ) );
		}, $campaigns ) );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $broadcast   A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $broadcast, $column_name ) {
		do_action( 'groundhogg/admin/broadcasts/table/column_default', $broadcast, $column_name );

		return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $broadcast A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $broadcast ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$broadcast->ID                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [];

		if ( in_array( $this->get_view(), [ 'scheduled', 'pending', 'sending' ] ) ) {
			$actions['cancel'] = _x( 'Cancel', 'List table bulk action', 'groundhogg' );
		}

		if ( in_array( $this->get_view(), [ 'scheduled', 'pending', 'sending', 'sent' ] ) ) {
			$actions['add_campaigns'] = _x( 'Add to campaign', 'List table bulk action', 'groundhogg' );
			$actions['remove_campaigns'] = _x( 'Remove from campaign', 'List table bulk action', 'groundhogg' );
		}

		if ( in_array( $this->get_view(), [ 'cancelled', 'sent' ] ) ) {
			$actions['delete'] = _x( 'Delete', 'List table bulk action', 'groundhogg' );
		}

		return apply_filters( 'wpgh_broadcast_bulk_actions', $actions );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * REQUIRED! This is where you prepare your data for display. This method will
	 *
	 * @global wpdb $wpdb
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
		$search   = sanitize_text_field( get_url_var( 's' ) );
		$order    = get_url_var( 'order', 'DESC' );
		$orderby  = get_url_var( 'orderby', 'ID' );

		// Only look for emails.

		$args = get_request_query();

		$args = array_merge( $args, [
			'status'     => $this->get_view(),
			'limit'      => $per_page,
			'offset'     => $offset,
			'order'      => $order,
			'orderby'    => $orderby,
			'found_rows' => true,
		] );

		if ( ! is_sms_plugin_active() ) {
			$args['object_type'] = 'email';
		}

		if ( ! empty( $search ) ) {

			unset( $args['s'] );
			unset( $args['search'] );

			// Handle search
			add_action( 'groundhogg/broadcast/pre_get_results', function ( Table_Query &$query ) use ( $search ) {

				$emailJoin = $query->addJoin( 'LEFT', 'emails' );
				$emailJoin->onColumn( 'ID', 'object_id' )
				          ->equals( "$query->alias.object_type", 'email' );

				$searchWhere = $query->where()->subWhere();

				$searchWhere->like( "$emailJoin->alias.title", '%' . $query->db->esc_like( $search ) . '%' );
				$searchWhere->like( "$emailJoin->alias.subject", '%' . $query->db->esc_like( $search ) . '%' );

				if ( is_sms_plugin_active() ) {
					$smsJoin = $query->addJoin( 'LEFT', 'sms' );
					$smsJoin->onColumn( 'ID', 'object_id' )
					        ->equals( "$query->alias.object_type", 'sms' );

					$searchWhere->like( "$smsJoin->alias.title", '%' . $query->db->esc_like( $search ) . '%' );
				}

			} );
		}

		$items = get_db( 'broadcasts' )->query( $args );
		$total = get_db( 'broadcasts' )->found_rows();

		$this->items = $items;

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $per_page ? ceil( (int) $total / (int) $per_page ) : 1;

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		) );
	}
}
