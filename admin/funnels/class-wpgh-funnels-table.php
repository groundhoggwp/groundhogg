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
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WPGH_Funnels_Table extends WP_List_Table {

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'funnel',     // Singular name of the listed records.
            'plural'   => 'funnels',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }
    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * bulk elements or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {

        $columns = array(
            'cb'                => '<input type="checkbox" />', // Render a checkbox instead of text.
            'title'             => _x( 'Title', 'Column label', 'groundhogg' ),
            'active_contacts'   => _x( 'Active Contacts', 'Column label', 'groundhogg' ),
            'last_updated'      => _x( 'Last Updated', 'Column label', 'groundhogg' ),
            'date_created'      => _x( 'Date Created', 'Column label', 'groundhogg' ),
        );

        return apply_filters( 'wpgh_funnels_get_columns', $columns );
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
            'title'             => array( 'title', false ),
            'active_contacts'   => array( 'active_contacts', false ),
            'last_updated'      => array( 'last_updated', false ),
            'date_created'      => array( 'date_created', false )
        );

        return apply_filters( 'wpgh_funnels_get_sortable_columns', $sortable_columns );
    }

    /**
     * Get the views for the emails, all, ready, unready, trash
     *
     * @return array
     */
    protected function get_views()
    {
        $views =  array();

        $count = array(
            'active'    => WPGH()->funnels->count( array( 'status' => 'active' ) ),
            'inactive'  => WPGH()->funnels->count( array( 'status' => 'inactive' ) ),
            'archived'  => WPGH()->funnels->count( array( 'status' => 'archived' ) )
        );

        $views['all'] = "<a class='" .  print_r( ( $this->get_view() === 'all' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=all' ) . "'>" . _x( 'All', 'view', 'groundhogg' ) . " <span class='count'>(" . ( $count[ 'active' ] + $count[ 'inactive' ] ) . ")</span>" . "</a>";
        $views['active'] = "<a class='" .  print_r( ( $this->get_view() === 'active' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=active' ) . "'>" . _x( 'Active', 'view', 'groundhogg'  ) . " <span class='count'>(" . $count[ 'active' ] . ")</span>" . "</a>";
        $views['inactive'] = "<a class='" .  print_r( ( $this->get_view() === 'inactive' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=inactive' ) . "'>" . _x( 'Inactive', 'view', 'groundhogg' ) . " <span class='count'>(" . $count[ 'inactive' ] . ")</span>" . "</a>";
        $views['archived'] = "<a class='" .  print_r( ( $this->get_view() === 'archived' )? 'current' : '' , true ) . "' href='" . admin_url( 'admin.php?page=gh_funnels&view=archived' ) . "'>" . _x( 'Archived', 'view', 'groundhogg' ) . " <span class='count'>(" . $count[ 'archived' ] . ")</span>" . "</a>";

        return apply_filters(  'wpgh_funnel_views', $views );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function extra_tablenav( $which ) {

		if ( $which !== 'top' ){
			return;
		}

	    wp_enqueue_style(  'jquery-ui' );
	    wp_enqueue_script( 'jquery-ui-datepicker' );

		?>
        <div class="alignleft actions bulkactions">
            <?php $args = array(
                'name'      => 'date_range',
                'id'        => 'date_range',
                'options'   => array(
                    'today'         => _x( 'Today', 'reporting_range', 'groundhogg' ),
                    'yesterday'     => _x( 'Yesterday', 'reporting_range', 'groundhogg' ),
                    'this_week'     => _x( 'This Week', 'reporting_range', 'groundhogg' ),
                    'last_week'     => _x( 'Last Week', 'reporting_range', 'groundhogg' ),
                    'last_30'       => _x( 'Last 30 Days', 'reporting_range', 'groundhogg' ),
                    'this_month'    => _x( 'This Month', 'reporting_range', 'groundhogg' ),
                    'last_month'    => _x( 'Last Month', 'reporting_range', 'groundhogg' ),
                    'this_quarter'  => _x( 'This Quarter', 'reporting_range', 'groundhogg' ),
                    'last_quarter'  => _x( 'Last Quarter', 'reporting_range', 'groundhogg' ),
                    'this_year'     => _x( 'This Year', 'reporting_range', 'groundhogg' ),
                    'last_year'     => _x( 'Last Year', 'reporting_range', 'groundhogg' ),
                    'custom'        => _x( 'Custom Range', 'reporting_range', 'groundhogg' ),
                ),
                'selected' => WPGH()->menu->funnels_page->get_url_var( 'date_range', 'this_week' ),
            ); echo WPGH()->html->dropdown( $args );

            submit_button( _x( 'Refresh', 'action', 'groundhogg' ), 'secondary', 'change_reporting', false );

            $class = WPGH()->menu->funnels_page->get_url_var( 'date_range' ) === 'custom' ? '' : 'hidden';

            ?><div class="custom-range <?php echo $class ?> alignleft actions"><?php

                echo WPGH()->html->date_picker(array(
                    'name'  => 'custom_date_range_start',
                    'id'    => 'custom_date_range_start',
                    'class' => 'input',
                    'value' => WPGH()->menu->funnels_page->get_url_var( 'custom_date_range_start' ),
                    'attributes' => '',
                    'placeholder' => 'YYY-MM-DD',
                    'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                    'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                    'format' => 'yy-mm-dd'
                ));
                echo WPGH()->html->date_picker(array(
                    'name'  => 'custom_date_range_end',
                    'id'    => 'custom_date_range_end',
                    'class' => 'input',
                    'value' => WPGH()->menu->funnels_page->get_url_var( 'custom_date_range_end' ),
                    'attributes' => '',
                    'placeholder' => 'YYY-MM-DD',
                    'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                    'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                    'format' => 'yy-mm-dd'
                )); ?>
            </div>
            <script>jQuery(function($){$('#date_range').change(function(){
                    if($(this).val() === 'custom'){
                        $('.custom-range').removeClass('hidden');
                    } else {
                        $('.custom-range').addClass('hidden');
                    }})});
            </script>
        </div>
		<?php
    }

	/**
     * Get default row elements...
     *
     * @param $funnel object
     * @return string a list of elements
     */
    protected function handle_row_actions( $funnel, $column_name, $primary )
    {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $id = $funnel->ID;

        if ( $this->get_view() === 'archived' ) {
            $actions[ 'restore' ] = "<span class='restore'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=restore&funnel='. $id ), 'restore'  ). "'>" . _x( 'Restore', 'action', 'groundhogg'  ) . "</a></span>";
            $actions[ 'delete' ] = "<span class='delete'><a href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=archived&action=delete&funnel='. $id ), 'delete'  ). "'>" . _x( 'Delete Permanently', 'action', 'groundhogg'  ) . "</a></span>";
        } else {
            $actions[ 'edit' ] = "<span class='edit'><a href='" . admin_url( 'admin.php?page=gh_funnels&action=edit&funnel='. $id ). "'>" . __( 'Build' ) . "</a></span>";
            $actions[ 'duplicate' ] = "<span class='duplicate'><a href='" .  wp_nonce_url(admin_url( 'admin.php?page=gh_funnels&action=duplicate&funnel='. $id ), 'duplicate' ). "'>" . _x( 'Duplicate', 'action', 'groundhogg' ) . "</a></span>";
            $actions[ 'trash' ] = "<span class='delete'><a class='submitdelete' href='" . wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&view=all&action=archive&funnel='. $id ), 'archive' ). "'>" . __( 'Archive', 'action', 'groundhogg' ) . "</a></span>";
        }

        return $this->row_actions( apply_filters( 'wpgh_funnel_row_actions', $actions, $funnel, $column_name ) );
    }

    protected function column_title( $funnel )
    {
        $subject = ( ! $funnel->title )? '(' . __( 'no title' ) . ')' : $funnel->title ;
        $editUrl = admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel->ID );

        if ( $this->get_view() === 'archived' ){
            $html = "<strong>{$subject}</strong>";
        } else {
            $html = "<strong>";

            $html .= "<a class='row-title' href='$editUrl'>{$subject}</a>";

            if ( $funnel->status === 'inactive' ){
                $html .= " &#x2014; " . "<span class='post-state'>(" . _x( 'Inactive', 'status', 'groundhogg' ) . ")</span>";
            }
        }
        $html .= "</strong>";

        return $html;
    }

    protected function column_active_contacts( $funnel )
    {

        $query  = new WPGH_Contact_Query();

        $count = $query->query( array(
            'count'  => true,
            'report' => array(
                'start'     => WPGH()->menu->funnels_page->reporting_start_time,
                'end'       => WPGH()->menu->funnels_page->reporting_end_time,
                'funnel'    => $funnel->ID
            )
        ) );

        $queryUrl = admin_url( sprintf( 'admin.php?page=gh_contacts&view=report&funnel=%d&start=%d', $funnel->ID, WPGH()->menu->funnels_page->reporting_start_time ) );
        return "<a href='$queryUrl'>$count</a>";
    }

    protected function column_last_updated( $funnel )
    {
        $lu_time = mysql2date( 'U', $funnel->last_updated );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $lu_time - $cur_time;
        $time_prefix = _x( 'Updated', 'status', 'groundhogg' );
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = date_i18n( 'Y/m/d \@ h:i A', intval( $lu_time ) );
        } else {
            $time = sprintf( _x( "%s ago" , 'status', 'groundhogg'), human_time_diff( $lu_time, $cur_time ) );
        }
        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $lu_time ) ) . '">' . $time . '</abbr>';
    }

    protected function column_date_created( $funnel )
    {
        $dc_time = mysql2date( 'U', $funnel->date_created );
        $cur_time = (int) current_time( 'timestamp' );
        $time_diff = $dc_time - $cur_time;
        $time_prefix = _x( 'Created', 'status', 'created' );
        if ( absint( $time_diff ) > 24 * HOUR_IN_SECONDS ){
            $time = date_i18n( 'Y/m/d \@ h:i A', intval( $dc_time ) );
        } else {
            $time = sprintf(  _x( "%s ago" , 'status', 'groundhogg'), human_time_diff( $dc_time, $cur_time ) );
        }
        return $time_prefix . '<br><abbr title="' . date_i18n( DATE_ISO8601, intval( $dc_time ) ) . '">' . $time . '</abbr>';
    }

    /**
     * For more detailed insight into how columns are handled, take a look at
     * WP_List_Table::single_row_columns()
     *
     * @param object $funnel        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $funnel, $column_name ) {

        do_action( 'wpgh_funnels_custom_column', $funnel, $column_name );

        return '';

    }

    /**
     * Get value for checkbox column.
     *
     * @param object $funnel A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $funnel ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $funnel->ID                // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk elements available on this table.
     *
     * @return array An associative array containing all the bulk elements.
     */
    protected function get_bulk_actions() {

        if ( $this->get_view() === 'archived' )
        {
            $actions = array(
                'delete' => _x( 'Delete Permanently', 'List table bulk action', 'groundhogg' ),
                'restore' => _x( 'Restore', 'List table bulk action', 'groundhogg' )
            );

        } else {
            $actions = array(
                'archive' => _x( 'Archive', 'List table bulk action', 'groundhogg' )
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

        $per_page = 20;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        if ( isset( $_REQUEST[ 's' ] ) ){

            $query_args[ 'search' ] = $_REQUEST[ 's' ];

        }

        switch ( $this->get_view() ){

            case 'active':

                $data = WPGH()->funnels->get_funnels( array( 'status' => 'active' ) );

                break;
            case 'inactive':

                $data = WPGH()->funnels->get_funnels( array( 'status' => 'inactive' ) );

                break;
            case 'archived':

                $data = WPGH()->funnels->get_funnels( array( 'status' => 'archived' ) );

                break;
            default:

                $data = array_merge(
                    WPGH()->funnels->get_funnels( array( 'status' => 'inactive' ) ),
                    WPGH()->funnels->get_funnels( array( 'status' => 'active' ) )
                );

                break;

        }

        /*
         * Sort the data
         */
        usort( $data, array( $this, 'usort_reorder' ) );

        $current_page = $this->get_pagenum();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

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
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_created'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }
}