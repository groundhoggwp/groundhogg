<?php

namespace Groundhogg;

use Groundhogg\Api\Api_Loader;
use Groundhogg\DB\Manager as DB_Manager;
use Groundhogg\Admin\Admin_Menu;
use Groundhogg\Form\Submission_Handler;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Reporting\Email_Reports;
use Groundhogg\Steps\Manager as Step_Manager;
use Groundhogg\Bulk_Jobs\Manager as Bulk_Job_Manager;

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
	 * @since  1.0.0
	 * @access public
	 *
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
	 * @since  2.0.0
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
	 * @since  1.0.0
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
	 * @var Proxy_Service
	 */
	public $proxy_service;

	/**
	 * @var Telemetry
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
	 * @var Imap_Inbox
	 */
	public $imap_inbox;

	/**
	 * @var Step_Manager
	 */
	public $step_manager;

	/**
	 * @var Bulk_Job_Manager
	 */
	public $bulk_jobs;

	/**
	 * @var Library
	 */
	public $library;

	/**
	 * @var User_Syncing
	 */
	public $user_syncing;

	/**
	 * Clone.
	 *
	 * Disable class cloning and throw an error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @return Plugin An instance of the class.
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Init.
	 *
	 * Initialize Groundhogg Plugin. Register Groundhogg support for all the
	 * supported post types and initialize Groundhogg components.
	 *
	 * @since  1.0.0
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
		do_action( 'groundhogg/init/v2' );
	}

	/**
	 * Init components.
	 *
	 * Initialize Groundhogg components. Register actions, run setting manager,
	 * initialize all the components that run groundhogg, and if in admin page
	 * initialize admin components.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_components() {

		// Settings & DBS needs to go first...
		$this->settings = new Settings();
		$this->roles    = new Main_Roles();
		$this->dbs      = new DB_Manager();

		// Modules
		$this->preferences = new Preferences();
		$this->tracking    = new Tracking();
		$this->utils       = new Utils();
		$this->scripts     = new Scripts();
		$this->notices     = new Notices();
		$this->rewrites    = new Rewrites();

		// Back Compat...
		new Backwards_Compatibility();

		$this->replacements = new Replacements();
		$this->tag_mapping  = new Tag_Mapping();
		$this->step_manager = new Step_Manager();
		$this->bulk_jobs    = new Bulk_Job_Manager();

		$this->bounce_checker = new Bounce_Checker();
//		$this->imap_inbox     = new Imap_Inbox();
//		$this->sending_service  = new Sending_Service();
		$this->proxy_service    = new Proxy_Service();
		$this->stats_collection = new Telemetry();
//
		$this->event_queue = new Event_Queue();

		if ( is_admin() ) {
			$this->admin = new Admin_Menu();
		}

		// Goes last to ensure everything is installed before running...
		$this->installer = new Main_Installer();
		$this->updater   = new Main_Updater();

		$this->api = new Api_Loader();

		$this->shortcodes         = new Shortcodes();
		$this->submission_handler = new Submission_Handler();

		$this->library = new Library();

		new Blocks\Blocks();

		if ( ! is_white_labeled() ) {
			new Reviews();
			new License_Notice();
		}

		new Extension_Upgrader();
		new Email_Logger();

		$this->user_syncing = new User_Syncing();

		new Activity_Handler();

		\Groundhogg_Email_Services::init();

		new Plugin_Compatibility();
		new License_Manager();
		new Background_Tasks();
		new Big_File_Uploader();
		new Cleanup_Actions();
		new Daily_Actions();
		new Email_Reports(); // Todo remove this because it's just for testing right now
	}

	/**
	 * Register autoloader.
	 *
	 * Groundhogg autoloader loads all the classes needed to run the plugin.
	 *
	 * @since  1.6.0
	 * @access private
	 */
	private function register_autoloader() {
		require __DIR__ . '/autoloader.php';
//		require __DIR__ . '/../vendor/autoload.php';
//		require __DIR__ . '/overrides.php';

		Autoloader::run();
	}

	/**
	 * Plugin constructor.
	 *
	 * Initializing Groundhogg plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function __construct() {

		$this->register_autoloader();
		$this->load_immediate();

		if ( did_action( 'plugins_loaded' ) ) {
			$this->init();
		} else {
			add_action( 'plugins_loaded', [ $this, 'init' ], 0 );
		}
	}

	private function load_immediate() {

		// Load the email handler
		include __DIR__ . '/email-services.php';

		// Override wp_mail maybe
		include __DIR__ . '/pluggable.php';
	}

	/**
	 * Include other files
	 */
	private function includes() {
		require __DIR__ . '/polyfill.php';
		require __DIR__ . '/functions.php';
		require __DIR__ . '/kses.php';
		require __DIR__ . '/edit-lock.php';
		require __DIR__ . '/filters.php';
		require __DIR__ . '/tools.php';
		require __DIR__ . '/better-meta-compat.php';
		require __DIR__ . '/cli/bootstrap.php';
	}
}

Plugin::instance();
