<?php

namespace Groundhogg;

use WP_Error;
use function Avifinfo\read;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Extension_Updater
 *
 * Old versions of plugins cannot update if they are not updated to 2.0
 * This class will allow them to update still, hopefully.
 *
 * @package Groundhogg
 */
class Extension_Upgrader {

	protected static $file_map = [
		210    => 'groundhogg-wooc/groundhogg-wooc.php',
		216    => 'groundhogg-edd/groundhogg-edd.php',
		219    => 'groundhogg-gravity/groundhogg-gravity.php',
		251    => 'groundhogg-cf7/groundhogg-cf7.php',
		447    => 'groundhogg-contracts/groundhogg-contracts.php',
		777    => 'groundhogg-wp-simple-pay/groundhogg-wp-simple-pay.php',
		948    => 'groundhogg-email-countdown-timers/groundhogg-email-countdown-timers.php',
		954    => 'groundhogg-proof/groundhogg-proof.php',
		1167   => 'groundhogg-form-styling/groundhogg-form-styling.php',
		1342   => 'groundhogg-forminator/groundhogg-forminator.php',
		1350   => 'groundhogg-formidable/groundhogg-formidable.php',
		1358   => 'groundhogg-ninja/groundhogg-ninja.php',
		1529   => 'groundhogg-zapier/groundhogg-zapier.php',
		1595   => 'groundhogg-wpforms/groundhogg-wpforms.php',
		3008   => 'groundhogg-pipeline/groundhogg-pipeline.php',
		3461   => 'groundhogg-appointments/groundhogg-appointments.php',
		4631   => 'groundhogg-replacements/groundhogg-replacements.php',
		4707   => 'groundhogg-wpep/groundhogg-wpep.php',
		4754   => 'groundhogg-white-label/groundhogg-white-label.php',
		5535   => 'groundhogg-twilio/groundhogg-twilio.php',
		5617   => 'groundhogg-aws/groundhogg-aws.php',
		6355   => 'groundhogg-caldera/groundhogg-caldera.php',
		7132   => 'groundhogg-lead-scoring/groundhogg-lead-scoring.php',
		15036  => 'groundhogg-lifterlms/groundhogg-lifterlms.php',
		15028  => 'groundhogg-learndash/groundhogg-learndash.php',
		15016  => 'groundhogg-content-restriction/groundhogg-content-restriction.php',
		16538  => 'groundhogg-tutorlms/groundhogg-tutorlms.php',
		16557  => 'groundhogg-divi/groundhogg-divi.php',
		17321  => 'groundhogg-smtp/groundhogg-smtp.php',
		18312  => 'groundhogg-birthday/groundhogg-birthday.php',
		19738  => 'groundhogg-advanced-preferences/groundhogg-advanced-preferences.php',
		20158  => 'groundhogg-thrivecart/groundhogg-thrivecart.php',
		22198  => 'groundhogg-elementor/groundhogg-elementor.php',
		22397  => 'groundhogg-pro/groundhogg-pro.php',
		23532  => 'groundhogg-weforms/groundhogg-weforms.php',
		23534  => 'groundhogg-fluent-form/groundhogg-fluent-form.php',
		23538  => 'groundhogg-sms/groundhogg-sms.php',
		28273  => 'groundhogg-rsp/groundhogg-rsp.php',
		28364  => 'groundhogg-affwp/groundhogg-affwp.php',
		28670  => 'groundhogg-awesome-support/groundhogg-awesome-support.php',
		34308  => 'groundhogg-logic/groundhogg-logic.php',
		37360  => 'groundhogg-companies/groundhogg-companies.php',
		38376  => 'groundhogg-zerobounce/groundhogg-zerobounce.php',
		38642  => 'groundhogg-backup/groundhogg-backup.php',
		39872  => 'groundhogg-add-to-calendar/groundhogg-add-to-calendar.php',
		45632  => 'groundhogg-sms77/groundhogg-sms77.php',
		48143  => 'groundhogg-helper/groundhogg-helper.php',
		48348  => 'groundhogg-beta-updates/groundhogg-beta-updates.php',
		48864  => 'groundhogg-sendgrid/groundhogg-sendgrid.php',
		49869  => 'groundhogg-better-meta/groundhogg-better-meta.php',
		50123  => 'groundhogg-elastic-email/groundhogg-elastic-email.php',
		52477  => 'groundhogg-buddyboss/groundhogg-buddyboss.php',
		93174  => 'groundhogg-helpscout/groundhogg-helpscout.php',
		98242  => 'groundhogg-givewp/groundhogg-givewp.php',
		101745 => 'groundhogg-memberpress/groundhogg-memberpress.php',
		134192 => 'groundhogg-presto-player/groundhogg-presto-player.php',
		135217 => 'groundhogg-traffic-filter/groundhogg-traffic-filter.php',
		135350 => 'groundhogg-postmark/groundhogg-postmark.php',
		143328 => 'groundhogg-facebook-conversions-api/groundhogg-facebook-conversions-api.php',
	];

	/**
	 * The ids of official Groundhogg extensions
	 *
	 * @return int[]
	 */
	public static function get_extension_ids() {
		return array_keys( static::$file_map );
	}

