<?php

namespace Groundhogg;

use Groundhogg\Api\V3\Base;
use Groundhogg\Api\V4\Base_Api;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Plugin_Compatibility {

	public function __construct() {
//        add_action( 'current_screen', [ $this, 'remove_unwanted_actions_and_filters_from_editors' ], 999 );

		// Material WP
		add_action( 'admin_enqueue_scripts', [ $this, 'remove_styles_and_scripts_from_editors' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_dequeue_lifterlms' ], 999 );

		// MailHawk
		add_action( 'mailhawk/bounced', [ $this, 'mailhawk_bounced' ], 10, 2 );

		// BuddyBoss
		add_filter( 'bp_core_wpsignup_redirect', [ $this, 'prevent_buddyboss_redirect' ], 99 );
		add_filter( 'bp_enable_private_network_public_content', [ $this, 'buddyboss_public_content' ] );
		add_filter( 'bb_enable_private_rest_apis_public_content', [ $this, 'buddyboss_public_api_content' ] );

		// WPUltimo
		add_filter( 'wu_signup_step_handler_create-account', [ $this, 'prevent_new_user_from_adding_contacts_to_template_site' ], 9 );

		// Cookie Law Info
		add_filter( 'groundhogg/has_accepted_cookies', [ $this, 'cookie_law_info_plugin' ] );

		// Dokan
		add_action( 'dokan_enqueue_admin_dashboard_script', [ $this, 'dokan_lite' ] );
		add_action( 'dokan_enqueue_admin_scripts', [ $this, 'dokan_lite' ] );

		// Query Monitor
		add_action( 'wp_ajax_gh_process_bg_task', [ $this, 'kill_qm'] );
		add_action( 'groundhogg/background_tasks', [ $this, 'kill_qm'] );
		add_action( 'groundhogg/event_queue/before_process', [ $this, 'kill_qm'] );

		// Polylang
		add_action( 'generate_rewrite_rules', [ $this, 'groundhogg_polylang_rules' ] );
		add_filter( 'pll_rewrite_rules', fn( $rules ) => array_merge( $rules, [ 'groundhogg' ] )  );
	}

	/**
	 * Handles polylang's translation filters for Groundhogg endpoints
	 *
	 * @param  \WP_Rewrite  $wp_rewrite
	 *
	 * @return void
	 */
	function groundhogg_polylang_rules( \WP_Rewrite $wp_rewrite ) {
		// get the groundhogg rewrite rules
		$rules = array_filter( $wp_rewrite->rules, fn( $index, $rule ) => str_starts_with( $rule, '^' . get_managed_page_name() ), ARRAY_FILTER_USE_BOTH );
		// pass them to Polylang filters
		$rules = apply_filters( 'groundhogg_rewrite_rules', $rules );
		// add them back into the main rules array
		$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	}

	/**
	 * Kill query monitor during intensive processes...
	 *
	 * @return void
	 */
	public function kill_qm() {
		do_action( 'qm/cease' );
	}

	/**
	 * Silly Dokan
	 *
	 * @return void
	 */
	public function dokan_lite() {

		if ( ! current_screen_is_gh_page( 'gh_reporting' ) ) {
			return;
		}

		wp_dequeue_script( 'dokan-vue-vendor' );
		wp_dequeue_script( 'dokan-promo-notice-js' );
	}

	/**
	 * Detect cookie law info and handle the has_accepted_cookies
	 * https://wordpress.org/plugins/cookie-law-info/
	 *
	 * @param $accepted
	 *
	 * @return bool|mixed
	 */
	public function cookie_law_info_plugin( $accepted ) {

		if ( ! defined( 'CLI_LATEST_VERSION_NUMBER' ) ) {
			return $accepted;
		}

		return get_cookie( 'viewed_cookie_policy' ) === 'yes';
	}

	/**
	 * WPUltimo will add the new user to the template site and then remove them after the fact, but this will add a new contact
	 * To the template site which is then copied over. No good! We must remove the action which creates new contacts from registered users.
	 * Aside: It is not clear how the new user is added to the template site... so we'll be blanket preventing it and add the contact to the main site after the fact.
	 * Prevent by overriding the original handler with our new one.
	 *
	 * @param $handler callable
	 *
	 * @return callable
	 */
	public function prevent_new_user_from_adding_contacts_to_template_site( $handler ) {

		return function () use ( $handler ) {

			// Prevent new contacts from being added to the template sites
			remove_action( 'user_register', [ Plugin::instance()->user_syncing, 'sync_new_user' ] );

			// Add the new user as a contact to the main site.
			add_action( 'wp_ultimo_registration', function ( $site_id, $user_id, $transient, $plan ) {

				if ( is_main_site() ) {
					create_contact_from_user( $user_id );
				}

			}, 10, 4 );

			call_user_func( $handler );

		};

	}

	/**
	 * BuddyBoss should know better, if access from PHP var SCRIPT_NAME is not guaranteed and thus is causing an unwanted redirect
	 *
	 * @see bp_core_wpsignup_redirect
	 *
	 * @param $redirect
	 *
	 * @return false|mixed
	 */
	public function prevent_buddyboss_redirect( $redirect ) {

		// If DOING_CRON is defined, return false and prevent any redirection from BuddyBoss
		if ( defined( 'DOING_CRON' ) || defined( 'DOING_GH_CRON' ) ) {
			return false;
		}

		return $redirect;
	}

	/**
	 * Add the managed page to the list of BuddyBoss private content exclusion list
	 *
	 * @param $excludes
	 *
	 * @return string
	 */
	public function buddyboss_public_content( $excludes = '' ) {

		$managed_page_fragment = '/' . trim( get_managed_page_name(), '/' ) . '/';

		if ( ! str_contains( $excludes, $managed_page_fragment ) ){
			$excludes .= "\n$managed_page_fragment";
		}

		return $excludes;
	}

	/**
	 * BuddyBoss should not control Groundhogg endpoints, we're capable of that ourselves.
	 *
	 * @param $excludes
	 *
	 * @return string
	 */
	public function buddyboss_public_api_content( $excludes = '' ) {

		$v3_api_fragment = 'wp-json/' .  Base::NAME_SPACE;
		$v4_api_fragment = 'wp-json/' .  Base_Api::NAME_SPACE;

		if ( ! str_contains( $excludes, $v3_api_fragment ) ){
			$excludes .= "\n$v3_api_fragment";
		}

		if ( ! str_contains( $excludes, $v4_api_fragment ) ){
			$excludes .= "\n$v4_api_fragment";
		}

		return $excludes;
	}

	/**
	 * When a message is marked as bounced, find the contact and mark them as boucned as well.
	 *
	 * @param $email  string the email address
	 * @param $msg_id string
	 */
	public function mailhawk_bounced( $email, $msg_id ) {
		$contact = get_contactdata( $email );

		if ( ! $contact ) {
			return;
		}

		$contact->change_marketing_preference( Preferences::HARD_BOUNCE );
	}

	/**
	 * If the current page is the funnel editor...
	 *
	 * @return bool
	 */
	protected function is_editor_page() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		$action = get_request_var( 'action', 'view' );

		if ( current_screen_is_gh_page( 'gh_funnels' ) && $action === 'edit' ) {
			return true;
		}

		if ( current_screen_is_gh_page( 'gh_emails' ) && $action === 'edit' ) {
			return true;
		}

		return false;
	}

	public function remove_unwanted_actions_and_filters_from_editors() {
		if ( ! $this->is_editor_page() ) {
			return;
		}

		// Add actions that need to be removed here.
	}

	/**
	 * Dequeue lifterLMS css on all groundhogg admin pages.
	 *
	 * @return void
	 */
	public function maybe_dequeue_lifterlms() {

		if ( ! is_admin_groundhogg_page() ) {
			return;
		}

		wp_dequeue_style( 'llms-admin-styles' );
	}

	public function remove_styles_and_scripts_from_editors() {

		if ( ! $this->is_editor_page() ) {
			return;
		}

		// Material WP compatibility
		if ( function_exists( 'initialize_material_wp' ) ) {
			wp_dequeue_script( 'material-wp' );
			wp_dequeue_script( 'material-wp_dynamic' );
			wp_dequeue_style( 'material-wp' );
			wp_dequeue_style( 'material-wp_dynamic' );

			// Only way to prevent the loading of the parallax box in material WP is to set the vc value
			add_action( 'in_admin_header', function () {
				$_GET['vc_action'] = 'vc_inline';
			}, - 201 );

			// Remove it directly after to avoid conflicts
			add_action( 'in_admin_header', function () {
				unset( $_GET['vc_action'] );
			}, - 199 );
		}
	}

}
