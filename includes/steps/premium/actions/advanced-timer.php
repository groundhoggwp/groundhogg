<?php

namespace Groundhogg\Steps\Premium\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Advanced_Timer extends Timer {

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Advanced Timer', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'advanced_timer';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Use a <code>strtotime</code> friendly string to create a delay.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/advanced-timer.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/advanced-timer.svg';
	}
}
