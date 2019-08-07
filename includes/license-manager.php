<?php
namespace Groundhogg;


/**
 * Module Manager
 *
 * This class is a helper class for the settigns page. it essentially provides an api with Groundhogg.io for managing premium extension licenses.
 *
 * @package     Admin
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class License_Manager
{
    static $extensions = array(); // array( item_id => array( license, status ) )
    static $storeUrl = "https://www.groundhogg.io";

	/**
     * Add an extension to the extensions options.
     *
	 * @param $item_id int
	 * @param $license string
	 * @param $status string
	 * @param $expiry string
	 *
	 * @return bool
	 */
    public static function add_extension( $item_id, $license, $status, $expiry )
    {
        if ( empty( static::$extensions ) ){
            static::$extensions = get_option( "gh_extensions", array() );
        }

        static::$extensions[ $item_id ] = array(
            'license' => $license,
            'status' => $status,
	        'expiry' => $expiry
        );

        return update_option( "gh_extensions", static::$extensions );
    }

	/**
     * Remove an extension
	 * @param $item_id int
	 *
	 * @return bool
	 */
    public static function delete_extension( $item_id )
    {
	    if ( empty( static::$extensions ) ){
		    static::$extensions = get_option( "gh_extensions", array() );
		    if ( empty( static::$extensions ) ){
		        return false;
            }
	    }

	    unset( static::$extensions[ $item_id ] );

	    return update_option( "gh_extensions", static::$extensions );
    }

    public static function has_extensions()
    {
        if ( empty( static::$extensions ) )
            static::$extensions = get_option( "gh_extensions", array() );

        return ! empty( static::$extensions );
    }

    public static function get_license( $item_id )
    {
    	if ( empty( static::$extensions ) )
    		static::$extensions = get_option( "gh_extensions", array() );

        return static::$extensions[$item_id]['license'];
    }

    public static function get_license_status( $item_id )
    {
	    if ( empty( static::$extensions ) )
		    static::$extensions = get_option( "gh_extensions", array() );

	    if ( isset( static::$extensions[ $item_id ] ) ){
            return static::$extensions[ $item_id ][ 'status' ];
        }

        return false;

    }

    public static function update_license_status( $item_id, $status )
    {
        static::$extensions[ $item_id ][ 'status' ] = $status;
        return update_option( "gh_extensions", static::$extensions );
    }

	/**
	 * Activate a license
	 */
    public static function perform_activation()
    {
        if ( isset( $_POST['gh_activate_license'] ) ){

    		if ( ! current_user_can('manage_options' ) )
    			wp_die( "Cannot access this functionality" );

    		$licenses = $_POST[ 'licenses' ];

    		if ( ! is_array( $licenses ) ){
    		    wp_die( _x( 'Invalid license format', 'notice', 'groundhogg' ) );
            }

            foreach ( $licenses as $item_id => $license ){
                $license = trim( $license );
                $item_id = intval( trim( $item_id ) );

                if ( ! empty( $license ) && ! self::get_license_status( $license ) ){
                    self::activate_license( $license, $item_id );
                }
            }
	    }
    }

    public static function activate_license( $license, $item_id )
    {

        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_id'    => $item_id,// The ID of the item in EDD,
           // 'item_name'  => $item_name,
            'url'        => home_url(),
	        'beta'      => false
		);
		// Call the custom api.
		$response = wp_remote_post( static::$storeUrl, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $message =  ( is_wp_error( $response ) && $response->get_error_message() ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( false === $license_data->success ) {
                switch( $license_data->error ) {
                    case 'expired' :
                        $message = sprintf(
                            _x( 'Your license key expired on %s.', 'notice', 'groundhogg' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        break;
                    case 'revoked' :
                        $message = _x( 'Your license key has been disabled.', 'notice', 'groundhogg' );
                        break;
                    case 'missing' :
                        $message = _x( 'Invalid license.', 'notice', 'groundhogg' );
                        break;
                    case 'invalid' :
                    case 'site_inactive' :
                        $message = _x( 'Your license is not active for this URL.', 'notice', 'groundhogg' );
                        break;
                    case 'item_name_mismatch' :
                        $message = sprintf( _x( 'This appears to be an invalid license key', 'notice', 'groundhogg' ) );
                        break;
                    case 'no_activations_left':
                        $message = _x( 'Your license key has reached its activation limit.' , 'notice', 'groundhogg' );
                        break;
                    default :
                        $message = _x( 'An error occurred, please try again.', 'notice', 'groundhogg' );
                        break;
                }
            }
        }

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
            Plugin::$instance->notices->add( esc_attr( 'license_failed' ), __( $message ), 'error' );
        } else {
			$status = 'valid';
			$expiry = $license_data->expires;

            Plugin::$instance->notices->add( esc_attr( 'license_activated' ), _x( 'License activated', 'notice', 'groundhogg' ), 'success' );

            self::add_extension( $item_id, $license, $status, $expiry );

        }

		return $license_data->success;
    }

	/**
     * Deactivate a license
     *
	 * @param $license string
     * @return bool
	 */
    public static function deactivate_license( $item_id=0 )
    {

        $license = self::get_license( $item_id );

	    $api_params = array(
		    'edd_action' => 'deactivate_license',
		    'item_id'    => $item_id,
		    'license'    => $license,
		    'url'        => home_url(),
	    );

	    $response = wp_remote_post( self::$storeUrl, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );

	    if ( is_wp_error( $response ) ){
	        $success = false;
	        $message = _x( 'Something went wrong.', 'notice', 'groundhogg' );
        } else {
	        $response = json_decode( wp_remote_retrieve_body( $response ) );

	        if ( $response->success === false ){
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

    public static function verify_license( $item_id, $item_name, $license )
    {
        $api_params = array(
            'edd_action' => 'check_license',
            'license' => $license,
            'item_id' => $item_id,
            'url' => home_url()
        );

        $response = wp_remote_post( static::$storeUrl, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => true ) );

        if ( is_wp_error( $response ) ) {
            // return true in the event of an error. Check again later...
            return true;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if( isset( $license_data->license ) && $license_data->license == 'invalid' ) {
            self::update_license_status( $item_id, 'invalid' );
            return false;
        }

        return true;
    }

    /**
     * @return Extension[]
     */
    public static function get_installed()
    {
        return Extension::get_extensions();
    }

    /**
     * @param array $args
     * @return array|mixed|object
     */
    public static function get_store_products( $args=array() )
    {
        if ( get_transient( 'gh_store_products' ) ){
            return get_transient( 'gh_store_products' );
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

        if ( is_wp_error( $response ) ){
            return $response;
        }

        $products = json_decode( wp_remote_retrieve_body( $response ) );

        set_transient( 'gh_store_products', $products, WEEK_IN_SECONDS );

        return $products;
    }

    /**
     * Get a list of extensions to promote on the welcome page
     *
     * @return array
     */
    public static function get_extensions( $num = 4 )
    {
        $products = self::get_store_products( array(
            'category' => [ 16, 9 ],
        ) );

        if ( is_wp_error( $products ) ){
            Plugin::$instance->notices->add( $products );
            return [];
        }

        $products = $products->products;

        $installed = self::get_installed();

        if ( ! empty( $installed ) ){
            $keep = [];

            foreach ( $products as $i => $product ){
                foreach ( $installed as $extension ){
                    if ( absint( $product->info->id ) !== $extension->get_download_id() ){
                        $keep[] = $product;
                    }
                }
            }

            // Switch out.
            $products = $keep;
        }

        shuffle( $products );

        if ( $num > count( $products ) ){
            $num = count( $products );
        }

        $rands = array_rand( $products, $num );
        $extensions = [];

        foreach ( $rands as $rand ){
            $extensions[] = $products[ $rand ];
        }

        $extensions = apply_filters( 'groundhogg/license_manager/get_extensions', $extensions );
        return $extensions;
    }

    /**
     * Convert array to html article
     *
     * @param $args array
     */
    public static function extension_to_html( $args=array() )
    {
        /* I'm lazy so just covert it to an object*/
        $extension = (object) $args;

        $extension->info->link = add_query_arg( [
            'utm_source' => get_bloginfo(),
            'utm_medium' => 'extension-ad',
            'utm_campaign'  => 'admin-links',
            'utm_content'   => sanitize_key( $extension->info->title ),
        ], $extension->info->link );

        ?>
        <div class="postbox">
            <?php if ( $extension->info->title ): ?>
                <h2 class="hndle"><?php echo $extension->info->title; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ( $extension->info->thumbnail ): ?>
                    <div class="img-container">
                        <a href="<?php echo $extension->info->link; ?>" target="_blank">
                            <img src="<?php echo $extension->info->thumbnail; ?>" style="width: 100%;max-width: 100%;">
                        </a>
                    </div>
                    <hr/>
                <?php endif; ?>
                <?php if ( $extension->info->excerpt ): ?>
                    <div class="article-description">
                        <?php echo $extension->info->excerpt; ?>
                    </div>
                    <hr/>
                <?php endif; ?>
                <?php if ( $extension->info->link ): ?>
                    <p>
                        <?php $pricing = (array) $extension->pricing;
                        if (count($pricing) > 1) {

                            $price1 = min($pricing);
                            $price2 = max($pricing);

                            ?>
                            <a class="button-primary" target="_blank"
                               href="<?php echo $extension->info->link; ?>"> <?php printf( _x('Buy Now ($%s - $%s)', 'action', 'groundhogg'), $price1, $price2 ); ?></a>
                            <?php
                        } else {

                            $price = array_pop($pricing);

                            if ($price > 0.00) {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action','groundhogg' ), $price ); ?></a>
                                <?php
                            } else {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $extension->info->link; ?>"> <?php _ex('Download', 'action', 'groundhogg'); ?></a>
                                <?php
                            }
                        }

                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php

    }
}