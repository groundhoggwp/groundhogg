<?php

namespace Groundhogg;

class Plugin_Compatibility {

	public function __construct() {
//        add_action( 'current_screen', [ $this, 'remove_unwanted_actions_and_filters_from_editors' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'remove_styles_and_scripts_from_editors' ], 999 );
		add_action( 'mailhawk/bounced', [ $this, 'mailhawk_bounced' ], 10, 2 );
		add_filter( 'bp_core_wpsignup_redirect', [ $this, 'prevent_buddyboss_redirect' ], 99 );
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
	public function prevent_buddyboss_redirect( $redirect ){

		// If DOING_CRON is defined, return false and prevent any redirection from BuddyBoss
		if ( defined( 'DOING_CRON' ) || defined( 'DOING_GH_CRON' ) ){
			return false;
		}

		return $redirect;
	}

	/**
	 * When a message is marked as bounced, find the contact and mark them as boucned as well.
	 *
	 * @param $email string the email address
	 * @param $msg_id string
	 */
	public function mailhawk_bounced( $email, $msg_id ){
		$contact = get_contactdata( $email );

		if ( ! $contact ){
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

		if ( $screen->id === 'groundhogg_page_gh_funnels' && $action === 'edit' ) {
			return true;
		}

		if ( $screen->id === 'groundhogg_page_gh_emails' && $action === 'edit' && is_option_enabled( 'gh_use_advanced_email_editor' ) ) {
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
