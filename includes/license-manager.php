<?php

namespace Groundhogg;


use Groundhogg\Utils\DateTimeHelper;
use WP_Error;

/**
 * Module Manager
 *
 * This class is a helper class for the settings page. it essentially provides an api with Groundhogg.io for managing premium extension licenses.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class License {

	public int $item_id = 0;
	public string $item_name = '';
	public string $description = '';
	public string $license = '';
	public string $expires = '';

	public int $payment_id = 0;
	public string $customer_name = '';
	public string $customer_email = '';

	public string $price_id = '';
	public int $license_limit = 0;
	public int $site_count = 0;

	public int $activations_left = 0;

	public bool $is_local = false;

	/**
	 * Download IDs this license grants access to.
	 *
	 * @var int[]
	 */
	public array $items = [];

	/**
	 * @param  array  $data  API response.
	 */
	public function __construct( array $data = [] ) {
		$this->hydrate( $data );
	}

	/**
	 * Create from an API response.
	 */
	public static function from_array( array $data ): self {
		return new self( $data );
	}

	/**
	 * Populate the license from API data.
	 */
	protected function hydrate( array $data ): void {

		if ( array_key_exists( 'item_id', $data ) ) {
			$this->item_id = (int) $data['item_id'];
		}

		if ( array_key_exists( 'item_name', $data ) ) {
			$this->item_name = (string) $data['item_name'];
		}

		if ( array_key_exists( 'description', $data ) ) {
			$this->description = (string) $data['description'];
		}

		if ( array_key_exists( 'success', $data ) ) {
			$this->success = (bool) $data['success'];
		}

		if ( array_key_exists( 'license', $data ) ) {
			$this->license = (string) $data['license'];
		}

		if ( array_key_exists( 'checksum', $data ) ) {
			$this->checksum = (string) $data['checksum'];
		}

		if ( array_key_exists( 'expires', $data ) ) {
			$this->expires = (string) $data['expires'];
		}

		if ( array_key_exists( 'payment_id', $data ) ) {
			$this->payment_id = (int) $data['payment_id'];
		}

		if ( array_key_exists( 'customer_name', $data ) ) {
			$this->customer_name = (string) $data['customer_name'];
		}

		if ( array_key_exists( 'customer_email', $data ) ) {
			$this->customer_email = (string) $data['customer_email'];
		}

		if ( array_key_exists( 'price_id', $data ) ) {
			$this->price_id = (string) $data['price_id'];
		}

		if ( array_key_exists( 'license_limit', $data ) ) {
			$this->license_limit = (int) $data['license_limit'];
		}

		if ( array_key_exists( 'site_count', $data ) ) {
			$this->site_count = (int) $data['site_count'];
		}

		if ( array_key_exists( 'activations_left', $data ) ) {
			$this->activations_left = (int) $data['activations_left'];
		}

		if ( array_key_exists( 'is_local', $data ) ) {
			$this->is_local = (bool) $data['is_local'];
		}

		if ( array_key_exists( 'items', $data ) ) {
			$this->items = array_values(
				array_unique(
					wp_parse_id_list( $data['items'] )
				)
			);
		}
	}

	/**
	 * Whether this license never expires.
	 */
	public function is_lifetime(): bool {
		return strtolower( $this->expires ) === 'lifetime';
	}

	/**
	 * Whether the license is expired.
	 */
	public function is_expired(): bool {

		if ( $this->is_lifetime() ) {
			return false;
		}

		if ( ! $this->expires ) {
			return true;
		}

		$expires = strtotime( $this->expires );

		if ( ! $expires ) {
			return true;
		}

		return $expires < time();
	}

	/**
	 * Whether the license is currently valid.
	 */
	public function is_valid(): bool {
		return (
			$this->license === 'valid'
			&& ! $this->is_expired()
		);
	}

	/**
	 * Whether this license grants access to a particular download.
	 */
	public function can_access( int $download_id ): bool {
		return in_array( $download_id, $this->items, true );
	}

	/**
	 * Whether another site activation is available.
	 */
	public function has_activations_left(): bool {

		// 0 is commonly used for unlimited licenses.
		if ( $this->license_limit === 0 ) {
			return true;
		}

		return $this->activations_left > 0;
	}

	/**
	 * Number of downloads granted by this license.
	 */
	public function get_item_count(): int {
		return count( $this->items );
	}

	/**
	 * Convert back into an array.
	 */
	public function to_array(): array {
		return [
			'item_id'          => $this->item_id,
			'item_name'        => $this->item_name,
			'license'          => $this->license,
			'expires'          => $this->expires,
			'payment_id'       => $this->payment_id,
			'customer_name'    => $this->customer_name,
			'customer_email'   => $this->customer_email,
			'price_id'         => $this->price_id,
			'license_limit'    => $this->license_limit,
			'site_count'       => $this->site_count,
			'activations_left' => $this->activations_left,
			'is_local'         => $this->is_local,
			'items'            => $this->items,
			'description'      => $this->description,
		];
	}

	/**
	 * PHP serialization.
	 */
	public function __serialize(): array {
		return $this->to_array();
	}

	/**
	 * PHP unserialization.
	 */
	public function __unserialize( array $data ): void {
		$this->hydrate( $data );
	}

	public function expiry() {
		return $this->is_lifetime() ? 'never' : ( new DateTimeHelper( $this->expires ) )->date_i18n();
	}

	/**
	 * Package responses will include some information about the license, whether it's expired or whatever
	 *
	 * @param  array  $package_response
	 *
	 * @return License
	 */
	public function update( array $package_response ) {

        // remove these keys if they are empty so that they don't get overridden if unsupplied
        foreach ( [ 'item_id', 'item_name' ] as $key ) {
            if ( array_key_exists( $key, $package_response ) && empty( $package_response[$key] ) ) {
                unset( $package_response[$key] );
            }
        }

		$this->hydrate( $package_response );

		return $this;
	}
}

