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

    wp_register_script( 'groundhogg-frontend',WPGH_ASSETS_FOLDER . 'js/frontend' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION );
    wp_register_script( 'groundhogg-email-iframe',WPGH_ASSETS_FOLDER . 'js/email' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION );

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
    wp_register_style( 'jquery-ui', WPGH_ASSETS_FOLDER . 'lib/jquery-ui/jquery-ui.min.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-frontend',WPGH_ASSETS_FOLDER . 'css/frontend.css', [], WPGH_VERSION );

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
    wp_register_script( 'select2', WPGH_ASSETS_FOLDER . 'lib/select2/js/select2.full' . $IS_MINIFIED .'.js' , [ 'jquery' ] );

    // Code Mirror
    wp_register_script( 'codemirror', WPGH_ASSETS_FOLDER . 'lib/codemirror/codemirror.js' );
    wp_register_script( 'codemirror-mode-css', WPGH_ASSETS_FOLDER . 'lib/codemirror/modes/css.js' );
    wp_register_script( 'codemirror-mode-xml', WPGH_ASSETS_FOLDER . 'lib/codemirror/modes/xml.js' );
    wp_register_script( 'codemirror-mode-js', WPGH_ASSETS_FOLDER . 'lib/codemirror/modes/javascript.js' );
    wp_register_script( 'codemirror-mode-html', WPGH_ASSETS_FOLDER . 'lib/codemirror/modes/htmlmixed.js' );

    // Beautify JS
    wp_register_script( 'beautify-js', WPGH_ASSETS_FOLDER . 'lib/js-beautify/beautify.min.js' );
    wp_register_script( 'beautify-css', WPGH_ASSETS_FOLDER . 'lib/js-beautify/beautify-css.min.js' );
    wp_register_script( 'beautify-html', WPGH_ASSETS_FOLDER . 'lib/js-beautify/beautify-html.min.js' );

    // PapaParse
    wp_register_script( 'papaparse', WPGH_ASSETS_FOLDER . 'lib/papa-parse/papaparse' . $IS_MINIFIED .'.js' );

    // Sticky Sidebar
    wp_register_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . 'lib/sticky-sidebar/sticky-sidebar.js' );
    wp_register_script( 'jquery-sticky-sidebar', WPGH_ASSETS_FOLDER . 'lib/sticky-sidebar/jquery.sticky-sidebar.js', [ 'jquery' ] );

    // Flot
    wp_register_script( 'jquery-flot',              WPGH_ASSETS_FOLDER . 'lib/flot/jquery.flot' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-pie',          WPGH_ASSETS_FOLDER . 'lib/flot/jquery.flot.pie' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-time',         WPGH_ASSETS_FOLDER . 'lib/flot/jquery.flot.time' . $IS_MINIFIED .'.js' );
    wp_register_script( 'jquery-flot-categories',   WPGH_ASSETS_FOLDER . 'lib/flot/jquery.flot.categories' . $IS_MINIFIED .'.js' );

    // Basic Admin Scripts
    wp_register_script( 'groundhogg-admin', WPGH_ASSETS_FOLDER . 'js/admin/admin' . $IS_MINIFIED .'.js', [ 'jquery', 'select2', 'jquery-ui-autocomplete' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-color', WPGH_ASSETS_FOLDER . 'js/admin/color-picker' . $IS_MINIFIED .'.js', [ 'jquery', 'wp-color-picker' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-contact-editor', WPGH_ASSETS_FOLDER . 'js/admin/contact-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-contact-inline', WPGH_ASSETS_FOLDER . 'js/admin/inline-edit-contacts' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-dashboard', WPGH_ASSETS_FOLDER . 'js/admin/dashboard' . $IS_MINIFIED .'.js', [ 'jquery', 'papaparse' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-email-editor', WPGH_ASSETS_FOLDER . 'js/admin/email-editor' . $IS_MINIFIED .'.js', [ 'jquery', 'sticky-sidebar', 'jquery-sticky-sidebar' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-funnel-editor', WPGH_ASSETS_FOLDER . 'js/admin/funnel-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-form-builder', WPGH_ASSETS_FOLDER . 'js/admin/form-builder' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-iframe', WPGH_ASSETS_FOLDER . 'js/admin/iframe-checker' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-import-export', WPGH_ASSETS_FOLDER . 'js/admin/import-export' . $IS_MINIFIED .'.js', [ 'jquery', 'papaparse' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-link-picker', WPGH_ASSETS_FOLDER . 'js/admin/link-picker' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-media-picker', WPGH_ASSETS_FOLDER . 'js/admin/media-picker' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-modal', WPGH_ASSETS_FOLDER . 'js/admin/modal' . $IS_MINIFIED .'.js', [ 'jquery', 'wp-color-picker' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-admin-simple-editor', WPGH_ASSETS_FOLDER . 'js/admin/simple-editor' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );

    // Email Blocks
    wp_register_script( 'groundhogg-email-button', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/button' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-email-divider', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/divider' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-email-html', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/html' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-email-image', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/image' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-email-spacer', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/spacer' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );
    wp_register_script( 'groundhogg-email-text', WPGH_ASSETS_FOLDER . 'js/admin/email-blocks/text' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );

    // Funnel Elements
    wp_register_script( 'groundhogg-funnel-email', WPGH_ASSETS_FOLDER . 'js/admin/funnel-elements/email' . $IS_MINIFIED .'.js', [ 'jquery' ], WPGH_VERSION, true );

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
    wp_register_style( 'jquery-ui', WPGH_ASSETS_FOLDER . 'lib/jquery-ui/jquery-ui.min.css' );
    wp_register_style( 'select2',   WPGH_ASSETS_FOLDER . 'lib/select2/css/select2.min.css' );
    wp_register_style( 'codemirror', WPGH_ASSETS_FOLDER . 'lib/codemirror/codemirror.css'  );

    wp_register_style( 'groundhogg-admin',WPGH_ASSETS_FOLDER . 'css/admin/admin.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-welcome',WPGH_ASSETS_FOLDER . 'css/admin/welcome.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-contact-inline',WPGH_ASSETS_FOLDER . 'css/admin/contacts.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-contact-editor',WPGH_ASSETS_FOLDER . 'css/admin/contact-editor.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-email-editor',WPGH_ASSETS_FOLDER . 'css/admin/email-editor.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-funnel-editor',WPGH_ASSETS_FOLDER . 'css/admin/funnel-editor.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-dashboard',WPGH_ASSETS_FOLDER . 'css/admin/dashboard.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-modal',WPGH_ASSETS_FOLDER . 'css/admin/modal.css', [ 'wp-color-picker' ], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-extensions',WPGH_ASSETS_FOLDER . 'css/admin/extensions.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-iframe',WPGH_ASSETS_FOLDER . 'css/admin/iframe.css', [], WPGH_VERSION );
    wp_register_style( 'groundhogg-admin-simple-editor',WPGH_ASSETS_FOLDER . 'css/admin/simple-editor.css', [], WPGH_VERSION );

    do_action( 'groundhogg/admin/after_register_styles' );
}

add_action( 'admin_enqueue_scripts', 'wpgh_register_admin_styles' );
