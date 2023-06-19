<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg\Email;
use Groundhogg\Plugin;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\get_request_query;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\scheduled_time_column;

/**
 * Emails Table Class
 *
 * This class shows the data table for accessing information about an email.
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
	 * @return array An associative array containing column information.
	 * @see WP_List_Table::::single_row_columns()
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />', // Render a checkbox instead of text.
			'title'        => _x( 'Title', 'Column label', 'groundhogg' ),
			'subject'      => _x( 'Subject', 'Column label', 'groundhogg' ),
			'from_user'    => _x( 'From User', 'Column label', 'groundhogg' ),
			'author'       => _x( 'Author', 'Column label', 'groundhogg' ),
			'last_updated' => _x( 'Last Updated', 'Column label', 'groundhogg' ),
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
			'subject'      => array( 'subject', false ),
			'from_user'    => array( 'from_user', false ),
			'author'       => array( 'author', false ),
			'last_updated' => array( 'last_updated', false ),
		);

		return apply_filters( 'wpgh_email_sortable_columns', $sortable_columns );
	}

	public function extra_tablenav( $which ) {
		if ( $this->get_view() !== 'trash' ) {
			return;
		}
		?>
		<div class="alignleft gh-actions">
			<a class="button"
			   href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=trash&action=empty_trash' ), 'empty_trash' ); ?>"><?php _e( 'Empty Trash' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Get the views for the emails, all, ready, unready, trash
	 *
	 * @return array
	 */
	protected function get_views() {
		$views = array();

		$count_ready = get_db( 'emails' )->count( array( 'status' => 'ready' ) );
		$count_draft = get_db( 'emails' )->count( array( 'status' => 'draft' ) );
		$count_trash = get_db( 'emails' )->count( array( 'status' => 'trash' ) );
		$count_all   = $count_ready + $count_draft;

		$views['all']   = "<a class='" . print_r( ( $this->get_view() === 'all' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_emails' ) . "'>" . __( 'All' ) . " <span class='count'>(" . _nf( $count_all ) . ")</span>" . "</a>";
		$views['ready'] = "<a class='" . print_r( ( $this->get_view() === 'ready' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&status=ready' ) . "'>" . __( 'Ready' ) . " <span class='count'>(" . _nf( $count_ready ) . ")</span>" . "</a>";
		$views['draft'] = "<a class='" . print_r( ( $this->get_view() === 'draft' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&status=draft' ) . "'>" . __( 'Draft' ) . " <span class='count'>(" . _nf( $count_draft ) . ")</span>" . "</a>";
		$views['trash'] = "<a class='" . print_r( ( $this->get_view() === 'trash' ) ? 'current' : '', true ) . "' href='" . admin_url( 'admin.php?page=gh_emails&status=trash' ) . "'>" . __( 'Trash' ) . " <span class='count'>(" . _nf( $count_trash ) . ")</span>" . "</a>";

		return apply_filters( 'groundhogg/admin/emails/table/views', $views );
	}

	protected function get_view() {
		return get_url_var( 'status', [ 'ready', 'draft' ] );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 *
	 * @since 3.1.0
	 *
	 */
	public function single_row( $item ) {

		$email = new Email( $item->ID );

		echo '<tr>';
		$this->single_row_columns( $email );
		echo '</tr>';
	}

	/**
	 * @param        $email Email
	 * @param string $column_name
	 * @param string $primary
	 *
	 * @return string
	 */
	protected function handle_row_actions( $email, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();
		$id      = $email->get_id();

		if ( $this->get_view() === 'trash' ) {
			$actions['restore'] = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=restore&email=' . $id ), 'restore' ) . "'>" . __( 'Restore' ) . "</a>";
			$actions['delete']  = "<a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=archived&action=delete&email=' . $id ), 'delete' ) . "'>" . __( 'Delete Permanently' ) . "</a>";
		} else {
			$actions['edit']   = "<a href='" . admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $id ) . "'>" . __( 'Edit' ) . "</a>";
			$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_emails&view=all&action=trash&email=' . $id ), 'trash' ) . "'>" . __( 'Trash' ) . "</a>";
		}

		return $this->row_actions( apply_filters( 'groundhogg/admin/emails/table/row_actions', $actions, $email, $column_name ) );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_title( $email ) {
		$subject = ( ! $email->get_title() ) ? '(' . __( 'no title' ) . ')' : $email->get_title();
		$editUrl = admin_url( 'admin.php?page=gh_emails&action=edit&email=' . $email->get_id() );

		if ( $this->get_view() === 'trash' ) {
			$html = "<strong>{$subject}</strong>";
		} else {
			$html = "<strong>";

			$html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

			if ( $email->get_status() === 'draft' ) {
				$html .= " &#x2014; " . "<span class='post-state'>(" . __( 'Draft' ) . ")</span>";
			}
			$html .= "</strong>";
		}

		return $html;
	}

	/**
	 * @param $email Email
	 *
	 * @return mixed
	 */
	protected function column_subject( $email ) {
		return $email->get_subject_line();
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_from_user( $email ) {

		if ( $email->get_from_user_id() == 0 && ! $email->get_meta( 'use_default_from' ) ) {
			return __( 'The contact\'s owner', 'groundhogg' );
		}

		if ( $email->get_from_user() ) {
			return html()->e( 'a', [
				'href' => admin_page_url( 'gh_emails', [
					'from_user' => $email->get_from_user_id()
				] )
			], esc_html( sprintf( '%s <%s>', $email->get_from_name(), $email->get_from_email() ) ) );
		}

		return esc_html( sprintf( '%s <%s>', get_default_from_name(), get_default_from_email() ) );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_author( $email ) {
		$user = get_userdata( intval( ( $email->get_author_id() ) ) );
		if ( ! $user ) {
			return __( 'Unknown', 'groundhogg' );
		}
		$from_user = esc_html( $user->display_name );
		$queryUrl  = admin_url( 'admin.php?page=gh_emails&view=author&author=' . $email->get_author_id() );

		return "<a href='$queryUrl'>$from_user</a>";
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_date_created( $email ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $email->get_date_created() ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * @param $email Email
	 *
	 * @return string
	 */
	protected function column_last_updated( $email ) {
		$ds_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $email->get_last_updated() ) );

		return scheduled_time_column( $ds_time, false, false, false );
	}

	/**
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param object $email       A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
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
	 *
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
				'delete'  => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
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

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = absint( get_url_var( 'limit', get_screen_option( 'per_page' ) ) );
		$paged    = $this->get_pagenum();
		$offset   = $per_page * ( $paged - 1 );
		$search   = trim( sanitize_text_field( get_url_var( 's' ) ) );
		$order    = get_url_var( 'order', 'DESC' );
		$orderby  = get_url_var( 'orderby', 'ID' );

		$args = array_filter( [
			'status'     => $this->get_view(),
			'from_user'  => absint( get_url_var( 'from_user' ) ),
			'author'     => absint( get_url_var( 'author' ) ),
			'search'     => $search,
			'limit'      => $per_page,
			'offset'     => $offset,
			'order'      => $order,
			'orderby'    => $orderby,
			'found_rows' => true,
		] );

		$emails = get_db( 'emails' )->query( $args );
		$total  = get_db( 'emails' )->found_rows();

		$this->items = $emails;

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