class License_Manager {

	/**
     * Array of licenses
     *
	 * @var License[]
	 */
	static array $licenses;
	static $storeUrl = "https://groundhogg.io/license-api/";
	static $user_agent = 'Groundhogg/' . GROUNDHOGG_VERSION . ' license-manager';

	public function __construct() {
        add_action( 'admin_post_groundhogg_add_license', [ __CLASS__, 'handle_add_license_form' ] );
		add_action( 'admin_post_groundhogg_manage_license', [ __CLASS__, 'handle_manage_license_form' ] );
	}

	/**
	 * Sanitize a license api response from the check/activate endpoints...
	 *
	 * @param $api_response
	 *
	 * @return array
	 */
	public static function sanitize_license_respones( $api_response ) {
		return array_apply_callbacks( (array) $api_response, [
			'item_id'          => 'absint',
			'item_name'        => 'sanitize_text_field',
			'description'      => 'sanitize_text_field',
			'license'          => fn( $status ) => one_of( $status, [ 'valid', 'invalid', 'expired', 'disabled', 'site_inactive' ] ),
			'license_limit'    => 'absint',
			'site_count'       => 'absint',
			'activations_left' => 'absint',
			'items'            => 'wp_parse_id_list',
			'payment_id'       => 'absint',
			'expires'          => function ( $expires ) {
				if ( $expires === 'lifetime' ) {
					return $expires;
				}

				try {
					return ( new DateTimeHelper( $expires ) )->ymdhis();
				} catch ( \Exception $e ) {
					return '?';
				}
			}
		], true );
	}

	/**
	 * Save a license to the site's options. We'll save the whole license object as opposed to whatever we were doing before
	 *
	 * @param  string  $license
	 * @param  object  $api_response
	 *
	 * @return License
	 */
	public static function add_license( string $license, object $api_response ) {

		$licenses = self::get_licenses();

		if ( empty( $licenses ) ) {
			$licenses = [];
		}

		$sanitized = self::sanitize_license_respones( $api_response );

		self::$licenses[ $license ] = License::from_array( $sanitized );

		self::save_licenses();

		return $licenses[ $license ];
	}

	/**
	 * Update a license
	 *
	 * @param $license
	 * @param  object  $api_response
	 *
	 * @return License
	 */
	public static function update_license( $license, object $api_response ) {

		self::get_licenses();

		$sanitized = self::sanitize_license_respones( $api_response );
		$existing  = self::$licenses[ $license ] ?? null;

        if ( ! $existing ) {
            $existing = License::from_array( $sanitized );
        } else {
            $existing->update( $sanitized );
        }

		self::$licenses[ $license ] = $existing;

		self::save_licenses();

		return $existing;

	}

