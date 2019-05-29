<?php
namespace Groundhogg;

use Groundhogg\Api\Api_Loader;
use Groundhogg\DB\Manager as DB_Manager;
use Groundhogg\Admin\Admin_Menu;
use Groundhogg\Dropins\Test_Extension;
use Groundhogg\Dropins\Test_Extension_2;
use Groundhogg\Form\Submission_Handler;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Reporting\Reporting;
use Groundhogg\Steps\Manager as Step_Manager;
use Groundhogg\Bulk_Jobs\Manager as Bulk_Job_Manager;

if ( ! defined( 'ABSPATH' ) ) {exit;}

/**
 * Groundhogg plugin.
 *
 * The main plugin handler class is responsible for initializing Groundhogg. The
 * class registers and all the components required to run the plugin.
 *
 * @since 2.0
 */
class Plugin {

    /**
     * Instance.
     *
     * Holds the plugin instance.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @var Plugin
     */
    public static $instance = null;

    /**
     * Database.
     *
     * Holds the plugin databases.
     *
     * @since 2.0.0
     * @access public
     *
     * @var DB_Manager
     */
    public $dbs;

    /**
     * @var Api\Api_Loader
     */
    public $api;

    /**
     * Holds plugin specific notices.
     *
     * @var Notices
     */
    public $notices;

    /**
     * @var Pointers
     */
    public $pointers;

    /**
     * Inits the admin screens.
     *
     * @var Admin_Menu
     */
    public $admin;

    /**
     * @var Utils
     */
    public $utils;

    /**
     * @var Scripts
     */
    public $scripts;

    /**
     * @var Main_Roles
     */
    public $roles;

    /**
     * Utils for compliance management
     *
     * @var Preferences
     */
    public $preferences;
    
    /**
     * Settings.
     *
     * Holds the plugin settings.
     *
     * @since 1.0.0
     * @access public
     *
     * @var Settings
     */
    public $settings;

    /**
     * @var Main_Installer
     */
    public $installer;

    /**
     * @var Main_Updater
     */
    public $updater;

    /**
     * @var Replacements
     */
    public $replacements;

    /**
     * @var Sending_Service
     */
    public $sending_service;

    /**
     * @var Stats_Collection
     */
    public $stats_collection;

    /**
     * @var Event_Queue
     */
    public $event_queue;

    /**
     * @var Tracking
     */
    public $tracking;

    /**
     * @var Rewrites
     */
    public $rewrites;

    /**
     * @var Tag_Mapping
     */
    public $tag_mapping;

    /**
     * @var Submission_Handler
     */
    public $submission_handler;

    /**
     * @var Shortcodes
     */
    public $shortcodes;

    /**
     * @var Bounce_Checker
     */
    public $bounce_checker;

    /**
     * @var Step_Manager
     */
    public $step_manager;

    /**
     * @var Bulk_Job_Manager
     */
    public $bulk_jobs;

    /**
     * @var Reporting
     */
    public $reporting;

//    /**
//     * @var Log_Manager
//     */
//    public $logger;
//
//    /**
//     * @var Core\Upgrade\Manager
//     */
//    public $upgrade;

    /**
     * Clone.
     *
     * Disable class cloning and throw an error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object. Therefore, we don't want the object to be cloned.
     *
     * @access public
     * @since 1.0.0
     */
    public function __clone() {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
    }

    /**
     * Wakeup.
     *
     * Disable unserializing of the class.
     *
     * @access public
     * @since 1.0.0
     */
    public function __wakeup() {
        // Unserializing instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
    }

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return Plugin An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();

            /**
             * Groundhogg loaded.
             *
             * Fires when Groundhogg was fully loaded and instantiated.
             *
             * @since 1.0.0
             */
            do_action( 'groundhogg/loaded' );
        }

        return self::$instance;
    }

    /**
     * Init.
     *
     * Initialize Groundhogg Plugin. Register Groundhogg support for all the
     * supported post types and initialize Groundhogg components.
     *
     * @since 1.0.0
     * @access public
     */
    public function init() {

        $this->includes();
        $this->init_components();

        /**
         * Groundhogg init.
         *
         * Fires on Groundhogg init, after Groundhogg has finished loading but
         * before any headers are sent.
         *
         * @since 1.0.0
         */
        do_action( 'groundhogg/init' );

    }

    /**
     * Init components.
     *
     * Initialize Groundhogg components. Register actions, run setting manager,
     * initialize all the components that run groundhogg, and if in admin page
     * initialize admin components.
     *
     * @since 1.0.0
     * @access private
     */
    private function init_components() {

        // Settings & DBS needs to go first...
        $this->settings     = new Settings();
        $this->roles        = new Main_Roles();
        $this->dbs          = new DB_Manager();

        // Modules
        $this->preferences  = new Preferences();
        $this->tracking     = new Tracking();
        $this->utils        = new Utils();
        $this->scripts      = new Scripts();
        $this->notices      = new Notices();
        $this->rewrites     = new Rewrites();
        $this->replacements = new Replacements();
        $this->tag_mapping  = new Tag_Mapping();
        $this->step_manager = new Step_Manager();
        $this->bulk_jobs    = new Bulk_Job_Manager();
        $this->reporting    = new Reporting();

        $this->bounce_checker = new Bounce_Checker();
        $this->sending_service = new Sending_Service();
        $this->stats_collection = new Stats_Collection();

        $this->event_queue  = new Event_Queue();

        if ( is_admin() ) {
            $this->admin   = new Admin_Menu();
        }

        // Goes last to ensure everything is installed before running...
        $this->installer    = new Main_Installer();
        $this->updater      = new Main_Updater();

        $this->api = new Api_Loader();

        $this->shortcodes = new Shortcodes();
        $this->submission_handler = new Submission_Handler();
    }

    /**
     * Register autoloader.
     *
     * Groundhogg autoloader loads all the classes needed to run the plugin.
     *
     * @since 1.6.0
     * @access private
     */
    private function register_autoloader() {
        require GROUNDHOGG_PATH . 'includes/autoloader.php';

        Autoloader::run();
    }

    /**
     * Plugin constructor.
     *
     * Initializing Groundhogg plugin.
     *
     * @since 1.0.0
     * @access private
     */
    private function __construct() {

        $this->register_autoloader();
        $this->init_dropins();


        if ( did_action( 'plugins_loaded' ) ){
            $this->init();
        } else {
            add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
        }
    }

    /**
     * initialize any dropins.
     */
    protected function init_dropins()
    {
//        new Test_Extension();
//        new Test_Extension_2();
    }

    /**
     * Include other files
     */
    private function includes()
    {
        require  GROUNDHOGG_PATH . '/includes/functions.php';
        require  GROUNDHOGG_PATH . '/includes/filters.php';
        require  GROUNDHOGG_PATH . '/includes/tools.php';
        require  GROUNDHOGG_PATH . '/includes/pluggable.php';
    }
}

Plugin::instance();