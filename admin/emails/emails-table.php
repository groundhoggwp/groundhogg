<?php
namespace Groundhogg\Admin\Emails;

use Groundhogg\Email;
use Groundhogg\Plugin;
use WP_List_Table;

/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Emails_Table extends WP_List_Table {

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
	 * bulk steps or checkboxes, simply leave the 'cb' entry out of your array.
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

	public function extra_tablenav($which)
    {
        if ( $this->get_view() !== 'trash' )
            return;
        ?>
        <div class="alignleft gh-actions">
            <a class="button" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=trash&action=empty_trash' ), 'empty_trash' ); ?>"><?php _e( 'Empty Trash' ); ?></a>
        </div>
        <?php
    }

    /**
     * Get the views for the emails, all, ready, unready, trash
     *
     * @return array
     */
	protected function get_views()
    {
        $views =  array();

        $count_ready  = Plugin::$instance->dbs->get_db('emails')->count( array( 'status' => 'ready' ) );
        $count_draft  = Plugin::$instance->dbs->get_db('emails')->count( array( 'status' => 'draft' ) );
        $count_trash  = Plugin::$instance->dbs->get_db('emails')->count( array( 'status' => 'trash' ) );
        $count_all = $count_ready + $count_draft;


        $views['all'] = "<a class='" .  print_r( ( $this->get_view() === 'all' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=all' ) . "'>" . __( 'All' ) . " <span class='count'>(" . $count_all . ")</span>" . "</a>";

        $views['ready'] = "<a class='" .  print_r( ( $this->get_view() === 'ready' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=ready' ) . "'>" . __( 'Ready' ) . " <span class='count'>(" . $count_ready . ")</span>" . "</a>";

        $views['draft'] = "<a class='" .  print_r( ( $this->get_view() === 'draft' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=draft' ) . "'>" . __( 'Draft' ) . " <span class='count'>(" . $count_draft . ")</span>" . "</a>";

        $views['trash'] = "<a class='" .  print_r( ( $this->get_view() === 'trash' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&view=trash' ) . "'>" . __( 'Trash' ) . " <span class='count'>(" . $count_trash . ")</span>" . "</a>";

        return apply_filters(  'wpgh_email_views', $views );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    public function single_row( $item ) {

        $email = new Email( $item->ID );

        echo '<tr>';
        $this->single_row_columns( $email );
        echo '</tr>';
    }

    /**
     * @param  $email Email
     * @param string $column_name
     * @param string $primary
     * @return string
     */
	protected function handle_row_actions( $email, $column_name, $primary )
    {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $id = $email->get_id();

        if ( $this->get_view() === 'trash' )
        {
            $actions[ 'restore' ] = "<span class='restore'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=restore&email='. $id ), 'restore'  ). "'>" . __( 'Restore' ) . "</a></span>";
            $actions[ 'delete' ] = "<span class='delete'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=archived&action=delete&email='. $id ), 'delete'  ). "'>" . __( 'Delete Permanently' ) . "</a></span>";
        } else {
            $actions[ 'edit' ] = "<span class='edit'><a href='" . admin_url( 'admin.php?page=gh_emails&action=edit&email='. $id ). "'>" . __( 'Edit' ) . "</a></span>";
            $actions[ 'trash' ] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=trash&email='. $id ), 'trash' ). "'>" . __( 'Trash' ) . "</a></span>";
        }

        return $this->row_actions( apply_filters( 'wpgh_email_row_actions', $actions, $email, $column_name ) );
    }

    /**
     * @param $email Email
     * @return string
     */
    protected function column_subject( $email )
    {
        $subject = ( ! $email->get_subject_line() )? '(' . __( 'no subject' ) . ')' : $email->get_subject_line() ;
        $editUrl = admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $email->get_id() );

        if ( $this->get_view() === 'trash' ){
            $html = "<strong>{$subject}</strong>";
        } else {
            $html = "<strong>";

            $html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

            if ( $email->get_status() === 'draft' ){
                $html .= " &#x2014; " . "<span class='post-state'>(" . __( 'Draft' ) . ")</span>";
            }
	        $html .= "</strong>";
        }

        return $html;
    }

    /**
     * @param $email Email
     * @return string
     */
    protected function column_from_user( $email )
    {
        $from = intval( ( $email->get_from_user() ) );

        if ( $from ){
            $user = get_userdata( $from );
            $from_user = esc_html( $user->display_name . ' <' . $user->user_email . '>' );
            $queryUrl = admin_url( 'admin.php?page=gh_emails&view=from_user&from_user=' . $from );
            return "<a href='$queryUrl'>$from_user</a>";
        } else {
            return sprintf("<a href='%s'>%s</a>",
                admin_url( 'admin.php?page=gh_emails&view=from_user&from_user=0' ),
                __( 'The Contact\'s Owner', 'groundhogg' )
            );
        }
    }

    /**
     * @param $email Email
     * @return string
     */
    protected function column_author( $email )
    {
        $user = get_userdata( intval( ( $email->get_author_id() ) ) );
        $from_user = esc_html( $user->display_name );
        $queryUrl = admin_url( 'admin.php?page=gh_emails&view=author&author=' . $email->get_author_id() );
        return "<a href='$queryUrl'>$from_user</a>";
    }

    /**
     * @param $email Email
     * @return string
     */
    protected function column_date_created( $email )
    {
        $dc_time = mysql2date( 'U', $email->get_date_created() );
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

    /**
     * @param $email Email
     * @return string
     */
    protected function column_last_updated( $email )
    {
        $lu_time = mysql2date( 'U', $email->get_last_updated() );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $lu_time - $cur_time;
        $time_prefix = __( 'Updated', 'groundhogg' );

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
	 * @param object $email        A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $email, $column_name ) {

	    do_action( 'wpgh_email_custom_column', $email, $column_name );

	    return '';
	}
	/**
	 * Get value for checkbox column.
	 *
	 * @param object $email A singular item (one full row's worth of data).
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $email ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $email->ID                // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk steps available on this table.
	 *
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {

        if ( $this->get_view() === 'trash' ) {

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

        /*
		 * First, lets decide how many records per page to show
		 */
		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

//        $query_args = array();
//
//        if ( isset( $_REQUEST[ 's' ] ) ){
//
//            $query_args[ 'search' ] = $_REQUEST[ 's' ];
//
//        }
//
//        switch ( $this->get_view() )
//        {
//            case 'trash':
//                $query_args[ 'status' ] = 'trash';
//                $data = WPGH()->emails->get_emails( $query_args );
//                break;
//
//            case 'ready':
//                $query_args[ 'status' ] = 'ready';
//                $data = WPGH()->emails->get_emails( $query_args );
//                break;
//
//            case 'draft':
//                $query_args[ 'status' ] = 'draft';
//                $data = WPGH()->emails->get_emails( $query_args );
//                break;
//
//            case 'from_user':
//                $query_args[ 'from_user' ] = intval( $_REQUEST[ 'from_user' ] );
//                $data = WPGH()->emails->get_emails( $query_args );
//                break;
//
//            default:
//
//                $query_args[ 'status' ] = 'ready';
//                $data = WPGH()->emails->get_emails( $query_args );
//
//                $query_args[ 'status' ] = 'draft';
//                $data2 = WPGH()->emails->get_emails( $query_args );
//
//                $data = array_merge( $data, $data2 );
//
//                break;
//        }

        $data = Plugin::$instance->dbs->get_db('broadcasts')->query( $_GET ); //todo check

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
        $a = (array) $a;
        $b = (array) $b;
		// If no sort, default to title.
		$orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'ID'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( 'desc' === $order ) ? $result : - $result;
	}
}