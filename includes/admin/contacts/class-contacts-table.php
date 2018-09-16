<?php
/**
 * Contacts Table Class
 *
 * This class shows the data table for accessing information about a customer.
 *
 * @package     wp-funnels
 * @subpackage  Modules/Contacts
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
            'cb'       => '<input type="checkbox" />', // Render a checkbox instead of text.
            'email'    => _x( 'Email', 'Column label', 'wp-funnels' ),
            'first_name'   => _x( 'First Name', 'Column label', 'wp-funnels' ),
            'last_name' => _x( 'Last Name', 'Column label', 'wp-funnels' ),
            'user_id' => _x( 'Username', 'Column label', 'wp-funnels' ),
            'owner_id' => _x( 'Owner', 'Column label', 'wp-funnels' ),
            'date_created' => _x( 'Date', 'Column label', 'wp-funnels' ),
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
            'email'    => array( 'email', false ),
            'first_name' => array( 'first_name', false ),
            'last_name' => array( 'last_name', false ),
            'user_id' => array( 'user_id', false ),
            'owner_id' => array( 'owner_id', false ),
            'date_created' => array( 'date_created', false )
        );
        return apply_filters( 'wpgh_contact_sortable_columns', $sortable_columns );
    }

    protected function column_email( $item )
    {
        $contact = new WPGH_Contact( intval( $item['ID'] ) );

        $editUrl = admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $item['ID'] );
        $html  = '<div id="inline_' . intval( $item['ID'] ). '" class="hidden">';
        $html .= '  <div class="email">' . esc_html( $contact->get_email() ). '</div>';
        $html .= '  <div class="first_name">' . esc_html( $contact->get_first() ). '</div>';
        $html .= '  <div class="last_name">' . esc_html( $contact->get_last() ). '</div>';
        $html .= '  <div class="optin_status">' . esc_html( $contact->get_optin_status() ). '</div>';
        $html .= '  <div class="owner">' . esc_html( $contact->get_owner() ). '</div>';
        $html .= '  <div class="tags">' . esc_html( json_encode( $contact->get_tags() ) ). '</div>';
        $html .= '</div>';
        $html .= "<a class='row-title' href='$editUrl'>" . esc_html( $contact->get_email() ) . "</a>";
        return $html;
    }

    protected function column_first_name( $item )
    {
        $contact = new WPGH_Contact( intval( $item['ID'] ) );

        return $contact->get_first() ? $contact->get_first() : '&#x2014;' ;
    }

    protected function column_last_name( $item )
    {
        $contact = new WPGH_Contact( intval( $item['ID'] ) );

        return $contact->get_last() ? $contact->get_last() : '&#x2014;' ;
    }

    protected function column_user_id( $item )
    {
        $user = get_user_by( 'email', $item[ 'email' ] );
        return $user ? '<a href="'.admin_url('user-edit.php?user_id='.$user->ID ).'">'.$user->display_name.'</a>' :  '&#x2014;';
    }

    protected function column_owner_id( $item )
    {
        $owner = get_userdata( $item['owner_id'] );
        return ! empty( $item['owner_id'] ) ? '<a href="'.admin_url('admin.php?page=gh_contacts&view=owner&owner=' .$item['owner_id'] ).'">'. $owner->user_login .'</a>' :  '&#x2014;';
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

    /**
     * Get default column value.
     * @param object $item        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $item, $column_name ) {

        do_action( 'wpgh_contacts_custom_column', $item, $column_name );

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
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {
        $actions = array(
            'delete' => _x( 'Delete', 'List table bulk action', 'wp-funnels' ),
//            'export' => _x( 'Export', 'List table bulk action', 'wp-funnels' ),
//            'apply_tag' => _x( 'Apply Tag', 'List table bulk action', 'wp-funnels'),
//            'remove_tag' => _x( 'Remove Tag', 'List table bulk action', 'wp-funnels')
        );

        if ( $this->get_view() === 'spam' ){
            $actions[ 'unspam' ] = _x( 'Approve', 'List table bulk action', 'wp-funnels' );
        }  else {
	        $actions[ 'spam' ] = _x( 'Mark as Spam', 'List table bulk action', 'wp-funnels' );
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

        $table_name = $wpdb->prefix . WPGH_CONTACTS;

        $count = array(
            'unconfirmed' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = " . WPGH_UNCONFIRMED ) ),
            'confirmed' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = " . WPGH_CONFIRMED ) ),
            'opted_out' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = " . WPGH_UNSUBSCRIBED ) ),
            'spam' => count($wpdb->get_results("SELECT ID FROM $table_name WHERE optin_status = " . WPGH_SPAM ) ),
        );

        return apply_filters( 'contact_views', array(
            'all' => "<a class='" . ($view === 'all' ? 'current' : '') . "' href='" . admin_url( 'admin.php?page=gh_contacts' ) . "'>" . __( 'All <span class="count">('.array_sum($count).')</span>' ) . "</a>",
            'unconfirmed' => "<a class='" . ($view === 'unconfirmed' ? 'current' : '') . "' href='" . $base_url . "unconfirmed" . "'>" . __( 'Unconfirmed <span class="count">('.$count['unconfirmed'].')</span>' ) . "</a>",
            'confirmed' => "<a class='" . ($view === 'confirmed' ? 'current' : '') . "' href='" . $base_url . "confirmed" . "'>" . __( 'Confirmed <span class="count">('.$count['confirmed'].')</span>' ) . "</a>",
            'opted_out' => "<a class='" . ($view === 'opted_out' ? 'current' : '') . "' href='" . $base_url . "opted_out" . "'>" . __( 'Unsubscribed <span class="count">('.$count['opted_out'].')</span>' ) . "</a>",
            'spam' => "<a class='" . ($view === 'spam' ? 'current' : '') . "' href='" . $base_url . "spam" . "'>" . __( 'Spam <span class="count">('.$count['spam'].')</span>' ) . "</a>"
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
        global $wpdb; //This is used only if making any database queries
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 30;

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $search = isset( $_REQUEST['s'] )? $wpdb->prepare( "AND ( c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s)", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%", "%" . $wpdb->esc_like( $_REQUEST['s'] ) . "%" ) : '';

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
                        default:
                            $view = WPGH_UNCONFIRMED;
                            break;
                    }

                    $sql = $wpdb->prepare(
                        "SELECT c.* FROM " . $wpdb->prefix . WPGH_CONTACTS . " c
                        WHERE c.optin_status = %d $search
                        ORDER BY c.date_created DESC" , $view
                    );
                }
                break;
            case 'tag':
                if ( isset( $_REQUEST[ 'tag'] ) ){
                    $tag_id = $_GET['tag'];
                    $sql = $wpdb->prepare(
                        "SELECT t.*, c.* FROM "
                        .$wpdb->prefix . WPGH_CONTACT_TAG_RELATIONSHIPS . " t "
                        . " LEFT JOIN " .$wpdb->prefix . WPGH_CONTACTS . " c ON t.contact_id = c.ID 
                        WHERE t.tag_id = %d $search
                        ORDER BY c.date_created DESC"
                    , $tag_id);
                }
                break;
            case 'report':

                $sql = "SELECT DISTINCT e.contact_id, c.*
                FROM " . $wpdb->prefix . WPGH_EVENTS ." e 
                LEFT JOIN " . $wpdb->prefix . WPGH_CONTACTS . " c ON e.contact_id = c.ID 
                WHERE (1=1 ";
                if ( isset( $_REQUEST['status'] ) ) {
                    $status = $_REQUEST['status'];
                    $sql .= $wpdb->prepare(' AND e.status = %s', $status);
                }
                if ( isset( $_REQUEST['funnel'] ) ){
                    $funnel = intval ( $_REQUEST['funnel'] );
                    $sql .= $wpdb->prepare( ' AND e.funnel_id = %d', $funnel );
                }
                if ( isset(  $_REQUEST['step'] ) ){
                    $step = intval ( $_REQUEST['step'] );
                    $sql .= $wpdb->prepare( ' AND e.step_id = %d', $step );
                }
                if ( isset( $_REQUEST['start'] ) ){
                    $start = intval ( $_REQUEST['start'] );
                    $sql .= $wpdb->prepare( ' AND %d <= e.time', $start );
                }
                if ( isset( $_REQUEST['end'] ) ){
                    $end = intval ( $_REQUEST['end'] );
                    $sql .= $wpdb->prepare( ' AND e.time <= %d', $end );
                }

                $sql .= ") $search
                ORDER BY c.date_created DESC";
                break;
            case 'owner':
                if ( isset( $_REQUEST['owner'] ) ){
                    $owner = intval( $_REQUEST['owner'] );
                    $sql = $wpdb->prepare( "SELECT c.* FROM " . $wpdb->prefix . WPGH_CONTACTS . " c
                    WHERE c.owner_id = %d $search
                    ORDER BY c.date_created DESC", $owner );
                }
                break;
            case 'activity':
                $sql = "SELECT DISTINCT a.contact_id, c.*
                FROM " . $wpdb->prefix . WPGH_ACTIVITY ." a 
                LEFT JOIN " . $wpdb->prefix . WPGH_CONTACTS . " c ON a.contact_id = c.ID 
                WHERE (1=1 ";
                if ( isset( $_REQUEST['activity_type'] ) ) {
                    $activity_type = $_REQUEST['activity_type'];
                    $sql .= $wpdb->prepare(' AND a.activity_type = %s', $activity_type);
                }
                if ( isset( $_REQUEST['funnel'] ) ){
                    $funnel = intval ( $_REQUEST['funnel'] );
                    $sql .= $wpdb->prepare( ' AND a.funnel_id = %d', $funnel );
                }
                if ( isset(  $_REQUEST['step'] ) ){
                    $step = intval ( $_REQUEST['step'] );
                    $sql .= $wpdb->prepare( ' AND a.step_id = %d', $step );
                }
                if ( isset( $_REQUEST['start'] ) ){
                    $start = intval ( $_REQUEST['start'] );
                    $sql .= $wpdb->prepare( ' AND %d <= a.timestamp', $start );
                }
                if ( isset( $_REQUEST['end'] ) ){
                    $end = intval ( $_REQUEST['end'] );
                    $sql .= $wpdb->prepare( ' AND a.timestamp <= %d', $end );
                }

                $sql .= ") $search
                ORDER BY a.timestamp DESC";
                break;
            default:
                $sql = "SELECT c.* FROM " . $wpdb->prefix . WPGH_CONTACTS . " c
                WHERE 1=1 $search
                ORDER BY c.date_created DESC";
                break;
        }

        $data = $wpdb->get_results( $sql, ARRAY_A );

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
        // If no sort, default to title.
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'date_created'; // WPCS: Input var ok.
        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
        // Determine sort order.
        $result = strnatcmp( $a[ $orderby ], $b[ $orderby ] );
        return ( 'desc' === $order ) ? $result : - $result;
    }

    /**
     * Generates and displays row action links.
     *
     * @param object $item        Contact being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary     Primary column name.
     * @return string Row actions output for posts.
     */
    protected function handle_row_actions( $item, $column_name, $primary ) {
        if ( $primary !== $column_name ) {
            return '';
        }

        $actions = array();
        $title = $item['email'];

        $actions['inline hide-if-no-js'] = sprintf(
            '<a href="#" class="editinline" aria-label="%s">%s</a>',
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), $title ) ),
            __( 'Quick&nbsp;Edit' )
        );

        $actions['edit'] = sprintf(
            '<a href="#" class="edit" aria-label="%s">%s</a>',
            /* translators: %s: title */
            esc_attr(  __( 'Edit' ) ),
            __( 'Edit' )
        );

        if ( isset( $_REQUEST['optin_status'] ) && $_REQUEST[ 'optin_status' ] === 'spam' ){
            $actions['unspam'] = sprintf(
		        '<a href="%s" class="unspam" aria-label="%s">%s</a>',
		        wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $item['ID'].'&action=unspam')),
		        /* translators: %s: title */
		        esc_attr( sprintf( __( 'Mark %s as approved' ), $title ) ),
		        __( 'Approve' )
	        );
        } else {
	        $actions['spam'] = sprintf(
		        '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
		        wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $item['ID'].'&action=spam')),
		        /* translators: %s: title */
		        esc_attr( sprintf( __( 'Mark %s as spam' ), $title ) ),
		        __( 'Spam' )
	        );
        }

        $actions['delete'] = sprintf(
            '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
            wp_nonce_url(admin_url('admin.php?page=gh_contacts&contact[]='. $item['ID'].'&action=delete')),
            /* translators: %s: title */
            esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
            __( 'Delete Permanently' )
        );

        return $this->row_actions( apply_filters( 'wpgh_contact_row_actions', $actions, $item, $column_name ) );
    }

    /**
     * @param object $item
     * @param int $level
     */
    public function single_row($item, $level = 0)
    {
        ?>
        <tr id="contact-<?php echo $item['ID']; ?>">
            <?php $this->single_row_columns( $item ); ?>
        </tr>
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
                            <?php wpgh_dropdown_tags( array( 'select2' => false ) ); ?>
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