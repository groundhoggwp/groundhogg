<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Contact;
use function Groundhogg\get_contactdata;
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use Groundhogg\Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API_V3_BASE Class
 *
 * Renders API returns as a JSON
 */
abstract class Base_Api {

	const NAME_SPACE = 'gh/v4';

	/**
	 * @var \WP_User
	 */
	protected static $current_user;

	/**
	 * WPGH_API_V3_BASE constructor.
	 */
	public function __construct() {
		add_action( 'groundhogg/api/v4/init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the relevant REST routes
	 *
	 * @return mixed
	 */
	abstract public function register_routes();

	/**
	 * Standards args for singular requests
	 *
	 * @return array
	 */
	protected static function STANDARD_SINGULAR_ARGS() {
		return [
			'ID' => [
				'validate_callback' => function ( $param, $request, $key ) {
					return is_numeric( $param );
				}
			]
		];
	}

	/**
	 * Return an error code with modified HTTP Status
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 * @param int    $http_response
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_CODE( $code = '', $msg = '', $data = [], $http_response = 500 ) {

		$code = $code ?: 'error';
		$msg  = $msg ?: 'Something went wrong';

		return new WP_Error( $code, $msg, [ 'status' => $http_response, 'data' => $data ] );
	}

	/**
	 * Return an error if the endpoint is not yet in service
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_NOT_IN_SERVICE() {
		return self::ERROR_403( 'not_in_service', 'Endpoint not in service.' );
	}

	/**
	 * HTTP CODE 200 OK RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_200( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 200 );
	}

	/**
	 * HTTP CODE 400 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_400( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 400 );
	}


	/**
	 * HTTP CODE 401 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_401( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 401 );
	}

	/**
	 * HTTP CODE 403 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_403( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 403 );
	}

	/**
	 * HTTP CODE 403 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_404( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 404 );
	}

	/**
	 * HTTP CODE 409 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_409( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 409 );
	}

	/**
	 * HTTP CODE 422 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_422( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 422 );
	}

	/**
	 * HTTP CODE 500 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_500( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 500 );
	}

	/**
	 * HTTP CODE 501 ERROR RESPONSE Wrapper
	 *
	 * @param string $code
	 * @param string $msg
	 * @param array  $data
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_501( $code = '', $msg = '', $data = [] ) {
		return self::ERROR_CODE( $code, $msg, $data, 403 );
	}

	/**
	 * 401 Error for invalid permissions.
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_INVALID_PERMISSIONS() {
		return self::ERROR_401( 'invalid_permissions', _x( 'Your user level does not have sufficient permissions.', 'api', 'groundhogg' ) );
	}

	/**
	 * 500 Error for unknown error.
	 *
	 * @return WP_Error
	 */
	protected static function ERROR_UNKNOWN() {
		return self::ERROR_500( 'unknown_error', _x( 'Unknown error.', 'api', 'groundhogg' ) );
	}

	/**
	 * Returns a default set of args along with a status
	 *
	 * @param array  $args
	 * @param string $message
	 * @param string $status
	 *
	 * @return WP_REST_Response
	 */
	protected static function SUCCESS_RESPONSE( $args = [], $message = '', $status = 'success' ) {

		if ( ! is_array( $args ) ) {
			$args = [ $args ];
		}

		if ( ! key_exists( 'status', $args ) ) {
			$args['status'] = $status;
		}

		if ( ! key_exists( 'message', $args ) && $message ) {
			$args['message'] = $message;
		}

		return rest_ensure_response( $args );
	}
}
