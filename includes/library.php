<?php

namespace Groundhogg;

class Library extends Supports_Errors {

	const LIBRARY_URL = 'https://library.groundhogg.io/wp-json/gh/v4/';

	/**
	 * Flush cache templates
	 */
	public function flush() {
		delete_transient( 'groundhogg_funnel_templates' );
		delete_transient( 'groundhogg_email_templates' );
	}

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

		$result = remote_post_json( $url, $body, $method, $headers );

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
		$funnels = get_transient( 'groundhogg_funnel_templates' );

		if ( ! empty( $funnels ) ) {
			return $funnels;
		}

		$step_steps = array_keys( Plugin::instance()->step_manager->elements );

		$response = $this->request( 'funnels/', [
			'step_types' => $step_steps,
			'status'     => 'active',
			'orderby'    => 'title',
			'order'      => 'asc',
		], 'GET' );

		$funnels = get_array_var( $response, 'items', [] );

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
		$response = $this->request( 'funnels/' . $id, [], 'GET' );

		return get_array_var( $response, 'item', [] );
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

		$response = $this->request( 'emails', [
			'is_template' => 1,
		] );

		$emails = get_array_var( $response, 'items', [] );

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
		$response = $this->request( 'emails/' . $id );

		return get_array_var( $response, 'item', [] );
	}
}
