<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Tag;
use WP_List_Table;
use function Groundhogg\_nf;
use function Groundhogg\get_db;
use function Groundhogg\get_screen_option;
use function Groundhogg\get_url_var;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tags Table
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Tags_Table extends WP_List_Table {
	/**
	 * TT_Example_List_Table constructor.
	 *
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'tag',     // Singular name of the listed records.
			'plural'   => 'tags',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}

	/**
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information.
	 */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />', // Render a checkbox instead of text.
			'tag_name'        => _x( 'Name', 'Column label', 'groundhogg' ),
			'tag_description' => _x( 'Description', 'Column label', 'groundhogg' ),
			'contact_count'   => _x( 'Count', 'Column label', 'groundhogg' ),
		);

		return apply_filters( 'groundhogg/admin/tags/table/get_columns', $columns );
	}

	/**
	 * @return array An associative array containing all the columns that should be sortable.
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'tag_name'        => array( 'tag_name', false ),
			'tag_description' => array( 'tag_description', false ),
			'contact_count'   => array( 'contact_count', false ),
		);

		return apply_filters( 'groundhogg/admin/tags/table/sortable_columns', $sortable_columns );
	}

	protected function extra_tablenav( $which ) {
		?>
        <div class="alignleft gh-actions">
            <a class="button action" href="<?php echo wp_nonce_url( add_query_arg( [
				'page'   => 'gh_tags',
				'action' => 'recount'
			], admin_url( 'admin.php' ) ) ) ?>"><?php _ex( 'Recount Contacts', 'action', 'groundhogg' ); ?></a>
        </div>
		<?php
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.1.0
	 *
	 * @param object $item The current item
	 *
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( new Tag( absint( $item->tag_id ) ) );
		echo '</tr>';
	}

	/**
	 * @param $tag Tag
	 *
	 * @return string
	 */
	protected function column_tag_name( $tag ) {
		$editUrl = admin_url( 'admin.php?page=gh_tags&action=edit&tag=' . $tag->get_id() );
		$html    = "<a class='row-title' href='$editUrl'>" . esc_html( $tag->get_name() ) . "</a>";

		return $html;
	}

	/**
	 * @param $tag Tag
	 *
	 * @return string
	 */
	protected function column_contact_count( $tag ) {
		$count = $tag->get_contact_count();

		return $count ? '<a href="' . admin_url( 'admin.php?page=gh_contacts&tags_include=' . $tag->get_id() ) . '">' . _nf( $count ) . '</a>' : '0';
	}

	/**
	 * @param $tag Tag
	 *
	 * @return string
	 */
	protected function column_tag_description( $tag ) {
		return ! empty( $tag->get_description() ) ? $tag->get_description() : '&#x2014;';
	}

	/**
	 * Get default column value.
	 *
	 * @param object $tag         A singular item (one full row's worth of data).
	 * @param string $column_name The name/slug of the column to be processed.
	 *
	 * @return string Text or HTML to be placed inside the column <td>.
	 */
	protected function column_default( $tag, $column_name ) {

		return do_action( "groundhogg/admin/tags/table/{$column_name}", $tag );

	}


	/**
	 * @param  $tag Tag A singular item (one full row's worth of data).
	 *
	 * @return string Text to be placed inside the column <td>.
	 */
	protected function column_cb( $tag ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$tag->get_id()               // The value of the checkbox should be the record's ID.
		);
	}

	/**
	 * @return array An associative array containing all the bulk steps.
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
		);

		return apply_filters( 'wpgh_contact_tag_bulk_actions', $actions );
	}

	/**
	 * Prepares the list of items for displaying.
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
		$order    = strtoupper( get_url_var( 'order', 'DESC' ) );
		$orderby  = get_url_var( 'orderby', 'tag_id' );

		$args = [
			'search'     => $search,
			'limit'      => $per_page,
			'offset'     => $offset,
			'order'      => $order,
			'orderby'    => $orderby,
			'found_rows' => true,
		];

		$items = get_db( 'tags' )->query( $args );
		$total = get_db( 'tags' )->found_rows();

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

	/**
	 * Generates and displays row actions.
	 *
	 * @param        $tag         Tag      Contact being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row steps output for posts.
	 */
	protected function handle_row_actions( $tag, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array();
		$title   = $tag->get_name();

		$actions['id'] = 'ID: ' . $tag->get_id();

		$actions['edit'] = sprintf(
			'<a href="%s" class="editinline" aria-label="%s">%s</a>',
			/* translators: %s: title */
			admin_url( 'admin.php?page=gh_tags&action=edit&tag=' . $tag->get_id() ),
			esc_attr( sprintf( __( 'Edit' ), $title ) ),
			__( 'Edit' )
		);

		$actions['delete'] = sprintf(
			'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
			wp_nonce_url( admin_url( 'admin.php?page=gh_tags&tag=' . $tag->get_id() . '&action=delete' ) ),
			/* translators: %s: title */
			esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
			__( 'Delete' )
		);

		return $this->row_actions( $actions );
	}
}
