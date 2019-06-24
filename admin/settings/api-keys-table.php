<?php
namespace Groundhogg\Admin\Settings;

use Groundhogg\Plugin;

/**
 * API Key Table Class
 *
 * @package     WPGH
 * @subpackage  Admin/Tools/APIKeys
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WPGH_API_Keys_Table Class
 *
 * Renders the API Keys table
 *
 * @since 2.0
 */
class API_Keys_Table extends \WP_List_Table {

    /**
     * @var int Number of items per page
     * @since 2.0
     */
    public $per_page = 30;

    /**
     * @var object Query results
     * @since 2.0
     */
    private $keys;

    /**
     * Get things started
     *
     * @since 1.5
     * @see WP_List_Table::__construct()
     */
    public function __construct() {
        global $status, $page;

        // Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'API Key', 'groundhogg' ),
            'plural'    => __( 'API Keys', 'groundhogg' ),
            'ajax'      => false,
        ) );

        $this->query();
    }

    /**
     * Gets the name of the primary column.
     *
     * @since 2.5
     * @access protected
     *
     * @return string Name of the primary column.
     */
    protected function get_primary_column_name() {
        return 'user';
    }

    /**
     * This function renders most of the columns in the list table.
     *
     * @since 2.0
     *
     * @param array $item Contains all the data of the keys
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_default( $item, $column_name ) {
        return $item[ $column_name ];
    }

    /**
     * Displays the public key rows
     *
     * @since 2.4
     *
     * @param array $item Contains all the data of the keys
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_key( $item ) {
        return '<input onfocus="this.select()" readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item[ 'key' ] ) . '"/>';
    }

    /**
     * Displays the token rows
     *
     * @since 2.4
     *
     * @param array $item Contains all the data of the keys
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_token( $item ) {
        return '<input onfocus="this.select()" readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item[ 'token' ] ) . '"/>';
    }

    /**
     * Displays the secret key rows
     *
     * @since 2.4
     *
     * @param array $item Contains all the data of the keys
     * @param string $column_name The name of the column
     *
     * @return string Column Name
     */
    public function column_secret( $item ) {
        return '<input onfocus="this.select()" readonly="readonly" type="text" class="large-text" value="' . esc_attr( $item[ 'secret' ] ) . '"/>';
    }

    /**
     * Renders the column for the user field
     *
     * @since 2.0
     * @return void
     */
    public function column_user( $item ) {

        $actions = array();

        $actions['reissue'] = sprintf(
            '<a href="%s" class="wpgh-regenerate-api-key">%s</a>',
            esc_url( wp_nonce_url( add_query_arg( array( 'user_id' => $item['id'], 'wpgh_action' => 'process_api_key', 'wpgh_api_process' => 'regenerate' ) ), 'wpgh-api-nonce' ) ),
            _x( 'Reissue', 'action', 'groundhogg' )
        );
        $actions['revoke'] = sprintf(
            '<a href="%s" class="wpgh-revoke-api-key wpgh-delete">%s</a>',
            esc_url( wp_nonce_url( add_query_arg( array( 'user_id' => $item['id'], 'wpgh_action' => 'process_api_key', 'wpgh_api_process' => 'revoke' ) ), 'wpgh-api-nonce' ) ),
            _x( 'Revoke', 'action', 'groundhogg' )
        );

        $actions = apply_filters( 'wpgh_api_row_actions', array_filter( $actions ) );

        return sprintf('%1$s %2$s', $item['user'], $this->row_actions( $actions ) );
    }

    /**
     * Retrieve the table columns
     *
     * @since 2.0
     * @return array $columns Array of all the list table columns
     */
    public function get_columns() {
        $columns = array(
            'user'   => _x( 'Username', 'column_title', 'groundhogg' ),
            'key'    => _x( 'Public Key', 'column_title', 'groundhogg' ),
            'token'  => _x( 'Token', 'column_title', 'groundhogg' ),
            'secret' => _x( 'Secret Key', 'column_title', 'groundhogg' ),
        );

        return $columns;
    }

    /**
     * Display the key generation form
     *
     * @since 1.5
     * @return void
     */
    public function bulk_actions( $which = '' ) {
        // These aren't really bulk actions but this outputs the markup in the right place
        static $wpgh_api_is_bottom;

        if( $wpgh_api_is_bottom ) {
            return;
        }
        ?>

        <form id="api-key-generate-form" method="post" action="<?php echo admin_url( 'admin.php?page=gh_settings&tab=api_tab' ); ?>">
            <input type="hidden" name="wpgh_action" value="process_api_key" />
            <input type="hidden" name="wpgh_api_process" value="generate" />
            <?php wp_nonce_field( 'wpgh-api-nonce' ); ?>
            <?php echo Plugin::$instance->utils->html->dropdown_owners( array( 'option_none' => __( 'Please Select a User', 'groundhogg' ) ) ); ?>
            <?php submit_button( _x( 'Generate New API Keys', 'action', 'groundhogg' ), 'secondary', 'submit', false );?>
        </form>
        <?php
        $wpgh_api_is_bottom = true;
    }

    /**
     * Generate the table navigation above or below the table
     *
     * @since 3.1.0
     * @access protected
     * @param string $which
     */
    protected function display_tablenav( $which ) {
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
        }
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">

            <div class="alignleft actions bulkactions">
                <?php $this->bulk_actions( $which ); ?>
            </div>
            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>

            <br class="clear" />
        </div>
        <?php
    }




    /**
     * Retrieve the current page number
     *
     * @since 2.0
     * @return int Current page number
     */
    public function get_paged() {
        return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    }

    /**
     * Performs the key query
     *
     * @since 2.0
     * @return array
     */
    public function query() {
        $users    = get_users( array(
            'meta_key' => 'wpgh_user_secret_key',
            'number'     => $this->per_page,
            'offset'     => $this->per_page * ( $this->get_paged() - 1 ),
        ) );
        $keys     = array();

        foreach( $users as $user ) {
            $keys[$user->ID]['id']     = $user->ID;
            $keys[$user->ID]['email']  = $user->user_email;
            $keys[$user->ID]['user']   = '<a href="' . add_query_arg( 'user_id', $user->ID, 'user-edit.php' ) . '"><strong>' . $user->user_login . '</strong></a>';

            $keys[$user->ID]['key']    = get_user_meta( $user->ID, 'wpgh_user_public_key' ,true);
            $keys[$user->ID]['secret'] = get_user_meta( $user->ID,'wpgh_user_secret_key' ,true);
            $keys[$user->ID]['token']  = $this->get_token( $user->ID );
        }

        return $keys;
    }



    /**
     * Retrieve count of total users with keys
     *
     * @since 2.0
     * @return int
     */
    public function total_items() {
        global $wpdb;

        if( ! get_transient( 'wpgh_total_api_keys' ) ) {
            $total_items = $wpdb->get_var( "SELECT count(user_id) FROM $wpdb->usermeta WHERE meta_value='wpgh_user_secret_key'" );

            set_transient( 'wpgh_total_api_keys', $total_items, 60 * 60 );
        }

        return get_transient( 'wpgh_total_api_keys' );
    }

    /**
     * Setup the final data for the table
     *
     * @since 2.0
     * @return void
     */
    public function prepare_items() {


        // check for form submit to create
        if( isset( $_POST['owner_id'] ) && $_POST['owner_id'] != 0 && $_POST['wpgh_api_process'] === 'generate') {
            $this->generate_api_key($_POST['owner_id']);
        }

        if ( isset( $_GET['wpgh_api_process'] ) && $_GET['wpgh_api_process'] ==='revoke' && isset($_GET['user_id']))
        {
            delete_user_meta($_GET['user_id'],'wpgh_user_public_key');
            delete_user_meta($_GET['user_id'],'wpgh_user_secret_key');
        }

        if ( isset( $_GET['wpgh_api_process'] ) && $_GET['wpgh_api_process'] ==='regenerate' && isset($_GET['user_id']))
        {
            $this->generate_api_key($_GET['user_id']);
        }

        $columns = $this->get_columns();

        $hidden = array(); // No hidden columns
        $sortable = array(); // Not sortable... for now

        $this->_column_headers = array( $columns, $hidden, $sortable, 'user' );

        $data = $this->query();

        $total_items = $this->total_items();

        $this->items = $data;

        $this->set_pagination_args( array(
                'total_items' => $total_items,
                'per_page'    => $this->per_page,
                'total_pages' => ceil( $total_items / $this->per_page ),
            )
        );
    }


    public function generate_api_key( $user_id = 0) {

        if( empty( $user_id ) ) {
            return false;
        }

        $user = get_userdata( $user_id );

        if( ! $user ) {
            return false;
        }
        //genrate new keys

        $new_public_key = $this->generate_public_key( $user->user_email );
        $new_secret_key = $this->generate_private_key( $user->ID );

        //update meta
        update_user_meta( $user_id,'wpgh_user_public_key',$new_public_key );
        update_user_meta( $user_id,'wpgh_user_secret_key', $new_secret_key  );

        return true;
    }

    /**
     * Generate the public key for a user
     *
     * @access private
     * @since 1.9.9
     * @param string $user_email
     * @return string
     */
    public function generate_public_key( $user_email = '' ) {

        $auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
        $public   = hash( 'md5', $user_email . $auth_key . date( 'U' ) );
        return $public;
    }

    /**
     * Generate the secret key for a user
     *
     * @access private
     * @since 1.9.9
     * @param int $user_id
     * @return string
     */
    public function generate_private_key( $user_id = 0 ) {
        $auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
        $secret   = hash( 'md5', $user_id . $auth_key . date( 'U' ) );
        return $secret;
    }

    /**
     * Retrieve the user's token
     *
     * @access private
     * @since 1.9.9
     * @param int $user_id
     * @return string
     */
    public function get_token( $user_id = 0 ) {
        return hash( 'md5', get_user_meta( $user_id,'wpgh_user_secret_key' ,true) . get_user_meta( $user_id, 'wpgh_user_public_key' ,true) );
    }
}