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

    /**
     * @var WPGH_Notices
     */
    public $notices;

    public $order = 10;

    public function __construct()
    {

        add_action('admin_menu', array($this, 'register'), $this->get_order() );

        if ( $this->is_current_page() ) {

            add_action('admin_enqueue_scripts', array($this, 'scripts'));
            add_action('init', array($this, 'process_action'));
            $this->notices = WPGH()->notices;

        }
    }

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
            array($this, 'page')
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
     * @return bool
     */
    protected function get_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action']))
            return false;

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return false;
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
    abstract protected function get_title();

    /**
     * Do any actions such as bulka actions which occur inside the process action call
     *
     * @return mixed
     */
    abstract protected function do_actions();

    /**
     * Process the given action
     */
    public function process_action()
    {

        if (!$this->get_action() || !$this->verify_action())
            return;

        $base_url = remove_query_arg(array('_wpnonce', 'action'), wp_get_referer());

        $this->do_actions();

        set_transient('gh_last_action', $this->get_action(), 30 );

        $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_items())), $base_url);

        wp_redirect($base_url);
        die();
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
     * Display the title and dependent action include the appropriate page content
     */
    abstract public function page();
}