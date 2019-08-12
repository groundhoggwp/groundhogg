<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

class Scripts
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [ $this, 'register_frontend_scripts' ] );
        add_action('wp_enqueue_scripts', [ $this, 'register_frontend_styles' ] );

        add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_styles' ] );
        add_action('admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );
    }

    public function is_script_debug_enabled()
    {
        return Plugin::$instance->settings->is_option_enabled('script_debug');
    }

    /**
     * Register frontend scripts.
     */
    public function register_frontend_scripts()
    {
        $IS_MINIFIED = $this->is_script_debug_enabled() ? '' : '.min';

        wp_register_script('groundhogg-frontend', GROUNDHOGG_ASSETS_URL . 'js/frontend/frontend' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true );
        wp_register_script('groundhogg-ajax-form', GROUNDHOGG_ASSETS_URL . 'js/frontend/ajax-form' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-frontend' ], GROUNDHOGG_VERSION, true );
        wp_register_script('manage-preferences', GROUNDHOGG_ASSETS_URL . 'js/frontend/preferences' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION);
        wp_register_script('fullframe', GROUNDHOGG_ASSETS_URL . 'js/frontend/fullframe' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION);

        // Integrations
        wp_register_script( 'groundhogg-elementor-integration', GROUNDHOGG_URL . 'integrations/elementor' . $IS_MINIFIED .'.js', [], GROUNDHOGG_VERSION, true );

        if (!Plugin::$instance->settings->is_option_enabled('disable_api')) {
            wp_localize_script('groundhogg-frontend', 'Groundhogg', array(
                'page_view_endpoint' => rest_url( 'gh/v3/tracking/page-view/'),
                'form_impression_endpoint' => rest_url( 'gh/v3/tracking/form-impression/'),
                'form_submission_endpoint' => rest_url( 'gh/v3/forms/submit/'),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                '_wpnonce' => wp_create_nonce('wp_rest' ),
                '_ghnonce' => wp_create_nonce('groundhogg_frontend' )
            ));
        } else {
            /* backwards compat */
            wp_localize_script('groundhogg-frontend', 'Groundhogg', array(
                'page_view_endpoint' => admin_url('admin-ajax.php?action=gh_page_view'),
                'form_impression_endpoint' => admin_url('admin-ajax.php?action=gh_form_impression'),
                '_wpnonce' => wp_create_nonce('wp_rest'),
                '_ghnonce' => wp_create_nonce('groundhogg_frontend')
            ));
        }

        wp_enqueue_script( 'groundhogg-frontend' );

        do_action('groundhogg/scripts/after_register_frontend_scripts', $this->is_script_debug_enabled(), $IS_MINIFIED );
    }

    /**
     * Register frontend Styles
     */
    public function register_frontend_styles()
    {
        wp_register_style('jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION);
        wp_register_style('manage-preferences', GROUNDHOGG_ASSETS_URL . 'css/frontend/preferences.css', [], GROUNDHOGG_VERSION );
        wp_register_style('groundhogg-managed-page', GROUNDHOGG_ASSETS_URL . 'css/frontend/managed-page.css', [], GROUNDHOGG_VERSION );
        wp_register_style('groundhogg-loader', GROUNDHOGG_ASSETS_URL . 'css/frontend/loader.css', [], GROUNDHOGG_VERSION);

        do_action('groundhogg/scripts/after_register_frontend_styles');
    }

    /**
     * Register all the required admin scripts.
     */
    public function register_admin_scripts()
    {
        // Whether to include minified files or not.
        $IS_MINIFIED = $this->is_script_debug_enabled() ? '' : '.min';
        wp_register_script( 'groundhogg-elementor-integration', GROUNDHOGG_URL . 'integrations/elementor' . $IS_MINIFIED .'.js', [], GROUNDHOGG_VERSION, true );

        // Select 2
        wp_register_script('select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/js/select2.full' . $IS_MINIFIED . '.js', ['jquery'] );

        // Integrations

        // Beautify JS
        wp_register_script('beautify-js', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify.min.js');
        wp_register_script('beautify-css', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-css.min.js');
        wp_register_script('beautify-html', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-html.min.js');

        // PapaParse
        wp_register_script('papaparse', GROUNDHOGG_ASSETS_URL . 'lib/papa-parse/papaparse' . $IS_MINIFIED . '.js');

        // Sticky Sidebar
        wp_register_script('sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/sticky-sidebar.js' );
        wp_register_script('jquery-sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/jquery.sticky-sidebar.js', ['jquery'] );

        // Flot
        wp_register_script('jquery-flot', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot' . $IS_MINIFIED . '.js' );
        wp_register_script('jquery-flot-pie', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.pie' . $IS_MINIFIED . '.js', [ 'jquery-flot' ] );
        wp_register_script('jquery-flot-time', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.time' . $IS_MINIFIED . '.js', [ 'jquery-flot' ] );
        wp_register_script('jquery-flot-categories', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.categories' . $IS_MINIFIED . '.js', [ 'jquery-flot' ]);

        // Basic Admin Scripts
        wp_register_script('groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'js/admin/admin' . $IS_MINIFIED . '.js', ['jquery', 'select2', 'jquery-ui-autocomplete'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-functions', GROUNDHOGG_ASSETS_URL . 'js/admin/functions' . $IS_MINIFIED . '.js', ['jquery', 'select2', 'jquery-ui-autocomplete'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-color', GROUNDHOGG_ASSETS_URL . 'js/admin/color-picker' . $IS_MINIFIED . '.js', ['jquery', 'wp-color-picker'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/contact-editor' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'js/admin/inline-edit-contacts' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'js/admin/dashboard' . $IS_MINIFIED . '.js', ['jquery', 'papaparse'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-email-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor' . $IS_MINIFIED . '.js', ['jquery', 'sticky-sidebar', 'jquery-ui-resizable', 'groundhogg-admin-functions' ], GROUNDHOGG_VERSION, true);

        wp_register_script('groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-editor' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-functions', 'sticky-sidebar' ], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-funnel-editor-v2', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-editor-v2' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-functions', 'sticky-sidebar' ], GROUNDHOGG_VERSION, true);

        wp_register_script('groundhogg-admin-form-builder', GROUNDHOGG_ASSETS_URL . 'js/admin/form-builder' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'js/admin/iframe-checker' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, false);
        wp_register_script('groundhogg-admin-import-export', GROUNDHOGG_ASSETS_URL . 'js/admin/import-export' . $IS_MINIFIED . '.js', ['jquery', 'papaparse'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-link-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/link-picker' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-media-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/media-picker' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'js/admin/modal' . $IS_MINIFIED . '.js', ['jquery', 'wp-color-picker'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-replacements', GROUNDHOGG_ASSETS_URL . 'js/admin/replacements' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-modal'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-admin-simple-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/simple-editor' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);

        // Email Blocks
        wp_register_script('groundhogg-email-button', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/button' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-email-divider', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/divider' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-email-html', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/html' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-email-image', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/image' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-email-spacer', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/spacer' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-email-text', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/text' . $IS_MINIFIED . '.js', ['jquery', 'groundhogg-admin-email-editor'], GROUNDHOGG_VERSION, true);

        // Funnel Elements
        wp_register_script('groundhogg-funnel-email', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/email' . $IS_MINIFIED . '.js', ['jquery','groundhogg-admin-modal'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-funnel-delay-timer', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/delay-timer' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-funnel-webhook', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/webhook' . $IS_MINIFIED . '.js', ['jquery'], GROUNDHOGG_VERSION, true);
        wp_register_script('groundhogg-funnel-form-integration', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/form-integration' . $IS_MINIFIED . '.js', ['jquery','groundhogg-admin', 'groundhogg-admin-modal'], GROUNDHOGG_VERSION, true);

        wp_enqueue_script( 'groundhogg-admin-functions' );

        wp_localize_script( 'groundhogg-admin', 'groundhogg_endpoints', [
            'tags'      => rest_url('gh/v3/tags?select2=true'),
            'emails'    => rest_url('gh/v3/emails?select2=true&status[]=ready&status[]=draft'),
            'sms'       => rest_url('gh/v3/sms?select2=true'),
            'contacts'  => rest_url('gh/v3/contacts?select2=true'),
        ]  );

        wp_localize_script( 'groundhogg-admin', 'groundhogg_nonces', [
            '_wpnonce'  => wp_create_nonce(),
            '_wprest'   => wp_create_nonce( 'wp_rest' ),
            '_adminajax' => wp_create_nonce( 'admin_ajax' ),
            '_ajax_linking_nonce' => wp_create_nonce( 'internal-linking' ),
        ]  );

        wp_localize_script( 'groundhogg-admin', 'Groundhogg', [
            'test' => 'Hello World!'
        ]  );

        do_action('groundhogg/scripts/after_register_admin_scripts', $this->is_script_debug_enabled(), $IS_MINIFIED );
    }

    /**
     * Register all the required admin styles.
     */
    public function register_admin_styles()
    {
        wp_register_style('jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css');
        wp_register_style('select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/css/select2.min.css');

        wp_register_style('groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'css/admin/admin.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-welcome', GROUNDHOGG_ASSETS_URL . 'css/admin/welcome.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'css/admin/contacts.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/contact-editor.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-email-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/email-editor.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-funnel-editor-v2', GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor-v2.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'css/admin/dashboard.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'css/admin/modal.css', ['wp-color-picker'], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-extensions', GROUNDHOGG_ASSETS_URL . 'css/admin/extensions.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'css/admin/iframe.css', [], GROUNDHOGG_VERSION);
        wp_register_style('groundhogg-admin-simple-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/simple-editor.css', [], GROUNDHOGG_VERSION);

        wp_register_style('groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION);

        do_action('groundhogg/scripts/after_register_admin_styles');
    }

}
