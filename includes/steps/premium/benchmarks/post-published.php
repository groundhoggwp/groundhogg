<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Published extends Benchmark {

	use Trait_Premium_Step;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Post Published', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'post_published';
	}

	public function get_sub_group() {
		return 'wordpress';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( "Runs whenever a WordPress post meeting the criteria is published.", 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/wordpress/post-published.svg';
	}
}
