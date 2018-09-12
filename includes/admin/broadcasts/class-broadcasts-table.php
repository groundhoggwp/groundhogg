<?php
/**
 * Broadcasts Table Class
 *
 * This class shows the data table for accessing information about an broadcast.
 *
 * @package     groundhogg
 * @subpackage  Includes/Broadcasts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPGH_Broadcasts_Table extends WP_List_Table {

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
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
			'email_id'    => _x( 'Email', 'Column label', 'groundhogg' ),
			'from_user'   => _x( 'Scheduled By', 'Column label', 'groundhogg' ),
			'send_at'   => _x( 'Scheduled Run Date', 'Column label', 'groundhogg' ),
            'send_to_tags' => _x( 'Send To Tags', 'Column label', 'groundhogg' ),
            'stats' => _x( 'Stats', 'Column label', 'groundhogg' ),
            'date_created' => _x( 'Date Created', 'Column label', 'groundhogg' ),
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
			'email_id'    => array( 'email_id', false ),
			'from_user' => array( 'from_user', false ),
			'send_at' => array( 'sent_at', false ),
			'date_created' => array( 'date_created', false )
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
        $views =  array();

        $views['all'] = "<a class='" .  print_r( ( $this->get_view() === 'all' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&view=all' ) . "'>" . __( 'All' ) . " <span class='count'>(" . wpgh_count_broadcast_items() . ")</span>" . "</a>";

        $views['sent'] = "<a class='" .  print_r( ( $this->get_view() === 'sent' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&view=sent' ) . "'>" . __( 'Sent' ) . " <span class='count'>(" . wpgh_count_broadcast_items( 'broadcast_status', 'sent' ) . ")</span>" . "</a>";

        $views['scheduled'] = "<a class='" .  print_r( ( $this->get_view() === 'scheduled' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&view=scheduled' ) . "'>" . __( 'Scheduled' ) . " <span class='count'>(" . wpgh_count_broadcast_items( 'broadcast_status', 'scheduled' ) . ")</span>" . "</a>";

        $views['cancelled'] = "<a class='" .  print_r( ( $this->get_view() === 'cancelled' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_broadcasts&view=cancelled' ) . "'>" . __( 'Cancelled' ) . " <span class='count'>(" . wpgh_count_broadcast_items( 'broadcast_status', 'cancelled' ) . ")</span>" . "</a>";

        return apply_filters(  'wpgh_broadcast_views', $views );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    /**
     * Get default row actions...
     *
     * @param $id int an item ID
     * @return string a list of actions
     */
	protected function handle_row_actions( $item, $column_name, $primary )
    {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $id = intval( $item['ID'] );

        $broadcast = wpgh_get_broadcast_by_id( $id );

        if ( $this->get_view() !== 'cancelled' ) {
            $actions['edit'] = "<span class='edit'><a href='" . admin_url('admin.php?page=gh_emails&action=edit&email=' . $broadcast['email_id']) . "'>" . __('Edit Email') . "</a></span>";
            if ( intval( $item[ 'send_at' ] ) > time() ){
                $actions['trash'] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url(admin_url('admin.php?page=gh_broadcasts&view=all&action=cancel&broadcast=' . $id), 'cancel') . "'>" . __('Cancel') . "</a></span>";
            }
        }

        return $this->row_actions( apply_filters( 'wpgh_broadcast_row_actions', $actions, $item, $column_name ) );
    }

    protected function column_email_id( $item )
    {
        $email = wpgh_get_email_by_id( intval( $item['email_id'] ) );

        $subject = ( ! $email->subject )? '(' . __( 'no email' ) . ')' : $email->subject;
        $editUrl = admin_url( 'admin.php?page=gh_broadcasts&action=edit&broadcast=' . $item['ID'] );

        if ( $this->get_view() === 'cancelled' ){
            $html = "<strong>{$subject}</strong>";
        } else {
            $html = "<strong>";

            $html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

            if ( $item['broadcast_status'] === 'scheduled' ){
                $html .= " &#x2014; " . "<span class='post-state'>(" . __( 'Scheduled' ) . ")</span>";
            }
        }
        $html .= "</strong>";

        return $html;
    }

    protected function column_from_user( $item )
    {
        $user = get_userdata( intval( ( $item['from_user'] ) ) );
        $from_user = esc_html( $user->display_name );
        $queryUrl = admin_url( 'admin.php?page=gh_broadcasts&view=from_user&from_user=' . $item['from_user'] );
        return "<a href='$queryUrl'>$from_user</a>";
    }

    protected function column_stats( $item )
    {
        if ( $item[ 'broadcast_status' ] !== 'sent' )
            return '&#x2014;';

        $opens = wpgh_get_broadcast_opens( $item['ID'] );
        $clicks = wpgh_get_broadcast_clicks( $item['ID'] );

        $html = sprintf( "%s: <strong><a href='%s' target='_blank' >%d</a></strong><br/>",
            __( "Opens" ),
            admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $item['ID'], 'email_opened', 0, time() ) ),
            $opens
        );
        $html.= sprintf( "%s: <strong><a href='%s' target='_blank' >%d</a></strong><br/>",
            __( "Clicks" ),
            admin_url( sprintf( 'admin.php?page=gh_contacts&view=activity&funnel=%s&step=%s&activity_type=%s&start=%s&end=%s', WPGH_BROADCAST, $item['ID'], 'email_link_click', 0, time() ) ),
            $clicks );
        $html.= sprintf( "%s: <strong>%d%%</strong><br/>", __( "CTR" ), round( ( $clicks / ( ( $opens > 0 )? $opens : 1 ) * 100 ), 2 ) );

        return $html;
    }

    protected function column_send_at( $item )
    {
        /* convert to local time. */
        $p_time = intval( $item[ 'send_at' ] ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        $cur_time = (int) current_time( 'timestamp' );

        $time_diff = $p_time - $cur_time;

        if ( $time_diff < 0 ){
            $time_prefix = __( 'Sent' );
            /* The event has passed */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = date_i18n( 'jS F, Y \@ h:i A', intval( $p_time ) );
            } else {
                $time = sprintf( "%s ago", human_time_diff( $p_time, $cur_time ) );
            }
        } else {
            $time_prefix = __( 'Will send' );
            /* the event is scheduled */
            if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
                $time = sprintf( "on %s", date_i18n( 'jS F, Y \@ h:i A', intval( $p_time )  ) );
            } else {
                $time = sprintf( "in %s", human_time_diff( $p_time, $cur_time ) );
            }
        }

        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $p_time ) ) . '">' . $time . '</abbr>';
    }

    protected function column_date_created( $item )
    {
        $dc_time = mysql2date( 'U', $item['date_created'] );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $dc_time - $cur_time;
        $time_prefix = __( 'Created' );
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = date_i18n( 'Y/m/d \@ h:i A', intval( $dc_time ) );
        } else {
            $time = sprintf( "%s ago", human_time_diff( $dc_time, $cur_time ) );
        }
        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $dc_time ) ) . '">' . $time . '</abbr>';
    }

    protected function column_send_to_tags( $item )
    {
        $tags = $item[ 'send_to_tags' ] ? maybe_unserialize( $item[ 'send_to_tags' ] ) : array();
        foreach ( $tags as $i => $tag_id ){
            $tags[$i] = '<a href="'.admin_url('admin.php?page=gh_contacts&view=tag&tag='.$tag_id).'">' . wpgh_get_tag_name( $tag_id ). '</a>';
        }
        return implode( ', ', $tags );
    }

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $item        A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $item, $column_name ) {

	    do_action( 'wpgh_broadcasts_custom_columns', $item, $column_name );

	    return '';
	}

	/**
	 * Get value for checkbox column.
	 *
	 * @param object $item A singular item (one full row's worth of data).
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$item['ID']                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @return array An associative array containing all the bulk actions.
	 */
	protected function get_bulk_actions() {
        if ( $this->get_view() !== 'cancelled' )
        {
            $actions = array(
                'cancel' => _x( 'Cancel Broadcast', 'List table bulk action', 'groundhogg' ),
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
		global $wpdb; //This is used only if making any database queries
		/*
		 * First, lets decide how many records per page to show
		 */
		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$table_emails = $wpdb->prefix . WPGH_EMAILS;
		$table_broadcasts = $wpdb->prefix . WPGH_BROADCASTS;

//		$query = "SELECT * FROM $table_broadcasts WHERE ";
		$query = "SELECT b.*, e.subject FROM $table_broadcasts b LEFT JOIN $table_emails e ON b.email_id = e.ID WHERE ";

        if ( isset( $_REQUEST[ 's' ] ) ){

            $pattern = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST[ 's' ] ) ) . '%' ;
            $query .= $wpdb->prepare( "( e.subject LIKE %s OR e.content LIKE %s OR e.pre_header LIKE %s) AND ", $pattern , $pattern , $pattern );

        }

        if ( $this->get_view() === 'scheduled' ){

            $query .= $wpdb->prepare( '( b.broadcast_status = %s )', 'scheduled' );

        } else if ( $this->get_view() === 'cancelled' ) {

            $query .= $wpdb->prepare( '( b.broadcast_status = %s )', 'cancelled' );

        } else if ( $this->get_view() === 'sent' ) {

            $query .= $wpdb->prepare( '( b.broadcast_status = %s )', 'sent' );

        } else {

            $query .= $wpdb->prepare( '( b.broadcast_status = %s OR b.broadcast_status = %s )', 'sent', 'scheduled' );

        }

//        $query.= " ORDER BY ID DESC";
        $query.= " ORDER BY b.ID DESC";

        $data = $wpdb->get_results( $query, ARRAY_A );

		/*
		 * Sort the data
		 */
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
		// If no sort, default to title.
		$orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_created'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( 'desc' === $order ) ? $result : - $result;
	}
}