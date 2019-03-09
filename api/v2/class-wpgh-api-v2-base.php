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
class WPGH_API_V2_BASE {

    public function rest_authentication( WP_REST_Request $request )
    {
        // validate user and set user id for contact operations..

        $token = $request->get_header( 'GH_TOKEN' );
        $key = $request->get_header( 'GH_PUBLIC_KEY' );

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
                    return new WP_Error( 'error',_x( 'Invalid Authentication.', 'api', 'groundhogg' ) );
                }
            } else {
                return new WP_Error( 'error',_x( 'API key is not valid.', 'api', 'groundhogg' ) );
            }

        } else {

            return new WP_Error( 'error',_x( 'Please enter a API valid token and public key.', 'api', 'groundhogg' ));

        }

        $request->set_param( 'token', '*****' . substr( $token, strlen( $token ) - 5 ) );
        $request->set_param( 'key', '*****' . substr( $key, strlen( $key ) - 5 ) );


        return true;
    }

    public function check_keys( $secret, $public, $token ) {
        return hash_equals( md5( $secret . $public ), $token );
    }

}
