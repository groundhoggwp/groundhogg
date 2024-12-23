<?php

namespace Groundhogg\Utils;

use WpOrg\Requests\Requests;

trait Static_Multi_Request {

	/**
	 * @var int how many requests can be made at once
	 */
	protected static $concurrency = 1;

	/**
	 * @var array requests to make
	 */
	protected static $requests = [];

	/**
	 * @var int how many requests have been sent within the last second
	 */
	protected static $sent_last_second = 0;

	/**
	 * @var int when the most recent request was sent
	 */
	protected static $time_last_sent = 0;

	public static function set_concurrency( int $concurrency ) {
		self::$concurrency = $concurrency;
	}

	/**
	 * Add a new request
	 *
	 * @return true
	 */
	public static function add_request( $request, callable $callback ) {

		$request = wp_parse_args( $request, [
			'type'    => 'GET',
			'headers' => [],
			'url'     => '',
			'data'    => '',
			'options' => [],
		] );

		if ( is_callable( $callback ) ) {
			$request['options']['complete'] = $callback;
		}

		self::$requests[] = $request;

		if ( count( self::$requests ) >= self::$concurrency ) {
			self::send_requests();
		}

		return true;
	}

	/**
	 * Clear pending requests
	 *
	 * @return void
	 */
	public static function clear_requests() {
		self::$requests = [];
	}

	/**
	 * Whether requests is empty or not
	 *
	 * @return bool
	 */
	public static function has_requests() {
		return ! empty( self::$requests );
	}

	/**
	 * Send any pending requests
	 *
	 * @return int|mixed
	 */
	public static function send_requests() {

		$sent = 0;

		while ( self::has_requests() ) {

			if ( ( microtime( true ) - self::$time_last_sent ) < 1 ) {
				// If commands are being sent within a second of each other
				$requests_to_send = self::$concurrency - self::$sent_last_second;
			} else {
				// Otherwise, we can send a full request!
				$requests_to_send = min( self::$concurrency, count( self::$requests ) );
			}

			self::$sent_last_second = $requests_to_send;

			if ( $requests_to_send > 0 ) {
				$requests             = array_splice( self::$requests, 0, $requests_to_send );
				self::$time_last_sent = microtime( true );
				Requests::request_multiple( $requests );
			}

			$sent += $requests_to_send;

		}

		return $sent;
	}

}
