<?php
/**
 * Abstract Admin Page
 *
 * This is a base class for all admin pages
 *
 * @package     Admin
 * @subpackage  Admin
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


abstract class WPGH_Admin_Page
{

    const SELF = 0;
    const PAGE = 1;
//    const ADD  = 2;

    /**
     * @var WPGH_Notices
     */
    public $notices;

    public $order = 10;

    public function __construct()
    {

        add_action('admin_menu', array($this, 'register'), $this->get_order() );

        if ( wp_doing_ajax() ){
            $this->add_ajax_actions();
        }

        if ( $this->is_current_page() ) {
            add_action('admin_enqueue_scripts', array($this, 'scripts'));
            add_action('init', array($this, 'process_action'));
            $this->notices = WPGH()->notices;
        }
    }

    /**
     * Add Ajax actions...
     *
     * @return mixed
     */
    abstract public function add_ajax_actions();

    /**
     * Get the menu order between 1 - 99
     *
     * @return int
     */
    abstract public function get_order();

    /**
     * Get the page slug
     *
     * @return string
     */
    abstract public function get_slug();

    /**
     * Get the menu name
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    abstract public function get_cap();

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    abstract public function get_item_type();

    /**
     * Whether this page is the current page
     *
     * @return bool
     */
    public function is_current_page()
    {
        return isset($_GET['page']) && $_GET['page'] === $this->get_slug();
    }

    /**
     * Enqueue any scripts
     */
    abstract public function scripts();

    /* Register the page */
    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            $this->get_name(),
            $this->get_name(),
            $this->get_cap(),
            $this->get_slug(),
            [ $this, 'page' ]
        );

        add_action("load-" . $page, array($this, 'help'));
    }

    /**
     * Add any help items
     *
     * @return mixed
     */
    abstract public function help();

    /**
     * Get the affected items on this page
     *
     * @return array|bool
     */
    protected function get_items()
    {
        $items = isset($_REQUEST[ $this->get_item_type() ]) ? $_REQUEST[$this->get_item_type()] : null;

        if (!$items)
            return false;

        return is_array($items) ? array_map('intval', $items) : array(intval($items));
    }

    /**
     * Get the current action
     *
     * @return bool|string
     */
    protected function get_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action']))
            return false;

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return 'view';
    }

    /**
     * Get the previous action
     *
     * @return mixed
     */
    protected function get_previous_action()
    {
        $action = get_transient('gh_last_action');

        delete_transient('gh_last_action');

        return $action;
    }

    /**
     * Get the screen title
     */
    protected function get_title()
    {
        return $this->get_name();
    }

    /**
     * Verify that the current user can perform the action
     *
     * @return bool
     */
    protected function verify_action()
    {
        if (!isset($_REQUEST['_wpnonce']))
            return false;

        return wp_verify_nonce($_REQUEST['_wpnonce']) || wp_verify_nonce($_REQUEST['_wpnonce'], $this->get_action()) || wp_verify_nonce($_REQUEST['_wpnonce'], sprintf( 'bulk-%ss', $this->get_item_type() ) );
    }

    /**
     * Process the given action
     */
    public function process_action()
    {

        if (!$this->get_action() || !$this->verify_action())
            return;

        $base_url = remove_query_arg(array('_wpnonce', 'action'), wp_get_referer());

        $func = sprintf( "%s_rule", $this->get_action() );

        if ( method_exists( $this, $func ) ){
            $exitCode = call_user_func( [ $this, $func ] );
        }

        set_transient('gh_last_action', $this->get_action(), 30 );

        if ( $exitCode === self::SELF ){
            return;
        }

        $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_items())), $base_url);

        wp_redirect($base_url);
        die();
    }

    /**
     * Get an array of links => titles for page title actions
     *
     * @return array[]
     */
    abstract protected function get_title_actions();

    /**
     * Output the title actions
     */
    protected function do_title_actions()
    {
        foreach ( $this->get_title_actions() as $action ):

            $action = wp_parse_args( $action, [
                'link' => admin_url(),
                'action' => __( 'Add New', 'groundhogg' ),
                'target' => '_self',
            ] )

            ?>
            <a class="page-title-action aria-button-if-js" target="<?php esc_attr_e( $action[ 'target' ] ); ?>" href="<?php esc_attr_e( $action[ 'link' ] ); ?>"><?php _e( $action[ 'action' ] ); ?></a>
        <?php
        endforeach;

    }

    /**
     * Output the basic view.
     *
     * @return mixed
     */
    abstract public function view();


    /**
     * Display the title and dependent action include the appropriate page content
     */
    public function page(){
        ?>
        <div class="wrap">

            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
            <?php $this->do_title_actions(); ?>
            <div id="notices">
                <?php $this->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
            <?php

            if ( method_exists( $this, $this->get_action() ) ){
                call_user_func( [ $this, $this->get_action() ] );
            } else {
                do_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_action()}", $this );
            }

            ?>
        </div>
        <?php
    }


}