	/**
	 * The ids of official Groundhogg extensions
	 *
	 * @return int[]
	 */
	public static function get_extension_paths() {
		return array_values( static::$file_map );
	}

	/**
	 * The ids of official Groundhogg extensions
	 *
	 * @return int
	 */
	public static function get_extension_id_by_path( $path ) {
		return array_search( $path, static::$file_map, true );
	}

	/**
	 * Return the item_id of an extension given the slug
	 *
	 * @param $slug
	 *
	 * @return false|int
	 */
	public static function get_extension_id_by_slug( $slug = '' ) {

		foreach ( static::$file_map as $id => $path ) {
			if ( basename( $path, '.php' ) === $slug ) {
				return $id;
			}
		}

		return false;

	}

	/**
	 * @param $id
	 * @param $file
	 */
	public static function add_extension( $id, $file ) {
		self::$file_map[ $id ] = $file;
	}

	/**
	 * Extension_Updater constructor.
	 */
	public function __construct() {

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'legacy_update_check' ] );
		add_filter( 'update_plugins_groundhogg.io', [ $this, 'check_for_updates_w_update_uri' ], 10, 4 );
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );

	}

	/**
	 * Items that can be installed based on the user's license key
	 *
	 * @return int[]
	 */
	public static function get_installable_items() {
		return array_values( array_intersect( License_Manager::get_accessible_items(), Extension_Upgrader::get_extension_ids() ) );
	}

	/**
	 * Remotely install an extension
	 *
	 * @param $item_id
	 * @param $license
	 *
	 * @return bool|\WP_Error
	 */
	public static function remote_install( $item_id, $license = '' ) {

		// already installed :)
		if ( Extension::installed( $item_id ) ) {
			return true;
		}

		$plugin = get_array_var( self::$file_map, $item_id );

		if ( ! $plugin ) {
			return new \WP_Error( 'invalid_plugin_id', 'Invalid plugin ID provided.' );
		}

		$is_installed = false;

		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $path => $details ) {
			if ( ! str_contains( $path, $plugin ) ) {
				continue;
			}

			$is_installed = true;

			$activate = activate_plugin( $path );

			if ( is_wp_error( $activate ) ) {
				return $activate;
			}

			break;
		}

		$install = null;

		if ( ! $is_installed ) {

			// if a license was supplied and it's not already registered
			if ( $license && ! License_Manager::license_is_registered( $license ) ) {

				// attempt to activate with the supplied license
				$result = License_Manager::activate_license( $license );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

			} elseif ( ! License_Manager::is_licensed( $item_id ) ) {
				return new \WP_Error( 'error', 'No valid license found.' );
			}

			if ( ! class_exists( '\Plugin_Upgrader' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				include_once ABSPATH . 'wp-admin/includes/file.php';
				include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			// Get the package info from the Store API
			$response = self::get_package_info_from_store( $item_id );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! $response ) {
				return new \WP_Error( 'error', 'Could not retrieve download package', $response );
			}

			$download_link = $response->download_link ?? null;

			if ( ! $download_link ) {
				return new \WP_Error( 'error', 'Could not retrieve download package' );
			}

			// Use the AJAX upgrader skin to quietly install the plugin.
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

			$install = $upgrader->install( $download_link );

			if ( is_wp_error( $install ) ) {
				return $install;
			}

			$activate = activate_plugin( $upgrader->plugin_info() );

			if ( is_wp_error( $activate ) ) {
				return $activate;
			}
		}

		return true;


	}

	/**
	 * Pre-process the post content
	 *
	 * @param $slug string
	 *
	 * @return \WP_Error|bool
	 */
	public static function install_repo_plugin( $slug ) {

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( get_plugins() as $path => $details ) {

			if ( ! str_contains( $path, sprintf( '/%s.php', $slug ) ) ) {
				continue;
			}

			$activate = activate_plugin( $path );

			if ( is_wp_error( $activate ) ) {
				return $activate;
			}

			return true;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Use the WordPress Plugins API to get the plugin download link.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug' => $slug,
			)
		);

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		// Use the AJAX upgrader skin to quietly install the plugin.
		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$install  = $upgrader->install( $api->download_link );

		if ( is_wp_error( $install ) ) {
			return $install;
		}

		$activate = activate_plugin( $upgrader->plugin_info() );

		if ( is_wp_error( $activate ) ) {
			return $activate;
		}

		return true;
	}

	static array $batch_package_response = [];

	/**
	 * Send a batch request for all Groundhogg extensions in a single request instead of DDOSing ourselves
	 *
	 * @return array|bool|object|WP_Error
	 */
	public static function maybe_batch_get_package_info_from_store() {

		if ( flagged( __METHOD__ ) ) {
			return false;
		}

		flagged( __METHOD__, true );

		$plugins = get_plugins();

		$products = [];

		foreach ( $plugins as $path => $details ) {

			if ( ! in_array( $path, self::$file_map ) ) {
				continue;
			}

			$item_id = self::get_extension_id_by_path( $path );
			$license = License_Manager::get_license( $item_id );
			$slug    = basename( $path, '.php' );
			$version = $details['Version'];

			$products[ $slug ] = [
				'license' => $license ?: 'none',
				'item_id' => $item_id,
				'version' => $version,
				'slug'    => $slug,
				'url'     => home_url(),
				'beta'    => is_option_enabled( 'gh_get_beta_versions' ),
			];

		}

		if ( empty( $products ) ) {
			return false;
		}

		$api_params = [
			'edd_action' => 'get_version',
			'products'   => $products,
		];

		$response = remote_post_json( License_Manager::$storeUrl, $api_params, 'FORM', [], false, DAY_IN_SECONDS );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		foreach ( (array) $response as $slug => $response_data ) {
			$item_id                                  = self::get_extension_id_by_slug( $slug );
			self::$batch_package_response[ $item_id ] = $response_data;
		}

		return $response;
	}

	/**
	 * Get the version info for this plugin
	 *
	 * @return object|false
	 */
	public static function get_package_info_from_store( $item_id, $version = '' ) {

		$slug        = basename( self::$file_map[ $item_id ], '.php' );
		$license_key = License_Manager::get_license( $item_id );

		if ( isset( self::$batch_package_response[ $item_id ] ) ) {
			$response = self::$batch_package_response[ $item_id ];
		} else {

			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => $license_key ?: 'none',
				'item_id'    => $item_id,
				'version'    => $version,
				'slug'       => $slug,
				'url'        => home_url(),
				'beta'       => is_option_enabled( 'gh_get_beta_versions' ),
			);

			// built-in caching from the remote api
			$response = remote_post_json( License_Manager::$storeUrl, $api_params, 'GET', [], false, DAY_IN_SECONDS );

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		foreach ( [ 'sections', 'banners', 'icons' ] as $attribute ) {
			$response->$attribute = (array) maybe_unserialize( $response->$attribute ?? [] );
		}

		if ( $license_key ) {
			// no new version was supplied, possibly because of a licensing issue, automatically check the license
			// or if we did get a response but the local license is not valid, check it again to sync
			if ( ( empty( $response->package ) && License_Manager::is_valid( $license_key ) ) || ( ! empty( $response->package ) && ! License_Manager::is_valid( $license_key ) ) ) {
				License_Manager::check_license( $license_key );
			}
		}

		$response->slug    = $slug;
		$response->version = $response->new_version; // this is so "view details" shows in the plugins list oddly enough
		$response->tested  = get_bloginfo( 'version' ); // Are we sure? No. Will we say it anyway? Yes.

		return $response;
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param  mixed  $data
	 * @param  string  $action
	 * @param  object  $args
	 *
	 * @return object $_data
	 *
	 */
	public function plugins_api_filter( $data, $action = '', $args = null ) {

		if ( $action !== 'plugin_information' ) {
			return $data;
		}

		$slug = $args->slug;

		$item_id = self::get_extension_id_by_slug( $slug );

		if ( ! $item_id ) {
			return $data;
		}

		$version_info = self::get_package_info_from_store( $item_id );

		if ( is_wp_error( $version_info ) ) {
			wp_die( esc_html( $version_info->get_error_message() ) );
		}

		if ( ! $version_info ) {
			return $data;
		}

		return $version_info;
	}


	/**
	 * Use WordPress' Update URI to do plugin updates
	 *
	 * @param $update
	 * @param $plugin_data
	 * @param $plugin_file
	 * @param $locales
	 *
	 * @return object|false
	 */
	public function check_for_updates_w_update_uri(
		$update,
		$plugin_data,
		$plugin_file,
		$locales
	) {
		$item_id = self::get_extension_id_by_path( $plugin_file );

		if ( ! $item_id ) {
			return $update;
		}

		self::maybe_batch_get_package_info_from_store();

		$response = self::get_package_info_from_store( $item_id, $plugin_data['Version'] );

		if ( ! $response || is_wp_error( $response ) ) {
			return $update;
		}

		return $response;
	}

	/**
	 * Legacy update support if Update URI is unavailable
	 *
	 * @param $transient
	 *
	 * @return mixed
	 */
	public function legacy_update_check( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		self::maybe_batch_get_package_info_from_store();

		$plugins = get_plugins();

		foreach ( self::$file_map as $item_id => $plugin_file ) {

			if ( ! isset( $transient->checked[ $plugin_file ] ) ) {
				continue;
			}

			// New versions use WordPress' native Update URI mechanism.
			if ( ! empty( $plugins[ $plugin_file ]['UpdateURI'] ) ) {
				continue;
			}

			$installed_version = $transient->checked[ $plugin_file ];

			$update = self::get_package_info_from_store(
				$item_id,
				$installed_version
			);

			if ( ! $update || is_wp_error( $update ) ) {
				continue;
			}

			$update->plugin = $plugin_file;

			if ( version_compare( $update->new_version, $installed_version, '>' ) ) {
				$transient->response[ $plugin_file ] = $update;
			} else {
				$transient->no_update[ $plugin_file ] = $update;
			}
		}

		return $transient;
	}

}
