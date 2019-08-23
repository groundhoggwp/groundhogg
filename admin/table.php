<?php
namespace Groundhogg\Admin;

use Groundhogg\DB\DB;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( '\WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

abstract class Table extends \WP_List_Table
{

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
     * @return mixed
     */
    protected function parse_query( $query )
    {
        return $query;
    }

    protected function get_page_url()
    {
        return add_query_arg( array_filter( [
            'page' => get_request_var( 'page' ),
            'tab'  => get_request_var( 'tab' )
        ] ), admin_url( 'admin.php' ) );
    }

    /**
     * Generate a view link.
     *
     * @param $view
     * @param $display
     * @param $count
     * @return string
     */
    protected function create_view($view, $display, $count=0 )
    {
        return html()->e( 'a',
            [
                'class' => $this->get_view() === $view ? 'current' : '',
                'href'  => add_query_arg( [
                    'status' => $view,
                ], $this->get_page_url() ),
            ],
            sprintf( '%s <span class="count">(%d)</span>', $display, $count )
        );
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
     * @param mixed $item
     * @param string $column_name
     * @param string $primary
     * @return string
     */
    protected function handle_row_actions( $item, $column_name, $primary )
    {
        if ( $primary !== $column_name ) {
            return '';
        }


        $row_actions = [];

        $actions = $this->get_row_actions( $item, $column_name, $primary );

        foreach ( $actions as $action ){

            $action = wp_parse_args( $action, [
                'display' => '',
                'class' => '',
                'url' => '#'
            ] );

            $row_actions[] = $this->create_row_action( $action[ 'class' ], $action[ 'url' ], $action[ 'display' ] );

        }

        return $this->row_actions( $row_actions );

    }

    /**
     * Create a row action.
     *
     * @param $class
     * @param $url
     * @param $display
     * @return string
     */
    protected function create_row_action( $class, $url, $display )
    {
        return html()->wrap( html()->e( 'a', [ 'href' => $url ], $display ), 'span', [ 'class' => $class ] );
    }

    /**
     * [
     *   [
     *     'display' => '',
     *     'view' => '',
     *     'count' => [],
     *   ]
     * ]
     *
     * @return array
     */
    abstract protected function get_views_setup();

    /**
     * Parse the views and return them
     *
     * @return array
     */
    protected function get_views()
    {
        $setup = $this->get_views_setup();

        $views = [];

        foreach ( $setup as $view ){

            $view = wp_parse_args( $view, [
                'display' => '',
                'view' => '',
                'count' => 0
            ] );

            if ( is_array( $view[ 'count' ] ) || is_object( $view[ 'count' ] ) ){
                $view[ 'count' ] = $this->get_db()->count( $view[ 'count' ] );
            }

            $views[] = $this->create_view( $view[ 'view' ], $view[ 'display' ], $view[ 'count' ] );
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
    public function prepare_items()
    {
        $columns  = $this->get_columns();
        $hidden   = array(); // No hidden columns
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $data    = [];
        $per_page = absint( get_url_var( 'limit', 30 ) );
        $paged   = $this->get_pagenum();
        $offset  = $per_page * ( $paged - 1 );
        $search  = get_url_var( 's' );
        $order   = get_url_var( 'order', 'DESC' );
        $orderby = get_url_var( 'orderby', 'time' );

        $where = [
            'relationship' => "AND",
            [ 'col' => $this->view_param(), 'val' => $this->get_view(), 'compare' => '=' ],
        ];

        $args = array(
            'where'   => $where,
            'limit'   => $per_page,
            'offset'  => $offset,
            'order'   => $order,
            'orderby' => $orderby,
        );

        $events = $this->get_db()->query( $args );
        $total = $this->get_db()->count( $args );

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

    /**
     * Parse the item before it gets treated as data.
     *
     * @param $item
     * @return mixed
     */
    protected function parse_item( $item )
    {
        return $item;
    }

    /**
     * @return string
     */
    protected function get_view()
    {
        return get_request_var( $this->view_param(), $this->get_default_view() );
    }

    protected function get_default_view()
    {
        $views = wp_list_pluck( $this->get_views_setup(), 'view' );
        return array_shift( $views );
    }

    /**
     * @param object $item
     * @param string $column_name
     */
    protected function column_default($item, $column_name)
    {
        do_action( "groundhogg/admin/table/{$this->get_table_id()}/column_default", $item, $column_name );
    }

    /**
     * The param which will be used in the view...
     *
     * @return string
     */
    protected function view_param()
    {
        return 'view';
    }

}