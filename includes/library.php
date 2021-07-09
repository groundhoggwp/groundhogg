<?php

namespace Groundhogg;

use Groundhogg\Api\V4\Base_Api;

class Library extends Supports_Errors {

	static $user_agent = 'Groundhogg/' . GROUNDHOGG_VERSION . ' library-manager';

	public function get_libraries() {
		return apply_filters( 'groundhogg/template_libraries', [
			'https://app-60b684a2c1ac185aa47ce22e.closte.com', //  todo replace with actual library url
		] );
	}

	/**
	 * Flush cache templates
	 */
	public function flush() {
		delete_transient( 'groundhogg_funnel_templates' );
		delete_transient( 'groundhogg_email_templates' );
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
	public function request( $endpoint = '', $body = [] ) {

		$libraries = $this->get_libraries();
		$requests  = [];

		foreach ( $libraries as $library ) {
			$requests[] = [
				'type'    => 'GET',
				'url'     => $library . '/wp-json/gh/v4/' . $endpoint,
				'data'    => wp_json_encode( $body ),
				'headers' => [
					'content-type' => sprintf( 'application/json; charset=%s', get_bloginfo( 'charset' ) )
				],
				'options' => [
					'data_format' => 'body',
				]
			];
		}

		$requests_hooks = new \Requests_Hooks();

		$requests_hooks->register( 'curl.before_request', function ( $handle ) {
			curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, 0 );
		} );

		$responses = \Requests::request_multiple( $requests, [
			'hooks' => $requests_hooks
		] );

		$templates = [];

		foreach ( $responses as $response ) {

			if ( ! $response || is_wp_error( $response ) || is_a( $response, '\Requests_Exception' ) ) {
				continue;
			}

			if ( is_a( $response, '\Requests_Response' ) ) {
				$body = $response->body;
				$code = $response->status_code;
			} else {
				$body = wp_remote_retrieve_body( $response );
				$code = wp_remote_retrieve_response_code( $response );
			}

			if ( $code !== 200 ) {
				continue;
			}

			$json = json_decode( $body, true );

			$templates = array_merge( $templates, get_array_var( $json, 'items', [] ) );
		}

		return $templates;
	}

	/**
	 * Get the funnel templates
	 *
	 * @return mixed
	 */
	public function get_funnel_templates() {
//		$funnels = get_transient( 'groundhogg_funnel_templates' );
//
//		if ( ! empty( $funnels ) ) {
//			return $funnels;
//		}

		$templates = $this->request( 'funnels', [ 'limit' => 999, 'status' => 'active' ] );

		set_transient( 'groundhogg_funnel_templates', $templates, DAY_IN_SECONDS );

		return $templates;
	}

	/**
	 * Get email templates
	 *
	 * @return mixed
	 */
	public function get_email_templates() {
//		$emails = get_transient( 'groundhogg_email_templates' );
//
//		if ( ! empty( $emails ) ) {
//			return $emails;
//		}

		$templates = $this->request( 'emails', [ 'limit' => 999, 'status' => 'ready' ] );

//		var_dump( $templates );

		$templates = array_map( function ( $e ) {

			$email       = new Email();
			$email->data = get_array_var( $e, 'data' );
			$email->meta = get_array_var( $e, 'meta' );
			$email->ID   = uniqid( 'email-' );

			return $email;

		}, $templates );

		set_transient( 'groundhogg_email_templates', $templates, DAY_IN_SECONDS );

		return $templates;
	}
}
