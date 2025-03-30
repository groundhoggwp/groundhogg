<?php

namespace Groundhogg;

class Library extends Supports_Errors {

	const LIBRARY_URL = 'https://library.groundhogg.io/wp-json/gh/v4/';
//	const LIBRARY_URL = 'https://app-667e6062c1ac1837ccd93f5b.closte.com/wp-json/gh/v4/';

	/**
	 * Get the library url
	 *
	 * @return mixed|null
	 */
	function get_library_url() {
		/**
		 * Filter the library url
		 *
		 * @param $url string the library url
		 */
		return apply_filters( 'groundhogg/library/get_library_url', self::LIBRARY_URL );
	}

	/**
	 * Send a request to the library
	 *
	 * @param string $endpoint
	 * @param array  $body
	 * @param string $method
	 * @param array  $headers
	 *
	 * @return array|bool|\WP_Error
	 */
	public function request( $endpoint = '', $body = [], $method = 'GET', $headers = [] ) {

		$url = $this->get_library_url() . $endpoint;

		$result = remote_post_json( $url, $body, $method, $headers, false, DAY_IN_SECONDS );

		if ( is_wp_error( $result ) ) {
			notices()->add( $result );
		}

		return $result;
	}

	/**
	 * Get the funnel templates
	 *
	 * @return mixed
	 */
	public function get_funnel_templates() {

		$step_steps = array_keys( Plugin::instance()->step_manager->elements );

		$filters = [
			[
				// filter by registered step types
				[
					'type'  => 'step_type',
					'types' => $step_steps
				],
			]
		];

		$response = $this->request( 'funnels/', [
			'filters' => base64_json_encode( $filters ),
			'status'  => 'active',
			'orderby' => 'title',
			'order'   => 'asc',
		], 'GET' );

		return get_array_var( $response, 'items', [] );
	}

	/**
	 * Get a specific funnel template
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_funnel_template( $id ) {
		$response = $this->request( 'funnels/' . $id, [], 'GET' );

		return get_array_var( $response, 'item', [] );
	}

	/**
	 * Get email templates
	 *
	 * @return mixed
	 */
	public function get_email_templates() {
		$response = $this->request( 'emails', [
			'is_template' => 1,
			'status'      => 'ready'
		] );

		return get_array_var( $response, 'items', [] );
	}

	/**
	 * Get a specific email template
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_email_template( $id ) {
		$response = $this->request( 'emails/' . $id );

		return get_array_var( $response, 'item', [] );
	}
}
