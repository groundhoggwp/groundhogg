<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
     * @var DB[]
     */
    public $dbs = [];
    
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
     * Role Manager.
     *
     * Holds the plugin Role Manager
     *
     * @since 2.0.0
     * @access public
     *
     * @var \Groundhogg\Core\RoleManager\Role_Manager
     */
    public $role_manager;

    /**
     * Admin.
     *
     * Holds the plugin admin.
     *
     * @since 1.0.0
     * @access public
     *
     * @var Admin
     */
    public $admin;
    
    /**
     * @var Log_Manager
     */
    public $logger;

    /**
     * @var Core\Upgrade\Manager
     */
    public $upgrade;

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
     * @since 2.3.0
     * @access public
     */
    public function on_rest_api_init() {
        // On admin/frontend sometimes the rest API is initialized after the common is initialized.
//        if ( ! $this->common ) {
//            $this->init_common();
//        }
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


        if ( is_admin() ) {

        }

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
        require ELEMENTOR_PATH . '/includes/autoloader.php';

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

        $this->logger = Log_Manager::instance();

        Maintenance::init();
        Compatibility::register_actions();

        add_action( 'init', [ $this, 'init' ], 0 );
        add_action( 'rest_api_init', [ $this, 'on_rest_api_init' ] );
    }
}

Plugin::instance();