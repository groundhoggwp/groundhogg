<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 3:28 PM
 */

class Groundhogg_Service_Manager
{

    public function __construct()
    {
        if ( is_admin() && current_user_can( 'manage_options' ) ){
            add_action( 'plugins_loaded', array( $this, 'connect_email_api' ) );
        }

    }

    /**
     * Sends a request to Groundhogg.io to add this domain
     * Request returns a text record and a list of DKIM records
     */
    public function connect_email_api()
    {

        if ( ! is_admin()
            || $this->email_api_is_active()
            || ! key_exists( 'action', $_GET )
            || 'connect_to_gh' !== $_GET['action']
            || ! current_user_can( 'manage_options' )
            || ! isset( $_GET[ 'token' ] )
        ){

            return;
        }

        $token  = sanitize_text_field( urldecode( $_GET[ 'token' ] ) );
        $gh_uid = intval( $_GET[ 'user_id' ] );

        $post = [
            'domain'    => str_replace( 'www.', '', parse_url( site_url(), PHP_URL_HOST ) ),
            'user_id'   => $gh_uid,
            'token'     => $token
        ];

        $response = wp_remote_post( 'https://www.groundhogg.io/wp-json/', array( 'body' => $post ) );

        if ( is_wp_error( $response ) ){
            return;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! isset( $json->TEXT ) || ! isset( $json->DKIM ) ){
            return;
        }

        wpgh_update_option( 'gh_email_api_user_id', $gh_uid );
        wpgh_update_option( 'gh_email_api_oauth_token', $token );
        wpgh_update_option( 'gh_email_api_check_verify_status', 1 );
        wpgh_update_option( 'gh_email_api_text_record', $json->TEXT );
        wpgh_update_option( 'gh_email_api_dkim_records', $json->DKIM );
    }

    /**
     * Send a request to Gorundhogg.io to verify this domains status
     * Request provides domain status, and if verified an email token to use for sending
     */
    public function check_verification_status()
    {
        $token  = wpgh_get_option( 'gh_email_api_oauth_token' );
        $gh_uid = wpgh_get_option( 'gh_email_api_user_id' );

        $post = [
            'domain'    => str_replace( 'www.', '', parse_url( site_url(), PHP_URL_HOST ) ),
            'user_id'   => $gh_uid,
            'token'     => $token
        ];

        $response = wp_remote_post( 'https://www.groundhogg.io/wp-json/', array( 'body' => $post ) );

        if ( is_wp_error( $response ) ){
            return;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ) );

//        $json

    }

    public function email_api_is_active()
    {
        return wpgh_is_option_enabled( 'gh_send_with_gh_api' );
    }

}