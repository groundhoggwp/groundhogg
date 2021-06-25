<?php

namespace Groundhogg;

use Groundhogg\Api\V4\Base_Api;

class Library extends Supports_Errors {

	const PROXY_URL = 'https://library.groundhogg.io/wp-json/gh/v4';
	static $user_agent = 'Groundhogg/' . GROUNDHOGG_VERSION . ' library-manager';

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

		$url = self::PROXY_URL . '/' . $endpoint;

		$result = remote_post_json( $url, $body, 'GET', [
//			'x-wp-nonce' => wp_create_nonce( 'wp_rest' ),
//			'gh-token'      => '49fda7b5408ddc08b59cb0512dda81c4',
//			'gh-public-key' => '0abbbbd56a29ec207f62d0f872f8fcea',
		] );

//		wp_send_json( $result );

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
//		$funnels = get_transient( 'groundhogg_funnel_templates' );
//
//		if ( ! empty( $funnels ) ) {
//			return $funnels;
//		}

		$response = $this->request( 'funnels', [ 'limit' => 999, 'status' => 'active' ] );

		$funnels = get_array_var( $response, 'items', [] );

		set_transient( 'groundhogg_funnel_templates', $funnels, DAY_IN_SECONDS );

		return $funnels;
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

		$response = $this->request( 'emails', [ 'limit' => 999, 'status' => 'ready' ] );

		$emails = get_array_var( $response, 'items', [] );

		set_transient( 'groundhogg_email_templates', $emails, DAY_IN_SECONDS );

		return $emails;
	}

	/**
	 * Get a specific funnel template
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get_funnel_template( $id ) {
		$response = $this->request( "funnels/$id", [], 'GET' );

		return get_array_var( $response, 'funnel', [] );
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
