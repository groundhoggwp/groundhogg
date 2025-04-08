<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:31 AM
 */
abstract class Supports_Errors {
	/**
	 * @var \WP_Error[]
	 */
	protected $errors = [];

	/**
	 * @param string|\WP_Error $code
	 * @param string           $message
	 * @param array            $data
	 *
	 * @return false always
	 */
	public function add_error( $code = '', $message = '', $data = [] ) {

		$error = is_wp_error( $code ) ? $code : new \WP_Error( $code, $message, $data );

		if ( is_wp_error( $error ) && ! $this->has_error( $error->get_error_code()) ) {
			$this->errors[] = $error;
		}

		return false;
	}

	/**
	 * Clear the errors.
	 */
	public function clear_errors() {
		$this->errors = [];
	}

	/**
	 * @return bool
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * @return \WP_Error
	 */
	public function get_last_error() {
		return $this->errors[ count( $this->errors ) - 1 ];
	}

	/**
	 * @return \WP_Error[]
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Whether a specific error is present
	 *
	 * @param $code
	 *
	 * @return false|\WP_Error
	 */
	public function has_error( $code = '' ){
		return array_find( $this->errors, function ( $error ) use ( $code ){
			return $error->get_error_code() === $code;
		} );
	}


}
