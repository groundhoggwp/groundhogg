<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Library extends Supports_Errors {

	const LIBRARY_URL = 'https://library.groundhogg.io/wp-json/gh/v4/';

	/**
	 * Cached mirror of the library, served from the edge. Same endpoints, prefixed with library/
	 */
	const CDN_URL = 'https://cdn.groundhogg.io/library/';

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

		// Try the CDN first for cacheable reads, but only when the library url hasn't been filtered
		// (e.g. pointed at a dev library), since the CDN only mirrors the production library.
		if ( strtoupper( $method ) === 'GET' && $this->get_library_url() === self::LIBRARY_URL ) {

			$cdn_result = remote_post_json( self::CDN_URL . ltrim( $endpoint, '/' ), $body, $method, $headers, false, DAY_IN_SECONDS );

			// Only accept a well-formed library response, otherwise fall back to the library itself
			if ( ! is_wp_error( $cdn_result ) && ( get_array_var( $cdn_result, 'items' ) || get_array_var( $cdn_result, 'item' ) ) ) {
				return $cdn_result;
			}
		}

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
			'status'      => 'ready',
			'orderby'     => 'title',
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
