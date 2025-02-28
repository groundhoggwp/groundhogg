<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HTTP_Post extends Action {

	use Trait_Premium_Step;

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/http-post/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Webhook', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'http_post';
	}

	public function get_sub_group() {
		return 'developer';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an HTTP Post to your favorite external software.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/webhook.svg';
	}
}
