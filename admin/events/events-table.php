<?php

namespace Groundhogg\Admin\Events;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Utils\DateTimeHelper;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\code_it;
use function Groundhogg\find_object;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

/**
 * Events Table Class
 *
 * This class shows the events queue with bulk options to manage events or 1 at a time.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Events_Table extends WP_List_Table {

	protected $table;

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'event',     // Singular name of the listed records.
			'plural'   => 'events',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = [
			'cb'             => '<input type="checkbox" />', // Render a checkbox instead of text.
			'contact'        => _x( 'Contact', 'Column label', 'groundhogg' ),
			'funnel'         => _x( 'Flow', 'Column label', 'groundhogg' ),
			'step'           => _x( 'Step', 'Column label', 'groundhogg' ),
			'time'           => _x( 'Run Date', 'Column label', 'groundhogg' ),
			'time_scheduled' => _x( 'Date Scheduled', 'Column label', 'groundhogg' ),
			'error_code'     => _x( 'Error Code', 'Column label', 'groundhogg' ),
			'error_message'  => _x( 'Error Message', 'Column label', 'groundhogg' ),
		];

		if ( ! in_array( $this->get_view(), [ 'skipped', 'failed' ] ) ) {
			unset( $columns['error_code'] );
			unset( $columns['error_message'] );
		}

		switch ( $this->get_view() ) {
			case Event::WAITING:
				$columns['time'] = _x( 'Runs On', 'Column label', 'groundhogg' );
				break;
			case Event::PAUSED:
				$columns['time_scheduled'] = _x( 'Date Paused', 'Column label', 'groundhogg' );
				break;
			case Event::COMPLETE:
				$columns['time'] = _x( 'Date Completed', 'Column label', 'groundhogg' );
				unset( $columns['time_scheduled'] );
				break;
			case Event::SKIPPED:
				$columns['time_scheduled'] = _x( 'Date Skipped', 'Column label', 'groundhogg' );
				$columns['error_message']  = _x( 'Skipped Reason', 'Column label', 'groundhogg' );
				unset( $columns['error_code'] );
				break;
			case Event::FAILED:
				$columns['time'] = _x( 'Date Failed', 'Column label', 'groundhogg' );
				unset( $columns['time_scheduled'] );
				break;
			case Event::CANCELLED:
				$columns['time_scheduled'] = _x( 'Date Cancelled', 'Column label', 'groundhogg' );
				break;
		}

		return apply_filters( 'groundhogg_event_columns', $columns );
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = [
			'contact'        => [ 'contact_id', false ],
			'funnel'         => [ 'funnel_id', false ],
			'step'           => [ 'step_id', false ],
			'time'           => [ 'time', false ],
			'time_scheduled' => [ 'time_scheduled', false ],
		];

		return apply_filters( 'groundhogg_event_sortable_columns', $sortable_columns );
	}

	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Event( $item->ID, $this->table ) );
		echo '</tr>';
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_contact( $event ) {
		if ( ! $event->get_contact() || ! $event->get_contact()->exists() ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'contact deleted', 'status', 'groundhogg' ) );
		}

		$html = sprintf( "<a class='row-title' href='%s'>%s</a>",
			sprintf( admin_url( 'admin.php?page=gh_events&contact_id=%s&&status=%s' ), $event->get_contact_id(), $this->get_view() ),
			$event->get_contact()->get_email()
		);

		return $html;
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_funnel( $event ) {
		$funnel_title = $event->get_funnel_title();

		if ( ! $funnel_title ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'flow deleted', 'status', 'groundhogg' ) );
		}

		return sprintf( "<a href='%s'>%s</a>",
			sprintf( admin_url( 'admin.php?page=gh_events&funnel_id=%s&event_type=%s&status=%s' ), $event->get_funnel_id(), $event->get_event_type(), $this->get_view() ),
			$funnel_title );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_step( $event ) {
		$step_title = $event->get_step_title();

		if ( ! $step_title ) {
			return sprintf( "<strong>(%s)</strong>", _x( 'step deleted', 'status', 'groundhogg' ) );
		}

		return sprintf( "<a href='%s'>%s</a>",
			admin_url( sprintf( 'admin.php?page=gh_events&step_id=%d&event_type=%s&status=%s', $event->get_step_id(), $event->get_event_type(), $this->get_view() ) ),
			$step_title );

	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_time( $event ) {

		$date = new DateTimeHelper( $event->get_time() );

		$class = '';

		$abbr = html()->e( 'abbr', [
			'title' => $date->ymdhis(),
		], [ $date->wpDateTimeFormat() ] );

		if ( ! in_array( $this->get_view(), [ Event::WAITING, Event::PAUSED ] ) ) {
			return $abbr;
		}

		$tooltip = '';

		if ( $date->isPast() ) {
			$class   = 'gh-text warning';
			$tooltip = 'This event is overdue. It will likely run soon.';

			if ( $date->diff( new DateTimeHelper() )->days > 1 ) {
				$class   = 'gh-text red';
				$tooltip = 'This event is <b>very</b> overdue. There might be an issue with your cron jobs.';
			}
		}

		return html()->e( 'span', [
			'class' => $class,
		], [ $abbr, $tooltip ? "<div class='gh-tooltip top'>$tooltip</div>" : '' ] );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_time_scheduled( $event ) {
		$date = new DateTimeHelper( $event->get_time_scheduled() );

		return html()->e( 'abbr', [
			'title' => $date->ymdhis()
		], $date->wpDateTimeFormat() );
	}

	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_error_code( $event ) {
		return $event->get_error_code() ? code_it( esc_html( strtolower( $event->get_error_code() ) ) ) : '&#x2014;';
	}


	/**
	 * @param $event Event
	 *
	 * @return string
	 */
	protected function column_error_message( $event ) {
		return $event->get_error_message() ? esc_html( strtolower( $event->get_error_message() ) ) : '&#x2014;';
	}

	/**
	 * Get default column value.
	 *
	 * @param Event  $event       A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $event, $column_name ) {

		do_action( 'groundhogg_events_custom_column', $event, $column_name );

		return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $event A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $event ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$event->ID           // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

		$actions = [];

		switch ( $this->get_view() ) {
			case 'paused':
				$actions['unpause'] = _x( 'Unpause', 'List table bulk action', 'groundhogg' );
				$actions['cancel']  = _x( 'Cancel', 'List table bulk action', 'groundhogg' );
				break;
			default:
			case 'waiting':
				$actions['execute_now'] = _x( 'Run Now', 'List table bulk action', 'groundhogg' );
				$actions['pause']       = _x( 'Pause', 'List table bulk action', 'groundhogg' );
				$actions['cancel']      = _x( 'Cancel', 'List table bulk action', 'groundhogg' );
				break;
			case 'cancelled':
				$actions['uncancel'] = _x( 'Uncancel', 'List table bulk action', 'groundhogg' );
				$actions['purge']    = _x( 'Purge', 'List table bulk action', 'groundhogg' );
				break;
			case 'skipped':
			case 'failed':
				$actions['purge'] = _x( 'Purge', 'List table bulk action', 'groundhogg' );
			case 'complete':
				$actions['execute_again'] = _x( 'Run Again', 'List table bulk action', 'groundhogg' );
				break;
		}

		return apply_filters( 'groundhogg_event_bulk_actions', $actions );
	}

	protected function get_view() {
		return get_url_var( 'status', 'waiting' );
	}

	protected function get_views() {

		$view = $this->get_view();

		$eventQuery = new Table_Query( 'event_queue' );
		$eventQuery->setSelect( 'status', [ 'COUNT(ID)', 'total' ] )
		           ->setGroupby( 'status' );
		$results = $eventQuery->get_results();

		$views = [
			Event::WAITING     => __( 'Waiting', 'groundhogg' ),
			Event::PAUSED      => __( 'Paused', 'groundhogg' ),
			Event::IN_PROGRESS => __( 'In Progress', 'groundhogg' ),
		];

		$views = array_map_with_keys( $views, function ( $text, $status ) use ( $view, $results ) {
			$count = get_array_var( find_object( $results, [ 'status' => $status ] ), 'total', 0 );

			if ( $count === 0 ) {
				return '';
			}

			return html()->e( 'a', [
				'class' => [ $status, $view == $status ? 'current' : '' ],
				'href'  => admin_page_url( 'gh_events', [
					'status' => $status
				] )
			], [
				$text . ' ',
				html()->e( 'span', [ 'class' => 'count' ], '(' . _nf( $count ) . ')' )
			] );
		} );

		$more_views = [
			Event::COMPLETE  => __( 'Complete', 'groundhogg' ),
			Event::SKIPPED   => __( 'Skipped', 'groundhogg' ),
			Event::CANCELLED => __( 'Cancelled', 'groundhogg' ),
			Event::FAILED    => __( 'Failed', 'groundhogg' ),
		];

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'status', [ 'COUNT(ID)', 'total' ] )
		           ->setGroupby( 'status' );
		$results = $eventQuery->get_results();

		$more_views = array_map_with_keys( $more_views, function ( $text, $status ) use ( $view, $results ) {
			$count = get_array_var( find_object( $results, [ 'status' => $status ] ), 'total', 0 );

			if ( $count === 0 ) {
				return '';
			}

			return html()->e( 'a', [
				'class' => [ $status, $view == $status ? 'current' : '' ],
				'href'  => admin_page_url( 'gh_events', [
					'status' => $status
				] )
			], [
				$text . ' ',
				html()->e( 'span', [ 'class' => 'count' ], '(' . _nf( $count ) . ')' )
			] );
		} );

		return apply_filters( 'gh_event_views', array_filter( array_merge( $views, $more_views ) ) );
	}

	/**
	 * Generates and displays row actions.
	 *
	 * @param Event  $event       Event being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $event, $column_name, $primary ) {

		$actions = [];

		if ( $primary === $column_name ) {

			$actions = array();

			switch ( $this->get_view() ) {
				default:
				case 'waiting':
					$actions['execute_now'] = html()->e( 'a', [
						'href' => action_url( 'execute_now', [ 'event' => $event->get_id() ] ),
					], __( 'Run Now', 'groundhogg' ) );
					$actions['pause']       = html()->e( 'a', [
						'href' => action_url( 'pause', [ 'event' => $event->get_id() ] ),
					], __( 'Pause', 'groundhogg' ) );
					$actions['trash']       = html()->e( 'a', [
						'href' => action_url( 'cancel', [ 'event' => $event->get_id() ] ),
					], __( 'Cancel', 'groundhogg' ) );
					break;
				case 'paused':
					$actions['unpause'] = html()->e( 'a', [
						'href' => action_url( 'unpause', [ 'event' => $event->get_id() ] ),
					], __( 'Unpause', 'groundhogg' ) );
					$actions['trash']   = html()->e( 'a', [
						'href' => action_url( 'cancel', [ 'event' => $event->get_id() ] ),
					], __( 'Cancel', 'groundhogg' ) );
					break;
				case 'cancelled':
					$actions['uncancel'] = html()->e( 'a', [
						'href' => action_url( 'uncancel', [ 'event' => $event->get_id() ] ),
					], __( 'Uncancel', 'groundhogg' ) );
					break;
				case 'complete':
				case 'skipped':
				case 'failed':
					$actions['execute_again'] = html()->e( 'a', [
						'href' => action_url( 'execute_again', [
							'event'  => $event->get_id(),
							'status' => $this->get_view()
						] ),
					], __( 'Run Again', 'groundhogg' ) );
					break;
			}


			if ( $event->get_contact() && $event->get_contact()->exists() ) {
				$actions['view'] = html()->a( $event->get_contact()->admin_link(), __( 'Contact', 'groundhogg' ), [
					'aria-label' => _x( 'View Contact', 'action', 'groundhogg' ),
					'title'      => _x( 'View Contact', 'action', 'groundhogg' ),
				] );
			}
		} else if ( $column_name === 'funnel' ) {

			if ( $event->is_funnel_event() ) {

				$actions['edit'] = html()->a( admin_page_url( 'gh_funnels', [
					'action' => 'edit',
					'funnel' => $event->get_funnel_id()
				] ), _x( 'Edit Flow', 'action', 'groundhogg' ), [
					'aria-label' => _x( 'Edit Flow', 'action', 'groundhogg' ),
//					'title'      => _x( 'Edit Funnel', 'action', 'groundhogg' ),
				] );

			}

		} else if ( $column_name === 'step' ) {

			if ( $event->is_funnel_event() ) {
				$actions['edit'] = sprintf( "<a class='edit' href='%s' aria-label='%s'>%s</a>",
					admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%d#%d', $event->get_funnel_id(), $event->get_step_id() ) ),
					esc_attr( _x( 'Edit Step', 'action', 'groundhogg' ) ),
					_x( 'Edit Step', 'action', 'groundhogg' )
				);
			}
		}

		return $this->row_actions( apply_filters( 'groundhogg_event_row_actions', $actions, $event, $column_name ) );
	}

	protected function extra_tablenav( $which ) {

		$items = $this->get_pagination_arg( 'total_items' );

		// no items
		if ( $items === 0 ) {
			return;
		}

		?>
        <div class="alignleft gh-actions">
			<?php if ( $this->get_view() === Event::WAITING ): ?>
                <a class="gh-button primary small"
                   href="<?php echo Plugin::instance()->bulk_jobs->process_events->get_start_url(); ?>"><?php _ex( 'Process Events', 'action', 'groundhogg' ); ?></a>
                <a class="gh-button secondary small"
                   href="<?php echo wp_nonce_url( add_query_arg( [ 'action' => 'pause' ], $_SERVER['REQUEST_URI'] ), 'pause' ); ?>"><?php printf( _x( 'Pause %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
                <a class="gh-button danger small danger-confirm"
                   href="<?php echo wp_nonce_url( add_query_arg( [
					   'action' => 'cancel',
					   'status' => Event::WAITING
				   ], $_SERVER['REQUEST_URI'] ), 'cancel' ); ?>"><?php printf( _x( 'Cancel %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
			<?php endif; ?>
			<?php if ( $this->get_view() === Event::PAUSED ): ?>
                <a class="gh-button secondary small"
                   href="<?php echo wp_nonce_url( add_query_arg( [ 'action' => 'unpause' ], $_SERVER['REQUEST_URI'] ), 'unpause' ); ?>"><?php printf( _x( 'Unpause %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
                <a class="gh-button danger small danger-confirm"
                   href="<?php echo wp_nonce_url( add_query_arg( [
					   'action' => 'cancel',
					   'status' => Event::PAUSED
				   ], $_SERVER['REQUEST_URI'] ), 'cancel' ); ?>"><?php printf( _x( 'Cancel %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
			<?php endif; ?>
			<?php if ( $this->get_view() === Event::CANCELLED ): ?>
                <a class="gh-button secondary small"
                   href="<?php echo wp_nonce_url( add_query_arg( [ 'action' => 'uncancel' ], $_SERVER['REQUEST_URI'] ), 'uncancel' ); ?>"><?php printf( _x( 'Uncancel %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
			<?php endif; ?>
			<?php if ( in_array( $this->get_view(), [ 'failed', 'skipped', 'cancelled' ] ) ): ?>
                <a class="gh-button danger danger-permanent small"
                   href="<?php echo wp_nonce_url( add_query_arg( [
					   'action' => 'purge',
					   'status' => $this->get_view()
				   ], $_SERVER['REQUEST_URI'] ), 'purge' ); ?>"><?php printf( _x( 'Purge %s events', 'action', 'groundhogg' ), _nf( $items ) ); ?></a>
			<?php endif; ?>
        </div>
		<?php
	}

	/**
	 * Prepares the list of items for displaying.
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
		$order    = get_url_var( 'order', $this->get_view() === Event::WAITING ? 'ASC' : 'DESC' );
		$orderby  = get_url_var( 'orderby', 'time' );

		$view = $this->get_view();

		// Special handling of the unprocessed status
		if ( $view === 'unprocessed' ) {
			$where = [
				'relationship' => "AND",
				[ 'col' => 'status', 'val' => [ Event::WAITING, Event::IN_PROGRESS ], 'compare' => 'IN' ],
				[ 'col' => 'time', 'val' => time() - MINUTE_IN_SECONDS, 'compare' => '<' ],
			];
		} else {
			$where = [
				'relationship' => "AND",
				[ 'col' => 'status', 'val' => $this->get_view(), 'compare' => '=' ],
			];
		}

		$request_query = get_request_query( [], [], array_merge( array_keys( get_db( 'events' )->get_columns() ), [ 'include_filters' ] ) );

		unset( $request_query['status'] );

		$args = array_merge( [
			'where'      => $where,
			'limit'      => $per_page,
			'offset'     => $offset,
			'order'      => $order,
			'orderby'    => $orderby,
			'found_rows' => true
		], $request_query );

		$this->table = in_array( $this->get_view(), [
			Event::PAUSED,
			Event::WAITING,
			Event::IN_PROGRESS,
			'unprocessed',
		] ) ? 'event_queue' : 'events';

		$events = get_db( $this->table )->query( $args );
		$total  = get_db( $this->table )->found_rows();

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
