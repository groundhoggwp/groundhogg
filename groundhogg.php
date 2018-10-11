<?php
/*
Plugin Name: Groundhogg
Plugin URI: https://wordpress.org/plugins/groundhogg/
Description: CRM and marketing automation for WordPress
Version: 0.1.0
Author: Adrian Tobey
Author URI: http://health-check-team.example.com
Text Domain: groundhogg
Domain Path: /languages
*/
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Groundhogg' ) ) :

    final class Groundhogg
    {

        /**
         * @var $instance Groundhogg instance
         */
        private static $instance;


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
         * Groundhogg constructor.
         */
        public function __construct()
        {
            $this->setup_constants();

            $this->includes();


            $this->contacts     = new WPGH_DB_Contacts();
            $this->contact_meta = new WPGH_DB_Contact_Meta();

            $this->tags                 = new WPGH_DB_Tags();
            $this->tag_relationships    = new WPGH_DB_Tag_Relationships();

            $this->funnels      = new WPGH_DB_Funnels();

            $this->steps        = new WPGH_DB_Steps();
            $this->step_meta    = new WPGH_DB_Step_Meta();

            $this->emails       = new WPGH_DB_Emails();
            $this->email_meta   = new WPGH_DB_Email_Meta();

            $this->broadcasts   = new WPGH_DB_Broadcasts();

            $this->activity     = new WPGH_DB_Activity();
            $this->events       = new WPGH_DB_Events();
            $this->superlinks   = new WPGH_DB_Superlinks();


            $this->tracking     = new WPGH_Tracking();
            $this->event_queue  = new WPGH_Event_Queue();

            $this->replacements = new WPGH_Replacements();
            $this->notices      = new WPGH_Notices();
            $this->submission   = new WPGH_Submission();
            $this->superlink    = new WPGH_Superlink();
            $this->html         = new WPGH_HTML();

            $this->bounce_checker   = new WPGH_Bounce_Checker();
            $this->template_loader  = new WPGH_Template_Loader();

            $this->elements     = new WPGH_Elements();

            if ( is_admin() ){
                $this->menu     = new WPGH_Admin_Menu();
            }

        }

        /**
         * Returns the instance on Groundhogg.
         *
         * @return Groundhogg
         */
        public static function instance()
        {
            if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Groundhogg ) ) {

                self::$instance = new Groundhogg();

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
                define( 'WPGH_VERSION', '0.2' );
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