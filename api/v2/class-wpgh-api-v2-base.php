<?php
/**
 * Groundhogg API
 *
 * This class provides a front-facing JSON API that makes it possible to
 * query data.
 *
 *
 * @package     WPGH
 * @subpackage  Classes/API
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_API_V2 Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
abstract class WPGH_API_V2_BASE {

    public function __construct()
    {
        //initialize api if user check the api section
        add_action('groundhogg/api/v2/init', array( $this, 'register_routes' ) );
    }

    abstract public function register_routes();

    public function rest_authentication( WP_REST_Request $request )
    {
        /* Check if the API is enabled... */
        if ( ! wpgh_is_option_enabled( 'gh_enable_api' ) ){
            return new WP_Error( 'api_unavailable', 'The api has been disabled by the administrator.', [ 'status' => 403 ] );
        }

        $token = $request->get_header( 'gh_token' );
        $key = $request->get_header( 'gh_public_key' );

        if ( ! $token || ! $key ){
            $token = $request->get_param( 'token' );
            $key = $request->get_param( 'key' );
        }

        if( $token && $key ) {

            //validate user
            global $wpdb;

            $user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wpgh_user_public_key' AND meta_value = %s LIMIT 1", $key ) );

            if ( $user != NULL ) {
                $secret = get_user_meta($user,'wpgh_user_secret_key',true);
                $valid = $this->check_keys( $secret, $key, $token );
                if ( $valid ) {

                    $request->set_param( 'wpgh_user_id', $user );

                } else {
                    return new WP_Error( 'error',_x( 'Invalid Authentication.', 'api', 'groundhogg' ), [ 'status' => 401 ] );
                }
            } else {
                return new WP_Error( 'error',_x( 'API key is not valid.', 'api', 'groundhogg' ), [ 'status' => 401 ] );
            }

        } else {

            return new WP_Error( 'error',_x( 'Please enter a API valid token and public key.', 'api', 'groundhogg' ), [ 'status' => 401 ] );

        }


        return true;
    }

    public function check_keys( $secret, $public, $token ) {
        return hash_equals( md5( $secret . $public ), $token );
    }

}
