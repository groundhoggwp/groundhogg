<?php
/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
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

class WPGH_Emails_Table extends WP_List_Table {

	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'email',     // Singular name of the listed records.
			'plural'   => 'emails',    // Plural name of the listed records.
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
			'subject'    => _x( 'Subject', 'Column label', 'groundhogg' ),
			'from_user'   => _x( 'From User', 'Column label', 'groundhogg' ),
			'author'   => _x( 'Author', 'Column label', 'groundhogg' ),
            'last_updated' => _x( 'Last Updated', 'Column label', 'groundhogg' ),
            //'date_created' => _x( 'Date Created', 'Column label', 'groundhogg' ),
		);
		return apply_filters( 'wpgh_email_columns', $columns );
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
			'subject'    => array( 'subject', false ),
			'from_user' => array( 'from_user', false ),
			'author' => array( 'author', false ),
			'last_updated' => array( 'last_updated', false ),
			//'date_created' => array( 'date_created', false )
		);
		return apply_filters( 'wpgh_email_sortable_columns', $sortable_columns );
	}

    /**
     * Get the views for the emails, all, ready, unready, trash
     *
     * @return array
     */
	protected function get_views()
    {
        $views =  array();

        $views['all'] = "<a class='" .  print_r( ( $this->get_view() === 'all' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=all' ) . "'>" . __( 'All' ) . " <span class='count'>(" . wpgh_count_email_items() . ")</span>" . "</a>";

        $views['ready'] = "<a class='" .  print_r( ( $this->get_view() === 'ready' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=ready' ) . "'>" . __( 'Ready' ) . " <span class='count'>(" . wpgh_count_email_items( 'email_status', 'ready' ) . ")</span>" . "</a>";

        $views['draft'] = "<a class='" .  print_r( ( $this->get_view() === 'draft' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=draft' ) . "'>" . __( 'Draft' ) . " <span class='count'>(" . wpgh_count_email_items( 'email_status', 'draft' ) . ")</span>" . "</a>";

        $views['trash'] = "<a class='" .  print_r( ( $this->get_view() === 'trash' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=trash' ) . "'>" . __( 'Trash' ) . " <span class='count'>(" . wpgh_count_email_items( 'email_status', 'trash' ) . ")</span>" . "</a>";

        return apply_filters(  'wpgh_email_views', $views );
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
        $id = $item['ID'];

        if ( $this->get_view() === 'archived' )
        {
            $actions[ 'restore' ] = "<span class='restore'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=restore&email='. $id ), 'restore'  ). "'>" . __( 'Restore' ) . "</a></span>";
            $actions[ 'delete' ] = "<span class='delete'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=archived&action=delete&email='. $id ), 'delete'  ). "'>" . __( 'Delete Permanently' ) . "</a></span>";
        } else {
            $actions[ 'edit' ] = "<span class='edit'><a href='" . admin_url( 'admin.php?page=gh_emails&action=edit&email='. $id ). "'>" . __( 'Edit' ) . "</a></span>";
            $actions[ 'trash' ] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=trash&email='. $id ), 'trash' ). "'>" . __( 'Trash' ) . "</a></span>";
        }

        return $this->row_actions( apply_filters( 'wpgh_email_row_actions', $actions, $item, $column_name ) );
    }

    protected function column_subject( $item )
    {
        $subject = ( ! $item[ 'subject' ] )? '(' . __( 'no subject' ) . ')' : $item[ 'subject' ] ;
        $editUrl = admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $item['ID'] );

        if ( $this->get_view() === 'trash' ){
            $html = "<strong>{$subject}</strong>";
        } else {
            $html = "<strong>";

            $html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

            if ( $item['email_status'] === 'draft' ){
                $html .= " — " . "<span class='post-state'>(" . __( 'Draft' ) . ")</span>";
            }
        }
        $html .= "</strong>";

        return $html;
    }

    protected function column_from_user( $item )
    {
        $user = get_userdata( intval( ( $item['from_user'] ) ) );
        $from_user = esc_html( $user->display_name . ' <' . $user->user_email . '>' );
        $queryUrl = admin_url( 'admin.php?page=gh_emails&view=from_user&from_user=' . $item['from_user'] );
        return "<a href='$queryUrl'>$from_user</a>";
    }

    protected function column_author( $item )
    {
        $user = get_userdata( intval( ( $item['author'] ) ) );
        $from_user = esc_html( $user->user_login );
        $queryUrl = admin_url( 'admin.php?page=gh_emails&view=author&author=' . $item['author'] );
        return "<a href='$queryUrl'>$from_user</a>";
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

    protected function column_last_updated( $item )
    {
        $lu_time = mysql2date( 'U', $item['last_updated'] );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $lu_time - $cur_time;
        $time_prefix = __( 'Updated' );
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = date_i18n( 'Y/m/d \@ h:i A', intval( $lu_time ) );
        } else {
            $time = sprintf( "%s ago", human_time_diff( $lu_time, $cur_time ) );
        }
        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $lu_time ) ) . '">' . $time . '</abbr>';
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

	    do_action( 'wpgh_email_custom_column', $item, $column_name );

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
        if ( $this->get_view() === 'trash' )
        {
            $actions = array(
                'delete' => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
                'restore' => _x( 'Restore', 'List table bulk action', 'groundhogg' )
            );

        } else {
            $actions = array(
                'trash' => _x( 'Trash', 'List table bulk action', 'groundhogg' )
            );
        }

        return apply_filters( 'wpgh_email_bulk_actions', $actions );
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

		$table_name = $wpdb->prefix . WPGH_EMAILS;

		$query = "SELECT * FROM $table_name WHERE ";

        if ( isset( $_REQUEST[ 's' ] ) ){

            $pattern = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST[ 's' ] ) ) . '%' ;
            $query .= $wpdb->prepare( "(subject LIKE %s OR content LIKE %s OR pre_header LIKE %s) AND ", $pattern , $pattern , $pattern );

        }

        if ( $this->get_view() === 'trash' ){

            $query .= $wpdb->prepare( '( email_status = %s )', 'trash' );

        } else if ( $this->get_view() === 'ready' ) {

            $query .= $wpdb->prepare( '( email_status = %s )', 'ready' );

        } else if ( $this->get_view() === 'draft' ) {

            $query .= $wpdb->prepare( '( email_status = %s )', 'draft' );

        } else {

            $query .= $wpdb->prepare( '( email_status = %s OR email_status = %s OR email_status = %s )', 'ready', 'draft', '' );

        }

        $data = $wpdb->get_results( $query, ARRAY_A );

		/*
		 * Sort the data
		 */
		usort( $data, array( $this, 'usort_reorder' ) );


		/*
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();
		/*
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $data );
		/*
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to do that.
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		/*
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
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