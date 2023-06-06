<?php

namespace Groundhogg;


/**
 * Module Manager
 *
 * This class is a helper class for the settigns page. it essentially provides an api with Groundhogg.io for managing premium extension licenses.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class License_Manager {

	static $extensions = array(); // array( item_id => array( license, status ) )
	static $storeUrl = "https://www.groundhogg.io";
	static $user_agent = 'Groundhogg/' . GROUNDHOGG_VERSION . ' license-manager';

	public function __construct() {
		add_action( 'activated_plugin', [ self::class, 'maybe_activate_using_master_license' ], 99, 2 );
	}

	/**
	 * Activate the a
	 *
	 * @param $plugin       string
	 * @param $network_wide bool
	 *
	 * @return void
	 */
	public static function maybe_activate_using_master_license( $plugin, $network_wide ) {

		if ( $network_wide ) {
			return;
		}

		$item_id = Extension_Upgrader::get_extension_id_by_path( $plugin );


		// The plugin that's being activated is a registered extension
		if ( ! $item_id ) {
			return;
		}

		$master_license = get_option( 'gh_master_license' );

		if ( empty( $master_license ) ) {
			return;
		}

		$license = self::get_license( $item_id );

		if ( $license ) {
			return;
		}

		self::activate_license_quietly( $master_license, $item_id );
	}

	/**
	 * Maybe setup the licenses unless they haven't been already
	 */
	public static function init_licenses() {

		if ( empty( static::$extensions ) ) {
			$extensions = get_option( "gh_extensions", [] );

			// Ignore inactive addons
			static::$extensions = array_filter( $extensions, function ( $item_id ) {
				return array_key_exists( $item_id, Extension::$extension_ids );
			}, ARRAY_FILTER_USE_KEY );
		}
	}

	/**
	 * Get all the stored licenses
	 *
	 * @return array
	 */
	public static function get_extension_licenses() {
		self::init_licenses();

		return static::$extensions;
	}

	/**
	 * Get a unique array of the licenses
	 *
	 * @return array
	 */
	public static function get_licenses() {
		self::init_licenses();

		return array_unique( wp_list_pluck( static::$extensions, 'license' ) );
	}

	/**
	 * The number of licenses used
	 *
	 * @return int
	 */
	public static function get_num_licenses() {
		return count( self::get_licenses() );
	}

	/**
	 * Whether there are expired licences
	 *
	 * @return bool
	 */
	public static function has_expired_licenses() {
		return count( self::get_expired_licenses() ) > 0;
	}

	/**
	 * Get a list of the expired licenses
	 *
	 * @return array
	 */
	public static function get_expired_licenses() {
		self::init_licenses();

		return array_unique( wp_list_pluck( self::get_expired_items(), 'license' ) );
	}

	/**
	 * Get the expired items
	 *
	 * @return array
	 */
	public static function get_expired_items() {
		self::init_licenses();

		return array_filter( self::$extensions, function ( $license ) {
			return $license['status'] === 'invalid' || ( $license['expiry'] !== 'lifetime' && strtotime( $license['expiry'] ) < time() );
		} );
	}

	/**
	 * Add an extension to the extensions options.
	 *
	 * @param $item_id int
	 * @param $license string
	 * @param $status  string
	 * @param $expiry  string
	 *
	 * @return bool
	 */
	public static function add_extension( $item_id, $license, $status, $expiry ) {
		self::init_licenses();

		static::$extensions[ $item_id ] = array(
			'license' => $license,
			'status'  => $status,
			'expiry'  => $expiry
		);

		return update_option( "gh_extensions", static::$extensions );
	}

	/**
	 * Remove an extension
	 *
	 * @param $item_id int
	 *
	 * @return bool
	 */
	public static function delete_extension( $item_id ) {
		self::init_licenses();

		unset( static::$extensions[ $item_id ] );

		return update_option( "gh_extensions", static::$extensions );
	}

	/**
	 * Whether the current install has extensions installed.
	 *
	 * @return bool
	 */
	public static function has_extensions() {
		self::init_licenses();

		return ! empty( static::$extensions );
	}

	/**
	 * Will get a specific license for a given item
	 * If no item is specified, will return the first license
	 * If item is specific but no license exists, return false
	 *
	 * @param bool $item_id
	 *
	 * @return bool|mixed
	 */
	public static function get_license( $item_id = false ) {
		self::init_licenses();

		if ( empty( static::$extensions ) ) {
			return false;
		}

		if ( $item_id && isset_not_empty( static::$extensions, $item_id ) ) {
			return static::$extensions[ $item_id ]['license'];
		}

		if ( ! $item_id ) {

			$licenses = array_filter( wp_list_pluck( static::$extensions, 'license' ) );

			if ( ! empty( $licenses ) ) {
				return $licenses[0];
			}

		}

		return false;
	}

	/**
	 * Get the extension Ids which are being used with a specific license.
	 *
	 * @param $license
	 *
	 * @return array
	 */
	public static function get_extensions_by_license( $license ) {
		self::init_licenses();

		return array_keys( array_filter( self::$extensions, function ( $extension ) use ( $license ) {
			return $extension['license'] === $license;
		} ) );
	}

	/**
	 * Get the status of a specific license
	 *
	 * @param $item_id
	 *
	 * @return false|mixed
	 */
	public static function get_license_status( $item_id ) {
		self::init_licenses();

		if ( isset( static::$extensions[ $item_id ] ) ) {
			return static::$extensions[ $item_id ]['status'];
		}

		return false;

	}

	/**
	 * Update the status of a license
	 *
	 * @param int         $item_id
	 * @param string      $status
	 *
	 * @param string|bool $expiry Maybe update the expiry
	 *
	 * @return bool
	 */
	public static function update_license_status( $item_id, $status, $expiry = false ) {
		self::init_licenses();

		// If the item does not exist, hence it was never activated, then ignore.
		if ( ! isset_not_empty( static::$extensions, $item_id ) ) {
			return false;
		}

		static::$extensions[ $item_id ]['status'] = $status;

		if ( $expiry ) {
			static::$extensions[ $item_id ]['expiry'] = $expiry;
		}

		return update_option( "gh_extensions", static::$extensions );
	}

	/**
	 * Activate a license
	 */
	public static function perform_activation() {
		if ( isset( $_POST['gh_activate_license'] ) ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( "Cannot access this functionality" );
			}

			$licenses = map_deep( get_request_var( 'licenses' ), 'sanitize_text_field' );

			if ( ! is_array( $licenses ) ) {
				wp_die( _x( 'Invalid license format', 'notice', 'groundhogg' ) );
			}

			foreach ( $licenses as $item_id => $license ) {
				$license = trim( $license );
				$item_id = intval( trim( $item_id ) );

				if ( ! empty( $license ) && ! self::get_license_status( $license ) ) {
					self::activate_license( $license, $item_id );
				}
			}
		}
	}

	/**
	 * Get the error message for a given error.
	 *
	 * @param       $error
	 * @param false $expiry
	 *
	 * @return string
	 */
	protected static function get_license_error_message( $error, $expiry = false ) {

		switch ( $error ) {
			case 'expired' :
				$message = sprintf(
					_x( 'Your license key expired on %s.', 'notice', 'groundhogg' ),
					date_i18n( get_option( 'date_format' ), strtotime( $expiry, current_time( 'timestamp' ) ) )
				);
				break;
			case 'invalid' :
			case 'disabled' :
				$message = _x( 'Your license key has been disabled.', 'notice', 'groundhogg' );
				break;
			case 'site_inactive' :
				$message = _x( 'Your license is not active for this URL.', 'notice', 'groundhogg' );
				break;
			case 'key_mismatch' :
			case 'invalid_item_id' :
			case 'item_name_mismatch' :
				$message = sprintf( _x( 'The extension you are licensing is unrecognized.', 'notice', 'groundhogg' ) );
				break;
			case 'missing_url' :
			case 'missing' :
				$message = sprintf( _x( 'This appears to be an invalid license key.', 'notice', 'groundhogg' ) );
				break;
			case 'no_activations_left':
				$message = _x( 'Your license key has reached its activation limit.', 'notice', 'groundhogg' );
				break;
			default :
				$message = _x( 'An error occurred, please try again.', 'notice', 'groundhogg' );
				break;
		}

		return $message . ' (' . $error . ')';
	}

	/**
	 * Activate a license quietly
	 *
	 * @param $license
	 * @param $item_id
	 *
	 * @return bool|\WP_Error
	 */
	public static function activate_license_quietly( $license, $item_id ) {

		$existing_license = self::get_license( $item_id );

		// If there is no change in the license...
		if ( $existing_license === $license ) {
			return true;
		}

		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => $item_id,// The ID of the item in EDD,
			// 'item_name'  => $item_name,
			'url'        => home_url(),
			'beta'       => false
		);

		$request = [
			'timeout'    => 15,
			'sslverify'  => true,
			'body'       => $api_params,
			'user-agent' => self::$user_agent,
		];

		$license_data = null;

		// Call the custom api.
		$response = wp_remote_post( static::$storeUrl, $request );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = ( is_wp_error( $response ) && $response->get_error_message() ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( false === $license_data->success ) {
				$message = self::get_license_error_message( $license_data->error, $license_data->expires );
			}
		}

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
			return new \WP_Error( 'license_failed', __( $message ), $license_data );
		}

		$status = 'valid';
		$expiry = $license_data->expires;

		self::add_extension( $item_id, $license, $status, $expiry );

		return true;
	}

	/**
	 * Activate a license key
	 *
	 * @param $license
	 * @param $item_id
	 *
	 * @return bool
	 */
	public static function activate_license( $license, $item_id ) {

		$result = self::activate_license_quietly( $license, $item_id );

		if ( is_wp_error( $result ) ) {
			notices()->add( $result );

			return false;
		}

		notices()->add( 'license_activated', __( 'License activated!', 'groundhogg' ) );

		return true;
	}

	/**
	 * Deactivate a license
	 *
	 * @param int|string $item_id_or_license
	 *
	 * @return bool
	 */
	public static function deactivate_license( $item_id_or_license = 0 ) {

		if ( is_int( $item_id_or_license ) ) {
			$item_id = $item_id_or_license;
			$license = self::get_license( $item_id );
		} else {
			$license = $item_id_or_license;
			$items   = self::get_extensions_by_license( $license );
			$item_id = array_pop( $items );
		}

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'item_id'    => $item_id,
			'license'    => $license,
			'url'        => home_url(),
		);

		$response = wp_remote_post( self::$storeUrl, array(
			'body'       => $api_params,
			'timeout'    => 15,
			'sslverify'  => false,
			'user-agent' => self::$user_agent,
		) );

		if ( is_wp_error( $response ) ) {
			$success = false;
			$message = _x( 'Something went wrong.', 'notice', 'groundhogg' );
		} else {
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $response->success === false ) {
				$success = false;
				$message = _x( 'Something went wrong.', 'notice', 'groundhogg' );
			} else {
				$success = true;
				$message = _x( 'License deactivated.', 'notice', 'groundhogg' );
			}
		}

		self::delete_extension( $item_id );

		$type = $success ? 'success' : 'error';
		Plugin::$instance->notices->add( 'license_outcome', $message, $type );

		return $success;
	}

	/**
	 * Verify that a license is in good standing.
	 *
	 * @param $item_id
	 * @param $license
	 *
	 * @return bool true if valid, false otherwise
	 */
	public static function verify_license( $item_id, $license = '' ) {

		if ( ! $item_id ) {
			return false;
		}

		if ( ! $license ) {
			$license = self::get_license( $item_id );
		}

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			'item_id'    => $item_id,
			'url'        => home_url()
		);

		$response = wp_remote_post( static::$storeUrl, array(
			'body'       => $api_params,
			'timeout'    => 15,
			'sslverify'  => true,
			'user-agent' => self::$user_agent,
		) );

		if ( is_wp_error( $response ) ) {
			// return true in the event of an error. Check again later...
			return true;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->success === true && $license_data->license === 'valid' ) {

			self::update_license_status( $item_id, 'valid', $license_data->expires );
		} else {

			$code    = $license_data->license;
			$message = self::get_license_error_message( $code, $license_data->expires );

			notices()->add( new \WP_Error( $code, $message, [
				'response' => $license_data
			] ) );

			self::update_license_status( $item_id, 'invalid', $license_data->expires );

			return false;
		}

		return true;
	}

	/**
	 * Get download package details of a plugin
	 *
	 * @param $item_id
	 * @param $license
	 *
	 * @return bool
	 */
	public static function get_version( $item_id, $license ) {
		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => $license,
			'item_id'    => $item_id,
			'url'        => home_url()
		);

		$response = wp_remote_post( static::$storeUrl, array(
			'body'       => $api_params,
			'timeout'    => 15,
			'sslverify'  => true,
			'user-agent' => self::$user_agent,
		) );

		if ( is_wp_error( $response ) ) {
			// return true in the event of an error. Check again later...
			return true;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * @return Extension[]
	 */
	public static function get_installed() {
		return Extension::get_extensions();
	}

	/**
	 * @param array $args
	 *
	 * @return array|mixed|object
	 */
	public static function get_store_products( $args = array() ) {
		$key = md5( serialize( $args ) );

		if ( get_transient( "gh_store_products_{$key}" ) ) {
			return get_transient( "gh_store_products_{$key}" );
		}

		$args = wp_parse_args( $args, array(
			//'category' => 'templates',
			'category' => '',
			'tag'      => '',
			's'        => '',
			'page'     => '',
			'number'   => '-1'
		) );

		$url = 'https://www.groundhogg.io/edd-api/v2/products/';

		$response = wp_remote_get( add_query_arg( $args, $url ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$products = json_decode( wp_remote_retrieve_body( $response ) );

		set_transient( "gh_store_products_{$key}", $products, WEEK_IN_SECONDS );

		return $products;
	}

	/**
	 * Get a list of extensions to promote on the welcome page
	 *
	 * @return array
	 */
	public static function get_extensions( $num = 4 ) {
		$products = self::get_store_products( array(
			'category' => [ 16, 9 ],
		) );

		if ( is_wp_error( $products ) ) {
			notices()->add( $products );

			return [];
		}

		$products = $products->products;

		$installed = self::get_installed();

		if ( ! empty( $installed ) ) {
			$keep = [];

			foreach ( $products as $i => $product ) {
				foreach ( $installed as $extension ) {
					if ( absint( $product->info->id ) !== $extension->get_download_id() ) {
						$keep[] = $product;
					}
				}
			}

			// Switch out.
			$products = $keep;
		}

		shuffle( $products );

		if ( $num > count( $products ) ) {
			$num = count( $products );
		}

		$rands      = array_rand( $products, $num );
		$extensions = [];

		foreach ( $rands as $rand ) {
			$extensions[] = $products[ $rand ];
		}

		return apply_filters( 'groundhogg/license_manager/get_extensions', $extensions );
	}

	/**
	 * Convert array to html article
	 *
	 * @param $args array
	 */
	public static function extension_to_html( $args = array() ) {
		/* I'm lazy so just covert it to an object*/
		$extension = (object) $args;

		$extension->info->link = add_query_arg( [
			'utm_source'   => get_bloginfo(),
			'utm_medium'   => 'extension-ad',
			'utm_campaign' => 'admin-links',
			'utm_content'  => sanitize_key( $extension->info->title ),
		], $extension->info->link );

		?>
		<div class="postbox">
			<?php if ( $extension->info->title ): ?>
				<h2 class="hndle"><b><?php echo $extension->info->title; ?></b></h2>
			<?php endif; ?>
			<div class="inside" style="padding: 0;margin: 0">
				<?php if ( $extension->info->thumbnail ): ?>
					<div class="img-container">
						<a href="<?php echo $extension->info->link; ?>" target="_blank">
							<img src="<?php echo $extension->info->thumbnail; ?>"
							     style="width: 100%;max-width: 100%;border-bottom: 1px solid #ddd">
						</a>
					</div>
				<?php endif; ?>
				<?php if ( $extension->info->excerpt ): ?>
					<div class="article-description" style="padding: 10px;">
						<?php echo $extension->info->excerpt; ?>
					</div>
					<hr/>
				<?php endif; ?>
				<?php if ( $extension->info->link ): ?>
					<div class="buy" style="padding: 10px">
						<?php $pricing = (array) $extension->pricing;
						if ( count( $pricing ) > 1 ) {

							$price1 = min( $pricing );
							$price2 = max( $pricing );

							?>
							<a class="button-secondary" target="_blank"
							   href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s - $%s)', 'action', 'groundhogg' ), $price1, $price2 ); ?></a>
							<?php
						} else {

							$price = array_pop( $pricing );

							if ( $price > 0.00 ) {
								?>
								<a class="button-secondary" target="_blank"
								   href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action', 'groundhogg' ), $price ); ?></a>
								<?php
							} else {
								?>
								<a class="button-secondary" target="_blank"
								   href="<?php echo $extension->info->link; ?>"> <?php _ex( 'Download', 'action', 'groundhogg' ); ?></a>
								<?php
							}
						}

						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php

	}
}
