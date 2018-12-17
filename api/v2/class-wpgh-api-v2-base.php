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
        if( isset( $request['token'] ) && isset( $request['key'] ) ) {
            //validate user
            global $wpdb;
            $token      = $request['token'];
            $public_key = $request['key'];
            $user       = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wpgh_user_public_key' AND meta_value = %s LIMIT 1", $public_key ) );
            if ( $user != NULL ) {
                $secret = get_user_meta($user,'wpgh_user_secret_key',true);
                $valid = $this->check_keys( $secret, $public_key, $token );
                if ( $valid ) {
                    $request['wpgh_user_id'] = $user;
                    return  true;
                } else {
                    return new WP_Error( 'error','Invalid Authentication.'  );
                }
            } else {
                return new WP_Error( 'error','API key is not valid.',array('user' => $user) );
            }

        } else {
            return new WP_Error( 'error','Please Enter Token and Key for API.' );
        }

        return true;
    }

    public function check_keys( $secret, $public, $token ) {
        return hash_equals( md5( $secret . $public ), $token );
    }

}
