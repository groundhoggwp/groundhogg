<?php

// Exit if accessed directly
use function Groundhogg\get_array_var;
use function Groundhogg\get_hostname;
use function Groundhogg\remote_post_json;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows plugins to use their own update API.
 *
 * @author Easy Digital Downloads
 * @version 1.6.17
 */
class GH_EDD_SL_Plugin_Updater {

	private $api_url = '';
	private $api_data = array();
	private $name = '';
	private $slug = '';
	private $version = '';
	private $wp_override = false;
	private $beta = false;
	private string $plugin_key = '';

	static array $plugin_data = [];

	/**
	 * Class constructor.
	 *
	 * @param string $_api_url The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array $_api_data Optional data to send with API calls.
	 *
	 * @uses hook()
	 *
	 * @uses plugin_basename()
	 */
	public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {

		$this->api_url     = trailingslashit( $_api_url );
		$this->api_data    = $_api_data;
		$this->name        = plugin_basename( $_plugin_file );
		$this->slug        = basename( $_plugin_file, '.php' );
		$this->version     = $_api_data['version'];
		$this->wp_override = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;
		$this->beta        = ! empty( $this->api_data['beta'] ) ? true : false;
		$this->plugin_key  = $this->slug . ( $this->beta ? '-beta' : '' );
		self::$plugin_data[ $this->plugin_key ] = $this->api_data;

		// Set up hooks.
		$this->init();
	}

	/**
	 * Allow accessing plugin data
	 *
	 * @throws Exception
	 *
	 * @param  string  $name
	 *
	 * @return bool|mixed
	 */
	public function __get( string $name ) {
		$plugin_data = self::$plugin_data[ $this->plugin_key ];

		return get_array_var( $plugin_data, $name, null );
	}

	/**
	 * Set up WordPress filters to hook into WP's update process.
	 *
	 * @return void
	 * @uses add_filter()
	 *
	 */
	public function init() {

		add_filter( 'all_plugins', [ $this, 'filter_plugin_data' ] );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10 );
	}

	/**
	 * Sets the slug parameter so "View details" shows in the plugins list normally
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function filter_plugin_data( $plugins ) {

		foreach ( $plugins as $plugin_name => &$plugin_data ) {
			if ( $plugin_name == $this->name ){
				$plugin_data['slug'] = $this->slug;
				$plugin_data['version'] = $this->version;
			}
		}

		return $plugins;
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @param array $_transient_data Update array build by WordPress.
	 *
	 * @return array Modified update array with custom plugin data.
	 *
	 */
	public function check_update( $_transient_data ) {

		global $pagenow;

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new stdClass;
		}

		if ( 'plugins.php' == $pagenow && is_multisite() ) {
			return $_transient_data;
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$version_info = $this->get_version_info();

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {

			if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $version_info;
			}

			$_transient_data->last_checked           = time();
			$_transient_data->checked[ $this->name ] = $this->version;

		}

		return $_transient_data;
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param mixed $_data
	 * @param string $_action
	 * @param object $_args
	 *
	 * @return object $_data
	 *
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( $_action != 'plugin_information' ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {
			return $_data;
		}

		$version_info = $this->get_version_info();

		if ( false === $version_info ) {
			return $_data;
		}

		return $version_info;
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 *
	 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
	 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
	 *
	 * @param stdClass $data
	 *
	 * @return array
	 * @since 3.6.5
	 *
	 */
	private function convert_object_to_array( $data ) {

		if ( empty( $data ) ) {
			return [];
		}

		$data     = (object) $data;
		$new_data = array();
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = $value;
		}

		return $new_data;
	}

	/**
	 * Get the version info for this plugin
	 *
	 * @return object|false
	 */
	protected function get_version_info() {

		if ( get_hostname( $this->api_url ) === get_hostname() ) {
			return false; // Don't allow a plugin to ping itself
		}

		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => $this->license,
			'item_id'    => $this->item_id,
			'version'    => $this->version,
			'slug'       => $this->slug,
			'url'        => home_url(),
			'beta'       => $this->beta,
		);

		// built-in caching from the remote api
		$response = remote_post_json( $this->api_url, $api_params, 'GET', [], false, DAY_IN_SECONDS );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		// must be arrays
		$attributes = [
			'sections',
			'banners',
			'icons'
		];

		foreach ( $attributes as $attribute ) {
			$response->$attribute = maybe_unserialize( $response->$attribute );
			if ( ! is_array( $response->$attribute ) ) {
				$response->$attribute = $this->convert_object_to_array( $response->$attribute );
			}

			if ( $attribute === 'sections' ) {
				foreach ( $response->sections as $key => $section ) {
					$response->$key = (array) $section;
				}
			}
		}

		$response->slug    = $this->slug;
		$response->version = $response->stable_version;

		return $response;
	}
}
