<?php

namespace Groundhogg;

use Groundhogg\Admin\Admin_Menu;
use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use Groundhogg\DB\Manager;
use Groundhogg\Reporting\Reports\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extension
 *
 * Helper class for extensions with Groundhogg.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class Extension {

	/**
	 * TODO Override this static var in child class.
	 * @var Extension
	 */
	public static $instance = null;

	/**
	 * @var Installer
	 */
	public $installer;

	/**
	 * @var Updater
	 */
	public $updater;

	/**
	 * @var Roles
	 */
	public $roles;

	/**
	 * Keep a going array of all the Extensions.
	 *
	 * @var Extension[]
	 */
	public static $extensions = [];

	/**
	 * Keep a going array of the extension IDs which are available.
	 *
	 * @var int[]
	 */
	public static $extension_ids = [];

	/**
	 * Extension constructor.
	 */
	public function __construct() {
		if ( $this->dependent_plugins_are_installed() ) {

			$this->register_autoloader();

			if ( ! did_action( 'groundhogg/init/v2' ) ) {
				add_action( 'groundhogg/init/v2', [ $this, 'init' ] );
			} else {
				$this->init();
			}
		}

		// Add to main list
		Extension::$extensions[]                              = $this;
		Extension::$extension_ids[ $this->get_download_id() ] = $this->get_download_id();
	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 * @static
	 *
	 * @return Extension
	 */
	public static function instance() {

		$class = get_called_class();

		if ( is_null( $class::$instance ) ) {

			$class::$instance = new $class();
		}

		return $class::$instance;
	}

	/**
	 * Register autoloader.
	 *
	 * Groundhogg autoloader loads all the classes needed to run the plugin.
	 *
	 * @since  1.6.0
	 * @access private
	 */
	abstract protected function register_autoloader();

	/**
	 * @return Extension[]
	 */
	public static function get_extensions() {
		return self::$extensions;
	}

	/**
	 * Return a list of plugins which this plugin is dependent on before initializing.
	 * Such as plugins required for Integrations...
	 *
	 * @return string[]
	 */
	protected function get_dependent_plugins() {
		return [];
	}

	/**
	 * Check if all the dependent plugins are installed.
	 *
	 * @return bool
	 */
	protected function dependent_plugins_are_installed() {

		$plugins = $this->get_dependent_plugins();

		// No dependent plugins!
		if ( empty( $plugins ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		foreach ( $plugins as $plugin_file_path => $plugin_name ) {

			if ( is_numeric( $plugin_file_path ) ) {
				$plugin_file_path = $plugin_name;
			}

			if ( ! is_plugin_active( $plugin_file_path ) ) {
				add_action( 'admin_notices', [ $this, 'dependencies_missing_notice' ] );

				return false;
			}
		}

		return true;
	}

	public function dependencies_missing_notice() {
		$message      = sprintf( esc_html__( '%s is missing required plugins to be active: %s', 'groundhogg' ), $this->get_display_name(), implode( ', ', $this->get_dependent_plugins() ) );
		$html_message = sprintf( '<div class="notice notice-error">%s</div>', wpautop( $message ) );
		echo wp_kses_post( $html_message );
	}

	/**
	 * Add any other components...
	 *
	 * @return void
	 */
	public function init() {

		$this->includes();

		$this->init_components();

		add_action( 'groundhogg/scripts/after_enqueue_block_editor_assets', [ $this, 'register_react_assets' ], 10, 2 );
		add_action( 'groundhogg/scripts/after_register_admin_scripts', [ $this, 'register_admin_scripts' ], 10, 2 );
		add_action( 'groundhogg/scripts/after_register_admin_styles', [ $this, 'register_admin_styles' ] );
		add_action( 'groundhogg/scripts/after_register_frontend_scripts', [
			$this,
			'register_frontend_scripts'
		], 10, 2 );
		add_action( 'groundhogg/scripts/after_register_frontend_styles', [ $this, 'register_frontend_styles' ] );

		add_action( 'groundhogg/db/manager/init', [ $this, 'register_dbs' ] );
		add_action( 'groundhogg/api/v3/pre_init', [ $this, 'register_apis' ] );
		add_action( 'groundhogg/bulk_jobs/init', [ $this, 'register_bulk_jobs' ] );
		add_action( 'groundhogg/admin/init', [ $this, 'register_admin_pages' ] );
		add_action( 'groundhogg/steps/init', [ $this, 'register_funnel_steps' ] );
		add_action( 'groundhogg/dashboard/widgets/init', [ $this, 'register_dashboard_widgets' ] );
		add_action( 'groundhogg/replacements/init', [ $this, 'add_replacements' ] );
		add_filter( 'groundhogg/admin/emails/blocks/init', [ $this, 'register_email_blocks' ] );

		add_filter( 'groundhogg/reporting/reports', [ $this, 'register_reports' ] );
		add_filter( 'groundhogg/admin/settings/settings', [ $this, 'register_settings' ] );
		add_filter( 'groundhogg/admin/settings/tabs', [ $this, 'register_settings_tabs' ] );
		add_filter( 'groundhogg/admin/settings/sections', [ $this, 'register_settings_sections' ] );

		add_filter( 'groundhogg/templates/emails', [ $this, 'register_email_templates' ] );
		add_filter( 'groundhogg/templates/funnels', [ $this, 'register_funnel_templates' ] );

		$this->get_edd_updater();
	}

	/**
	 * Include any files.
	 *
	 * @return void
	 */
	abstract public function includes();

	/**
	 * Init any components that need to be added.
	 *
	 * @return void
	 */
	abstract public function init_components();

	/**
	 * @param $is_minified bool
	 * @param $dot_min     string
	 */
	public function register_admin_scripts( $is_minified, $dot_min ) {
	}

	public function register_react_assets() {

	}

	/**
	 * @param $is_minified bool
	 * @param $dot_min     string
	 */
	public function register_admin_styles() {
	}

	/**
	 * @param $is_minified bool
	 * @param $dot_min     string
	 */
	public function register_frontend_scripts( $is_minified, $dot_min ) {
	}

	/**
	 * @param $is_minified bool
	 * @param $dot_min     string
	 */
	public function register_frontend_styles() {
	}

	/**
	 * @param $templates
	 *
	 * @return mixed
	 */
	public function register_funnel_templates( $templates ) {
		return $templates;
	}

	/**
	 * @param $templates
	 *
	 * @return mixed
	 */
	public function register_email_templates( $templates ) {
		return $templates;
	}

	/**
	 * @param $blocks
	 *
	 * @return mixed
	 */
	public function register_email_blocks( $blocks ) {
		return $blocks;
	}

	/**
	 * @param $replacements Replacements
	 */
	public function add_replacements( $replacements ) {
	}

	/**
	 * @param $manager \Groundhogg\Steps\Manager
	 */
	public function register_funnel_steps( $manager ) {
	}

	/**
	 * @param $dashboard Dashboard_Widgets
	 */
	public function register_dashboard_widgets( $dashboard ) {
	}

	/**
	 * @param $manager \Groundhogg\Bulk_Jobs\Manager
	 */
	public function register_bulk_jobs( $manager ) {
	}

	/**
	 * @param $reports Report[]
	 *
	 * @return array
	 */
	public function register_reports( $reports ) {
		return $reports;
	}

	/**
	 * Add settings to the settings page
	 *
	 * @param $settings array[]
	 *
	 * @return array[]
	 */
	public function register_settings( $settings ) {
		return $settings;
	}

	/**
	 * Add settings sections to the settings page
	 *
	 * @param $sections array[]
	 *
	 * @return array[]
	 */
	public function register_settings_sections( $sections ) {
		return $sections;
	}

	/**
	 * Add settings tabs to the settings page
	 *
	 * @param $tabs array[]
	 *
	 * @return array[]
	 */
	public function register_settings_tabs( $tabs ) {
		return $tabs;
	}

	/**
	 * Register any proprietary DBS
	 *
	 * @param $db_manager Manager
	 */
	public function register_dbs( $db_manager ) {
	}

	/**
	 * Register any api endpoints.
	 *
	 * @param $api_manager
	 *
	 * @return void
	 */
	public function register_apis( $api_manager ) {
	}

	/**
	 * Register any new admin pages.
	 *
	 * @param $admin_menu Admin_Menu
	 *
	 * @return void
	 */
	public function register_admin_pages( $admin_menu ) {
	}

	/**
	 * Get the version #
	 *
	 * @return mixed
	 */
	abstract public function get_version();

	/**
	 * Get the ID number for the download in EDD Store
	 *
	 * @return int
	 */
	abstract public function get_download_id();

	protected $plugin_data = [];

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function get_plugin_data( $key = 'Name' ) {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->get_plugin_file() );
		}

		return $this->plugin_data[ $key ];
	}

	/**
	 * @return string
	 */
	public function get_display_name() {
		return apply_filters( 'groundhogg/extension/name', $this->get_plugin_data( 'Name' ) );
	}

	/**
	 * @return string
	 */
	public function get_display_description() {
		return apply_filters( 'groundhogg/extension/description', $this->get_plugin_data( 'Description' ) );
	}

	/**
	 * @return string
	 */
	abstract public function get_plugin_file();

	/**
	 * Get details...
	 *
	 * @return array|false
	 */
	public function get_extension_details() {
		return get_array_var( get_option( 'gh_extensions', [] ), $this->get_download_id(), [] );
	}

	/**
	 * Get this extension's license key
	 *
	 * @return string|false
	 */
	public function get_license_key() {
		return get_array_var( $this->get_extension_details(), 'license' );
	}

	/**
	 * @return bool|string
	 */
	public function get_expiry() {
		return date_i18n( get_date_time_format(), strtotime( get_array_var( $this->get_extension_details(), 'expiry' ) ) );
	}

	/**
	 * Get the EDD updater.
	 *
	 * @return \GH_EDD_SL_Plugin_Updater
	 */
	public function get_edd_updater() {
		if ( ! class_exists( '\GH_EDD_SL_Plugin_Updater' ) ) {
			require_once __DIR__ . '/lib/edd/GH_EDD_SL_Plugin_Updater.php';
		}

		return new \GH_EDD_SL_Plugin_Updater( License_Manager::$storeUrl, $this->get_plugin_file(), [
			'version' => $this->get_version(),
			'license' => $this->get_license_key(),
			'item_id' => $this->get_download_id(),
			'author'  => $this->get_author(),
			'url'     => home_url(),
			'beta'    => is_option_enabled( 'gh_get_beta_versions' ),
		] );
	}

	/**
	 * Return the author string
	 *
	 * @return string
	 */
	protected function get_author() {
		if ( ! is_white_labeled() ) {
			return 'Groundhogg Inc.';
		} else {
			return white_labeled_name();
		}
	}

	final public function __clone() {
		trigger_error( "Singleton. No cloning allowed!", E_USER_ERROR );
	}

	final public function __wakeup() {
		trigger_error( "Singleton. No serialization allowed!", E_USER_ERROR );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		$content = "<div class='postbox'>";
		$content .= "<h2 class='hndle'>{$this->get_display_name()}</h2>";
		$content .= "<div class=\"inside\">";
		$content .= "<p>" . $this->get_display_description() . "</p>";

		$content .= html()->input( [
			'placeholder' => __( 'License', 'groundhogg' ),
			'name'        => "license[{$this->get_download_id()}]",
			'value'       => $this->get_license_key()
		] );

		if ( $this->get_license_key() ) {
			$content .= "<p>";
			$content .= sprintf( __( "Your license expires on %s", 'groundhogg' ), $this->get_expiry() );
			$content .= "</p>";

			$content .= html()->wrap( [
				html()->wrap( __( 'Deactivate', 'groundhogg' ), 'a', [
					'class' => 'button button-secondary',
					'href'  => admin_url( wp_nonce_url( add_query_arg( [
						'action'    => 'deactivate_license',
						'extension' => $this->get_download_id()
					], 'admin.php?page=gh_settings&tab=extensions' ) ) )
				] ),
				'&nbsp;',
				html()->wrap( __( 'Check', 'groundhogg' ), 'a', [
					'class' => 'button button-secondary',
					'href'  => admin_url( wp_nonce_url( add_query_arg( [
						'action'    => 'check_license',
						'extension' => $this->get_download_id()
					], 'admin.php?page=gh_settings&tab=extensions' ) ) )
				] )
			], 'p' );
		} else {
			$content .= html()->wrap( html()->input( [
				'type'  => 'submit',
				'name'  => 'activate_license',
				'class' => 'button button-primary',
				'value' => __( 'Activate', 'groundhogg' ),
			] ), 'p' );
		}

		$content .= "</div>";
		$content .= "</div>";

		return $content;
	}

}