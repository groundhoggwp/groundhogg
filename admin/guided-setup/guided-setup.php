<?php

namespace Groundhogg\Admin\Guided_Setup;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Guided_Setup\Steps\Step;
use Groundhogg\Extension;
use Groundhogg\Extension_Upgrader;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use Groundhogg\Telemetry;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_user_timezone;
use function Groundhogg\qualifies_for_review_your_funnel;
use function Groundhogg\remote_post_json;

/**
 * Guided Setup
 *
 * An automated and simple experience that allows users to setup Groundhogg in a few steps.
 *
 * @since       File available since Release 0.9
 * @subpackage  Admin/Guided Setup
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Guided_Setup extends Admin_Page {

	/**
	 * Add Ajax actions...
	 *
	 * @return void
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_gh_guided_setup_subscribe', [ $this, 'subscribe_to_newsletter' ] );
		add_action( 'wp_ajax_gh_guided_setup_telemetry', [ $this, 'optin_to_telemetry' ] );
		add_action( 'wp_ajax_gh_guided_setup_license', [ $this, 'check_license' ] );
		add_action( 'wp_ajax_groundhogg_remote_install_hollerbox', [ $this, 'install_hollerbox' ] );
		add_action( 'wp_ajax_gh_apply_for_review_your_funnel', [ $this, 'review_your_funnel_application' ] );
	}

	/**
	 * Send data for review funnel application
	 *
	 * @return void
	 */
	public function review_your_funnel_application() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$email     = sanitize_text_field( get_post_var( 'email' ) );
		$name      = sanitize_text_field( get_post_var( 'name' ) );
		$business  = sanitize_text_field( get_post_var( 'business' ) );
		$more_info = wp_kses_post( get_post_var( 'more' ) );

		// Update the telemetry email
		update_option( 'gh_telemetry_email', $email );

		// Send the telemetry
		Telemetry::send_telemetry();

		// Submit the application
		$request = [
			'email'    => $email,
			'name'     => $name,
			'business' => $business,
			'more'     => $more_info,
			'license'  => get_option( 'gh_master_license' ),
			'url'      => home_url(),
		];

		remote_post_json( 'https://www.groundhogg.io/wp-json/gh/v4/webhooks/1998-review-your-funnel?token=l2d4PaK', $request );

		wp_send_json_success();
	}

	/**
	 * Installed HollerBox remotely
	 *
	 * @return void
	 */
	public function install_hollerbox() {

		if ( ! wp_verify_nonce( get_post_var( 'nonce' ), 'install_plugins' ) || ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error();
		}

		$res = Extension_Upgrader::install_repo_plugin( 'holler-box' );

		if ( is_wp_error( $res ) ) {
			wp_send_json_error( $res );
		}

		wp_send_json_success();
	}

	/**
	 * Checks the provided license to see if it's valid
	 */
	public function check_license() {

		$license = sanitize_text_field( get_post_var( 'license' ) );

		$response = remote_post_json( 'https://www.groundhogg.io/wp-json/edd/all-access/', [
			'license_key' => $license
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response );
		}

		update_option( 'gh_master_license', $license );

		wp_send_json_success();
	}

	/**
	 * Optin the contact to telemetry
	 */
	public function optin_to_telemetry() {
		// Add to telemetry
		$response = Plugin::$instance->stats_collection->optin( get_post_var( 'marketing', false ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response );
		}

		wp_send_json_success();
	}

	/**
	 * Subscribe the contact to the newsletter
	 */
	public function subscribe_to_newsletter() {

		$email = get_post_var( 'email' );
		$name  = wp_get_current_user()->display_name;

		// Add to list
		$response = remote_post_json( 'https://www.groundhogg.io/wp-json/gh/v3/webhook-listener?auth_token=NCM39k3&step_id=1641', [
			'email'     => $email,
			'name'      => $name,
			'time_zone' => get_user_timezone()->getName()
		] );

		wp_send_json_success( [
			'response' => $response
		] );
	}

	public function screen_options() {
	}

	/**
	 * Adds additional actions.
	 *
	 * @return void
	 */
	protected function add_additional_actions() {
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_guided_setup';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Guided Setup', 'groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'manage_options';
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		return 'step';
	}

	/**
	 * Output the basic view.
	 *
	 * @return void
	 */
	public function view() {
		_e( 'Look away!' );
	}

	/**
	 * Just use the step process...
	 */
	public function process_view() {
		$this->get_current_step()->go_to_next();
	}

	/**
	 * @return string
	 */
	public function get_parent_slug() {
		return 'options.php';
	}

	/**
	 * @return int
	 */
	public function get_current_step_id() {
		return absint( get_request_var( 'step', 0 ) );
	}

	/**
	 * @return bool|Step
	 */
	public function get_current_step() {
		return get_array_var( $this->steps, $this->get_current_step_id() );
	}

	/**
	 * The main output
	 */
	public function page() {
		?>
        <div id="guided-setup">

        </div><?php
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {

		$integrations_installed = [
			210    => function_exists( 'WC' ),
			52477  => defined( 'BP_VERSION' ),
			28364  => defined( 'AFFILIATEWP_VERSION' ),
			251    => defined( 'WPCF7_VERSION' ),
			216    => defined( 'EDD_VERSION' ),
			22198  => defined( 'ELEMENTOR_VERSION' ),
			1350   => function_exists( 'load_formidable_forms' ),
			1342   => defined( 'FORMINATOR_VERSION' ),
			98242  => defined( 'GIVE_VERSION' ),
			219    => class_exists( 'GFCommon' ),
			15028  => defined( 'LEARNDASH_VERSION' ),
			15036  => defined( 'LLMS_PLUGIN_FILE' ),
			101745 => defined( 'MEPR_VERSION' ),
			1358   => defined( 'NF_PLUGIN_DIR' ),
			16538  => defined( 'TUTOR_VERSION' ),
			23534  => defined( 'FLUENTFORM' ),
			777    => defined( 'SIMPLE_PAY_VERSION' ),
			1595   => defined( 'WPFORMS_VERSION' ),
		];

		$integrations = License_Manager::get_store_products( [ 'category' => 'integrations' ] )->products;
		$integrations = array_values( array_filter( $integrations, function ( $integration ) use ( $integrations_installed ) {
			return get_array_var( $integrations_installed, $integration->info->id ) && ! Extension::installed( $integration->info->id );
		} ) );

		$smtp_services = License_Manager::get_store_products( [ 'tag' => 'sending-service' ] )->products;

		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-guided-setup' );
		wp_enqueue_script( 'groundhogg-admin-guided-setup' );
		wp_enqueue_editor();

		$setup = [
			'smtpProducts'                 => $smtp_services,
			'integrations'                 => $integrations,
			'mailhawkInstalled'            => defined( 'MAILHAWK_VERSION' ),
			'install_mailhawk_nonce'       => wp_create_nonce( 'install_mailhawk' ),
			'install_plugins_nonce'        => wp_create_nonce( 'install_plugins' ),
			'installed'                    => [
				'mailhawk'  => defined( 'MAILHAWK_VERSION' ),
				'hollerbox' => defined( 'HOLLERBOX_VERSION' ),
			],
			'assets'                       => [
				'mailhawk'  => GROUNDHOGG_ASSETS_URL . 'images/recommended/mailhawk.png',
				'hollerbox' => GROUNDHOGG_ASSETS_URL . 'images/recommended/hollerbox.png',
			],
			'qualifiesForReviewYourFunnel' => qualifies_for_review_your_funnel(),
		];

		wp_add_inline_script( 'groundhogg-admin-guided-setup', 'var GroundhoggGuidedSetup = ' . wp_json_encode( $setup ), 'before' );
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
	}

}
