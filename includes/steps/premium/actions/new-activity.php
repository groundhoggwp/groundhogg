<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class New_Activity extends Action {

	use Trait_Premium_Step;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'New Custom Activity', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'new_custom_activity';
	}

	/**
	 * Add to developer sub group
	 *
	 * @return string
	 */
	public function get_sub_group() {
		return 'developer';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Track a new custom activity.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/developer/new-custom-activity.svg';
	}
}
