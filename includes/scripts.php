<?php

namespace Groundhogg;

use Groundhogg\Api\V4\Base_Api;
use Groundhogg\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scripts {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_styles' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );

		add_action( 'enqueue_block_editor_assets', [ $this, 'register_block_editor_assets' ] );

		add_action( 'wp_after_admin_bar_render', [ $this, 'toolbar_scripts' ] );

		add_filter( 'wp_refresh_nonces', [ $this, 'refresh_nonces' ], 10, 3 );

		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_scripts' ] );
	}

	public function block_editor_scripts() {
		wp_enqueue_script( 'groundhogg-gutenberg-filters' );
	}

	/**
	 * Refresh the groundhogg nonces
	 *
	 * @param array $response
	 * @param array $data
	 *
	 * @return array
	 */
	public function refresh_nonces( array $response, array $data ) {

		// We're need to refresh the nonces for stuff
		if ( isset_not_empty( $data, 'groundhogg-refresh-nonces' ) ) {

			$response['groundhogg_nonces'] = [
				'_wpnonce'            => wp_create_nonce(),
				'_meta_nonce'         => wp_create_nonce( 'meta-picker' ),
				'_adminajax'          => wp_create_nonce( 'admin_ajax' ),
				'_ajax_linking_nonce' => wp_create_nonce( 'internal-linking' ),
			];
		}

		return $response;
	}

	public function toolbar_scripts() {

		if ( is_admin_bar_widget_disabled() ) {
			return;
		}

		wp_enqueue_script( 'groundhogg-admin-toolbar' );
		wp_enqueue_style( 'groundhogg-admin-toolbar' );

		// Don't need these if white labelled
		if ( ! is_white_labeled() ) {
			wp_localize_script( 'groundhogg-admin-toolbar', 'GroundhoggToolbar', [
				'dismissed_notices' => array_values( parse_maybe_numeric_list( Notices::$dismissed_notices ) ),
				'read_notices'      => array_values( parse_maybe_numeric_list( Notices::$read_notices ) ),
				'unread'            => notices()->count_unread(),
			] );
		}

	}

	public function register_block_editor_assets() {
		wp_register_style( 'groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION );
	}

	public function is_script_debug_enabled() {
		return Plugin::$instance->settings->is_option_enabled( 'script_debug' );
	}

	/**
	 * Register frontend scripts.
	 */
	public function register_frontend_scripts() {
		$dot_min = $this->is_script_debug_enabled() ? '' : '.min';

		wp_register_script( 'groundhogg-frontend', GROUNDHOGG_ASSETS_URL . 'js/frontend/frontend' . $dot_min . '.js', [], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-morphdom', GROUNDHOGG_ASSETS_URL . 'js/admin/morphdom' . $dot_min . '.js', [], GROUNDHOGG_VERSION );
		wp_register_script( 'groundhogg-make-el', GROUNDHOGG_ASSETS_URL . 'js/admin/make-el' . $dot_min . '.js', [
			'groundhogg-morphdom'
		], GROUNDHOGG_VERSION );

		$form_dependencies = $form_v2_dependencies = [
			'groundhogg-frontend',
		];

		// If recaptcha is enabled
		if ( is_recaptcha_enabled() ) {
			$google_recaptcha_api_url = 'https://www.google.com/recaptcha/api.js';
			$site_key                 = get_option( 'gh_recaptcha_site_key' );

			if ( get_option( 'gh_recaptcha_version' ) === 'v3' ) {
				$google_recaptcha_api_url = add_query_arg( [ 'render' => $site_key ], $google_recaptcha_api_url );
			}

			wp_register_script( 'google-recaptcha', $google_recaptcha_api_url );

			wp_register_script( 'groundhogg-google-recaptcha', GROUNDHOGG_ASSETS_URL . 'js/frontend/reCAPTCHA' . $dot_min . '.js', [
				'google-recaptcha'
			], GROUNDHOGG_VERSION, true );

			wp_localize_script( 'groundhogg-google-recaptcha', 'ghReCAPTCHA', [
				'site_key' => $site_key
			] );

			$form_v2_dependencies[] = 'google-recaptcha';
			$form_dependencies[]    = 'groundhogg-google-recaptcha';
		}

		wp_register_script( 'groundhogg-ajax-form', GROUNDHOGG_ASSETS_URL . 'js/frontend/ajax-form' . $dot_min . '.js', $form_dependencies, GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-form-v2', GROUNDHOGG_ASSETS_URL . 'js/frontend/form' . $dot_min . '.js', $form_v2_dependencies, GROUNDHOGG_VERSION, true );

		wp_register_script( 'fullframe', GROUNDHOGG_ASSETS_URL . 'js/frontend/fullframe' . $dot_min . '.js', [], GROUNDHOGG_VERSION, true );

		wp_localize_script( 'groundhogg-frontend', 'Groundhogg', array(
			'base_url'                     => untrailingslashit( home_url() ),
			'i18n'                         => [
				'submitting' => __( 'Submitting', 'groundhogg' )
			],
			'routes'                       => [
				'tracking' => rest_url( 'gh/v4/tracking' ),
				'forms'    => rest_url( 'gh/v4/forms' ),
				'ajax'     => admin_url( 'admin-ajax.php' ),
			],
			'nonces'                       => [
				'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				'_wprest'  => wp_create_nonce( 'wp_rest' ),
				'_ghnonce' => wp_create_nonce( 'groundhogg_frontend' ),
			],
			'cookies'                      => [
				'tracking'         => Tracking::TRACKING_COOKIE,
				'lead_source'      => Tracking::LEAD_SOURCE_COOKIE,
				'form_impressions' => Tracking::FORM_IMPRESSIONS_COOKIE,
				'page_visits'      => Tracking::PAGE_VISITS_COOKIE,
			],
			'reCAPTCHA'                    => [
				'site_key' => get_option( 'gh_recaptcha_site_key' )
			],
			'settings'                     => [
				'consent_cookie_name'  => get_option( 'gh_consent_cookie_name', 'viewed_cookie_policy' ),
				'consent_cookie_value' => get_option( 'gh_consent_cookie_value', 'yes' ),
			],
			// Cookies can be disabled form via the settings
			'unnecessary_cookies_disabled' => is_option_enabled( 'gh_disable_unnecessary_cookies' ),
			'has_accepted_cookies'         => has_accepted_cookies(),

			//deprecated
			'ajaxurl'                      => admin_url( 'admin-ajax.php' ),
			'_wpnonce'                     => wp_create_nonce( 'wp_rest' ),
			'_ghnonce'                     => wp_create_nonce( 'groundhogg_frontend' ),
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
		wp_register_style( 'groundhogg-managed-page', GROUNDHOGG_ASSETS_URL . 'css/frontend/managed-page.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-loader', GROUNDHOGG_ASSETS_URL . 'css/frontend/loader.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-element', GROUNDHOGG_ASSETS_URL . 'css/admin/elements.css', [], GROUNDHOGG_VERSION );

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
		wp_register_script( 'groundhogg-select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/js/select2.full' . $dot_min . '.js', [ 'jquery' ] );

		// Integrations

		// Beautify JS
		wp_register_script( 'beautify-js', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify.min.js' );
		wp_register_script( 'beautify-css', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-css.min.js' );
		wp_register_script( 'beautify-html', GROUNDHOGG_ASSETS_URL . 'lib/js-beautify/beautify-html.min.js', [
			'beautify-js',
			'beautify-css'
		] );

		// Vue JS
//		wp_register_script( 'vuejs', 'https://unpkg.com/vue@next' );

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
		wp_register_script( 'groundhogg-chart-js', GROUNDHOGG_ASSETS_URL . 'lib/chart/Chart.bundle.min.js' );

//		wp_register_script( 'moment-js', GROUNDHOGG_ASSETS_URL . 'lib/calendar/js/moment.min.js' );

		wp_register_script( 'baremetrics-calendar', GROUNDHOGG_ASSETS_URL . 'lib/calendar/js/Calendar.min.js', [
			'moment'
		] );

		wp_register_script( 'groundhogg-admin-functions', GROUNDHOGG_ASSETS_URL . 'js/admin/functions' . $dot_min . '.js', [
			'jquery',
		], GROUNDHOGG_VERSION, true );

		// Basic Admin Scripts
		wp_register_script( 'groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'js/admin/admin' . $dot_min . '.js', [
			'jquery',
			'groundhogg-select2',
			'jquery-ui-autocomplete',
			'groundhogg-admin-functions',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-data', GROUNDHOGG_ASSETS_URL . 'js/admin/data' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'wp-api-request' // needed for wpApiSettigns.nonce
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-morphdom', GROUNDHOGG_ASSETS_URL . 'js/admin/morphdom' . $dot_min . '.js', [], GROUNDHOGG_VERSION );
		wp_register_script( 'groundhogg-make-el', GROUNDHOGG_ASSETS_URL . 'js/admin/make-el' . $dot_min . '.js', [
			'groundhogg-morphdom'
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-element', GROUNDHOGG_ASSETS_URL . 'js/admin/element' . $dot_min . '.js', [
			'groundhogg-admin',
			'groundhogg-admin-formatting',
			'groundhogg-admin-data',
			'beautify-html',
			'wp-i18n'
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-components', GROUNDHOGG_ASSETS_URL . 'js/admin/components' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-saved-replies',
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-properties', GROUNDHOGG_ASSETS_URL . 'js/admin/components/properties' . $dot_min . '.js', [
			'groundhogg-admin-element',
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-contact-search', GROUNDHOGG_ASSETS_URL . 'js/admin/contacts/contact-search' . $dot_min . '.js', [
			'groundhogg-admin-filter-contacts',
			'groundhogg-admin-send-broadcast',
			'groundhogg-admin-funnel-scheduler',
			'groundhogg-admin-components'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-bulk-edit-contacts', GROUNDHOGG_ASSETS_URL . 'js/admin/contacts/bulk-edit-contacts' . $dot_min . '.js', [
			'groundhogg-admin-filter-contacts',
			'groundhogg-admin-components',
			'groundhogg-admin-properties',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/contacts/contact-editor' . $dot_min . '.js', [
			'jquery',
			'moment',
			'jquery-ui-sortable',
			'groundhogg-admin-tasks',
			'groundhogg-admin-notes',
			'groundhogg-admin-components',
			'groundhogg-admin-properties',
			'groundhogg-admin',
			'groundhogg-admin-data',
			'groundhogg-make-el',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-contact-info-cards', GROUNDHOGG_ASSETS_URL . 'js/admin/contacts/info-cards' . $dot_min . '.js', [
			'jquery',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-remote-notifications', GROUNDHOGG_ASSETS_URL . 'js/admin/components/notifications' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-make-el',
			'groundhogg-admin-data',
		], GROUNDHOGG_VERSION, true );

		wp_localize_script( 'groundhogg-admin-remote-notifications', 'GroundhoggNotifications', [
			'dismissed_notices' => array_values( parse_maybe_numeric_list( Notices::$dismissed_notices ) ),
			'read_notices'      => array_values( parse_maybe_numeric_list( Notices::$read_notices ) ),
			'unread'            => notices()->count_unread(),
		] );

		wp_register_script( 'groundhogg-admin-toolbar', GROUNDHOGG_ASSETS_URL . 'js/admin/features/admin-bar' . $dot_min . '.js', [
			'groundhogg-admin-remote-notifications',
			'groundhogg-admin-components',
			'groundhogg-admin-send-broadcast'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-notes', GROUNDHOGG_ASSETS_URL . 'js/admin/components/notes' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-data',
			'groundhogg-admin-saved-replies',
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-saved-replies', GROUNDHOGG_ASSETS_URL . 'js/admin/components/replies' . $dot_min . '.js', [
			'groundhogg-admin-data',
			'groundhogg-make-el',
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-tasks', GROUNDHOGG_ASSETS_URL . 'js/admin/components/tasks' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-data',
			'groundhogg-admin-saved-replies',
			'groundhogg-admin-email-log',
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-send-broadcast', GROUNDHOGG_ASSETS_URL . '/js/admin/features/send-broadcast' . $dot_min . '.js', [
			'groundhogg-admin',
			'groundhogg-admin-data',
			'groundhogg-admin-element',
			'groundhogg-admin-functions',
			'groundhogg-admin-filter-contacts',
			'groundhogg-make-el',
			'wp-date',
		] );

		wp_register_script( 'groundhogg-admin-funnel-scheduler', GROUNDHOGG_ASSETS_URL . '/js/admin/funnels/funnel-scheduler' . $dot_min . '.js', [
			'groundhogg-admin',
			'groundhogg-admin-data',
			'groundhogg-admin-element',
			'groundhogg-admin-functions',
			'groundhogg-admin-filter-contacts',
			'groundhogg-make-el',
			'wp-date',
		] );

		wp_register_script( 'groundhogg-admin-formatting', GROUNDHOGG_ASSETS_URL . '/js/admin/formatting' . $dot_min . '.js' );

		wp_register_script( 'groundhogg-admin-color', GROUNDHOGG_ASSETS_URL . 'js/admin/color-picker' . $dot_min . '.js', [
			'jquery',
			'wp-color-picker'
		], GROUNDHOGG_VERSION, true );


		wp_register_script( 'groundhogg-admin-email-editor-plain', GROUNDHOGG_ASSETS_URL . 'js/admin/email-editor-plain' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-functions',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-form-builder-v2', GROUNDHOGG_ASSETS_URL . 'js/admin/forms/form-builder-v2' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-properties'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/funnels/funnel-editor' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'groundhogg-admin-element',
			'groundhogg-admin-functions',
			'groundhogg-admin-form-builder-v2',
			'groundhogg-email-block-editor',
			'groundhogg-admin-funnel-scheduler',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-funnel-steps', GROUNDHOGG_ASSETS_URL . 'js/admin/funnels/funnel-steps' . $dot_min . '.js', [
			'groundhogg-admin-funnel-editor'
		] );

		wp_register_script( 'groundhogg-admin-form-builder', GROUNDHOGG_ASSETS_URL . 'js/admin/forms/form-builder' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-media-picker', GROUNDHOGG_ASSETS_URL . 'js/admin/media-picker' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );
		wp_register_script( 'groundhogg-admin-modal', GROUNDHOGG_ASSETS_URL . 'js/admin/components/modal' . $dot_min . '.js', [
			'groundhogg-admin-element',
		], GROUNDHOGG_VERSION, true );

		wp_localize_script( 'groundhogg-admin-modal', 'GroundhoggModalDefaults', [
			'title'      => 'Modal',
			'footertext' => __( 'Close' ),
		] );

		wp_register_script( 'groundhogg-admin-replacements', GROUNDHOGG_ASSETS_URL . 'js/admin/features/replacements' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin-modal'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-email-log', GROUNDHOGG_ASSETS_URL . 'js/admin/email-log' . $dot_min . '.js', [
			'groundhogg-admin-components',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-email-block-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/emails/email-block-editor' . $dot_min . '.js', [
			'groundhogg-admin',
			'groundhogg-admin-element',
			'groundhogg-admin-components',
			'groundhogg-admin-saved-replies',
			'groundhogg-make-el',
			'jquery-ui-sortable',
			'jquery-ui-draggable',
			'jquery-ui-resizable',
			'jquery-ui-autocomplete',
			'wp-color-picker',
			'beautify-html',
			'react',
			'wp-blocks',
			'wp-edit-post'
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-guided-setup', GROUNDHOGG_ASSETS_URL . 'js/admin/features/setup' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-data',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-troubleshooter', GROUNDHOGG_ASSETS_URL . 'js/admin/features/troubleshooter' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-admin-data',
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-funnel-form-integration', GROUNDHOGG_ASSETS_URL . 'js/admin/funnels/form-integration' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'groundhogg-admin-modal'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-reporting', GROUNDHOGG_ASSETS_URL . 'js/admin/reports/reporting' . $dot_min . '.js', [
			'jquery',
			'groundhogg-chart-js',
			'baremetrics-calendar',
			'moment',
			'groundhogg-admin',
			'groundhogg-admin-element',
			'groundhogg-make-el'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-custom-reports', GROUNDHOGG_ASSETS_URL . 'js/admin/reports/custom-reports' . $dot_min . '.js', [
			'jquery',
			'jquery-ui-sortable',
			'groundhogg-chart-js',
			'moment',
			'groundhogg-admin',
			'groundhogg-admin-element',
			'groundhogg-admin-data'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-big-file-upload', GROUNDHOGG_ASSETS_URL . 'js/admin/big-file-upload' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'groundhogg-make-el'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-filters', GROUNDHOGG_ASSETS_URL . 'js/admin/filters/filters' . $dot_min . '.js', [
			'jquery',
			'groundhogg-admin',
			'groundhogg-admin-data',
			'groundhogg-admin-element',
			'groundhogg-make-el'
		], GROUNDHOGG_VERSION, true );

		$filters = [
			'contacts',
			'email-log',
			'emails',
			'events',
			'filters',
			'funnels'
		];

		foreach ( $filters as $filter ) {
			wp_register_script( "groundhogg-admin-filter-$filter", GROUNDHOGG_ASSETS_URL . "js/admin/filters/{$filter}{$dot_min}.js", [
				'groundhogg-admin-filters'
			], GROUNDHOGG_VERSION, true );
		}

		// Backwards compat alias
		wp_register_script( 'groundhogg-admin-search-filters', null, [
			'groundhogg-admin-filter-contacts'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-form-fields-editor', GROUNDHOGG_ASSETS_URL . 'js/admin/forms/form-fields-editor' . $dot_min . '.js', [
			'groundhogg-admin-element',
			'groundhogg-make-el',
			'wp-i18n',
			'jquery-ui-sortable'
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-edit-lock', GROUNDHOGG_ASSETS_URL . 'js/admin/edit-lock' . $dot_min . '.js', [
			'jquery',
			'wp-i18n',
			'groundhogg-make-el',
			'heartbeat'
		], GROUNDHOGG_VERSION );

		wp_register_script( 'groundhogg-admin-api-docs', GROUNDHOGG_ASSETS_URL . 'js/admin/api-docs/api-docs' . $dot_min . '.js', [
			'groundhogg-make-el',
			'groundhogg-admin-data',
			'groundhogg-admin-element',
			'wp-i18n',
			'moment'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-admin-dashboard', GROUNDHOGG_ASSETS_URL . 'js/admin/features/dashboard' . $dot_min . '.js', [
			'groundhogg-make-el',
			'groundhogg-admin-remote-notifications',
			'groundhogg-admin-tasks',
			'groundhogg-admin-components',
			'groundhogg-admin-reporting'
		], GROUNDHOGG_VERSION, true );

		wp_register_script( 'groundhogg-gutenberg-filters', GROUNDHOGG_ASSETS_URL . 'js/admin/features/gutenberg' . $dot_min . '.js', [
			'wp-edit-post',
			'groundhogg-admin-filter-contacts'
		] );

		wp_localize_script( 'groundhogg-gutenberg-filters', 'GroundhoggGutenberg', [
			'isContentRestrictionInstalled' => defined( 'GROUNDHOGG_CONTENT_RESTRICTION_VERSION' ),
		] );

		wp_enqueue_script( 'groundhogg-admin-functions' );

		wp_localize_script( 'groundhogg-admin', 'groundhogg_endpoints', [
			'tags'     => rest_url( 'gh/v3/tags?select2=true' ),
			'emails'   => rest_url( 'gh/v3/emails?select2=true&status[]=ready&status[]=draft' ),
			'sms'      => rest_url( 'gh/v3/sms?select2=true' ),
			'contacts' => rest_url( 'gh/v3/contacts?select2=true' ),
			'v4'       => [
				'tags'    => rest_url( Base_Api::NAME_SPACE . '/tags' ),
				'funnels' => rest_url( Base_Api::NAME_SPACE . '/funnels' )
			],
		] );

		wp_localize_script( 'groundhogg-admin', 'groundhogg_nonces', [
			'_wpnonce'            => wp_create_nonce(),
			'_meta_nonce'         => wp_create_nonce( 'meta-picker' ),
			'_wprest'             => wp_create_nonce( 'wp_rest' ),
			'_adminajax'          => wp_create_nonce( 'admin_ajax' ),
			'_ajax_linking_nonce' => wp_create_nonce( 'internal-linking' ),
		] );

		wp_add_inline_script( 'groundhogg-admin', 'var Groundhogg = ' . wp_json_encode( [
				'name'             => get_bloginfo( 'name' ),
				'locale'           => str_replace( '_', '-', get_locale() ),
				'user_test_email'  => get_user_test_email(),
				'user_test_emails' => get_user_meta( get_current_user_id(), 'gh_test_emails', true ) ?: [],
				'assets'           => [
					'path'    => GROUNDHOGG_ASSETS_URL,
					'images'  => GROUNDHOGG_ASSETS_URL . 'images/',
					'spinner' => is_white_labeled() ? GROUNDHOGG_ASSETS_URL . 'images/loading-gears.svg' : GROUNDHOGG_ASSETS_URL . 'images/groundhogg-spinner.svg',
				],
				'api'              => [
					'routes' => [
						'base'  => rest_url(),
						'wp'    => [
							'v2'         => rest_url( 'wp/v2' ),
							'posts'      => rest_url( 'wp/v2/posts' ),
							'categories' => rest_url( 'wp/v2/categories' ),
							'tags'       => rest_url( 'wp/v2/tags' ),
						],
						'posts' => rest_url( 'wp/v2/posts' ),
						'v3'    => [
							'tags'     => rest_url( 'gh/v3/tags?select2=true' ),
							'emails'   => rest_url( 'gh/v3/emails?select2=true&status[]=ready&status[]=draft' ),
							'sms'      => rest_url( 'gh/v3/sms?select2=true' ),
							'contacts' => rest_url( 'gh/v3/contacts?select2=true' ),
						],
						'v4'    => [
							'root'        => rest_url( Base_Api::NAME_SPACE ),
							'tags'        => rest_url( Base_Api::NAME_SPACE . '/tags' ),
							'activity'    => rest_url( Base_Api::NAME_SPACE . '/activity' ),
							'events'      => rest_url( Base_Api::NAME_SPACE . '/events' ),
							'event_queue' => rest_url( Base_Api::NAME_SPACE . '/event_queue' ),
							'notes'       => rest_url( Base_Api::NAME_SPACE . '/notes' ),
							'contacts'    => rest_url( Base_Api::NAME_SPACE . '/contacts' ),
							'forms'       => rest_url( Base_Api::NAME_SPACE . '/forms' ),
							'emails'      => rest_url( Base_Api::NAME_SPACE . '/emails' ),
							'funnels'     => rest_url( Base_Api::NAME_SPACE . '/funnels' ),
							'steps'       => rest_url( Base_Api::NAME_SPACE . '/steps' ),
							'searches'    => rest_url( Base_Api::NAME_SPACE . '/searches' ),
							'reports'     => rest_url( Base_Api::NAME_SPACE . '/reports' ),
							'campaigns'   => rest_url( Base_Api::NAME_SPACE . '/campaigns' ),
							'broadcasts'  => rest_url( Base_Api::NAME_SPACE . '/broadcasts' ),
							'options'     => rest_url( Base_Api::NAME_SPACE . '/options' ),
							'page_visits' => rest_url( Base_Api::NAME_SPACE . '/page_visits' ),
							'submissions' => rest_url( Base_Api::NAME_SPACE . '/submissions' ),
							'tasks'       => rest_url( Base_Api::NAME_SPACE . '/tasks' ),
							'email_log'   => rest_url( Base_Api::NAME_SPACE . '/email_log' ),
						]
					]
				],
				'defaults'         => [
					'from_name'  => get_default_from_name(),
					'from_email' => get_default_from_email(),
				],
				'replacements'     => [
					'groups' => Plugin::instance()->replacements->replacement_code_groups,
					'codes'  => Plugin::instance()->replacements->get_codes_for_frontend(),
				],
				'fields'           => [
					'mappable' => get_mappable_fields()
				],
				'filters'          => [
					'optin_status'                 => Preferences::get_preference_names(),
					'owners'                       => array_values( get_owners() ),
					'current'                      => get_request_var( 'filters', [] ),
					'roles'                        => get_editable_roles(),
					'countries'                    => utils()->location->get_countries_list(),
					'gh_contact_custom_properties' => Properties::instance()->get_all(),
					'unsubReasons'                 => get_unsub_reasons(),
				],
				'managed_page'     => [
					'root' => managed_page_url()
				],
				'url'              => [
					'admin'   => admin_url(),
					'home'    => home_url(),
					'content' => WP_CONTENT_URL,
					'plugins' => WP_PLUGIN_URL,
				],
				'rawStepTypes'     => Plugin::instance()->step_manager->get_elements(),
				'currentUser'      => wp_get_current_user(),
				'isMultisite'      => is_multisite(),
				'isWhiteLabeled'   => is_white_labeled(),
				'whiteLabelName'   => white_labeled_name(),
				'isSuperAdmin'     => is_super_admin(),
				'isWPFusionActive' => is_wp_fusion_active(),
				'timeZone'         => wp_timezone()->getName(),
//				'siteTime'         => ( new DateTimeHelper() )->wpDateTimeFormat(),
				'recaptcha'        => [
					'enabled' => is_recaptcha_enabled(),
					'version' => get_option( 'gh_recaptcha_version' )
				],
				'screen'           => get_current_screen()
			] ), 'before' );

		wp_register_script( 'groundhogg-admin-fullframe', GROUNDHOGG_ASSETS_URL . 'js/frontend/fullframe' . $dot_min . '.js', [ 'jquery' ], GROUNDHOGG_VERSION, true );

		do_action( 'groundhogg/scripts/after_register_admin_scripts', $this->is_script_debug_enabled(), $dot_min );
	}

	/**
	 * Register all the required admin styles.
	 */
	public function register_admin_styles() {
		wp_register_style( 'jquery-ui', GROUNDHOGG_ASSETS_URL . 'lib/jquery-ui/jquery-ui.min.css' );
		wp_register_style( 'select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/css/select2.min.css' );
		wp_register_style( 'groundhogg-select2', GROUNDHOGG_ASSETS_URL . 'lib/select2/css/select2.min.css' );
		wp_register_style( 'baremetrics-calendar', GROUNDHOGG_ASSETS_URL . 'lib/calendar/css/calendar.css' );

		wp_register_style( 'groundhogg-admin', GROUNDHOGG_ASSETS_URL . 'css/admin/admin.css', [
			'groundhogg-select2',
		], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-welcome', GROUNDHOGG_ASSETS_URL . 'css/admin/welcome.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-contact-inline', GROUNDHOGG_ASSETS_URL . 'css/admin/contacts.css', [ 'groundhogg-admin-element' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-contact-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/contact-editor.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-element', GROUNDHOGG_ASSETS_URL . 'css/admin/elements.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-filters', GROUNDHOGG_ASSETS_URL . 'css/admin/search-filters.css', [
			'groundhogg-admin',
			'groundhogg-admin-element'
		], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-admin-contact-info-cards', GROUNDHOGG_ASSETS_URL . 'css/admin/info-cards.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-email-block-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/email-block-editor.css', [
			'groundhogg-admin',
			'groundhogg-admin-element',
			'jquery-ui',
		], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-admin-funnel-editor', GROUNDHOGG_ASSETS_URL . 'css/admin/funnel-editor.css', [
			'groundhogg-admin',
			'groundhogg-admin-element',
			'groundhogg-email-block-editor'
		], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-admin-extensions', GROUNDHOGG_ASSETS_URL . 'css/admin/extensions.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-iframe', GROUNDHOGG_ASSETS_URL . 'css/admin/iframe.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-guided-setup', GROUNDHOGG_ASSETS_URL . 'css/admin/setup.css', [
			'groundhogg-admin',
			'groundhogg-admin-element',
		], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-help', GROUNDHOGG_ASSETS_URL . 'css/admin/help.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-admin-reporting', GROUNDHOGG_ASSETS_URL . 'css/admin/reporting.css', [
			'groundhogg-admin',
			'baremetrics-calendar',
			'groundhogg-admin-element'
		], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-admin-loader', GROUNDHOGG_ASSETS_URL . 'css/admin/loader.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-admin-toolbar', GROUNDHOGG_ASSETS_URL . 'css/admin/admin-bar.css', [
			'groundhogg-admin-element',
			'groundhogg-admin-filters',
		], GROUNDHOGG_VERSION );

		wp_register_style( 'groundhogg-form', GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css', [], GROUNDHOGG_VERSION );
		wp_register_style( 'groundhogg-loader', GROUNDHOGG_ASSETS_URL . 'css/frontend/loader.css', [], GROUNDHOGG_VERSION );

		do_action( 'groundhogg/scripts/after_register_admin_styles' );
	}

	public static function enqueue_advanced_search_filters_scripts() {
		wp_enqueue_script( 'groundhogg-admin-contact-advanced-search' );

		$components = [
			'filterGroup',
			'orSeparator'
		];

		foreach ( $components as $component ) {
			wp_enqueue_script( 'groundhogg-admin-contact-advanced-search-' . $component );
		}

		wp_enqueue_script( 'groundhogg-admin-contact-advanced-search-mounting' );
	}

}
