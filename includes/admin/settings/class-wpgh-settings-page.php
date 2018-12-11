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


        $this->tabs     = $this->get_default_tabs();
        $this->sections = $this->get_default_sections();
        $this->settings = $this->get_default_settings();


        add_action( 'admin_menu', array( $this, 'register' ) );
        add_action( 'admin_init', array( $this, 'register_sections' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        if ( ! class_exists( 'WPGH_Extensions_Manager' ) )
            include_once dirname( __FILE__ ) . '/../extensions/module-manager.php';

        add_action( 'admin_init', array( 'WPGH_Extension_Manager', 'check_for_updates' ) );

        if ( isset( $_GET['page'] ) && $_GET['page'] === 'gh_settings' ) {

            add_action( 'admin_init', array( 'WPGH_Extension_Manager', 'perform_activation' ) );
        }

        if ( ( isset( $_GET['page'] ) && $_GET['page'] === 'gh_settings' ) || wp_doing_ajax() ){
            $this->importer = new WPGH_Bulk_Contact_Manager();
        }

    }

    /* Register the page */
    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            'Settings',
            'Settings',
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
        return array(
            'general'      => array(
                'id'    => 'general',
                'title' => __( 'General' )
            ),
            'marketing'    =>  array(
                'id'    => 'marketing',
                'title' => __( 'Marketing' )
            ),
            'email'        =>  array(
                'id'    => 'email',
                'title' => __( 'Email' )
            ),
            'tools'        =>  array(
                'id'    => 'tools',
                'title' => __( 'Tools' )
            ),
            'extensions'   =>  array(
                'id'    => 'extensions',
                'title' => __( 'Licenses' )
            ),
        );
    }

    /**
     * Returns a list of all the default sections
     *
     * @return array
     */
    private function get_default_sections()
    {
        return array(
            'business_info' => array(
                'id'    => 'business_info',
                'title' => __( 'Business Settings' ),
                'tab'   => 'general'
            ),
            'misc_info' => array(
                'id'    => 'misc_info',
                'title' => __( 'Misc Settings' ),
                'tab'   => 'general'
            ),
            'pages' => array(
                'id'    => 'pages',
                'title' => __( 'Pages' ),
                'tab'   => 'marketing'
            ),
            'captcha' => array(
                'id'    => 'captcha',
                'title' => __( 'Captcha' ),
                'tab'   => 'marketing'
            ),
            'compliance' => array(
                'id'    => 'compliance',
                'title' => __( 'Compliance' ),
                'tab'   => 'marketing'
            ),
            'bounces' => array(
                'id'    => 'bounces',
                'title' => __( 'Email Bounces' ),
                'tab'   => 'email'
            ),
            'service' => array(
                'id'    => 'service',
                'title' => __( 'Groundhogg Email Service' ),
                'tab'   => 'email'
            ),
        );
    }

    /**
     * Add the default settings sections
     */
    public function register_sections()
    {

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

        return array(
            'gh_business_name' => array(
                'id'        => 'gh_business_name',
                'section'   => 'business_info',
                'label'     => __( 'Business Name', 'groundhogg' ),
                'desc'      => __( 'Your business name as it appears in the email footer.' ),
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
                'label'     => __( 'Street Address 1', 'groundhogg' ),
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg' ),
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
                'label'     => __( 'Street Address 2', 'groundhogg' ),
                'desc'      => __( '(Optional) As it should appear in your email footer.', 'groundhogg' ),
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
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg' ),
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
                'label'     => __( 'Postal/Zip Code', 'groundhogg' ),
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg' ),
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
                'label'     => __( 'State/Province/Region', 'groundhogg' ),
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg'),
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
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg' ),
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
                'desc'      => __( 'As it should appear in your email footer.', 'groundhogg' ),
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
                'label'     => __( 'Delete Groundhogg Data', 'groundhogg' ),
                'desc'      => __( 'Delete all information when uninstalling. This cannot be undone.', 'groundhogg' ),
                'type' => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_uninstall_on_delete[]',
                    'id'            => 'gh_uninstall_on_delete',
                    'value'         => 'on',
                ),
            ),
            'gh_max_events' => array(
                'id'        => 'gh_max_events',
                'section'   => 'misc_info',
                'label'     => __( 'Max Queued Events', 'groundhogg' ),
                'desc'      => __( 'The maximum number of events that can be run during a single process of the event queue. For larger lists you may want to set this at a lower number for performance reasons.', 'groundhogg' ),
                'type'      => 'number',
                'atts'      => array(
                    'id'            => 'gh_max_events',
                    'name'          => 'gh_max_events',
                    'placeholder'   => '999999'
                ),
            ),
            'gh_confirmation_page' => array(
                'id'        => 'gh_confirmation_page',
                'section'   => 'pages',
                'label'     => __( 'Email Confirmation Page', 'groundhogg' ),
                'desc'      => __( 'Page contacts see when they confirm their email.', 'groundhogg' ),
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
                'label'     => __( 'Unsubscribe Page', 'groundhogg' ),
                'desc'      => __( 'Page contacts see when they unsubscribe.', 'groundhogg' ),
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
                'label'     => __( 'Email Preferences Page', 'groundhogg' ),
                'desc'      => __( 'Page where contacts can manage their email preferences.', 'groundhogg' ),
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
                'label'     => __( 'View Email In Browser Page', 'groundhogg' ),
                'desc'      => __( 'Page containing the shortcode [browser_view] so contacts can view an email in the browser in the event their email client looks funky.', 'groundhogg' ),
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
                'desc'      => __( 'Link to your privacy policy.', 'groundhogg' ),
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
                'label'     => __( 'Terms & Conditions (Terms of Service)', 'groundogg' ),
                'desc'      => __( 'Link to your terms & conditions.', 'groundhogg' ),
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
                'label'     => __( 'Only send to confirmed emails.', 'groundhogg' ),
                'desc'      => __( 'This will stop emails being sent to contacts who do not have confirmed emails outside of the below grace period.', 'groundhogg' ),
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
                'label'     => __( 'Email confirmation grace Period', 'groundhogg' ),
                'desc'      => __( 'The number of days for which you can send an email to a contact after they are created but their email has not been confirmed. The default is 14 days.', 'groundhogg' ),
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
                'label'     => __( 'Enable GDPR features.', 'groundhogg' ),
                'desc'      => __( 'This will add a consent box to your forms as well as a "Delete Everything" Button to your email preferences page.', 'groundhogg') ,
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
                'label'     => __( 'Do not send email without consent.', 'groundhogg' ),
                'desc'      => __( 'This will prevent your system from sending emails to contacts for which you do not have explicit consent. Only works if GDPR features are enabled.', 'groundhogg' ),
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
                'label'     => __( 'Enable Recaptcha on forms', 'groundhogg' ),
                'desc'      => __( 'Add a google recaptcha to all your forms made with the [gh_form] shortcode', 'groundhogg' ),
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
                'label'     => __( 'Recaptcha Site Key', 'groundhogg' ),
                'desc'      => __( 'This is the key which faces the users on the front-end', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'name'  => 'gh_recaptcha_site_key',
                    'id'    => 'gh_recaptcha_site_key',
                ),
            ),
            'gh_recaptcha_secret_key' => array(
                'id'        => 'gh_recaptcha_secret_key',
                'section'   => 'captcha',
                'label'     => __( 'Recaptcha Secret Key', 'groundhogg' ),
                'desc'      => __( 'Never ever ever share this with anyone!', 'groundhogg' ),
                'type'      => 'input',
                'atts' => array(
                    'name'  => 'gh_recaptcha_secret_key',
                    'id'    => 'gh_recaptcha_secret_key',
                ),
            ),
            'gh_bounce_inbox' => array(
                'id'        => 'gh_bounce_inbox',
                'section'   => 'bounces',
                'label'     => __( 'Bounce Inbox', 'groundhogg' ),
                'desc'      => __( 'This is the inbox which emails will be sent to.', 'groundhogg' ),
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
                'label'     => __( 'Bounce Inbox Password', 'groundhogg' ),
                'desc'      => __( 'This password to access the inbox.', 'groundhogg' ),
                'atts' => array(
                    'type'  => 'password',
                    'name'  => 'gh_bounce_inbox_password',
                    'id'    => 'gh_bounce_inbox_password',
                ),
            ),
            'gh_email_token' => array(
                'id'        => 'gh_email_token',
                'section'   => 'service',
                'label'     => __( 'Email Service Token', 'groundhogg' ),
                'desc'      => __( 'Get this key from your <a target="_blank" href="https://www.groundhogg.io/account/manage/">Groundhogg Account Page.', 'groundhogg' ),
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
                'label'     => __( 'Send Email With Groundhogg', 'groundhogg' ),
                'desc'      => __( 'Choose to send email with API.', 'groundhogg' ),
                'type'      => 'checkbox',
                'atts' => array(
                    'label'         => __( 'Enable' ),
                    //keep brackets for backwards compat
                    'name'          => 'gh_send_with_gh_api[]',
                    'id'            => 'gh_send_with_gh_api',
                    'value'         => 'on',
                ),
            ),

        );
    }

    /**
     * Register all the settings
     */
    public function register_settings()
    {
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
                'label'     => __( '', 'groundhogg' ),
                'desc'      => __( '', 'groundhogg' ),
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

        $this->sections[ $setting[ 'id' ] ] = $setting;

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
            <h1>Groundhogg <?php _e( 'Settings' ); ?></h1>
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
                    submit_button();

                }

                do_action( 'gh_tab_' . $this->active_tab() );
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
                $field[ 'atts' ][ 'selected' ] = array( $value );
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
            printf( '<p class="description">%s </p>', $desc );
        }
    }
}