<?php
/**
 * Groundhogg API base
 *
 * This class provides an absic architecture for child API classes.
 *
 * @package     WPGH
 * @subpackage  Classes/API
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_API_V3_BASE Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
abstract class WPGH_API_V3_BASE {


    const NAMESPACE = 'gh/v3';

    /**
     * @var WP_User
     */
    protected static $current_user;

    /**
     * WPGH_API_V3_BASE constructor.
     */
    public function __construct()
    {
        add_action( 'groundhogg/api/v3/init', array( $this, 'register_routes' ) );
    }

    /**
     * Register the relevant REST routes
     *
     * @return mixed
     */
    abstract public function register_routes();

    /**
     * Set the current user prop
     *
     * @param $id int user ID
     */
    protected static function set_current_user( $id )
    {
        self::$current_user = get_userdata( $id );
    }

    /**
     * Return an error code with modified HTTP Status
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @param int $http_response
     * @return WP_Error
     */
    protected static function ERROR_CODE( $code='', $msg='', $data=[], $http_response=500 )
    {
        return new WP_Error( $code, $msg, [ 'status' => $http_response, 'data' => $data ] );
    }

    /**
     * HTTP CODE 200 OK RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_200( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 200 );
    }

    /**
     * HTTP CODE 400 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_400( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 400 );
    }


    /**
     * HTTP CODE 401 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_401( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 401 );
    }

    /**
     * HTTP CODE 403 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_403( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 403 );
    }

    /**
     * HTTP CODE 403 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_404( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 404 );
    }

    /**
     * HTTP CODE 500 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_500( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 500 );
    }

    /**
     * HTTP CODE 501 ERROR RESPONSE Wrapper
     *
     * @param string $code
     * @param string $msg
     * @param array $data
     * @return WP_Error
     */
    protected static function ERROR_501( $code='', $msg='', $data=[] )
    {
        return self::ERROR_CODE( $code, $msg, $data, 403 );
    }

    /**
     * 401 Error for invalid permissions.
     *
     * @return WP_Error
     */
    protected static function ERROR_INVALID_PERMISSIONS()
    {
        return self::ERROR_401( 'invalid_permissions', _x( 'Your user level does not have sufficient permissions.', 'api', 'groundhogg' ) );
    }

    /**
     * 500 Error for unknown error.
     *
     * @return WP_Error
     */
    protected static function ERROR_UNKNOWN()
    {
        return self::ERROR_500( 'unknown_error', _x( 'Unknown error.', 'api', 'groundhogg' ) );
    }

    /**
     * Returns a default set of args along with a status
     *
     * @param array $args
     * @param string $message
     * @param string $status
     * @return WP_REST_Response
     */
    protected static function SUCCESS_RESPONSE( $args = [], $message='', $status='success' )
    {

        if ( ! is_array( $args ) ){
            $args = [$args];
        }

        if ( ! key_exists( 'status', $args ) ){
            $args[ 'status' ] = $status;
        }

        if ( ! key_exists( 'message', $args ) && $message ){
            $args[ 'message' ] = $message;
        }

        return rest_ensure_response( $args );
    }


    /**
     * Given a request get a contact if one exists.
     *
     * @param WP_REST_Request $request
     * @return false|WP_Error|WPGH_Contact
     */
    protected static function get_contact_from_request( WP_REST_Request $request )
    {
        $id_or_email =  $request->get_param( 'id_or_email' );

        /* Return false if we are not requesting it. */
        if ( ! $id_or_email ){
            return false;
        }

        $by_user_id = filter_var( $request->get_param( 'by_user_id' ), FILTER_VALIDATE_BOOLEAN );
        $contact = wpgh_get_contact( $id_or_email, $by_user_id );

        if( ! $contact ) {
            if ( is_numeric( $id_or_email ) ){
                return self::ERROR_400('invalid_id', sprintf( _x( 'Contact with ID %s does not exist.', 'api', 'groundhogg' ), $id_or_email ) );
            } else {
                return self::ERROR_400('invalid_email', sprintf( _x( 'Contact with email %s does not exist.', 'api', 'groundhogg' ), $id_or_email ) );
            }
        }

        return $contact;
    }

    /**
     * Get the standard auth callback array
     *
     * @return array
     */
    public function get_auth_callback(){
        return [ $this,  'auth' ];
    }

    /**
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function auth( WP_REST_Request $request )
    {

        /* Check if the API is enabled... */
        if ( wpgh_is_option_enabled( 'gh_disable_api' ) ){
            return self::ERROR_403( 'api_unavailable', 'The api has been disabled by the administrator.' );
        }

        /* If the current user is logged in then we can bypass the key authentication */
        if ( is_user_logged_in() ){
            return true;
        }

        $token = $request->get_header( 'gh_token' );
        $key = $request->get_header( 'gh_public_key' );

        if( ! $token || ! $key ) {
            return self::ERROR_401( 'no_token_or_key', _x( 'Please enter a API valid token and public key.', 'api', 'groundhogg' ) );
        }

        //validate user
        global $wpdb;

        $user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wpgh_user_public_key' AND meta_value = %s LIMIT 1", $key ) );

        if ( ! $user_id ) {
            return self::ERROR_401( 'public_key_invalid', _x( 'Public key is invalid.', 'api', 'groundhogg' ) );
        }

        $secret = get_user_meta( $user_id,'wpgh_user_secret_key',true);

        if ( ! self::check_keys( $secret, $key, $token ) ){
            return self::ERROR_401( 'invalid_key_or_token', _x( 'Invalid Authentication.', 'api', 'groundhogg' ) );
        }

        /**
         * Set the current user for the request
         */
	    wp_set_current_user( $user_id );
        self::set_current_user( $user_id );

        return true;
    }

    /**
     * Check the keys provided.
     *
     * @param $secret
     * @param $public
     * @param $token
     * @return bool
     */
    public static function check_keys( $secret, $public, $token ) {
        return hash_equals( md5( $secret . $public ), $token );
    }

}
