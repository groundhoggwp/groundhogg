<?php
/*
Plugin Name: Groundhogg
Plugin URI: https://wordpress.org/plugins/groundhogg/
Description: CRM and marketing automation for WordPress
Version: 1.2.11
Author: Groundhogg Inc.
Author URI: http://www.groundhogg.io
Text Domain: groundhogg
Domain Path: /languages
*/
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Groundhogg' ) ) :

    final class Groundhogg
    {

        public $version = '1.2.11';

        /**
         * @var $instance Groundhogg instance
         */
        public static $instance;

        /**
         * @var bool Dummy variable to ensure GH was in fact setup.
         */
        public static $is_setup = false;

        /**
         * Funnel actions/benchmarks
         *
         * @var WPGH_Elements
         */
        public $elements;

        /**
         * GH roles object
         *
         * @var object|WPGH_Roles
         */
        public $roles;

        /**
         * GH api object
         *
         * @var object|WPGH_API_V2
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
         * @var WPGH_DB_SMS
         */
        public $sms;

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
         * @var WPGH_Bulk_Contact_Manager
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
         * @var object|WPGH_Event_Queue_v2
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
         * @var WPGH_Network_Settings_Page
         */
        public $network_options;

        /**
         * @var WPGH_Upgrade
         */
        public $upgrader;

        /**
         * @var WPGH_Form_Iframe;
         */
        public $iframe_listener;

        /**
         * Custom blocks...
         *
         * @var array
         */
        public $blocks = array();

        /**
         * @var WPGH_Stats_Collection
         */
        public $stats;

        /**
         * Manager to handle API requests to Groundhogg email & SMS Service.
         *
         * @var Groundhogg_Service_Manager
         */
        public $service_manager;

        /**
         * @var WPGH_Tag_Association_Mapper
         */
        public $status_tag_mapper;

        /**
         * Array of modules, this allows for quick adding of modules without having to define anything.
         *
         * @var array
         */
        private $modules = [];

        /**
         * @param $name
         * @param $value
         */
        public function __set( $name, $value )
        {
            if ( ! property_exists( $this, $name ) ){
                $this->modules[ $name ] = $value;
            } else {
                $this->$name = $value;
            }

        }

        /**
         * @param $name
         * @return bool|mixed
         */
        public function __get($name)
        {
            if ( ! property_exists( $this, $name ) ){
                if ( key_exists( $name ,$this->modules ) ){
                    return $this->modules[ $name ];
                }
            } else {
                return $this->$name;
            }

            return false;
        }

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
                self::$instance->sms          = new WPGH_DB_SMS();

                self::$instance->activity     = new WPGH_DB_Activity();
                self::$instance->events       = new WPGH_DB_Events();
                self::$instance->superlinks   = new WPGH_DB_Superlinks();

                self::$instance->roles        = new WPGH_Roles();
                self::$instance->tracking     = new WPGH_Tracking();
                self::$instance->superlink    = new WPGH_Superlink();

	            /**
	             * Replaced Queue with Queue V2 @since 1.0.18
	             */
                self::$instance->event_queue  = new WPGH_Event_Queue_V2();

                self::$instance->replacements = new WPGH_Replacements();
                self::$instance->notices      = new WPGH_Notices();
                self::$instance->submission   = new WPGH_Submission();
                self::$instance->html         = new WPGH_HTML();

                self::$instance->bounce_checker   = new WPGH_Bounce_Checker();
                self::$instance->template_loader  = new WPGH_Template_Loader();

                self::$instance->elements     = new WPGH_Elements();
                self::$instance->iframe_listener = new WPGH_Form_Iframe();
                self::$instance->service_manager = new Groundhogg_Service_Manager();
                self::$instance->status_tag_mapper = new WPGH_Tag_Association_Mapper();

                if ( is_admin() ){
                    self::$instance->menu       = new WPGH_Admin_Menu();
                    self::$instance->upgrader   = new WPGH_Upgrade();
                    if ( is_multisite() ){
                        self::$instance->network_options = new WPGH_Network_Settings_Page();
                    }
                }

                self::$instance->api = new WPGH_API_LOADER();
                self::$instance->stats = new WPGH_Stats_Collection();

                self::$instance->register_blocks();
                self::$instance->register_integrations();

                add_action( 'plugins_loaded', array( self::$instance, 'load_text_domain' ) );

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
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin huh?', 'groundhogg' ), '1.6' );
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
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin huh?', 'groundhogg' ), '1.6' );
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
            if ( ! defined( 'WPGH_PLUGIN_BASE_DIR' ) ){
                define( 'WPGH_PLUGIN_BASE_DIR', plugins_url(basename(__DIR__)));
            }
        }

        /**
         * Include custom blocks for page builders.
         */
        private function register_blocks()
        {
            require_once dirname( __FILE__ ) . '/blocks/elementor/class-wpgh-elementor-blocks.php';
            require_once dirname( __FILE__ ) . '/blocks/beaver-builder/class-wpgh-beaver-builder-blocks.php';
            require_once dirname( __FILE__ ) . '/blocks/wpbakery/class-wpgh-wpbakery-blocks.php';
//            require_once dirname( __FILE__ ) . '/blocks/divi/divi.php';
            //require_once dirname( __FILE__ ) . '/blocks/visual-composer/class-wpgh-visual-composer-blocks.php';
            //$this->blocks[ 'visual-composer' ]  = new WPGH_Visual_Composer_Blocks();
            $this->blocks[ 'elementor' ]        = new WPGH_Elementor_Blocks();
            $this->blocks[ 'beaver-builder' ]   = new WPGH_Beaver_Builder_Blocks();
            $this->blocks[ 'wpbakery' ]         = new WPGH_WPBakery_Blocks();
//            $this->blocks[ 'divi' ]         = new WPGH_Divi_Blocks();
        }

        /**
         * Register the form actions
         */
        private function register_integrations()
        {
            add_action( 'elementor_pro/init', function() {
                // Here its safe to include our action class file
                include_once dirname( __FILE__ ) . '/integrations/class-wpgh-elementor-form-integration.php';
                // Instantiate the action class
                $groundhogg_action = new WPGH_Elementor_Form_Integration();
                // Register the action with form widget
                \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($groundhogg_action->get_name(), $groundhogg_action);
            });
        }


        /**
         * Include required filed
         *
         * @return void
         */
        private function includes()
        {
            /* Databases */
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-activity.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-broadcasts.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-contactmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-contacts.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-emailmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-emails.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-sms.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-events.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-funnels.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-stepmeta.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-steps.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-superlinks.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-tag-relationships.php';
            require_once WPGH_PLUGIN_DIR . 'includes/db/class-wpgh-db-tags.php';

            /* Admin Files */
            if ( is_admin() ){
                require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-admin-menu.php';
                require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-bulk-contact-manager.php';
                require_once WPGH_PLUGIN_DIR . 'includes/dashboard.php';
                require_once WPGH_PLUGIN_DIR . 'includes/tools.php';
                require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-upgrade.php';

                if ( is_multisite() ){
                    require_once WPGH_PLUGIN_DIR . 'includes/admin/multisite/class-wpgh-network-settings-page.php';
                }
            }

            /* Automation files */
            require_once WPGH_PLUGIN_DIR . 'includes/queue/interface-wpgh-event-process.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-broadcast.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-email-notification.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-sms-notification.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-event.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-event-queue-v2.php';
            require_once WPGH_PLUGIN_DIR . 'includes/queue/class-wpgh-step.php';

            /* Core Files */
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-bounce-checker.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-contact.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-contact-query.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-elements.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-email.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-sms.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-extension.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-form.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-html.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-notices.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-replacements.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-roles.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-submission.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-superlink.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-template-loader.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-tracking.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-popup.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-form-iframe.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-stats-collection.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-groundhogg-service-manager.php';
            require_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-tag-association-mapper.php';

            /* Functions */
            require_once WPGH_PLUGIN_DIR . 'includes/functions.php';
            require_once WPGH_PLUGIN_DIR . 'includes/shortcodes.php';
            require_once WPGH_PLUGIN_DIR . 'includes/locations.php';
            require_once WPGH_PLUGIN_DIR . 'includes/gutenberg.php';

            /* API */
            require_once WPGH_PLUGIN_DIR . 'api/class-wpgh-api-loader.php';

            /* Install */
            require_once WPGH_PLUGIN_DIR . 'includes/install.php';

        }

        public function load_text_domain()
        {
            load_plugin_textdomain( 'groundhogg', false, basename( dirname( __FILE__ ) ) . '/languages' );
        }

        public function brand()
        {
            return apply_filters( 'wpgh_brand_name', 'Groundhogg' );
        }

        /**
         * @param array $args
         * @return array|mixed|object
         */
        public function get_store_products( $args=array() )
        {
            $args = wp_parse_args( $args, array(
                //'category' => 'templates',
                'category' => '',
                'tag'      => '',
                's'        => '',
                'page'     => '',
                'number'   => '-1'
            ) );

            $url = 'https://groundhogg.io/edd-api/v2/products/';

            $response = wp_remote_get( add_query_arg( $args, $url ) );

            if ( is_wp_error( $response ) ){
                return $response->get_error_message();
            }

            $products = json_decode( wp_remote_retrieve_body( $response ) );

            return $products;
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
do_action( 'groundhogg/init' );