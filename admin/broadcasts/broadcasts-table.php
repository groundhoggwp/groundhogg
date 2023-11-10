<?php

namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\bulk_jobs;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
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
			'send_time'      => _x( 'Scheduled Run Date', 'Column label', 'groundhogg' ),
			'from_user'      => _x( 'Scheduled By', 'Column label', 'groundhogg' ),
			'campaigns'      => _x( 'Campaigns', 'Column label', 'groundhogg' ),
//			'query'          => _x( 'Query', 'Column label', 'groundhogg' ),
			'sending_to'     => _x( 'Sending To', 'Column label', 'groundhogg' ),
			'stats'          => _x( 'Stats', 'Column label', 'groundhogg' ),
			'date_scheduled' => _x( 'Date Scheduled', 'Column label', 'groundhogg' ),
		);

		if ( $this->get_view() === 'cancelled' ) {
			unset( $columns['send_time'] );
			unset( $columns['sending_to'] );
			unset( $columns['stats'] );
			unset( $columns['query'] );
		} else if ( $this->get_view() === 'scheduled' ) {
			unset( $columns['stats'] );
		} else if ( $this->get_view() === 'pending' ) {
			unset( $columns['stats'] );
			unset( $columns['sending_to'] );

			$columns['time'] = _x( 'Time Remaining', 'Column label', 'groundhogg' );
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
			'scheduled' => get_db( 'broadcasts' )->count( [ 'status' => 'scheduled' ] ),
			'sent'      => get_db( 'broadcasts' )->count( [ 'status' => 'sent' ] ),
			'cancelled' => get_db( 'broadcasts' )->count( [ 'status' => 'cancelled' ] ),
			'pending'   => get_db( 'broadcasts' )->count( [ 'status' => 'pending' ] ),
		];

		$titles = [
			'scheduled' => _x( 'Scheduled', 'view', 'groundhogg' ),
			'sent'      => _x( 'Sent', 'view', 'groundhogg' ),
			'cancelled' => _x( 'Cancelled', 'view', 'groundhogg' ),
			'pending'   => _x( 'Pending', 'view', 'groundhogg' ),
		];

		// If there are no scheduled broadcasts, go to the sent view
		if ( $count['scheduled'] == 0 && $this->get_view() === 'scheduled' ) {
			$this->default_view = 'sent';
		}

		$views = [];

		foreach ( $count as $c => $num ) {

			$view_content = $titles[ $c ];
			$view_content .= " <span class='count'>(" . _nf( $count[ $c ] ) . ")</span>";

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

		if ( $broadcast->is_sent() ) {
			$actions['report'] = "<a href='" . esc_url( admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->get_id()
				] ) ) . "'>" . _x( 'Reporting', 'action', 'groundhogg' ) . "</a>";
		}

		if ( $broadcast->is_email() ) {
			$actions['edit'] = "<a href='" . esc_url( admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $broadcast->get_object_id() ) ) . "'>" . _x( 'Edit Email', 'action', 'groundhogg' ) . "</a>";
		} else {
			$actions['edit'] = "<a href='" . esc_url( admin_url( 'admin.php?page=gh_sms&action=edit&sms=' . $broadcast->get_object_id() ) ) . "'>" . _x( 'Edit SMS', 'action', 'groundhogg' ) . "</a>";
		}

		if ( $broadcast->is_pending() ){
			$actions['schedule'] = html()->e('a', [
				'href' => bulk_jobs()->broadcast_scheduler->get_start_url( [ 'broadcast' => $broadcast->get_id() ] )
			], __('Schedule manually'));
		}

		// Add query action
		$query = $broadcast->get_query();

		if ( ! empty( $query ) && is_array( $query ) ) {
			$query['is_searching'] = 'on';

			$actions['query'] = html()->e( 'a', [
				'href' => admin_page_url( 'gh_contacts', $query )
			], 'View Query' );
		}

		$report_data = $broadcast->get_report_data();

		if ( $broadcast->is_pending() || $broadcast->is_scheduled() ) {
			$actions['trash'] = "<a class='delete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_broadcasts&view=all&action=cancel&broadcast=' . $broadcast->get_id() ), 'cancel' ) . "'>" . _x( 'Cancel', 'action', 'groundhogg' ) . "</a>";
		}

		return $this->row_actions( apply_filters( 'groundhogg/admin/broadcasts/table/handle_row_actions', $actions, $broadcast, $column_name ) );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_object_id( $broadcast ) {

		switch ( $broadcast->get_status() ) {
			default:
			case 'scheduled':

				$html = sprintf( "<strong>%s</strong> &#x2014; <span class='post-state'>(%s)</span>", $broadcast->get_title(), __( 'Scheduled' ) );

				break;

			case 'cancelled':

				$html = sprintf( "<strong>%s</strong> &#x2014; <span class='post-state'>(%s)</span>", $broadcast->get_title(), __( 'Cancelled' ) );

				break;

			case 'sent':

				$edit_url = admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->get_id()
				] );

				$html = sprintf( "<strong><a class='row-title' href='%s'>%s</a></strong>", $edit_url, $broadcast->get_title() );

				break;
		}

		return $html;
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
	protected function column_query( $broadcast ) {

		$query                 = $broadcast->get_query();
		$query['is_searching'] = 'on';

		return html()->e( 'a', [
			'href' => admin_page_url( 'gh_contacts', $query )
		], 'View contacts' );
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_sending_to( $broadcast ) {

		$num = Plugin::$instance->dbs->get_db( 'event_queue' )->count( [
			'step_id'    => $broadcast->get_id(),
			'status'     => Event::WAITING,
			'event_type' => Event::BROADCAST
		] );

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

		if ( $broadcast->get_status() !== 'sent' ) {
			return '&#x2014;';
		}

		$stats = $broadcast->get_report_data();

		$html = "";

		$html .= sprintf(
			"%s: <strong><a href='%s'>%s</a></strong><br/>",
			_x( "Sent", 'stats', 'groundhogg' ),
			add_query_arg(
				[
					'report' => [
						'type'   => Event::BROADCAST,
						'step'   => $broadcast->get_id(),
						'status' => Event::COMPLETE
					]
				],
				admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
			),
			_nf( $stats['sent'] )
		);

		if ( $broadcast->is_email() ) {

			$html .= sprintf(
				"%s: <strong><a href='%s'>%s</a></strong><br/>",
				_x( "Opened", 'stats', 'groundhogg' ),
				add_query_arg(
					[
						'activity' => [
							'activity_type' => Activity::EMAIL_OPENED,
							'step'          => $broadcast->get_id(),
							'funnel'        => $broadcast->get_funnel_id()
						]
					],
					admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
				),
				_nf( $stats['opened'] )
			);

			$html .= sprintf(
				"%s: <strong><a href='%s'>%s</a></strong><br/>",
				_x( "Clicked", 'stats', 'groundhogg' ),
				add_query_arg(
					[
						'activity' => [
							'activity_type' => Activity::EMAIL_CLICKED,
							'step'          => $broadcast->get_id(),
							'funnel'        => $broadcast->get_funnel_id()
						]
					],
					admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
				),
				_nf( $stats['clicked'] )
			);
		} else if ( $broadcast->is_sms() ) {

			$html .= sprintf(
				"%s: <strong><a href='%s'>%s</a></strong><br/>",
				_x( "Clicked", 'stats', 'groundhogg' ),
				add_query_arg(
					[
						'activity' => [
							'activity_type' => Activity::SMS_CLICKED,
							'step'          => $broadcast->get_id(),
							'funnel'        => $broadcast->get_funnel_id()
						]
					],
					admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
				),
				_nf( $stats['clicked'] )
			);

		}

		return $html;
	}

	/**
	 * @param $broadcast Broadcast
	 *
	 * @return string
	 */
	protected function column_send_time( $broadcast ) {

		$prefix = $broadcast->is_sent() ? __( 'Sent', 'groundhogg' ) : __( 'Sending', 'groundhogg' );

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
			return __( 'Estimating...', 'groundhogg' );
		}

		$complete = $broadcast->get_percent_scheduled();

		return sprintf( __( '%d%% scheduled with %s remaining', 'groundhogg' ), $complete, human_time_diff( time(), time() + $time_remaining ) );
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
		], __( 'Finish Scheduling' ) );
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
				], $_SERVER['REQUEST_URI'] ),
			], $campaign->get_name() );
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

		switch ( $this->get_view() ) {
			default:
			case 'scheduled':
				$actions['cancel'] = _x( 'Cancel', 'List table bulk action', 'groundhogg' );
				break;
			case 'cancelled':
			case 'sent':
				$actions['delete'] = _x( 'Delete', 'List table bulk action', 'groundhogg' );
				break;
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

		$anonymous = function ( $clauses ) use ( $search ) {

			if ( ! empty( $search ) ) {

				$emails_table       = get_db( 'emails' )->table_name;
				$email_table_search = "SELECT ID FROM $emails_table WHERE `title` RLIKE '{$search}' OR `subject` RLIKE '{$search}'";
				$clauses['where']   .= " AND object_id IN ({$email_table_search})";
			}

			return $clauses;
		};

		add_filter( 'groundhogg/db/sql_query_clauses', $anonymous, 13 );

		$events = get_db( 'broadcasts' )->query( $args );
		$total  = get_db( 'broadcasts' )->found_rows();

		remove_filter( 'groundhogg/db/sql_query_clauses', $anonymous, 13 );

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
}
