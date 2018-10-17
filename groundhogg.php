<?php
/*
Plugin Name: Groundhogg
Plugin URI: https://wordpress.org/plugins/groundhogg/
Description: CRM and marketing automation for WordPress
Version: 0.9.4
Author: Groundhogg Inc.
Author URI: http://www.groundhogg.io
Text Domain: groundhogg
Domain Path: /languages
*/
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Groundhogg' ) ) :

    final class Groundhogg
    {

        public $version = '0.9.4';

        /**
         * @var $instance Groundhogg instance
         */
        public static $instance;

        /**
         * @var bool Dummy vairable to snusre GH was infact setup.
         */
        public static $is_setup = false;

        /**
         * Funnel actions/benchmarks
         *
         * @var WPGH_Elements
         */
        public $elements;


        /**
         * GH roles object //todo
         *
         * @var object|WPGH_Roles
         */
        public $roles;


        /**
         * GH API object //todo
         *
         * @var object|WPGH_API
         */
        public $api;

        /**
         * GH HTML Helper Class
         *
         * @var object|WPGH_HTML
         */
        public $html;

        /**
         * @var WPGH_DB_Broadcasts
         */
        public $broadcasts;

        /**
         * GH Emails DB
         *
         * @var object|WPGH_DB_Emails
         */
        public $emails;

        /**
         * @var WPGH_DB_Email_Meta
         */
        public $email_meta;

        /**
         * @var WPGH_Importer
         */
        public $importer;

        /**
         * GH Contact DB
         *
         * @var object|WPGH_DB_Contacts
         */
        public $contacts;

        /**
         * GH Contact Meta DB
         *
         * @var object|WPGH_DB_Contact_Meta
         */
        public $contact_meta;

        /**
         * GH Funnel DB
         *
         * @var object|WPGH_DB_Funnels
         */
        public $funnels;

        /**
         * GH Funnel Steps
         *
         * @var object|WPGH_DB_Steps
         */
        public $steps;

        /**
         * GH Step Meta
         *
         * @var object|WPGH_DB_Step_Meta
         */
        public $step_meta;

        /**
         * GH Tags
         *
         * @var object|WPGH_DB_Tags
         */
        public $tags;

        /**
         * GH Tag Relationships
         *
         * @var object|WPGH_DB_Tag_Relationships
         */
        public $tag_relationships;


        /**
         * GH Superlinks
         *
         * @var object|WPGH_DB_Superlinks
         */
        public $superlinks;

        /**
         *
         * @var WPGH_Superlink
         */
        public $superlink;

        /**
         * The tracking class
         *
         * @var object|WPGH_Tracking
         */
        public $tracking;

        /**
         * If a form submission is in progress access it via this
         *
         * @var WPGH_Submission
         */
        public $submission;

        /**
         * The event queue
         *
         * @var object|WPGH_Event_Queue
         */
        public $event_queue;

        /**
         * @var WPGH_DB_Events
         */
        public $events;

        /**
         * @var WPGH_Bounce_Checker
         */
        public $bounce_checker;

        /**
         * @var WPGH_Template_Loader
         */
        public $template_loader;

        /**
         * @var WPGH_Notices
         */
        public $notices;

        /**
         * @var WPGH_Admin_Menu
         */
        public $menu;

        /**
         * @var WPGH_DB_Activity
         */
        public $activity;

        /**
         * @var WPGH_Replacements
         */
        public $replacements;

        /**
         * Returns the instance on Groundhogg.
         *
         * @return Groundhogg
         */
        public static function instance()
        {
            if ( ! self::$is_setup ) {

//                echo 'not setup ';
                self::$is_setup = true;
                self::$instance = new Groundhogg;

                self::$instance->setup_constants();

                self::$instance->includes();

                self::$instance->contacts     = new WPGH_DB_Contacts();
                self::$instance->contact_meta = new WPGH_DB_Contact_Meta();

                self::$instance->tags                 = new WPGH_DB_Tags();
                self::$instance->tag_relationships    = new WPGH_DB_Tag_Relationships();

                self::$instance->funnels      = new WPGH_DB_Funnels();

                self::$instance->steps        = new WPGH_DB_Steps();
                self::$instance->step_meta    = new WPGH_DB_Step_Meta();

                self::$instance->emails       = new WPGH_DB_Emails();
                self::$instance->email_meta   = new WPGH_DB_Email_Meta();

                self::$instance->broadcasts   = new WPGH_DB_Broadcasts();

                self::$instance->activity     = new WPGH_DB_Activity();
                self::$instance->events       = new WPGH_DB_Events();
                self::$instance->superlinks   = new WPGH_DB_Superlinks();


                self::$instance->tracking     = new WPGH_Tracking();
                self::$instance->superlink    = new WPGH_Superlink();
                self::$instance->event_queue  = new WPGH_Event_Queue();
//
                self::$instance->replacements = new WPGH_Replacements();
                self::$instance->notices      = new WPGH_Notices();
                self::$instance->submission   = new WPGH_Submission();
                self::$instance->html         = new WPGH_HTML();
//
                self::$instance->bounce_checker   = new WPGH_Bounce_Checker();
                self::$instance->template_loader  = new WPGH_Template_Loader();
//
                self::$instance->elements     = new WPGH_Elements();
//
                if ( is_admin() ){
                    self::$instance->menu       = new WPGH_Admin_Menu();
                    self::$instance->importer   = new WPGH_Importer();
                }

            }


            return self::$instance;
        }

        /**
         * Throw error on object clone.
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since 1.6
         * @access protected
         * @return void
         */
        public function __clone() {
            // Cloning instances of the class is forbidden.
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'groundhogg' ), '1.6' );
        }

        /**
         * Disable unserializing of the class.
         *
         * @since 1.6
         * @access protected
         * @return void
         */
        public function __wakeup() {
            // Unserializing instances of the class is forbidden.
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'groundhogg' ), '1.6' );
        }


        /**
         * Setup plugin constants.
         *
         * @access private
         * @since 1.4
         * @return void
         */
        private function setup_constants() {

            if ( ! defined( 'WPGH_VERSION' ) ){
                define( 'WPGH_VERSION', $this->version );
            }

            if ( ! defined( 'WPGH_PLUGIN_DIR' ) ){
                define( 'WPGH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            }

            if ( ! defined( 'WPGH_PLUGIN_URL' ) ){
                define( 'WPGH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
            }

            if ( ! defined( 'WPGH_PLUGIN_FILE' ) ){
                define( 'WPGH_PLUGIN_FILE', __FILE__ );
            }

            if ( ! defined( 'WPGH_ASSETS_FOLDER' ) ){
                define( 'WPGH_ASSETS_FOLDER', plugin_dir_url( __FILE__ ) . 'assets/' );
            }
        }


        /**
         * Include required filed
         *
         * @return void
         */
        private function includes()
        {
            /* DB */
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-activity.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-broadcasts.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-contactmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-contacts.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-emailmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-emails.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-events.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-funnels.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-stepmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-steps.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-superlinks.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-tag-relationships.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-tags.php';

            /* Admin Files */
            if ( is_admin() ){
                include_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-admin-menu.php';
                require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-importer.php';
                include_once WPGH_PLUGIN_DIR . 'includes/dashboard.php';
                include_once WPGH_PLUGIN_DIR . 'includes/email-blocks.php';
            }

            /* Core Files */
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-bounce-checker.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-broadcast.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-contact.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-contact-query.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-elements.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-email.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-event.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-event-queue.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-extension.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-form.php';
//            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-funnel.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-html.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-notices.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-replacements.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-step.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-submission.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-superlink.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-template-loader.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-tracking.php';

            require_once WPGH_PLUGIN_DIR . 'includes/functions.php';
            require_once WPGH_PLUGIN_DIR . 'includes/shortcodes.php';
            require_once WPGH_PLUGIN_DIR . 'includes/install.php';

        }
    }

endif;

/**
 * Get the groundhogg instance. Can be used simliar to a global variable.
 *
 * @return Groundhogg
 */
function WPGH()
{
    return Groundhogg::instance();
}

WPGH();

do_action( 'groundhogg_loaded' );