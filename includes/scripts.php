<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-10
 * Time: 11:28 AM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register frontend scripts.
 */
function wpgh_register_frontend_scripts()
{
    $IS_MINIFIED = wpgh_is_option_enabled( 'gh_script_debug' ) ? '' : '.min' ;

    wp_register_script( 'groundhogg-frontend',GROUNDHOGG_ASSETS_URL . 'js/frontend' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION );
    wp_register_script( 'groundhogg-email-iframe',GROUNDHOGG_ASSETS_URL . 'js/email' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION );

    if ( ! wpgh_is_option_enabled( 'gh_disable_api' ) ){
        wp_localize_script( 'groundhogg-frontend', 'gh_frontent_object', array(
            'page_view_endpoint'        => site_url( 'wp-json/gh/v3/elements/page-view/' ),
            'form_impression_endpoint'  => site_url( 'wp-json/gh/v3/elements/form-impression/' ),
            '_wpnonce'                  => wp_create_nonce( 'wp_rest' ),
            '_ghnonce'                  => wp_create_nonce( 'groundhogg_frontend' )
        ));
    } else {
        /* backwards compat */
        wp_localize_script( 'groundhogg-frontend', 'gh_frontent_object', array(
            'page_view_endpoint'        => admin_url( 'admin-ajax.php?action=gh_page_view' ),
            'form_impression_endpoint'  => admin_url( 'admin-ajax.php?action=gh_form_impression' ),
            '_wpnonce'                  => wp_create_nonce( 'wp_rest' ),
            '_ghnonce'                  => wp_create_nonce( 'groundhogg_frontend' )
        ));
    }

    do_action( 'groundhogg/frontend/after_register_scripts' );
}

add_action( 'wp_enqueue_scripts', 'wpgh_register_frontend_scripts' );

/**
 * Register frontend Styles
 */
function wpgh_register_frontend_styles()
{
    wp_register_style( 'jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-frontend',GROUNDHOGG_ASSETS_URL . 'css/frontend.css', [], GROUNDHOGG_VERSION );

    do_action( 'groundhogg/frontend/after_register_styles' );
}

add_action( 'wp_enqueue_scripts', 'wpgh_register_frontend_styles' );

/**
 * Register all the required admin scripts.
 */
