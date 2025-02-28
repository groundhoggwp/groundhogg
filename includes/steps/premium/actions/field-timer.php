<?php

namespace Groundhogg\Steps\Premium\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Field_Timer extends Timer {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/field-timer/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Field Timer', 'step_name', 'groundhogg-pro' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'field_timer';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Pause for a specified amount of time before/after a date in the the contact\'s custom fields.', 'step_description', 'groundhogg-pro' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/field-timer.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/field-timer.svg';
	}
}
