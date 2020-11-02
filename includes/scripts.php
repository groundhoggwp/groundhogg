<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scripts {

	/**
	 * Script handles that should be treated as modules
	 */
	protected $module_handles = [
		'groundhogg-admin'
	];

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_styles' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );

		add_filter( 'script_loader_tag', [ $this, 'register_module_support' ], 10, 3 );
	}

	/**
	 * Allows scripts to be enqueued as modules, supporting imports without a build step
	 */
	public function register_module_support( $tag, $handle, $src ) {

		if ( ! in_array( $handle, $this->module_handles, true ) ) {
			return $tag;
		}

		return '<script type="module" src="' . esc_url( $src ) . '"></script>';
	}

	public function is_script_debug_enabled() {
		return Plugin::$instance->settings->is_option_enabled( 'script_debug' );
	}

	/**
	 * Register frontend scripts.
	 */
	public function register_frontend_scripts() {
		$dot_min = $this->is_script_debug_enabled() ? '' : '.min';

		wp_register_script( 'groundhogg-frontend', GROUNDHOGG_ASSETS_URL . 'js/frontend/frontend' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-ajax-form', GROUNDHOGG_ASSETS_URL . 'js/frontend/ajax-form' . $dot_min . '.js', [
			'jquery',
			'groundhogg-frontend'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'manage-preferences', GROUNDHOGG_ASSETS_URL . 'js/frontend/preferences' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION );
		wp_register_script( 'fullframe', GROUNDHOGG_ASSETS_URL . 'js/frontend/fullframe' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

		$google_recaptcha_api_url = 'https://www.google.com/recaptcha/api.js';
		$site_key                 = get_option( 'gh_recaptcha_site_key' );

		if ( get_option( 'gh_recaptcha_version' ) === 'v3' ) {
			$google_recaptcha_api_url = add_query_arg( [ 'render' => $site_key ], $google_recaptcha_api_url );
		}

		wp_register_script( 'google-recaptcha', $google_recaptcha_api_url );
		wp_register_script( 'groundhogg-google-recaptcha', GROUNDHOGG_ASSETS_URL . 'js/frontend/reCAPTCHA' . $dot_min . '.js', [
			'jquery',
			'google-recaptcha'
		], GROUNDHOGG_VERSION, true );
		wp_localize_script( 'groundhogg-google-recaptcha', 'ghReCAPTCHA', [
			'site_key' => $site_key
		] );

		wp_localize_script( 'groundhogg-frontend', 'Groundhogg', array(
			'tracking_enabled'         => ! is_option_enabled( 'gh_disable_page_view_tracking' ),
			'form_impression_endpoint' => rest_url( 'gh/v3/tracking/form-impression/' ),
			'page_view_endpoint'       => rest_url( 'gh/v3/tracking/page-view/' ),
			'form_submission_endpoint' => rest_url( 'gh/v3/forms/submit/' ),
			'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
			'_wpnonce'                 => wp_create_nonce( 'wp_rest' ),
			'_ghnonce'                 => wp_create_nonce( 'groundhogg_frontend' ),
			'cookies'                  => [
				'tracking'         => Tracking::TRACKING_COOKIE,
				'lead_source'      => Tracking::LEAD_SOURCE_COOKIE,
				'form_impressions' => Tracking::FORM_IMPRESSIONS_COOKIE
			]
		) );

		wp_enqueue_script( 'groundhogg-frontend' );


		do_action( 'groundhogg/scripts/after_register_frontend_scripts', $this->is_script_debug_enabled(), $dot_min );
	}

	/**
	 * Register frontend Styles
	 */
	public function register_frontend_styles() {
		wp_register_style( 'jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'manage-preferences', GROUNDHOGG_ASSETS_URL . 'css/frontend/preferences.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-managed-page', GROUNDHOGG_ASSETS_URL . 'css/frontend/managed-page.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-loader', GROUNDHOGG_ASSETS_URL . 'css/frontend/loader.css', [], GROUNDHOGG_VERSION );

		do_action( 'groundhogg/scripts/after_register_frontend_styles' );
	}

	/**
	 * Register all the required admin scripts.
	 */
	public function register_admin_scripts() {
		// Whether to include minified files or not.
		$dot_min = $this->is_script_debug_enabled() ? '' : '.min';

		// Select 2
		wp_register_script( 'select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/js/select2.full' . $dot_min . '.js', [ 'jquery' ] );

		// Integrations

		// Beautify JS
		wp_register_script( 'beautify-js', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify.min.js' );
		wp_register_script( 'beautify-css', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-css.min.js' );
		wp_register_script( 'beautify-html', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-html.min.js' );

		// PapaParse
		wp_register_script( 'papaparse', GROUNDHOGG_ASSETS_URL . 'lib/papa-parse/papaparse' . $dot_min . '.js' );

		// Sticky Sidebar
		wp_register_script( 'sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/sticky-sidebar.js' );
		wp_register_script( 'jquery-sticky-sidebar', GROUNDHOGG_ASSETS_URL . 'lib/sticky-sidebar/jquery.sticky-sidebar.js', [ 'jquery' ] );

		// Flot
		wp_register_script( 'jquery-flot', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot' . $dot_min . '.js' );
		wp_register_script( 'jquery-flot-pie', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.pie' . $dot_min . '.js', [ 'jquery-flot' ] );
		wp_register_script( 'jquery-flot-time', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.time' . $dot_min . '.js', [ 'jquery-flot' ] );
		wp_register_script( 'jquery-flot-categories', GROUNDHOGG_ASSETS_URL . 'lib/flot/jquery.flot.categories' . $dot_min . '.js', [ 'jquery-flot' ] );

		//chartjs
		wp_deregister_script( 'chart-js' );
		wp_register_script( 'chart-js', GROUNDHOGG_ASSETS_URL . 'lib/chart/Chart.bundle.min.js' );

//		wp_deregister_script( 'moment-js' );
		wp_register_script( 'moment-js', GROUNDHOGG_ASSETS_URL . 'lib/calendar/js/moment' . $dot_min . '.js' );
		wp_register_script( 'baremetrics-calendar', GROUNDHOGG_ASSETS_URL . 'lib/calendar/js/Calendar' . $dot_min . '.js' );

		// Basic Admin Scripts
		wp_register_script( 'groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'js/admin/admin' . $dot_min . '.js', [
			'jquery',
			'select2',
			'jquery-ui-autocomplete',
			'wp-plugins',
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-functions', GROUNDHOGG_ASSETS_URL . 'js/admin/functions' . $dot_min . '.js', [
			'jquery',
			'select2',
			'jquery-ui-autocomplete'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-color', GROUNDHOGG_ASSETS_URL . 'js/admin/color-picker' . $dot_min . '.js', [
			'jquery',
			'wp-color-picker'
		], GROUNDHOGG_VERSION, true );
//		wp_register_script( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/contact-editor' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/contact-editor.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'js/admin/inline-edit-contacts' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'js/admin/dashboard' . $dot_min . '.js', [
			'jquery',
			'papaparse'
		], GROUNDHOGG_VERSION, true );
//        wp_register_script('groundhogg-admin-email-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor' . $dot_min . '.js', ['jquery', 'sticky-sidebar', 'jquery-ui-resizable', 'groundhogg-admin-functions' ], GROUNDHOGG_VERSION, true);
		wp_register_script( 'groundhogg-admin-email-editor-plain', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor-plain' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-functions',
			'groundhogg-admin-iframe'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-editor' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-functions',
			'sticky-sidebar'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-funnel-editor-v2', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-editor-v3' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-functions',
			'sticky-sidebar'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-form-builder', GROUNDHOGG_ASSETS_URL . 'js/admin/form-builder' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'js/admin/iframe-checker' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, false );
		wp_register_script( 'groundhogg-admin-import-export', GROUNDHOGG_ASSETS_URL . 'js/admin/import-export' . $dot_min . '.js', [
			'jquery',
			'papaparse'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-link-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/link-picker' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-media-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/media-picker' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'js/admin/modal' . $dot_min . '.js', [
			'jquery',
			'wp-color-picker'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-replacements', GROUNDHOGG_ASSETS_URL . 'js/admin/replacements' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-modal'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-simple-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/simple-editor' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-email-preview', GROUNDHOGG_ASSETS_URL . 'js/admin/email-preview' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-email-editor-expand', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor-expand' . $dot_min . '.js', [
			'jquery',
			'underscore'
		], GROUNDHOGG_VERSION );
		wp_register_script( 'groundhogg-admin-guided-setup', GROUNDHOGG_ASSETS_URL . 'js/admin/setup' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

		// Funnel Elements
		wp_register_script( 'groundhogg-funnel-email', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/email' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-modal'
		], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-funnel-delay-timer', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/delay-timer' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-funnel-webhook', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/webhook' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-funnel-form-integration', GROUNDHOGG_ASSETS_URL . 'js/admin/funnel-steps/form-integration' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'groundhogg-admin-modal'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-funnel-react', GROUNDHOGG_URL . 'src/__funnel/build/build.js', [
			'jquery',
			'wp-element',
			'groundhogg-admin',
			'wp-i18n'
		], null, true );

		$asset_file = include_once( GROUNDHOGG_PATH . 'build/index.asset.php' );

		wp_register_script(
			'groundhogg-react',
			GROUNDHOGG_URL . 'build/index.js',
			array_merge( [
				'wp-core-data',
				'groundhogg-admin',
			], (array)$asset_file['dependencies'] ),
			$asset_file['version']
		);

		wp_register_script( 'groundhogg-admin-reporting', GROUNDHOGG_ASSETS_URL . 'js/admin/reporting' . $dot_min . '.js', [
			'jquery',
			'moment-js',
			'chart-js',
			'baremetrics-calendar',
			'groundhogg-admin'
		], GROUNDHOGG_VERSION, true );

		wp_enqueue_script( 'groundhogg-admin-functions' );

		$endpoints = [
			'tags'      => rest_url( 'gh/v3/tags' ),
			'emails'    => rest_url( 'gh/v3/emails' ),
			'contacts'  => rest_url( 'gh/v3/contacts' ),
			'funnels'   => rest_url( 'gh/v3/funnels' ),
			'steps'     => rest_url( 'gh/v3/steps' ),
			'meta_keys' => rest_url( 'gh/v3/contacts/meta-keys' ),
			'pages'     => rest_url( 'gh/v3/pages' ),

		];

		$endpoints = apply_filters( 'groundhogg/scripts/admin_rest_endpoints', $endpoints );

		wp_localize_script( 'groundhogg-admin', 'groundhogg_endpoints', $endpoints );

		wp_localize_script( 'groundhogg-admin', 'groundhogg_nonces', [
			'_wpnonce'            => wp_create_nonce(),
			'_meta_nonce'         => wp_create_nonce( 'meta-picker' ),
			'_wprest'             => wp_create_nonce( 'wp_rest' ),
			'_adminajax'          => wp_create_nonce( 'admin_ajax' ),
			'_ajax_linking_nonce' => wp_create_nonce( 'internal-linking' ),
		] );

		do_action( 'groundhogg/scripts/after_register_admin_scripts', $this->is_script_debug_enabled(), $dot_min );
	}

	/**
	 * Register all the required admin styles.
	 */
	public function register_admin_styles() {
		wp_register_style( 'jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css' );
		wp_register_style( 'select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/css/select2.min.css' );
		wp_register_style( 'baremetrics-calendar', GROUNDHOGG_ASSETS_URL . 'lib/calendar/css/calendar.css' );

		wp_register_style( 'groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'css/admin/admin.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-welcome', GROUNDHOGG_ASSETS_URL . 'css/admin/welcome.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'css/admin/contacts.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/contact-editor.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
//        wp_register_style('groundhogg-admin-email-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/email-editor.css', [], GROUNDHOGG_VERSION);
		wp_register_style( 'groundhogg-admin-email-editor-plain', GROUNDHOGG_ASSETS_URL . 'css/admin/email-editor-plain.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-email-wysiwyg', GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css', [], GROUNDHOGG_VERSION ); //todo I think un used
		wp_register_style( 'groundhogg-admin-email-preview', GROUNDHOGG_ASSETS_URL . 'css/admin/email-preview.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-funnel-editor-v2', GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor-v3.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'css/admin/dashboard.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'css/admin/modal.css', [ 'wp-color-picker' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-extensions', GROUNDHOGG_ASSETS_URL . 'css/admin/extensions.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'css/admin/iframe.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-simple-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/simple-editor.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-guided-setup', GROUNDHOGG_ASSETS_URL . 'css/admin/setup.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-help', GROUNDHOGG_ASSETS_URL . 'css/admin/help.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-reporting', GROUNDHOGG_ASSETS_URL . 'css/admin/reporting.css', [
			'groundhogg-admin',
			'baremetrics-calendar'
		], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-loader', GROUNDHOGG_ASSETS_URL . 'css/admin/loader.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-funnel-react', GROUNDHOGG_URL . 'src/__funnel/build/build.css', [], GROUNDHOGG_VERSION );

		do_action( 'groundhogg/scripts/after_register_admin_styles' );
	}

}
