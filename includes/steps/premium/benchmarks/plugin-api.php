<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Plugin_Api extends Benchmark {

	use Trait_Premium_Step;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Plugin API Benchmark', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'plugin_api';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Use a custom PHP script to add/move the contact within the funnel.' );
	}

	public function get_sub_group() {
		return 'developer';
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/plugin-api.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/plugin-benchmark.svg';
	}
}
