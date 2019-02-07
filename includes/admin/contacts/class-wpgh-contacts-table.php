<?php
/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WP_List_Table, contact-editor.php
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPGH_Contacts_Table extends WP_List_Table {

    /**
     * TT_Example_List_Table constructor.
     *
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'contact',     // Singular name of the listed records.
            'plural'   => 'contacts',    // Plural name of the listed records.
            'ajax'     => false,       // Does this table support ajax?
        ) );
    }
    /**
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />', // Render a checkbox instead of text.
            'email'         => _x( 'Email', 'Column label', 'groundhogg' ),
            'first_name'    => _x( 'First Name', 'Column label', 'groundhogg' ),
            'last_name'     => _x( 'Last Name', 'Column label', 'groundhogg' ),
            'user_id'       => _x( 'Username', 'Column label', 'groundhogg' ),
            'owner_id'      => _x( 'Owner', 'Column label', 'groundhogg' ),
            'date_created'  => _x( 'Date', 'Column label', 'groundhogg' ),
        );
        return apply_filters( 'wpgh_contact_columns', $columns );
    }
    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            'email'         => array( 'email', false ),
            'first_name'    => array( 'first_name', false ),
            'last_name'     => array( 'last_name', false ),
            'user_id'       => array( 'user_id', false ),
            'owner_id'      => array( 'owner_id', false ),
            'date_created'  => array( 'date_created', false )
        );
        return apply_filters( 'wpgh_contact_sortable_columns', $sortable_columns );
    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_email( $contact )
    {

        $editUrl = admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->ID );
        $html  = '<div id="inline_' . intval( $contact->ID ). '" class="hidden">';
        $html .= '  <div class="email">' . esc_html( $contact->email ). '</div>';
        $html .= '  <div class="first_name">' . esc_html( $contact->first_name ). '</div>';
        $html .= '  <div class="last_name">' . esc_html( $contact->last_name ). '</div>';
        $html .= '  <div class="optin_status">' . esc_html( $contact->optin_status ). '</div>';
        if ( $contact->owner ){
            $html .= '  <div class="owner">' . esc_html( $contact->owner->ID ). '</div>';
        }
        $html .= '  <div class="tags">' . esc_html( json_encode( $contact->tags ) ). '</div>';
        $html .= '  <div class="tags-data">' . esc_html( json_encode( wpgh_format_tags_for_select2( $contact->tags ) ) ) . '</div>';
        $html .= '</div>';


        $html .= "<strong>";

        $html .= "<a class='row-title' href='$editUrl'>" . esc_html( $contact->email ) . "</a>";

        if ( $contact->optin_status === WPGH_UNCONFIRMED && ( ! isset( $_REQUEST[ 'optin_status' ] ) || $_REQUEST[ 'optin_status' ] !== 'unconfirmed' ) ){
            $html .= " &#x2014; " . "<span class='post-state'>(" . __( 'Unconfirmed', 'groundhogg' ) . ")</span>";
        }

        $html .= "</strong>";

        return $html;

    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_first_name( $contact )
    {
        return $contact->first_name ? $contact->first_name : '&#x2014;' ;
    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_last_name( $contact )
    {
        return $contact->last_name ? $contact->last_name : '&#x2014;' ;
    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_user_id( $contact )
    {
        $user = get_user_by( 'email', $contact->email );
        return $user ? '<a href="'.admin_url('user-edit.php?user_id='.$user->ID ).'">'.$user->display_name.'</a>' :  '&#x2014;';
    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_owner_id( $contact )
    {
        return ! empty( $contact->owner->ID ) ? '<a href="'.admin_url('admin.php?page=gh_contacts&view=owner&owner=' . $contact->owner->ID ).'">'. $contact->owner->user_login .'</a>' :  '&#x2014;';
    }

    /**
     * @param $contact WPGH_Contact
     * @return string
     */
    protected function column_date_created( $contact )
    {
        $dc_time = mysql2date( 'U', $contact->date_created );
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
     * Get default column value.
     * @param object $contact        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $contact, $column_name ) {

        do_action( 'wpgh_contacts_custom_column', $contact, $column_name );

        return '';
    }
    /**
     * Get value for checkbox column.
     *
     * @param object $contact A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $contact ) {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
            $contact->ID                // The value of the checkbox should be the record's ID.
        );
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk elements available on this table.
     * @return array An associative array containing all the bulk elements.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'groundhogg' ),
//            'export' => _x( 'Export', 'List table bulk action', 'groundhogg' ),
//            'apply_tag' => _x( 'Apply Tag', 'List table bulk action', 'groundhogg'),
//            'remove_tag' => _x( 'Remove Tag', 'List table bulk action', 'groundhogg')
        );

        if ( $this->get_view() === 'spam' ){
            $actions[ 'unspam' ] = _x( 'Approve', 'List table bulk action', 'groundhogg' );
        }  else {
	        $actions[ 'spam' ] = _x( 'Mark as Spam', 'List table bulk action', 'groundhogg' );
        }


        return apply_filters( 'wpgh_contact_bulk_actions', $actions );
    }

    protected function get_view()
    {
        return ( isset( $_GET['view'] ) )? $_GET['view'] : 'all';
    }

    protected function get_views() {
        global $wpdb;
        $base_url = admin_url( 'admin.php?page=gh_contacts&view=optin_status&optin_status=' );

        $view = isset($_REQUEST['optin_status']) ? $_REQUEST['optin_status'] : 'all';

        $count = array(
            'unconfirmed'   => WPGH()->contacts->count( array( 'optin_status' => WPGH_UNCONFIRMED   ) ),
            'confirmed'     => WPGH()->contacts->count( array( 'optin_status' => WPGH_CONFIRMED     ) ),
            'opted_out'     => WPGH()->contacts->count( array( 'optin_status' => WPGH_UNSUBSCRIBED  ) ),
            'spam'          => WPGH()->contacts->count( array( 'optin_status' => WPGH_SPAM          ) ),
            'bounce'        => WPGH()->contacts->count( array( 'optin_status' => WPGH_HARD_BOUNCE   ) ),
        );

        return apply_filters( 'contact_views', array(
            'all'           => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . admin_url( 'admin.php?page=gh_contacts' ) . "'>" . __( 'All <span class="count">('.array_sum($count).')</span>' ) . "</a>",
            'unconfirmed'   => "<a class='" . ($view === 'unconfirmed' ? 'current' : '') . "' href='" . $base_url . "unconfirmed" . "'>" . __( 'Unconfirmed <span class="count">('.$count['unconfirmed'].')</span>' ) . "</a>",
            'confirmed'     => "<a class='" . ($view === 'confirmed' ? 'current' : '') . "' href='" . $base_url . "confirmed" . "'>" . __( 'Confirmed <span class="count">('.$count['confirmed'].')</span>' ) . "</a>",
            'opted_out'     => "<a class='" . ($view === 'opted_out' ? 'current' : '') . "' href='" . $base_url . "opted_out" . "'>" . __( 'Unsubscribed <span class="count">('.$count['opted_out'].')</span>' ) . "</a>",
            'spam'          => "<a class='" . ($view === 'spam' ? 'current' : '') . "' href='" . $base_url . "spam" . "'>" . __( 'Spam <span class="count">('.$count['spam'].')</span>' ) . "</a>",
            'bounce'        => "<a class='" . ($view === 'bounce' ? 'current' : '') . "' href='" . $base_url . "bounce" . "'>" . __( 'Bounced <span class="count">('.$count['bounce'].')</span>' ) . "</a>"
        ) );
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

        global $wpdb;

        $per_page = 30;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $query = array();

        if ( isset( $_REQUEST[ 's' ] ) ){

            $query[ 'search' ] = $_REQUEST['s'];
            $query[ 'search_columns' ] = array(
                'first_name',
                'last_name',
                'email'
            );

        }

        if ( in_array( 'sales_manager', wpgh_get_current_user_roles() ) ){
            $query[ 'owner' ] = get_current_user_id();
        }

        $query[ 'optin_status' ] = array(
            WPGH_CONFIRMED,
            WPGH_UNCONFIRMED
        );

        if ( isset( $_REQUEST[ 'meta_key' ] ) && isset( $_REQUEST[ 'meta_value' ] ) ){
            $query[ 'meta_key' ] = sanitize_key( $_REQUEST[ 'meta_key' ] );
            $query[ 'meta_value' ] = urldecode( $_REQUEST[ 'meta_value' ] );
            if ( isset( $_REQUEST[ 'meta_compare' ] ) ){
                $query['meta_compare'] = strtoupper( sanitize_key(  $_REQUEST[ 'meta_compare' ]  ) );
            }
        }

        if ( isset( $_REQUEST[ 'date_after' ] ) ){
            $query[ 'date_query' ][ 'after' ] = stripslashes( $_REQUEST[ 'date_after' ] );
        }

        if ( isset( $_REQUEST[ 'date_before' ] ) ){
            $query[ 'date_query' ][ 'before' ] = stripslashes( $_REQUEST[ 'date_before' ] );
        }


        switch ( $this->get_view() )
        {
            case 'optin_status':
                if ( isset( $_REQUEST['optin_status'] ) ){

                    switch ( $_REQUEST['optin_status'] ){

                        case 'unconfirmed':
                            $view = WPGH_UNCONFIRMED;
                            break;
                        case 'confirmed':
                            $view = WPGH_CONFIRMED;
                            break;
                        case 'opted_out':
                            $view = WPGH_UNSUBSCRIBED;
                            break;
                        case 'spam':
                            $view = WPGH_SPAM;
                            break;
                        case 'bounce':
                            $view = WPGH_HARD_BOUNCE;
                            break;
                        default:
                            $view = WPGH_UNCONFIRMED;
                            break;
                    }

                    $query[ 'optin_status' ] = $view;

                }
                break;

            case 'owner':
                if ( isset( $_REQUEST['owner'] ) ){

                    $query[ 'owner' ]  = intval( $_REQUEST['owner'] );

                }
                break;

            case 'tag':
                if ( isset( $_REQUEST[ 'tag'] ) ){
                    $tag_id = $_GET['tag'];

                    $query[ 'tags_include' ] = $tag_id;

                }
                break;

            case 'report':

                $report = array();

                if ( isset( $_REQUEST['status'] ) ) {
                    $report['status'] = $_REQUEST['status'];
                }

                if ( isset( $_REQUEST['funnel'] ) ){
                    $report['funnel'] = intval ( $_REQUEST['funnel'] );
                }

                if ( isset(  $_REQUEST['step'] ) ){
                    $report['step'] = intval ( $_REQUEST['step'] );
                }

                if ( isset( $_REQUEST['start'] ) ){
                    $report['start'] = intval ( $_REQUEST['start'] );
                }

                if ( isset( $_REQUEST['end'] ) ){
                    $report['end'] = intval ( $_REQUEST['end'] );
                }

                $query[ 'report' ] = $report;

                break;

            case 'activity':

                $report = array();

                if ( isset( $_REQUEST['activity_type'] ) ) {
                    $report[ 'activity_type' ] = sanitize_key( $_REQUEST['activity_type'] );
                }
                if ( isset( $_REQUEST['funnel'] ) ){
                    $report[ 'funnel' ] = intval ( $_REQUEST['funnel'] );
                }
                if ( isset( $_REQUEST['referer'] ) ){
                    $report[ 'referer' ] = urldecode( $_REQUEST['referer'] );
                }
                if ( isset(  $_REQUEST['step'] ) ){
                    $report[ 'step' ] = intval ( $_REQUEST['step'] );
                }
                if ( isset( $_REQUEST['start'] ) ){
                    $report[ 'start' ] = intval ( $_REQUEST['start'] );
                }
                if ( isset( $_REQUEST['end'] ) ){
                    $report[ 'end' ] = intval ( $_REQUEST['end'] );
                }

                $query[ 'activity' ] = $report;

                break;
            default:

                break;
        }

        $c_query = new WPGH_Contact_Query();

        $data = $c_query->query( $query );

        set_transient( 'wpgh_contact_query_args', $c_query->query_vars, HOUR_IN_SECONDS );

        /**
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
        // If no sort, default to title.
        $a = (array) $a;
        $b = (array) $b;

        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_created'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action superlinks.
     *
     * @param object $contact        Contact being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row elements output for posts.
     */
    protected function handle_row_actions( $contact, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $contact->email;

        $actions['inline hide-if-no-js'] = sprintf(
            '<a href="#" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $title ) ),
            __( 'Quick&nbsp;Edit' )
        );

        $editUrl = admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->ID );

        $actions['edit'] = sprintf(
            '<a href="%s" class="edit" aria-label="%s">%s</a>',
            /* translators: %s: title */
            $editUrl,
            esc_attr(  __( 'Edit' ) ),
            __( 'Edit' )
        );

        if ( isset( $_REQUEST['optin_status'] ) && $_REQUEST[ 'optin_status' ] === 'spam' ){
            $actions['unspam'] = sprintf(
		        '<a href="%s" class="unspam" aria-label="%s">%s</a>',
		        wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $contact->ID .'&action=unspam')),
		        /* translators: %s: title */
		        esc_attr( sprintf( __( 'Mark %s as approved.' ), $title ) ),
		        __( 'Approve' )
	        );
        } else if ( isset( $_REQUEST['optin_status'] ) && $_REQUEST[ 'optin_status' ] === 'bounce' ){
	        $actions['unbounce'] = sprintf(
		        '<a href="%s" class="unbounce" aria-label="%s">%s</a>',
		        wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $contact->ID .'&action=unbounce')),
		        /* translators: %s: title */
		        esc_attr( sprintf( __( 'Mark %s as a valid email.' ), $title ) ),
		        __( 'Valid Email', 'groundhogg' )
	        );
        } else {
	        $actions['spam'] = sprintf(
		        '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
		        wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $contact->ID .'&action=spam')),
		        /* translators: %s: title */
		        esc_attr( sprintf( __( 'Mark %s as spam' ), $title ) ),
		        __( 'Spam' )
	        );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $contact->ID .'&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete Permanently' )
        );

        return $this->row_actions( apply_filters( 'wpgh_contact_row_actions', $actions, $contact, $column_name ) );
    }

    /**
     * @param object $item
     * @param int $level
     */
    public function single_row($item, $level = 0)
    {
        ?>
        <tr id="contact-<?php echo $item->ID; ?>">
            <?php $this->single_row_columns( new WPGH_Contact( $item->ID ) ); ?>
        </tr>
        <?php
    }


    /**
     * @param string $which
     */
    protected function extra_tablenav($which)
    {
        ?>
        <div class="alignleft">
            <a class="button button-secondary action query-export" href="javascript:void(0)"><?php printf( __( 'Export %s contacts', 'groundhogg' ), $this->get_pagination_arg( 'total_items' ) ); ?></a>
        </div>
        <?php
    }

    /**
     * Outputs the hidden row displayed when inline editing
     *
     * @global string $mode List table view mode.
     */
    public function inline_edit()
    {
        ?>
        <table style="display: none">
            <tbody id="inlineedit">
            <tr id="inline-edit" class="inline-edit-row inline-edit-row-contact quick-edit-row quick-edit-row-contact inline-edit-contact inline-editor" style="display: none">
                <td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
                    <fieldset class="inline-edit-col-left">
                        <legend class="inline-edit-legend"><?php echo __('Quick Edit'); ?></legend>
                        <div class="inline-edit-col">
                            <label>
                                <span class="title"><?php _e('Email'); ?></span>
                                <span class="input-text-wrap"><input type="text" name="email" class="cemail regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e('First Name'); ?></span>
                                <span class="input-text-wrap"><input type="text" name="first_name" class="cfirst_name regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e('Last Name'); ?></span>
                                <span class="input-text-wrap"><input type="text" name="last_name" class="clast_name regular-text" value=""/></span>
                            </label>
                            <label>
                                <span class="title"><?php _e('Owner'); ?></span>
                                <span class="input-text-wrap">
                                    <?php $args = array( 'show_option_none' => __( 'Select an owner' ), 'id' => 'owner', 'name' => 'owner', 'role' => 'administrator', 'class' => 'cowner' ); ?>
                                    <?php wp_dropdown_users( $args ) ?>
                                </span>
                            </label>
                            <label>
                                <input type="checkbox" name="unsubscribe"><?php _e( 'Unsubscribe this contact.', 'groundhogg' ); ?>
                            </label>
                        </div>
                    </fieldset>
                    <fieldset class="inline-edit-col-right">
                        <div class="inline-edit-col">
                            <label class="inline-edit-tags">
                                <span class="title"><?php _e('Tags'); ?></span>
                            </label>
                            <?php echo WPGH()->html->dropdown( array( 'id' => 'tags', 'name' => 'tags[]' ) ); ?>
                        </div>
                    </fieldset>
                    <div class="submit inline-edit-save">
                        <button type="button" class="button cancel alignleft"><?php _e('Cancel'); ?></button>
                        <?php wp_nonce_field('inlineeditnonce', '_inline_edit' ); ?>
                        <button type="button" class="button button-primary save alignright"><?php _e('Update'); ?></button>
                        <span class="spinner"></span>
                        <br class="clear"/>
                        <div class="notice notice-error notice-alt inline hidden">
                            <p class="error"></p>
                        </div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }
}