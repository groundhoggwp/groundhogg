<?php
/**
 * The page gh_broadcasts
 *
 * This class adds the broadcasts page to the menu and renders the output for the broadcasts page
 * IT also contains the private functions add() and cancel()
 * These are made private for good reason as the broadcasts function was decided to be kept a closed process.
 * If you are a developer, simply BUGGER OFF!
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Broadcasts_Page
{
    /**
     * @var WPGH_Notices
     */
    public $notices;
    public $order = 20;

    function __construct()
    {
        add_action( 'admin_menu', array( $this, 'register' ), $this->order );

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_broadcasts' ){
            add_action( 'init' , array( $this, 'process_action' )  );
            add_action( 'admin_enqueue_scripts' , array( $this, 'scripts' )  );
            $this->notices = WPGH()->notices;
        }
    }

    /**
     * enqueue editor scripts
     */
    public function scripts()
    {

        wp_enqueue_script( 'wpgh-flot-chart', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.min.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.min.js') );
        wp_enqueue_script( 'wpgh-flot-chart-pie', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.pie.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.pie.js') );

    }

    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            _x( 'Broadcasts', 'page_title', 'groundhogg' ),
            _x( 'Broadcasts', 'page_title',  'groundhogg' ),
            'view_broadcasts',
            'gh_broadcasts',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));


    }

    public function help()
    {
        //todo
    }

    /**
     * Get a list of affected broadcasts
     *
     * @return array|bool
     */
    function get_broadcasts()
    {
        $broadcasts = isset( $_REQUEST['broadcast'] ) ? $_REQUEST['broadcast'] : null;

        if ( ! $broadcasts )
            return false;

        return is_array( $broadcasts )? array_map( 'intval', $broadcasts ) : array( intval( $broadcasts ) );
    }

    /**
     * Get the current action
     *
     * @return bool
     */
    function get_action()
    {
        if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * Get the previous action
     *
     * @return mixed
     */
    function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    /**
     * Get the current screen title
     */
    function get_title()
    {
        switch ( $this->get_action() ){
            case 'add':
                _ex( 'Schedule Broadcast', 'page_title', 'groundhogg' );
                break;
            default:
                _ex( 'Broadcasts', 'page_title','groundhogg' );
                break;
        }
    }

    /**
     * Process the current action
     */
    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( ! current_user_can( 'schedule_broadcasts' ) ){
                    wp_die( WPGH()->roles->error( 'schedule_broadcasts' ) );
                }

                if ( isset( $_POST ) ) {
                    $this->add_broadcast();
                }

                break;

            case 'cancel':

                if ( ! current_user_can( 'cancel_broadcasts' ) ){
                    wp_die( WPGH()->roles->error( 'cancel_broadcasts' ) );
                }

                foreach ( $this->get_broadcasts() as $id ){
                    $broadcast = new WPGH_Broadcast( $id );
                    $broadcast->cancel();
                }

                $this->notices->add( 'cancelled', _nx( '%d broadcasts cancelled', '%d broadcast cancelled', count( $this->get_broadcasts() ), 'notice', 'groundhogg' ) );

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_broadcasts() ){
            $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_broadcasts() ) ), $base_url );
        }

        wp_redirect( $base_url );
        die();
    }

    /**
     * Schedule a new broadcast
     */
    function add_broadcast()
    {
        if ( ! current_user_can( 'schedule_broadcasts' ) ){
            wp_die( WPGH()->roles->error( 'schedule_broadcasts' ) );
        }

        $email = isset( $_POST['email_id'] )? intval( $_POST[ 'email_id' ] ) : null;

        $tags = isset( $_POST[ 'tags' ] )? WPGH()->tags->validate( $_POST['tags'] ): array();

        if ( empty( $tags ) || ! is_array( $tags ) ) {
            $this->notices->add( 'no_tags', _x( 'Please select 1 or more tags to send this broadcast to', 'notice', 'groundhogg' ), 'error' );
            return;
        }

        $exclude_tags = isset( $_POST[ 'exclude_tags' ] )? WPGH()->tags->validate( $_POST['exclude_tags'] ): array();

        $contact_sum = 0;

        foreach ( $tags as $tag ){
            $tag = WPGH()->tags->get_tag( intval( $tag ) );
            if ( $tag ){
                $contact_sum += $tag->contact_count;
            }
        }

        if ( $contact_sum === 0 ){
            $this->notices->add( 'no_contacts', _x( 'Please select a tag with at least 1 contact', 'notice', 'groundhogg' ), 'error' );
            return;
        }

        $send_date = isset( $_POST['date'] )? $_POST['date'] : date( 'Y/m/d', strtotime( 'tomorrow' ) );
        $send_time = isset( $_POST['time'] )? $_POST['time'] : '09:30';

        $time_string = $send_date . ' ' . $send_time;

        /* convert to UTC */
        $send_time = wpgh_convert_to_utc_0( strtotime( $time_string ) );

        $send_now = false;
        if ( isset( $_POST[ 'send_now' ] ) ){
            $send_now = true;
            $send_time = time() + 10;
        }

        if ( $send_time < time() ){
            $this->notices->add( 'invalid_date', _x( 'Please select a time in the future', 'notice', 'groundhogg' ), 'error' );
            return;
        }

        $args = array(
            'email_id'  => $email,
            'tags'      => $tags,
            'send_time' => $send_time,
            'scheduled_by' => get_current_user_id(),
            'status'    => 'scheduled',
        );

        $broadcast_id = WPGH()->broadcasts->add( $args );

        if ( ! $broadcast_id ){
            wp_die( 'Something went wrong' );
        }

        $query = new WPGH_Contact_Query();

        $args = array(
            'tags_include' => $tags,
            'tag_exclude' => $exclude_tags
        );

        $contacts = $query->query( $args );

        $send_in_timezone = false;
        if ( isset( $_POST[ 'send_in_timezone' ] ) ){
            $send_in_timezone = true;
        }

        foreach ( $contacts as $i => $contact ) {

            $contact = wpgh_get_contact( $contact->ID );

            if (  $send_in_timezone && ! $send_now ){
                $local_time = $contact->get_local_time( $send_time );
                if ( $local_time > time() ){
                    $send_time = $local_time;
                }
            }

            $args = array(
                'time'          => $send_time,
                'contact_id'    => $contact->ID,
                'funnel_id'     => WPGH_BROADCAST,
                'step_id'       => $broadcast_id,
                'status'        => 'waiting',
                'event_type'    => WPGH_BROADCAST_EVENT
            );

            WPGH()->events->add( $args );
        }

        $this->notices->add( 'success', _x( 'Broadcast scheduled!', 'notice','groundhogg' ), 'success' );
    }

    /**
     * Verify the current user can process the action
     *
     * @return bool
     */
    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-broadcasts' );
    }

    /**
     * Display the table
     */
    function table()
    {
        if ( ! class_exists( 'WPGH_Broadcasts_Table' ) ){
            include dirname(__FILE__) . '/class-wpgh-broadcasts-table.php';
        }

        $broadcasts_table = new WPGH_Broadcasts_Table();

        $broadcasts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _ex( 'Search Broadcasts', 'search','groundhogg'); ?>:&nbsp;</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _ex( 'Search Broadcasts', 'search','groundhogg'); ?>">
            </p>
            <?php $broadcasts_table->prepare_items(); ?>
            <?php $broadcasts_table->display(); ?>
        </form>

        <?php
    }

    /**
     * Display the scheduling page
     */
    function add()
    {
        if ( ! current_user_can( 'schedule_broadcasts' ) ){
            wp_die( WPGH()->roles->error( 'schedule_broadcasts' ) );
        }

        include dirname(__FILE__) . '/add-broadcast.php';
    }

    /**
     * Display the reporting page
     */
    function report()
    {
        if ( ! current_user_can( 'view_broadcasts' ) ){
            wp_die( WPGH()->roles->error( 'view_broadcasts' ) );
        }

        include dirname( __FILE__ ) . '/broadcast-report.php';
    }

    /**
     * Display the screen content
     */
    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_broadcasts&action=add' ); ?>"><?php _ex( 'Schedule New', 'page_title_action', 'groundhogg' ); ?></a>
            <?php $this->notices->notices(); ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->report();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }
}