function wpgh_register_admin_scripts()
{
    // Whether to include minified files or not.
    $IS_MINIFIED = wpgh_is_option_enabled( 'gh_script_debug' ) ? '' : '.min' ;

    // Select 2
    wp_register_script( 'select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/js/select2.full' . $IS_MINIFIED .'.js' , [ 'jquery' ] );

    // Code Mirror
    wp_register_script( 'codemirror', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/codemirror.js' );
    wp_register_script( 'codemirror-mode-css', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/modes/css.js' );
    wp_register_script( 'codemirror-mode-xml', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/modes/xml.js' );
    wp_register_script( 'codemirror-mode-js', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/modes/javascript.js' );
    wp_register_script( 'codemirror-mode-html', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/modes/htmlmixed.js' );

    // Beautify JS
    wp_register_script( 'beautify-js', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify.min.js' );
    wp_register_script( 'beautify-css', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-css.min.js' );
    wp_register_script( 'beautify-html', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-html.min.js' );

    // PapaParse
    wp_register_script( 'papaparse', GROUNDHOGG_ASSETS_URL . 'lib/papa-parse/papaparse' . $IS_MINIFIED .'.js' );

    // Sticky Sidebar
    wp_register_script( 'sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/sticky-sidebar.js' );
    wp_register_script( 'jquery-sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/jquery.sticky-sidebar.js', [ 'jquery' ] );

    // Flot
    wp_register_script( 'jquery-flot',              GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-pie',          GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.pie' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-time',         GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.time' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-categories',   GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.categories' . $IS_MINIFIED .'.js' );

    // Basic Admin Scripts
    wp_register_script( 'groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'js/admin/admin' . $IS_MINIFIED .'.js', [ 'jquery', 'select2', 'jquery-ui-autocomplete' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-color', GROUNDHOGG_ASSETS_URL . 'js/admin/color-picker' . $IS_MINIFIED .'.js', [ 'jquery', 'wp-color-picker' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/contact-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'js/admin/inline-edit-contacts' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'js/admin/dashboard' . $IS_MINIFIED .'.js', [ 'jquery', 'papaparse' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-email-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor' . $IS_MINIFIED .'.js', [ 'jquery', 'sticky-sidebar', 'jquery-sticky-sidebar' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-form-builder', GROUNDHOGG_ASSETS_URL . 'js/admin/form-builder' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'js/admin/iframe-checker' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-import-export', GROUNDHOGG_ASSETS_URL . 'js/admin/import-export' . $IS_MINIFIED .'.js', [ 'jquery', 'papaparse' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-link-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/link-picker' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-media-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/media-picker' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'js/admin/modal' . $IS_MINIFIED .'.js', [ 'jquery', 'wp-color-picker' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-admin-simple-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/simple-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

    // Email Blocks
    wp_register_script( 'groundhogg-email-button', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/button' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-email-divider', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/divider' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-email-html', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/html' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-email-image', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/image' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-email-spacer', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/spacer' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
    wp_register_script( 'groundhogg-email-text', GROUNDHOGG_ASSETS_URL . 'js/admin/email-blocks/text' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

    // Funnel Elements
    wp_register_script( 'groundhogg-funnel-email', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-elements/email' . $IS_MINIFIED .'.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

    // LOCALIZE ANY REQUIRED SCRIPTS
    if ( ! wpgh_is_option_enabled( 'gh_disable_api' ) ){
        /* Load improved picker request urls */
        wp_localize_script( 'groundhogg-admin', 'gh_admin_object', [
            'tags_endpoint'         => site_url( 'wp-json/gh/v3/tags?select2=true' ),
            'emails_endpoint'       => site_url( 'wp-json/gh/v3/emails?select2=true' ),
            'sms_endpoint'          => site_url( 'wp-json/gh/v3/sms?select2=true' ),
            'contacts_endpoint'     => site_url( 'wp-json/gh/v3/contacts?select2=true' ),
            'nonce'                 => wp_create_nonce( 'wp_rest' ),
            '_ajax_linking_nonce'   => wp_create_nonce( 'internal-linking' )
        ] );
    } else {

        /* Backwards compat */
        wp_localize_script( 'groundhogg-admin', 'gh_admin_object', [
            'tags_endpoint'         => admin_url( 'admin-ajax.php?action=gh_get_tags' ),
            'emails_endpoint'       => admin_url( 'admin-ajax.php?action=gh_get_emails' ),
            'sms_endpoint'          => admin_url( 'admin-ajax.php?action=gh_get_sms' ),
            'contacts_endpoint'     => admin_url( 'admin-ajax.php?action=gh_get_contacts' ),
            'nonce'                 => wp_create_nonce( 'admin_ajax' ),
            '_ajax_linking_nonce'   => wp_create_nonce( 'internal-linking' )
        ] );
    }

    do_action( 'groundhogg/admin/after_register_scripts' );
}

add_action( 'admin_enqueue_scripts', 'wpgh_register_admin_scripts' );

/**
 * Register all the required admin styles.
 */
function wpgh_register_admin_styles()
{
    wp_register_style( 'jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css' );
    wp_register_style( 'select2',   GROUNDHOGG_ASSETS_URL . 'lib/select2/css/select2.min.css' );
    wp_register_style( 'codemirror', GROUNDHOGG_ASSETS_URL . 'lib/codemirror/codemirror.css'  );

    wp_register_style( 'groundhogg-admin',GROUNDHOGG_ASSETS_URL . 'css/admin/admin.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-welcome',GROUNDHOGG_ASSETS_URL . 'css/admin/welcome.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-contact-inline',GROUNDHOGG_ASSETS_URL . 'css/admin/contacts.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-contact-editor',GROUNDHOGG_ASSETS_URL . 'css/admin/contact-editor.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-email-editor',GROUNDHOGG_ASSETS_URL . 'css/admin/email-editor.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-funnel-editor',GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-dashboard',GROUNDHOGG_ASSETS_URL . 'css/admin/dashboard.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-modal',GROUNDHOGG_ASSETS_URL . 'css/admin/modal.css', [ 'wp-color-picker' ], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-extensions',GROUNDHOGG_ASSETS_URL . 'css/admin/extensions.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-iframe',GROUNDHOGG_ASSETS_URL . 'css/admin/iframe.css', [], GROUNDHOGG_VERSION );
    wp_register_style( 'groundhogg-admin-simple-editor',GROUNDHOGG_ASSETS_URL . 'css/admin/simple-editor.css', [], GROUNDHOGG_VERSION );

    do_action( 'groundhogg/admin/after_register_styles' );
}

add_action( 'admin_enqueue_scripts', 'wpgh_register_admin_styles' );
