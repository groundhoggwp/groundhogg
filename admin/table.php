<?php

namespace Groundhogg\Admin;

use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\DB\DB;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
use function Groundhogg\check_lock;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\swap_array_keys;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

abstract class Table extends \WP_List_Table {

	/**
	 * @return string
	 */
	abstract function get_table_id();

	/**
	 * @return DB
	 */
	abstract function get_db();

	/**
	 * Override to modify the query in any way
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	protected function parse_query( $query ) {
		return $query;
	}

	protected function get_page_url() {
		return add_query_arg( array_filter( [
			'page' => get_request_var( 'page' ),
			'tab'  => get_request_var( 'tab' )
		] ), admin_url( 'admin.php' ) );
	}

	/**
	 * Generate a view link.
	 *
	 * @param $view
	 * @param $query
	 * @param $display
	 *
	 * @return string
	 */
	protected function create_view( $view, $query, $display ) {

		$count = $this->get_db()->count( $query );

        $params = array_merge( $query, [
            $this->view_param() => $view
        ] );

		return html()->e( 'a',
			[
				'class' => $this->view_is( $view ) ? 'current' : '',
				'href'  => add_query_arg( $params, $this->get_page_url() ),
			],
			sprintf( '%s <span class="count">(%s)</span>', $display, _nf( $count ) )
		);
	}

	/**
	 * Output a checkbox column.
	 *
	 * @param $item Base_Object A singular item (one full row's worth of data).
	 *
	 * @return void
	 */
	protected function column_cb( $identity ) {

        printf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$identity->get_id()
		);

		if ( method_exists( $identity, 'get_title' ) ):
			?>
            <label for="cb-select-<?php echo $identity->ID; ?>">
				<span class="screen-reader-text">
				<?php
				/* translators: %s: Post title. */
				printf( __( 'Select %s' ), $identity->get_title() );
				?>
				</span>
            </label>
            <div class="locked-indicator">
                <span class="locked-indicator-icon" aria-hidden="true"></span>
                <span class="screen-reader-text">
				<?php
				printf(
				/* translators: Hidden accessibility text. %s: Post title. */
					__( '&#8220;%s&#8221; is locked' ),
					$identity->get_title()
				);
				?>
				</span>
            </div>
		<?php
		endif;
	}

	/**
	 * [
	 *   [
	 *     'class' => '',
	 *     'url' => '',
	 *     'display' => [],
	 *   ]
	 * ]
	 *
	 * @return array
	 */
	abstract protected function get_row_actions( $item, $column_name, $primary );

	/**
	 * @param mixed  $item
	 * @param string $column_name
	 * @param string $primary
	 *
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}


		$row_actions = [];

		$actions = $this->get_row_actions( $item, $column_name, $primary );

		foreach ( $actions as $action ) {

			if ( is_string( $action ) ) {
				$row_actions[] = $action;
				continue;
			}

			$action = wp_parse_args( $action, [
				'display' => '',
				'class'   => '',
				'url'     => '#'
			] );

			$row_actions[] = $this->create_row_action( $action['class'], $action['url'], $action['display'] );

		}

		return $this->row_actions( $row_actions );

	}

	/**
	 * Create a row action.
	 *
	 * @param $class
	 * @param $url
	 * @param $display
	 *
	 * @return string
	 */
	protected function create_row_action( $class, $url, $display ) {

		if ( empty( $url ) ) {
			return html()->e( 'span', [ 'class' => $class ], $display );
		}

		return html()->wrap( html()->e( 'a', [ 'href' => $url ], $display ), 'span', [ 'class' => $class ] );
	}

	/**
	 * [
	 *   [
	 *     'display' => '',
	 *     'view' => '',
	 *     'query' => [],
	 *   ]
	 * ]
	 *
	 * @return array
	 */
	abstract protected function get_views_setup();

	/**
	 * Gets the query from the current view
	 *
	 * @return false|mixed
	 */
	protected function get_current_query() {

		$setup = $this->get_views_setup();

		$view = array_find( $setup, function ( $view ) {
			return $view['view'] === $this->get_view();
		} );

		if ( ! $view ) {
			return [];
		}

		$view = swap_array_keys( $view, [ 'count' => 'query' ] );

		return $view['query'];
	}

	/**
	 * Parse the views and return them
	 *
	 * @return array
	 */
	protected function get_views() {
		$setup = $this->get_views_setup();

		$views = [];

		foreach ( $setup as $view ) {

			$view = wp_parse_args( $view, [
				'display' => '',
				'view'    => '',
				'query'   => [],
			] );

			$view = swap_array_keys( $view, [ 'count' => 'query' ] );

			$views[] = $this->create_view( $view['view'], $view['query'], $view['display'] );
		}

		return apply_filters( "groundhogg/admin/table/{$this->get_table_id()}/get_views", $views );
	}

	/**
	 * @return array
	 */
	abstract function get_default_query();

	/**
	 * Prepare all the items
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page       = absint( get_url_var( 'limit', 30 ) );
		$paged          = $this->get_pagenum();
		$offset         = $per_page * ( $paged - 1 );
		$search         = trim( sanitize_text_field( get_url_var( 's' ) ) );
		$order          = get_url_var( 'order', 'DESC' );
		$orderby        = get_url_var( 'orderby', $this->get_db()->get_primary_key() );
		$search_columns = get_url_var( 'search_columns', '' );

		$query = array_merge( $this->get_default_query(), get_request_query(), $this->get_current_query(), [
			'limit'          => $per_page,
			'offset'         => $offset,
			'order'          => $order,
			'search'         => $search,
			'search_columns' => $search_columns,
			'orderby'        => $orderby,
			'found_rows'     => true,
		] );

		try {
			$items = $this->get_db()->query( $query );
			$total = $this->get_db()->found_rows();
		} catch ( \Exception $e ) {
			$items = [];
			$total = 0;
		}

		foreach ( $items as $i => $item ) {
			$items[ $i ] = $this->parse_item( $item );
		}

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
	 * Parse the item before it gets treated as data.
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	protected function parse_item( $item ) {
		return $item;
	}

	/**
	 * @return string
	 */
	protected function get_view() {
		return get_request_var( $this->view_param(), $this->get_default_view() );
	}

	/**
	 * Compare the current view
	 *
	 * @param $view
	 *
	 * @return bool
	 */
	protected function view_is( $view ) {
		return $this->get_view() === $view;
	}

	/**
	 * The default view for the table
	 *
	 * @return string
	 */
	protected function get_default_view() {
		$views = wp_list_pluck( $this->get_views_setup(), 'view' );

		return array_shift( $views );
	}

	/**
	 * @param object $item
	 * @param string $column_name
	 */
	protected function column_default( $item, $column_name ) {
		do_action( "groundhogg/admin/table/{$this->get_table_id()}/column_default", $item, $column_name );
	}

	/**
	 * The param which will be used in the view...
	 *
	 * @return string
	 */
	protected function view_param() {
		return 'view';
	}

	public function single_row( $item ) {

		$classes = [];

		if ( is_a( $item, Base_Object_With_Meta::class ) ) {

			if ( check_lock( $item ) ) {
				$classes[] = 'wp-locked';
			}

			$classes[] = $item->object_type;

		}

		$classes = implode( ' ', $classes );

		echo "<tr id=\"$item->ID\" class=\"$classes\">";
		$this->single_row_columns( $item );
		echo '</tr>';
	}

}
