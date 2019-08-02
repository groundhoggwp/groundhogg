<?php
namespace Groundhogg\Admin\Broadcasts;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use function Groundhogg\get_request_query;
use function Groundhogg\groundhogg_url;
use Groundhogg\Plugin;
use function Groundhogg\scheduled_time;
use \WP_List_Table;

/**
 * The table for Broadcasts
 *
 * This just displays all the broadcast information in a WP_List_Table
 * Columns display basic information about the broadcast including send time
 * and basic reporting.
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WP_List_Table
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Broadcasts_Table extends WP_List_Table {

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
			'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
			'object_id'    => _x( 'Email/SMS', 'Column label', 'groundhogg' ),
			'from_user'   => _x( 'Scheduled By', 'Column label', 'groundhogg' ),
			'send_time'   => _x( 'Scheduled Run Date', 'Column label', 'groundhogg' ),
            'sending_to' => _x( 'Sending To', 'Column label', 'groundhogg' ),
            'stats' => _x( 'Stats', 'Column label', 'groundhogg' ),
            'date_scheduled' => _x( 'Date Scheduled', 'Column label', 'groundhogg' ),
		);
		return apply_filters( 'wpgh_broadcast_columns', $columns );
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
			'object_id'    => array( 'object_id', false ),
			'from_user' => array( 'from_user', false ),
			'send_time' => array( 'send_at', false ),
			'date_scheduled' => array( 'date_scheduled', false )
		);
		return apply_filters( 'wpgh_broadcast_sortable_columns', $sortable_columns );
	}

    /**
     * Get the views for the broadcasts, all, ready, unready, trash
     *
     * @return array
     */
	protected function get_views()
    {
        //todo
        $count = array(
            'scheduled' => Plugin::$instance->dbs->get_db( 'broadcasts' )->count( [ 'status' => 'scheduled' ] ),
            'sent'      => Plugin::$instance->dbs->get_db( 'broadcasts' )->count( [ 'status' => 'sent'      ] ),
            'cancelled' => Plugin::$instance->dbs->get_db( 'broadcasts' )->count( [ 'status' => 'cancelled' ] ),
        );

        $views['scheduled'] = "<a class='" .  print_r( ( $this->get_view() === 'scheduled' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&status=scheduled' ) . "'>" . _x( 'Scheduled', 'view', 'groundhogg' ) . " <span class='count'>(" . $count[ 'scheduled' ] . ")</span>" . "</a>";
        $views['sent'] = "<a class='" .  print_r( ( $this->get_view() === 'sent' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&status=sent' ) . "'>" . _x( 'Sent', 'view', 'groundhogg' ) . " <span class='count'>(" . $count[ 'sent' ] . ")</span>" . "</a>";
        $views['cancelled'] = "<a class='" .  print_r( ( $this->get_view() === 'cancelled' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&status=cancelled' ) . "'>" . _x( 'Cancelled', 'view', 'groundhogg' ) . " <span class='count'>(" . $count[ 'cancelled' ] . ")</span>" . "</a>";

        return apply_filters(  'groundhogg/admin/broadcasts/table/get_views', $views );
    }

    protected function get_view()
    {
        return ( isset( $_GET['status'] ) )? $_GET['status'] : 'scheduled';
    }

    /**
     * @param object $item convert $item to broadcast object
     */
    public function single_row($item)
    {
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
     * @return string a list of steps
     */
	protected function handle_row_actions( $broadcast, $column_name, $primary )
    {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();

        if ( $this->get_view() !== 'cancelled' ) {

        	if ( $broadcast->is_email() ){
		        $actions['edit'] = "<span class='edit'><a href='" . admin_url('admin.php?page=gh_emails&action=edit&email=' . $broadcast->get_object_id() ) . "'>" . _x( 'Edit Email', 'action', 'groundhogg') . "</a></span>";
	        } else {
		        $actions['edit'] = "<span class='edit'><a href='" . admin_url('admin.php?page=gh_sms&action=edit&sms=' . $broadcast->get_object_id() ) . "'>" . _x( 'Edit SMS', 'action', 'groundhogg') . "</a></span>";
	        }

            if ( $broadcast->get_send_time() > time() ){
                $actions['trash'] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url(admin_url('admin.php?page=gh_broadcasts&view=all&action=cancel&broadcast=' . $broadcast->get_id() ), 'cancel') . "'>" . _x( 'Cancel', 'action', 'groundhogg') . "</a></span>";
            }
        }

        return $this->row_actions( apply_filters( 'groundhogg/admin/broadcasts/table/handle_row_actions', $actions, $broadcast, $column_name ) );
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_object_id( $broadcast )
    {
        $subject = $broadcast->get_title();

        if ( $broadcast->is_email() ){
            $editUrl = admin_url( 'admin.php?page=gh_broadcasts&action=report&broadcast=' . $broadcast->get_id() );
        } else {
            $editUrl = '#';
        }

        switch ( $broadcast->get_status() )
        {
            case 'scheduled':

                $html = sprintf("<strong>%s</strong> &#x2014; <span class='post-state'>(%s)</span>", $broadcast->get_title(), __( 'Scheduled' ) );

                break;

            case 'cancelled':

                $html = sprintf("<strong>%s</strong> &#x2014; <span class='post-state'>(%s)</span>", $broadcast->get_title(), __( 'Cancelled' ) );

                break;

            case 'sent':

                $edit_url = groundhogg_url( 'broadcasts', [ 'action' => 'report', 'broadcast' => $broadcast->get_id() ] );

                $html = sprintf( "<strong><a class='row-title' href='%s'>%s</a></strong>", $edit_url, $broadcast->get_title() );

                break;
        }

        return $html;
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_from_user( $broadcast )
    {
        $user = get_userdata( $broadcast->get_scheduled_by_id() );
        $from_user = esc_html( $user->display_name );
        $queryUrl = admin_url( 'admin.php?page=gh_broadcasts&scheduled_by=' . $broadcast->get_scheduled_by_id() );
        return "<a href='$queryUrl'>$from_user</a>";
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_sending_to( $broadcast )
    {

        $num =  Plugin::$instance->dbs->get_db('events')->count( [
            'step_id'       => $broadcast->get_id(),
            'status'        => 'waiting',
            'event_type'    => Event::BROADCAST
        ] );

        if ( ! $num ){
            return '&#x2014;';
        }

        $link = sprintf( "<a href='%s'>%s %s</a>",
            admin_url( sprintf( 'admin.php?page=gh_contacts&%s', http_build_query( [
                'report' => [
                    'type' => Event::BROADCAST,
                    'step' => $broadcast->get_id(),
                ]
            ] ) ) ),
            $num,
            __( 'Contacts', 'groundhogg' )
        );

        return $link;
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_stats( $broadcast )
    {

	    if ( $broadcast->get_status() !== 'sent' )
		    return '&#x2014;';

	    $stats = $broadcast->get_report_data();

        $html = sprintf(
            "%s: <strong><a href='%s'>%d</a></strong><br/>",
            _x( "Sent", 'stats', 'groundhogg' ),
            add_query_arg(
                [ 'report' => [ 'type' => Event::BROADCAST, 'step' => $broadcast->get_id(), 'status' => Event::COMPLETE ] ],
                admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
            ),
            $stats[ 'sent' ]
        );

    	if ( ! $broadcast->is_sms() ){

            $html.= sprintf(
                "%s: <strong><a href='%s'>%d</a></strong><br/>",
                _x( "Opened", 'stats', 'groundhogg' ),
                add_query_arg(
                    [ 'activity' => [ 'activity_type' => Activity::EMAIL_OPENED, 'step' => $broadcast->get_id(), 'funnel' => $broadcast->get_funnel_id() ] ],
                    admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
                ),
                $stats[ 'opened' ]
            );

            $html.= sprintf(
                "%s: <strong><a href='%s'>%d</a></strong><br/>",
                _x( "Clicked", 'stats', 'groundhogg' ),
                add_query_arg(
                    [ 'activity' => [ 'activity_type' => Activity::EMAIL_CLICKED, 'step' => $broadcast->get_id(), 'funnel' => $broadcast->get_funnel_id() ] ],
                    admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
                ),
                $stats[ 'clicked' ]
            );
    	}

        return $html;
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_send_time( $broadcast )
    {
        $time = scheduled_time( $broadcast->get_send_time() );
        $time_prefix = $broadcast->get_send_time() > time() ? __( 'Sending', 'groundhogg' ) : __( 'Sent', 'groundhogg' );

        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $broadcast->get_send_time()  ) ) . '">' . $time . '</abbr>';
    }

    /**
     * @param $broadcast Broadcast
     * @return string
     */
    protected function column_date_scheduled( $broadcast )
    {
        $ds_time = strtotime( $broadcast->get_date_scheduled() );
        $time = scheduled_time( $ds_time );

        return __( 'Scheduled', 'groundhogg' ) . '<br><abbr title="' . date_i18n( DATE_ISO8601, $ds_time ) . '">' . $time . '</abbr>';
    }

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $broadcast        A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $broadcast, $column_name )
    {
	    do_action( 'groundhogg/admin/broadcasts/table/columns', $broadcast, $column_name );
	    return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $broadcast A singular item (one full row's worth of data).
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
        if ( $this->get_view() !== 'cancelled' )
        {
            $actions = array(
                'cancel' => _x( 'Cancel', 'List table bulk action', 'groundhogg' ),
            );
        } else {
            $actions = array();
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
		/*
		 * First, lets decide how many records per page to show
		 */
		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

        $query = get_request_query();

        if ( empty( $query ) ){
            $query = [
                'status' => 'scheduled'
            ];
        }

        $data = Plugin::$instance->dbs->get_db('broadcasts')->query( $query );

        usort( $data, array( $this, 'usort_reorder' ) );

		$current_page = $this->get_pagenum();

		$total_items = count( $data );

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->items = $data;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
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
		$orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_scheduled'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( 'desc' === $order ) ? $result : - $result;
	}
}