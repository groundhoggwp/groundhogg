<?php

namespace Groundhogg;

class Library extends Supports_Errors {
	const PROXY_URL = 'https://library.groundhogg.io/wp-json/gh/v3/';

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
	 * @param array $body
	 * @param string $method
	 * @param array $headers
	 *
	 * @return array|bool|\WP_Error
	 */
	public function request( $endpoint = '', $body = [], $method = 'GET', $headers = [] ) {

		$url = self::PROXY_URL . $endpoint;

		$result = remote_post_json( $url, $body, $method, $headers );

		if ( is_wp_error( $result ) ) {
			$this->add_error( $result );
		}

		return $result;
	}

	/**
	 * Get the funnel templates
	 *
	 * @return mixed
	 */
	public function get_funnel_templates() {
		$funnels = get_transient( 'groundhogg_funnel_templates' );

		if ( ! empty( $funnels ) ) {
			return $funnels;
		}

		$response = $this->request( 'funnels/', [
			'installed' => Extension::$extension_ids
		], 'GET' );

		$funnels = get_array_var( $response, 'funnels', [] );

		set_transient( 'groundhogg_funnel_templates', $funnels, DAY_IN_SECONDS );

		return $funnels;
	}

	/**
	 * Get a specific funnel template
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_funnel_template( $id ) {
		$response = $this->request( 'funnels/get', [ 'id' => $id ], 'GET' );

		return get_array_var( $response, 'funnel', [] );
	}

	/**
	 * Get email templates
	 *
	 * @return mixed
	 */
	public function get_email_templates() {
		$emails = get_transient( 'groundhogg_email_templates' );

		if ( ! empty( $emails ) ) {
			return $emails;
		}

		$response = $this->request( 'email/templates', [
			'installed' => Extension::$extension_ids
		], 'GET' );

		$emails = get_array_var( $response, 'emails', [] );

		set_transient( 'groundhogg_email_templates', $emails, DAY_IN_SECONDS );

		return $emails;
	}

	/**
	 * Get a specific email template
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_email_template( $id ) {
		$response = $this->request( 'email/templates/get', [ 'id' => $id ], 'GET' );

		return get_array_var( $response, 'email', [] );
	}
}
