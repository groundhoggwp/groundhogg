<?php

namespace Groundhogg\Api;

use Groundhogg\Api\V3\API_V3;
use Groundhogg\Api\V4\API_V4_HANDLER;
use function Groundhogg\get_array_var;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-13
 * Time: 9:59 AM
 */
class Api_Loader {

	/**
	 * @var API_V3
	 */
	public $v3;

	/**
	 * @var API_V4_HANDLER
	 */
	public $v4;

	/**
	 * WPGH_API_LOADER constructor.
	 */
	public function __construct() {

		add_action( 'rest_api_init', [ $this, 'load_api' ] );
		add_action( 'rest_api_init', [ $this, 'handle_api_key_usage' ] );
	}

	public function load_api() {

		define( 'DOING_GROUNDHOGG_REST_REQUEST', true );

		$this->v3 = new API_V3();
//		$this->v4 = new API_V4_HANDLER();
	}


	/**
	 * @param $rest_server \WP_REST_Server
	 *
	 * @return bool|void
	 */
	public function handle_api_key_usage( $rest_server ) {

		/* If the current user is logged in then we can bypass the key authentication */
		if ( is_user_logged_in() ) {
			return;
		}

		$headers = $rest_server->get_headers( $_SERVER );

		$token = get_array_var( $headers, 'GH_TOKEN' );
		$key   = get_array_var( $headers, 'GH_PUBLIC_KEY' );

		if ( ! $token || ! $key ) {
			return;
		}

		//validate user
		global $wpdb;

		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wpgh_user_public_key' AND meta_value = %s LIMIT 1", $key ) );

		if ( ! $user_id ) {
			return;
		}

		$secret = get_user_meta( $user_id, 'wpgh_user_secret_key', true );

		if ( ! self::check_keys( $secret, $key, $token ) ) {
			return;
		}

		/**
		 * Set the current user for the request
		 */
		wp_set_current_user( $user_id );
	}

	/**
	 * Check the keys provided.
	 *
	 * @param $secretsle
	 * @param $public
	 * @param $token
	 *
	 * @return bool
	 */
	public static function check_keys( $secret, $public, $token ) {
		return hash_equals( md5( $secret . $public ), $token );
	}

}