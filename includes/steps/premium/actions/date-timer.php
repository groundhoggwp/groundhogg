<?php

namespace Groundhogg\Steps\Premium\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Date_Timer extends Timer {

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Date Timer', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'date_timer';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Pause until a specific date & time.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/date-timer.svg';
	}
}
