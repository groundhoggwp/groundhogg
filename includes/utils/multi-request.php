<?php

namespace Groundhogg\Utils;

trait Multi_Request {

	/**
	 * @var int how many requests can be made at once
	 */
	protected $concurrency = 1;

	/**
	 * @var array requests to make
	 */
	protected $requests = [];

	/**
	 * @var int how many requests have been sent within the last second
	 */
	protected $sent_last_second = 0;

	/**
	 * @var int when the most recent request was sent
	 */
	protected $time_last_sent = 0;

	public function set_concurrency( int $concurrency ) {
		$this->concurrency = $concurrency;
	}

	/**
	 * Add a new request
	 *
	 * @return true
	 */
	public function add_request( $request, callable $callback ) {

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

		$this->requests[] = $request;

		if ( count( $this->requests ) >= $this->concurrency ) {
			$this->send_requests();
		}

		return true;
	}

	/**
	 * Clear pending requests
	 *
	 * @return void
	 */
	public function clear_requests() {
		$this->requests = [];
	}

	/**
	 * Whether requests is empty or not
	 *
	 * @return bool
	 */
	public function has_requests() {
		return ! empty( $this->requests );
	}

	/**
	 * Send any pending requests
	 *
	 * @return int|mixed
	 */
	public function send_requests() {

		if ( ! $this->has_requests() ) {
			return 0;
		}

		if ( ( microtime( true ) - $this->time_last_sent ) < 1 ) {
			// If commands are being sent within a second of each other
			$requests_to_send = $this->concurrency - $this->sent_last_second;
		} else {
			// Otherwise, we can send a full request!
			$requests_to_send = min( $this->concurrency, count( $this->requests ) );
		}

		$this->sent_last_second = $requests_to_send;

		if ( $requests_to_send > 0 ) {
			$requests             = array_splice( $this->requests, 0, $requests_to_send );
			$this->time_last_sent = microtime( true );
			\Requests::request_multiple( $requests );
		}

		return $requests_to_send;
	}

}
