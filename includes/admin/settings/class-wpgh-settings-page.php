<?php
/**
 * Plugin Settings
 *
 * This  is your fairly typical settigns page.
 * It's a BIT of a mess, but I digress.
 *
 * @package     Admin
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Settings_Page
{

    /**
     * @var WPGH_Bulk_Contact_Manager
     */
    private $importer;

    /**
     * A list of the settings tabs
     *
     * @var array
     */
    private $tabs;

    /**
     * A list of tab sections
     *
     * @var array
     */
    private $sections;

    /**
     * A list of all the settings
     *
     * @var array
     */
    private $settings;

    public function __construct()
    {
        /* Settings always come last. */
        add_action( 'admin_menu', array( $this, 'register' ), 99 );
        add_action( 'admin_init', array( $this, 'init_defaults' ) );
        add_action( 'admin_init', array( $this, 'register_sections' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        if ( ! class_exists( 'WPGH_Extensions_Manager' ) )
            include_once dirname( __FILE__ ) . '/../extensions/module-manager.php';

        add_action( 'admin_init', array( 'WPGH_Extension_Manager', 'check_for_updates' ) );

	    if ( isset( $_GET['page'] ) && $_GET['page'] === 'gh_settings' ) {
		    add_action( 'admin_init', array( 'WPGH_Extension_Manager', 'perform_activation' ) );
		    add_action( 'admin_init', array( 'WPGH_Extension_Manager', 'perform_deactivation' ) );
	    }

        if ( ( isset( $_GET['page'] ) && $_GET['page'] === 'gh_settings' ) || wp_doing_ajax() ){
            $this->importer = new WPGH_Bulk_Contact_Manager();
        }

    }

    /**
     * Init the default settings & sections.
     */
    public function init_defaults()
    {
        $this->tabs     = $this->get_default_tabs();
        $this->sections = $this->get_default_sections();
        $this->settings = $this->get_default_settings();

        do_action( 'wpgh_settings_post_defaults_init', $this );
    }

    /* Register the page */
    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            __( 'Settings' ),
            __( 'Settings' ),
            'manage_options',
            'gh_settings',
            array( $this, 'settings_content' )
        );

        add_action( "load-" . $page, array( $this, 'help' ) );

    }

    /* Display the help bar */
    public function help()
    {
        //todo
    }

    /**
     * Returns a list of tabs
     *
     * @return array
     */
    private function get_default_tabs()
    {
        return apply_filters( 'wpgh_settings_tabs', array(
            'general'      => array(
                'id'    => 'general',
                'title' => _x( 'General', 'settings_tabs', 'groundhogg' )
            ),
            'marketing'    =>  array(
                'id'    => 'marketing',
                'title' => _x( 'Marketing', 'settings_tabs', 'groundhogg' )
            ),
            'email'        =>  array(
                'id'    => 'email',
                'title' => _x( 'Email', 'settings_tabs', 'groundhogg' )
            ),
            'tools'        =>  array(
                'id'    => 'tools',
                'title' => _x( 'Tools', 'settings_tabs', 'groundhogg' )
            ),
            'extensions'   =>  array(
                'id'    => 'extensions',
                'title' => _x( 'Licenses', 'settings_tabs', 'groundhogg' )
            ),
            'api_tab'   =>  array(
                'id'    => 'api_tab',
                'title' => _x( 'API', 'settings_tabs', 'groundhogg' )
            ),
        ) );
    }

    /**
     * Returns a list of all the default sections
     *
     * @return array
     */
    private function get_default_sections()
    {
        return apply_filters( 'wpgh_settings_sections', array(
            'business_info' => array(
                'id'    => 'business_info',
                'title' => _x( 'Business Settings', 'settings_sections', 'groundhogg' ),
                'tab'   => 'general'
            ),
            'misc_info' => array(
                'id'    => 'misc_info',
                'title' => _x( 'Misc Settings', 'settings_sections', 'groundhogg' ),
                'tab'   => 'general'
            ),
            'pages' => array(
                'id'    => 'pages',
                'title' => _x( 'Pages', 'settings_sections', 'groundhogg' ),
                'tab'   => 'marketing'
            ),
            'captcha' => array(
                'id'    => 'captcha',
                'title' => _x( 'Captcha', 'settings_sections', 'groundhogg' ),
                'tab'   => 'marketing'
            ),
            'compliance' => array(
                'id'    => 'compliance',
                'title' => _x( 'Compliance', 'settings_sections', 'groundhogg' ),
                'tab'   => 'marketing'
            ),
            'bounces' => array(
                'id'    => 'bounces',
                'title' => _x( 'Email Bounces', 'settings_sections', 'groundhogg' ),
                'tab'   => 'email'
            ),
            'service' => array(
                'id'    => 'service',
                'title' => _x( 'Groundhogg Sending Service (Email & SMS)', 'settings_sections', 'groundhogg' ),
                'tab'   => 'email'
            ),
            'api_settings' => array(
                'id'    => 'api_settings',
                'title' => _x( 'API Settings', 'settings_sections', 'groundhogg' ),
                'tab'   => 'api_tab'
            ),
        ) );
    }

    /**
     * Add the default settings sections
     */
    public function register_sections()
    {

        do_action( 'wpgh_settings_pre_register_sections', $this );

//        var_dump( $this->sections );

        foreach ( $this->sections as $id => $section ){
            add_settings_section( 'gh_' . $section[ 'id' ], $section[ 'title' ], array(), 'gh_' . $section[ 'tab' ] );
        }

    }

    private function get_default_settings()
    {

        $pages = get_posts( array(
            'numberposts'   => -1,
            'category'      => 0,
            'orderby'       => 'post_title',
            'order'         => 'ASC',
            'include'       => array(),
            'exclude'       => array(),
            'meta_key'      => '',
            'meta_value'    => '',
            'post_type'     => 'page',
            'suppress_filters' => true
        ) );

        $pops = array();

        if ( $pages ){
            foreach ( $pages as $page ){
                $pops[ $page->ID ] = $page->post_title;
            }
        }

        return apply_filters( 'wpgh_settings_settings', array(
            'gh_business_name' => array(
                'id'        => 'gh_business_name',
                'section'   => 'business_info',
                'label'     => _x( 'Business Name', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Your business name as it appears in the email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_business_name',
                    'name'          => 'gh_business_name',
                    'placeholder'   => get_bloginfo( 'name' )
                ),
            ),
            'gh_street_address_1' => array(
                'id'        => 'gh_street_address_1',
                'section'   => 'business_info',
                'label'     => _x( 'Street Address 1', 'settings', 'groundhogg' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_street_address_1',
                    'name'          => 'gh_street_address_1',
                    'placeholder'   => '123 Any St.'
                ),
            ),
            'gh_street_address_2' => array(
                'id'        => 'gh_street_address_2',
                'section'   => 'business_info',
                'label'     => _x( 'Street Address 2', 'settings', 'groundhogg' ),
                'desc'      => _x( '(Optional) As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_street_address_2',
                    'name'          => 'gh_street_address_2',
                    'placeholder'   => __( 'Unit 42' )
                ),
            ),
            'gh_city' => array(
                'id'        => 'gh_city',
                'section'   => 'business_info',
                'label'     => __( 'City' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_city',
                    'name'          => 'gh_city',
                    'placeholder'   => __( 'Toronto' )
                ),
            ),
            'gh_zip_or_postal' => array(
                'id'        => 'gh_zip_or_postal',
                'section'   => 'business_info',
                'label'     => _x( 'Postal/Zip Code', 'settings', 'groundhogg' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_zip_or_postal',
                    'name'          => 'gh_zip_or_postal',
                    'placeholder'   => 'A1A 1A1'
                ),
            ),
            'gh_region' => array(
                'id'        => 'gh_region',
                'section'   => 'business_info',
                'label'     => _x( 'State/Province/Region', 'settings', 'groundhogg' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_region',
                    'name'          => 'gh_region',
                    'placeholder'   => 'Ontario'
                ),
            ),
            'gh_country' => array(
                'id'        => 'gh_country',
                'section'   => 'business_info',
                'label'     => __( 'Country' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'id'            => 'gh_country',
                    'name'          => 'gh_country',
                    'placeholder'   => 'Canada'
                ),
            ),
            'gh_phone' => array(
                'id'        => 'gh_phone',
                'section'   => 'business_info',
                'label'     => __( 'Phone' ),
                'desc'      => _x( 'As it should appear in your email footer.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts'      => array(
                    'type'          => 'tel',
                    'id'            => 'gh_phone',
                    'name'          => 'gh_phone',
                    'placeholder'   => '+1 (555) 555-555'
                ),
            ),
            'gh_uninstall_on_delete' => array(
                'id'        => 'gh_uninstall_on_delete',
                'section'   => 'misc_info',
                'label'     => _x( 'Delete Groundhogg Data', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Delete all information when uninstalling. This cannot be undone.', 'settings', 'groundhogg' ),
                'type' => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_uninstall_on_delete[]',
                    'id'            => 'gh_uninstall_on_delete',
                    'value'         => 'on',
                ),
            ),
            'gh_opted_in_stats_collection' => array(
                'id'        => 'gh_opted_in_stats_collection',
                'section'   => 'misc_info',
                'label'     => _x( 'Optin to anonymous usage tracking.', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Help us make Groundhogg better by providing anonymous usage information about your site.', 'settings', 'groundhogg' ),
                'type' => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_opted_in_stats_collection',
                    'id'            => 'gh_opted_in_stats_collection',
                    'value'         => 'on',
                ),
            ),
            'gh_max_events' => array(
                'id'        => 'gh_max_events',
                'section'   => 'misc_info',
                'label'     => _x( 'Max Queued Events', 'settings', 'groundhogg' ),
                'desc'      => _x( 'The maximum number of events that can be run during a single process of the event queue. For larger lists you may want to set this at a lower number for performance reasons.', 'settings', 'groundhogg' ),
                'type'      => 'number',
                'atts'      => array(
                    'id'            => 'gh_max_events',
                    'name'          => 'gh_max_events',
                    'placeholder'   => '999999'
                ),
            ),
            'gh_queue_interval' => array(
                'id'        => 'gh_queue_interval',
                'section'   => 'misc_info',
                'label'     => _x( 'Queue Interval', 'settings', 'groundhogg' ),
                'desc'      => _x( 'The time interval in between iterations of when the event queue is processed.', 'settings', 'groundhogg' ),
                'type'      => 'dropdown',
                'atts'      => array(
                    'id'            => 'gh_queue_interval',
                    'name'          => 'gh_queue_interval',
                    'options'       => array(
                        'every_1_minutes' => 'Every 1 Minutes',
                        'every_5_minutes' => 'Every 5 Minutes',
                        'every_10_minutes' => 'Every 10 Minutes',
                    ),
                ),
            ),
            'gh_confirmation_page' => array(
                'id'        => 'gh_confirmation_page',
                'section'   => 'pages',
                'label'     => _x( 'Email Confirmation Page', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Page contacts see when they confirm their email.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_confirmation_page',
                    'id'    => 'gh_confirmation_page',
                    'data'  => $pops,
                ),
            ),
            'gh_unsubscribe_page' => array(
                'id'        => 'gh_unsubscribe_page',
                'section'   => 'pages',
                'label'     => _x( 'Unsubscribe Page', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Page contacts see when they unsubscribe.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_unsubscribe_page',
                    'id'    => 'gh_unsubscribe_page',
                    'data'  => $pops,
                ),
            ),
            'gh_email_preferences_page' => array(
                'id'        => 'gh_email_preferences_page',
                'section'   => 'pages',
                'label'     => _x( 'Email Preferences Page', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Page where contacts can manage their email preferences.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_email_preferences_page',
                    'id'    => 'gh_email_preferences_page',
                    'data'  => $pops,
                ),
            ),
            'gh_view_in_browser_page' => array(
                'id'        => 'gh_view_in_browser_page',
                'section'   => 'pages',
                'label'     => _x( 'View Email In Browser Page', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Page containing the shortcode [browser_view] so contacts can view an email in the browser in the event their email client looks funky.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_view_in_browser_page',
                    'id'    => 'gh_view_in_browser_page',
                    'data'  => $pops,
                ),
            ),
            'gh_privacy_policy' => array(
                'id'        => 'gh_privacy_policy',
                'section'   => 'compliance',
                'label'     => __( 'Privacy Policy' ),
                'desc'      => _x( 'Link to your privacy policy.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_privacy_policy',
                    'id'    => 'gh_privacy_policy',
                    'data'  => $pops,
                ),
            ),
            'gh_terms' => array(
                'id'        => 'gh_terms',
                'section'   => 'compliance',
                'label'     => _x( 'Terms & Conditions (Terms of Service)', 'settings', 'groundogg' ),
                'desc'      => _x( 'Link to your terms & conditions.', 'settings', 'groundhogg' ),
                'type'      => 'select2',
                'atts'      => array(
                    'name'  => 'gh_terms',
                    'id'    => 'gh_terms',
                    'data'  => $pops,
                ),
            ),
            'gh_strict_confirmation' => array(
                'id'        => 'gh_strict_confirmation',
                'section'   => 'compliance',
                'label'     => _x( 'Only send to confirmed emails.', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This will stop emails being sent to contacts who do not have confirmed emails outside of the below grace period.', 'settings', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_strict_confirmation[]',
                    'id'            => 'gh_strict_confirmation',
                    'value'         => 'on',
                ),
            ),
            'gh_confirmation_grace_period' => array(
                'id'        => 'gh_confirmation_grace_period',
                'section'   => 'compliance',
                'label'     => _x( 'Email confirmation grace Period', 'settings', 'groundhogg' ),
                'desc'      => _x( 'The number of days for which you can send an email to a contact after they are created but their email has not been confirmed. The default is 14 days.', 'settings', 'groundhogg' ),
                'type'      => 'number',
                'atts'      => array(
                    'id'            => 'gh_confirmation_grace_period',
                    'name'          => 'gh_confirmation_grace_period',
                    'placeholder'   => '14'
                ),

            ),
            'gh_enable_gdpr' => array(
                'id'        => 'gh_enable_gdpr',
                'section'   => 'compliance',
                'label'     => _x( 'Enable GDPR features.', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This will add a consent box to your forms as well as a "Delete Everything" Button to your email preferences page.', 'settings', 'groundhogg' ) ,
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_enable_gdpr[]',
                    'id'            => 'gh_enable_gdpr',
                    'value'         => 'on',
                ),
            ),
            'gh_strict_gdpr' => array(
                'id'        => 'gh_strict_gdpr',
                'section'   => 'compliance',
                'label'     => _x( 'Do not send email without consent.', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This will prevent your system from sending emails to contacts for which you do not have explicit consent. Only works if GDPR features are enabled.', 'settings', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_strict_gdpr[]',
                    'id'            => 'gh_strict_gdpr',
                    'value'         => 'on',
                ),
            ),
            'gh_enable_recaptcha' => array(
                'id'        => 'gh_enable_recaptcha',
                'section'   => 'captcha',
                'label'     => _x( 'Enable Recaptcha on forms', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Add a google recaptcha to all your forms made with the [gh_form] shortcode', 'settings', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_enable_recaptcha[]',
                    'id'            => 'gh_enable_recaptcha',
                    'value'         => 'on',
                ),
            ),
            'gh_recaptcha_site_key' => array(
                'id'        => 'gh_recaptcha_site_key',
                'section'   => 'captcha',
                'label'     => _x( 'Recaptcha Site Key', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This is the key which faces the users on the front-end', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'name'  => 'gh_recaptcha_site_key',
                    'id'    => 'gh_recaptcha_site_key',
                ),
            ),
            'gh_recaptcha_secret_key' => array(
                'id'        => 'gh_recaptcha_secret_key',
                'section'   => 'captcha',
                'label'     => _x( 'Recaptcha Secret Key', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Never ever ever share this with anyone!', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'name'  => 'gh_recaptcha_secret_key',
                    'id'    => 'gh_recaptcha_secret_key',
                ),
            ),
            'gh_bounce_inbox' => array(
                'id'        => 'gh_bounce_inbox',
                'section'   => 'bounces',
                'label'     => _x( 'Bounce Inbox', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This is the inbox which emails will be sent to.', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'type'  => 'email',
                    'name'  => 'gh_bounce_inbox',
                    'id'    => 'gh_bounce_inbox',
                    'placeholder' => 'bounce@' . ( ( substr( $_SERVER['SERVER_NAME'], 0, 4 ) == 'www.' ) ?  substr( $_SERVER['SERVER_NAME'], 4 ) : $_SERVER['SERVER_NAME'] ),
                ),
            ),
            'gh_bounce_inbox_password' => array(
                'id'        => 'gh_bounce_inbox_password',
                'section'   => 'bounces',
                'type'      => 'input',
                'label'     => _x( 'Bounce Inbox Password', 'settings', 'groundhogg' ),
                'desc'      => _x( 'This password to access the inbox.', 'settings', 'groundhogg' ),
                'atts' => array(
                    'type'  => 'password',
                    'name'  => 'gh_bounce_inbox_password',
                    'id'    => 'gh_bounce_inbox_password',
                ),
            ),
            'gh_email_token' => array(
                'id'        => 'gh_email_token',
                'section'   => 'service',
                'label'     => _x( 'Email & SMS Service Token', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Get this key from your <a target="_blank" href="https://www.groundhogg.io/account/manage/">Groundhogg Account Page.</a>', 'settings', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'type'  => 'password',
                    'name'  => 'gh_email_token',
                    'id'    => 'gh_email_token',
                ),
            ),
            'gh_send_with_gh_api' => array(
                'id'        => 'gh_send_with_gh_api',
                'section'   => 'service',
                'label'     => _x( 'Send Email With Groundhogg', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Send email using the Groundhogg Sending Service! This will only be used by emails sent with Groundhogg and not other WP emails. You will still be able to send SMS if this is disabled.', 'settings', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_send_with_gh_api[]',
                    'id'            => 'gh_send_with_gh_api',
                    'value'         => 'on',
                ),
            ),
            'gh_enable_api' => array(
                'id'        => 'gh_enable_api',
                'section'   => 'api_settings',
                'label'     => _x( 'Enable the Groundhogg API', 'settings', 'groundhogg' ),
                'desc'      => _x( 'Enabling this will allow other platforms to access information on this site.', 'settings', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    'name'          => 'gh_enable_api',
                    'id'            => 'gh_enable_api',
                    'value'         => 'on',
                ),
            ),
        ) );
    }

    /**
     * Register all the settings
     */
    public function register_settings()
    {

        do_action( 'wpgh_settings_pre_register_settings', $this );

        foreach( $this->settings as $id => $setting ){
//            print_r($setting[ 'section' ]);
            add_settings_field( $setting['id'], $setting['label'], array( $this, 'settings_callback' ), 'gh_' . $this->sections[ $setting[ 'section' ] ][ 'tab' ], 'gh_' . $setting[ 'section' ], $setting );
            register_setting( 'gh_' . $this->sections[ $setting[ 'section' ] ][ 'tab' ], $setting['id'] );
        }
    }

    /**
     * Add a tab to the settings page
     *
     * @param string $id if of the tab
     * @param string $title title of the tab
     * @return bool
     */
    public function add_tab( $id='', $title='' )
    {
        if ( ! $id || ! $title )
            return false;


        $this->tabs[ $id ] = array(
            'id' => $id,
            'title' => $title,
        );

        return true;
    }

    /**
     * Add a section to a tab
     *
     * @param string $id id of the section
     * @param string $title title of the section
     * @param string $tab the tab
     * @return bool
     */
    public function add_section( $id='', $title='', $tab='' )
    {
        if ( ! $id || ! $title || ! $tab )
            return false;


        $this->sections[ $id ] = array(
            'id'    => $id,
            'title' => $title,
            'tab'   => $tab,
        );

        return true;
    }

    /**
     * Add a setting to the page
     *
     * @param array $args
     * @return bool
     */
    public function add_setting( $args=array())
    {
        $setting = wp_parse_args( $args, array(
                'id'        => '',
                'section'   => 'misc',
                'label'     => '',
                'desc'      => '',
                'type'      => 'input',
                'atts' => array(
                    //keep brackets for backwards compat
                    'name'          => '',
                    'id'            => '',
                ) )
        );

        if ( empty( $setting[ 'id' ] ) ){
            return false;
        }

        $this->settings[ $setting[ 'id' ] ] = $setting;

        return true;
    }

    /**
     * Return the id of the active tab
     *
     * @return string
     */
    private function active_tab()
    {
        return isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'general';
    }

    /**
     * Return whether a tab has settings or not.
     *
     * @param $tab string the ID of the tab
     * @return bool
     */
    private function tab_has_settings( $tab )
    {
        global $wp_settings_sections;
        return isset( $wp_settings_sections[ 'gh_' . $tab ] );
    }

    /**
     * Output the settings content
     */
    public function settings_content()
    {
        ?>
        <style>
            .select2{
                max-width: 300px;
            }
        </style>
        <div class="wrap">
            <h1><?php printf( '%s %s' , WPGH()->brand(), __( 'Settings' ) ); ?></h1>
            <?php
            settings_errors();
            WPGH()->notices->notices();
            $action = $this->tab_has_settings( $this->active_tab() ) ? 'options.php' : ''; ?>
            <form method="POST" enctype="multipart/form-data" action="<?php echo $action; ?>">

                <!-- BEGIN TABS -->
                <h2 class="nav-tab-wrapper">
                    <?php foreach ( $this->tabs as $id => $tab ): ?>
                        <a href="?page=gh_settings&tab=<?php echo $tab[ 'id' ]; ?>" class="nav-tab <?php echo $this->active_tab() ==  $tab[ 'id' ] ? 'nav-tab-active' : ''; ?>"><?php _e(  $tab[ 'title' ], 'groundhogg'); ?></a>
                    <?php endforeach; ?>
                </h2>
                <!-- END TABS -->

                <!-- BEGIN SETTINGS -->
                <?php
                if ( $this->tab_has_settings( $this->active_tab() ) ){

                    settings_fields( 'gh_' . $this->active_tab() );
                    do_settings_sections( 'gh_' . $this->active_tab() );
                    do_action( "groundhogg/settings/{$this->active_tab()}/after_settings" );
                    submit_button();

                }

                do_action( 'gh_tab_' . $this->active_tab() );
                do_action( "groundhogg/settings/{$this->active_tab()}/after_submit" );
                ?>
                <!-- END SETTINGS -->
            </form>
        </div> <?php
    }

    public function settings_callback( $field )
    {
        $value = wpgh_get_option( $field['id'] );

        switch ( $field['type'] ) {

            case 'select2':
            case 'dropdown_emails':
            case 'tag_picker':
                $field[ 'atts' ][ 'selected' ] = array( $value );
                break;
            case 'dropdown':
                $field[ 'atts' ][ 'selected' ] = $value;
                break;
            case 'checkbox':
                $field[ 'atts' ][ 'checked' ] = (bool) $value;
                break;
            case 'input':
            default:
                $field[ 'atts' ][ 'value' ] = $value;
                break;
        }

        echo call_user_func( array( WPGH()->html, $field[ 'type' ] ), $field[ 'atts' ] );


        if( isset( $field['desc'] ) && $desc = $field['desc'] ) {
            printf( '<p class="description">%s</p>', $desc );
        }
    }
}