	/**
	 * Retrieve the site's master license, which is any valid license that can access more than one item
	 *
	 * @return string|false the master license key or false if unavailable
	 */
	public static function get_master_license() {

		$licenses = self::get_licenses();

		foreach ( $licenses as $key => $license ) {
			if ( $license->is_valid() && $license->get_item_count() > 1 ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Return the licenses
	 *
	 * @return License[]
	 */
	public static function get_licenses() {

        if ( empty( self::$licenses ) ){
            self::$licenses = get_option( 'gh_licenses', [] );
        }

        return self::$licenses;
	}

    public static function save_licenses() {
	    update_option( 'gh_licenses', self::$licenses );
    }

	/**
	 * Whether an item has a valid license
	 *
	 * @throws \Exception
	 *
	 * @param  int  $item_id
	 *
	 * @return bool
	 */
	public static function has_valid_license( int $item_id ) {
		return self::is_licensed( $item_id );
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
		return array_filter( self::get_licenses(), function ( License $license ) {
			return ! $license->is_valid();
		} );
	}

	/**
	 * Will get a specific license for a given item
	 * Checks all registered licenses to see if they have access to the download
	 * If more than one license can handle it, prefer one that is active
	 *
	 * @param  int|bool  $item_id the item to check
	 * @param  bool  $must_be_valid whether only a valid license should be returned
	 *
	 * @return string|false the license key for a specific item ID
	 */
	public static function get_license( $item_id = false, $must_be_valid = false ) {

		if ( $item_id === false ) {
			return self::get_master_license();
		}

		$licenses = self::get_licenses();
        $found    = false;

		foreach ( $licenses as $key => $license ) {
			if ( $license->can_access( $item_id ) ) {

                if ( $license->is_valid() ){
	                return $key;
                }

                $found = $key;
			}
		}

        // no valid key was available, otherwise we would have returned it earlier
        if ( $must_be_valid ){
            return false;
        }

        // return any found key
		return $found;
	}

	/**
	 * Whether a given item is licensed
	 *
	 * @param  int  $item_id
	 *
	 * @return bool whether there is a valid license for a given item
	 */
	public static function is_licensed( int $item_id, $must_be_valid = false ) {
		$license = self::get_license( $item_id, $must_be_valid );
        return $license !== false;
	}

	public static function license_is_registered( string $license ) {
		return key_exists( $license, self::get_licenses() );
	}

	public static function is_valid( string $license ) {
        return self::license_is_registered( $license ) && self::get_licenses()[ $license ]->is_valid();
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
				$date    = new DateTimeHelper( $expiry );
				$message = sprintf(
                    /* translators: %s: license expiry date */
					esc_html_x( 'Your license key expired on %s.', 'notice', 'groundhogg' ), $date->i18n()
				);
				break;
			case 'invalid' :
			case 'disabled' :
				$message = esc_html_x( 'Your license key has been disabled.', 'notice', 'groundhogg' );
				break;
			case 'site_inactive' :
				$message = esc_html_x( 'Your license is not active for this URL.', 'notice', 'groundhogg' );
				break;
			case 'key_mismatch' :
			case 'invalid_item_id' :
			case 'item_name_mismatch' :
				$message = esc_html_x( 'The extension you are licensing is unrecognized.', 'notice', 'groundhogg' );
				break;
			case 'missing_url' :
			case 'missing' :
				$message = esc_html_x( 'This appears to be an invalid license key.', 'notice', 'groundhogg' );
				break;
			case 'no_activations_left':
				$message = esc_html_x( 'Your license key has reached its activation limit.', 'notice', 'groundhogg' );
				break;
			default :
				$message = esc_html_x( 'An error occurred, please try again.', 'notice', 'groundhogg' );
				break;
		}

		return esc_html( $message );
	}

	/**
	 * Call Groundhogg's licensing API
	 *
	 * @param  string  $action
	 * @param  string  $license
	 *
	 * @return mixed|WP_Error
	 */
	public static function licence_api_request( string $action, string $license = '' ) {

		$api_params = array(
			'edd_action' => $action,
			'license'    => $license,
			'url'        => home_url()
		);

		$response = wp_remote_post( static::$storeUrl, array(
			'body' => $api_params,
			'timeout'    => 15,
			'sslverify'  => true,
			'user-agent' => self::$user_agent,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        if ( 200 !== wp_remote_retrieve_response_code( $response ) ){
            return new WP_Error( 'request_error', esc_html__( 'An error occurred, please try again.', 'groundhogg' ) );
        }

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * @param array $args
	 *
	 * @return array|mixed|object
	 */
	public static function get_store_products( $args = array() ) {

		$args = wp_parse_args( $args, array(
			//'category' => 'templates',
			'category' => '',
			'tag'      => '',
			's'        => '',
			'page'     => '',
			'number'   => '-1'
		) );

		$url = 'https://groundhogg.io/edd-api/v2/products/';

		return remote_post_json( $url, $args, 'GET', [], false, DAY_IN_SECONDS );
	}

	/**
	 * Shows a form to manage the master license
	 *
	 * if no license, show an activation form to add the license
	 * if there is a license, show the field and check/deactivate buttons
	 *  --> if the license is expired, show a notice that it's expired or otherwise invalid
	 *
	 * @return void
	 */
	public static function add_license_form() {

		?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="groundhogg_add_license">
			<?php

			wp_nonce_field( 'groundhogg_add_license' );

			html( 'div', [ 'id' => 'master-license-form' ], [
				html()->e( 'h3', [], __( 'Activate License', 'groundhogg' ) ),
				html()->e( 'p', [], esc_html__( 'If you have a plan or add-on license enter it here!', 'groundhogg' ) ),
				html()->div( [ 'class' => 'display-flex gap-5' ], [
					html()->input( [
						'type'        => 'password',
						'placeholder' => __( 'License Key', 'groundhogg' ),
						'name'        => 'new_license'
					] ),
					html()->input( [
						'type'  => 'submit',
						'name'  => 'activate_new_license',
						'class' => 'gh-button primary',
						'value' => esc_html__( 'Activate', 'groundhogg' ),
					] )
				] ),

			] );

			?>
        </form>
		<?php
	}

	/**
	 * Activate a license with the remote api
	 *
	 * @param  string  $license
	 *
	 * @return License|WP_Error
	 */
	public static function activate_license( $license ) {

		$response = self::licence_api_request( 'activate_license', $license );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        // don't add license with error
        if ( isset( $response->error ) ){
            $message = self::get_license_error_message( $response->error, $response->expires );
            return new WP_Error( 'license_error', $message );
        }

        // don't add invalid licenses
        if ( $response->license !== 'valid' ){
	        return new WP_Error( 'license_error', esc_html__( 'Invalid license key.', 'groundhogg' ) );
        }

		return self::add_license( $license, $response );
	}

	/**
     * Run a license through the check API
     *
	 * @param $license
	 *
	 * @return License|mixed|WP_Error|null
	 */
	public static function check_license( $license ) {

        // don't check the license more than once during the same request
        if ( flagged( __METHOD__ . ':' . $license ) ){
            return self::$licenses[ $license ] ?? null;
        }

        flagged( __METHOD__ . ':' . $license, true );

		$response = self::licence_api_request( 'check_license', $license );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        // update the license regardless of the license status
		$current = self::update_license( $license, $response );

        // if the license is not valid, return an error
        if ( ! $current->is_valid() ){
            return new WP_Error( 'license_error', self::get_license_error_message( $response->license, $current->expires ) );
        }

        return $current;
	}

	/**
	 * Deactivate a license with the remote api
	 *
	 * @param $license
	 *
	 * @return mixed|true|WP_Error|null
	 */
	public static function deactivate_license( $license ) {

		$response = self::licence_api_request( 'deactivate_license', $license );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        if ( $response->license !== 'deactivated' ){
            return new WP_Error( 'license_error', esc_html__( 'An error occurred, please try again.', 'groundhogg' ) );
        }

        // this will instantiate the license object if empty
        self::get_licenses();

		unset( self::$licenses[ $license ] );

		self::save_licenses();

		return true;
	}

	public static function go_back() {

	    wp_safe_redirect(
		    wp_get_referer()
	    );

	    exit;
    }

	/**
	 * handle registering and activating a new license key
	 *
	 * @return void
	 */
	public static function handle_add_license_form() {

		check_admin_referer( 'groundhogg_add_license' );

		if ( ! current_user_can( 'manage_gh_licenses' ) ) {
			wp_die( esc_html__( 'Invalid permissions.', 'groundhogg' ), 'No Access!' );
		}

		$license = sanitize_text_field( get_request_var( 'new_license' ) );

		$result = self::activate_license( $license );

		if ( is_wp_error( $result ) ) {
			notices()->add_user_notice( $result->get_error_message(), 'error' );
			self::go_back();
		}

		notices()->add_user_notice( __( 'License activated!', 'groundhogg' ) );

		self::go_back();
	}

	/**
	 * Show a grid of license forms for managing licenses
	 *
	 * @return void
	 */
	public static function manage_licenses() {

		$licenses = self::get_licenses();

		if ( empty( $licenses ) ) {
			return;
		}

		html( 'h3', [], esc_html__( 'Your Licenses', 'groundhogg' ) );
		html( 'p', [], esc_html__( 'Manage your licenses below.', 'groundhogg' ) );

		?>
        <div class="display-grid gap-20"><?php

		foreach ( $licenses as $key => $license ):
			?>
        <form class="span-6" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="groundhogg_manage_license">
			<?php


			if ( $license->is_valid() ) {
				$prefix = '';
                $class  = '';
				// translators: the expiry date
				$message = sprintf( esc_html__( 'This license is valid and expires %s.', 'groundhogg' ), $license->expiry() );
			} elseif ( $license->is_expired() ) {
				$prefix  = '⚠️';
				$message = esc_html__( 'This license is expired. Please renew it now.', 'groundhogg' );
				$class   = 'gh-text red';
			} else {
				$prefix  = '⚠️';
				$message = esc_html__( 'This license is no longer valid. Contact customer support for assistance.', 'groundhogg' );
                $class   = 'gh-text red';
			}

			wp_nonce_field( 'groundhogg_manage_license' );

			html( 'div', [ 'class' => 'gh-panel' ], [
				html()->e( 'div', [ 'class' => 'gh-panel-header' ], html()->e( 'h2', [], esc_html( $prefix . ' ' . $license->item_name ) ) ),
				html()->e( 'div', [ 'class' => 'inside' ], [
					html()->e( 'p', [], esc_html( $license->description ) ),
					html()->div( [ 'class' => 'display-flex gap-5' ], [
						html()->input( [
							'type'     => 'password',
							'name'     => 'license',
							'value'    => $key,
							'readonly' => true,
						] ),
						html()->input( [
							'type'  => 'submit',
							'name'  => 'check_license',
							'class' => 'gh-button secondary',
							'value' => esc_html__( 'Sync', 'groundhogg' ),
						] ),
						html()->input( [
							'type'  => 'submit',
							'name'  => 'deactivate_license',
							'class' => 'gh-button danger text',
							'value' => esc_html__( 'Remove', 'groundhogg' ),
						] )
					] ),
					html()->e( 'p', [ 'class' => $class ], $message ),
				] ),
			] );

			?></form><?php

		endforeach;

		?></div><?php
	}

	public static function handle_manage_license_form() {

		check_admin_referer( 'groundhogg_manage_license' );

		$license = sanitize_text_field( get_request_var( 'license' ) );

		$exit = fn() => wp_safe_redirect(
			wp_get_referer()
		);

		if ( get_post_var( 'check_license' ) ) {

            $result = self::check_license( $license );

            if ( is_wp_error( $result ) ) {
                notices()->add_user_notice( $result->get_error_message(), 'error' );
            } else {
                notices()->add_user_notice( __( 'License synced!', 'groundhogg' ) );
            }

			self::go_back();
		}

        if ( get_post_var( 'deactivate_license' ) ) {
            $result = self::deactivate_license( $license );

            if ( is_wp_error( $result ) ) {
                notices()->add_user_notice( $result->get_error_message(), 'error' );
            } else {
                notices()->add_user_notice( __( 'License removed!', 'groundhogg' ) );
            }

	        self::go_back();
        }

        wp_die( esc_html__( 'Unhandled request.', 'groundhogg' ), 'Whoops!' );

	}
}
