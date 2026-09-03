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
        add_filter( 'pre_option_gh_master_license', [ $this, 'pre_get_option_gh_master_license' ] );
	}

	/**
     * If anywhere still uses get_option( 'gh_master_license' ) inject the master license found here.
     *
	 * @return string|null return null if no license so that get_option doesn't run the query
	 */
    public function pre_get_option_gh_master_license() {
	    $license = self::get_master_license();

	    return empty( $license ) ? null : $license;
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
	 * @param  string  $license_key
	 * @param  object  $api_response
	 *
	 * @return License
	 */
	public static function add_license( string $license_key, object $api_response ) {

		$licenses = self::get_licenses();

		if ( empty( $licenses ) ) {
			$licenses = [];
		}

		$sanitized = self::sanitize_license_respones( $api_response );

		self::$licenses[ $license_key ] = License::from_array( $sanitized );

		self::save_licenses();

		return $licenses[ $license_key ];
	}

	/**
	 * Update a license
	 *
	 * @param  string  $license_key
	 * @param  object  $api_response
	 *
	 * @return License
	 */
	public static function update_license( string $license_key, object $api_response ) {

		self::get_licenses();

		$sanitized = self::sanitize_license_respones( $api_response );
		$existing  = self::$licenses[ $license_key ] ?? null;

        if ( ! is_a( $existing, License::class ) ) {
            $existing = License::from_array( $sanitized );
        } else {
            $existing->update( $sanitized );
        }

		self::$licenses[ $license_key ] = $existing;

		self::save_licenses();

		return $existing;

	}

	/**
	 * Retrieve the site's master license, which is any valid license that can access more than one item
     * If no valid license is found, it returns a potentially invalid license with access to multiple items
	 *
	 * @return string|false the master license key or false if unavailable
	 */
	public static function get_master_license() {

		if ( defined( 'GH_MASTER_LICENSE' ) ) {
			return GH_MASTER_LICENSE;
		}

		$licenses = self::get_licenses();

        $found = false;

		foreach ( $licenses as $key => $license ) {
			if ( $license->get_item_count() > 1 ) {
				$found = $key;
                if ( $license->is_valid() ){
                    return $key;
                }
			}
		}

		return $found;
	}

	/**
	 * Return the licenses
	 *
	 * @return License[]
	 */
	public static function get_licenses() {

        if ( empty( self::$licenses ) ){
            self::$licenses = get_option( 'gh_licenses', [] );

            // filter out funky stuff
	        self::$licenses = array_filter( self::$licenses, function ( License $license, $key ) {
		        return ! empty( $key ) && is_a( $license, License::class );
	        }, ARRAY_FILTER_USE_BOTH );
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

        // return the master license if set in WP_CONFIG
        if ( defined( 'GH_MASTER_LICENSE' ) ) {
            return GH_MASTER_LICNESE;
        }

		if ( $item_id === false ) {
			return self::get_master_license();
		}

		$licenses = self::get_licenses();
        $found    = false;

		foreach ( $licenses as $license_key => $license ) {
			if ( $license->can_access( $item_id ) ) {

                if ( $license->is_valid() ){
	                return $license_key;
                }

                $found = $license_key;
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

	public static function license_is_registered( string $license_key ) {
		return key_exists( $license_key, self::get_licenses() );
	}

	public static function is_valid( string $license_key ) {
        return self::license_is_registered( $license_key ) && self::get_licenses()[ $license_key ]->is_valid();
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
	 * @param  string  $license_key
	 *
	 * @return mixed|WP_Error
	 */
	public static function licence_api_request( string $action, string $license_key = '' ) {

		return remote_post_json( static::$storeUrl, [
            'edd_action' => $action,
            'license'    => $license_key,
            'url'        => home_url()
        ], 'FORM' );

	}

	/**
     * Return a list of accessible items based on the installed licenses
     *
	 * @return int[]
	 */
    public static function get_accessible_items() {

        $items = [];

        foreach ( self::get_licenses() as $license ){
            $items = array_merge( $items, $license->items );
        }

        return array_unique( $items );
    }

	/**
	 * @param array $args
	 *
	 * @return array|mixed|object
	 */
	public static function get_store_products( $args = array() ) {

		$args = wp_parse_args( $args, array(
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
	 * @param  string $license_key the license key
	 * @param  bool   $save_anyway whether to add the license reponse as a License even if it's invalid
	 *
	 * @return License|WP_Error
	 */
	public static function activate_license( string $license_key, $save_anyway = false ) {

		if ( defined( 'GH_MASTER_LICENSE' ) && $license_key === GH_MASTER_LICENSE ){
			return new WP_Error( 'license_error', esc_html__( 'License is configured in `wp-config.php`.', 'groundhogg' ) );
		}

		$response = self::licence_api_request( 'activate_license', $license_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        // don't add license with error
        if ( isset( $response->error ) && $response->error && ! $save_anyway ){
            $message = self::get_license_error_message( $response->error, $response->expires );
            return new WP_Error( 'license_error', $message );
        }

		return self::add_license( $license_key, $response );
	}

	/**
	 * Run a license through the check API
	 *
	 * @param  string  $license_key a license key
	 *
	 * @return License|WP_Error
	 */
	public static function check_license( string $license_key ) {

        if ( empty( $license_key ) ){
            return new WP_Error( 'license_error', esc_html__( 'License is empty.', 'groundhogg' ) );
        }

        if ( defined( 'GH_MASTER_LICENSE' ) && $license_key === GH_MASTER_LICENSE ){
            return new WP_Error( 'license_error', esc_html__( 'License is configured in `wp-config.php`.', 'groundhogg' ) );
        }

        // don't check the license more than once during the same request
        if ( flagged( __METHOD__ . ':' . $license_key ) ){
            return self::$licenses[ $license_key ] ?? null;
        }

        flagged( __METHOD__ . ':' . $license_key, true );

		$response = self::licence_api_request( 'check_license', $license_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        // update the license regardless of the license status
		$license = self::update_license( $license_key, $response );

        // if the license is not valid, return an error
        if ( ! $license->is_valid() ){
            return new WP_Error( 'license_error', self::get_license_error_message( $response->license, $license->expires ) );
        }

        return $license;
	}

	/**
     * Checks all the registered licenses
     *
	 * @return \WP_Error[]|true array of errors if there were any, or true of all licenses validated successfully
	 */
	public static function check_all() {

        $errors = [];

        foreach ( self::get_licenses() as $license_key => $license ){
            $result = self::check_license( $license_key );

            if ( is_wp_error( $result ) ){
                $errors[] = $result;
            }
        }

        return empty( $errors ) ? true : $errors;
    }

	/**
	 * Deactivate a license with the remote api
	 *
	 * @param  string  $license_key
	 *
	 * @return mixed|true|WP_Error|null
	 */
	public static function deactivate_license( string $license_key ) {

		if ( defined( 'GH_MASTER_LICENSE' ) && $license_key === GH_MASTER_LICENSE ){
			return new WP_Error( 'license_error', esc_html__( 'License is configured in `wp-config.php`.', 'groundhogg' ) );
		}

		$response = self::licence_api_request( 'deactivate_license', $license_key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        // this will instantiate the license object if empty
        self::get_licenses();

		unset( self::$licenses[ $license_key ] );

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

		$license_key = sanitize_text_field( get_request_var( 'new_license' ) );

		$result = self::activate_license( $license_key );

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
	 * @return bool
	 */
	public static function manage_licenses() {

		$licenses = self::get_licenses();

		if ( empty( $licenses ) ) {
			return false;
		}

		html( 'h3', [], esc_html__( 'Your Licenses', 'groundhogg' ) );
		html( 'p', [], esc_html__( 'Manage your licenses below.', 'groundhogg' ) );

		?>
        <div class="display-grid gap-20"><?php

		foreach ( $licenses as $license_key => $license ):
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
							'value'    => $license_key,
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

		return true;
	}

	public static function handle_manage_license_form() {

		check_admin_referer( 'groundhogg_manage_license' );

		$license_key = sanitize_text_field( get_request_var( 'license' ) );

		if ( get_post_var( 'check_license' ) ) {

            $result = self::check_license( $license_key );

            if ( is_wp_error( $result ) ) {
                notices()->add_user_notice( $result->get_error_message(), 'error' );
            } else {
                notices()->add_user_notice( __( 'License synced!', 'groundhogg' ) );
            }

			self::go_back();
		}

        if ( get_post_var( 'deactivate_license' ) ) {
            $result = self::deactivate_license( $license_key );

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
