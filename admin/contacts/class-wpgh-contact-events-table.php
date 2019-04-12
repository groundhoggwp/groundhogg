<?php
/**
 * Contact Events table view
 *
 * This is an extension of the WP_List_Table, it shows the recent or future funnel history of a contact
 * Used in contact-editor.php
 *
 * Shows the name of the funnel, the name of the step, the run date and allows the user to cancel or run the event immediately.
 *
 * Because the data can be past or future, the actual data is set outside of the prepare items function in contact-editor.php
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WP_List_Table, contact-editor.php
 * @since       File available since Release 0.9
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if ( ! class_exists( 'WPGH_Events_Table' ) ){
    require_once ( WPGH_PLUGIN_DIR . 'includes/admin/events/class-wpgh-events-table.php' );
}

class WPGH_Contact_Events_Table extends WPGH_Events_Table {

    /**
     * The data concerning the contact
     *
     * @var array
     */
    public $data;

    /**
     * @var string
     */
    public $status;

    public function __construct( $status='waiting' )
    {
        $this->status = $status;
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
        $columns = array(
            'funnel'    => _x( 'Funnel', 'Column label', 'wp-funnels' ),
            'step'      => _x( 'Step', 'Column label', 'wp-funnels' ),
            'time'      => _x( 'Time', 'Column label', 'wp-funnels' ),
            'actions'   => _x( 'Actions', 'Column label', 'wp-funnels' ),
        );

        return apply_filters( 'wpgh_event_columns', $columns );
    }

    public function display_tablenav($which)
    {
        if ( $which === 'top' ):

        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <?php $this->extra_tablenav( $which ); ?>
            <br class="clear" />
        </div>
    <?php
    endif;
    }

    public function extra_tablenav($which)
    {
        ?>
        <div class="alignleft gh-actions">
        <a class="button button-secondary" href="<?php echo admin_url('admin.php?page=gh_events&view=contact&contact=' . $_GET[ 'contact' ] ); ?>"><?php _ex( 'View All Events', 'contact_record', 'groundhogg' ); ?></a>
        <?php if ( $this->status === 'waiting' ): ?>
        <a class="button action" href="<?php echo add_query_arg( 'process_queue', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>"><?php _ex( 'Process Events', 'action', 'groundhogg' ); ?></a>
        <?php endif; ?>
        </div>
        <?php
    }

    public function get_views()
    {
        __return_false();
    }

    public function handle_row_actions($event, $column_name, $primary)
    {

        $actions = [];

        if ( $column_name === 'funnel' ){
            if ( $event->is_funnel_event() ){
                $actions['edit'] = sprintf("<a class='edit' href='%s' aria-label='%s'>%s</a>",
                    admin_url('admin.php?page=gh_funnels&action=edit&funnel=' . $event->funnel_id),
                    esc_attr(_x('Edit Funnel', 'action', 'groundhogg')),
                    _x('Edit Funnel', 'action', 'groundhogg')
                );
            }

        } else if ( $column_name === 'step' ){
            if ( $event->is_funnel_event() ){
                $actions['edit'] = sprintf("<a class='edit' href='%s' aria-label='%s'>%s</a>",
                    admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%d#%d', $event->funnel_id, $event->step->ID ) ),
                    esc_attr(_x('Edit Step', 'action', 'groundhogg')),
                    _x('Edit Step', 'action', 'groundhogg')
                );
            }
        }


        return $this->row_actions( apply_filters( 'wpgh_event_row_actions', $actions, $event, $column_name ) );
    }

    /**
     * @param $event WPGH_Event
     * @return string
     */
    protected function column_actions( $event )
    {
        $run = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=execute' ), 'execute' ) );
        $cancel = esc_url( wp_nonce_url( admin_url('admin.php?page=gh_events&event='. $event->ID . '&action=cancel' ), 'cancel' ) );
        $actions = array();

        if ( $event->time > time() && $event->status === 'waiting' ){
            $actions[] = sprintf( "<span class=\"run\"><a href=\"%s\" class=\"run\">%s</a></span>", $run, _x( 'Run Now', 'action', 'groundhogg' ) );
            $actions[] = sprintf( "<span class=\"delete\"><a href=\"%s\" class=\"delete\">%s</a></span>", $cancel, _x( 'Cancel', 'action', 'groundhogg' ) );
        } else {
            $actions[] = sprintf( "<span class=\"run\"><a href=\"%s\" class=\"run\">%s</a></span>", $run, _x( 'Run Again', 'action', 'groundhogg' ) );
        }

        return $this->row_actions( $actions );
    }

    /**
     * Prepares the list of items for displaying
     */
    function prepare_items() {
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 10;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $data =  WPGH()->events->get_events( array( 'contact_id' => intval( $_GET[ 'contact' ] ), 'status' => $this->status ) );

         /* Sort the data */
        usort( $data, array( $this, 'usort_reorder' ) );
        $current_page = $this->get_pagenum();
        $total_items = count( $data );
        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items, // WE have to calculate the total number of items.
            'per_page'    => $per_page,    // WE have to determine how many items to show on a page.
            'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
        ) );
    }
}