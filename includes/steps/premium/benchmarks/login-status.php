<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login_Status extends Benchmark {

	use Trait_Premium_Step;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Logs In', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'login_status';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever a user logs in.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/wordpress/logged-in.svg';
	}

	public function get_sub_group() {
		return 'wordpress';
	}
}
