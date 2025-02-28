<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Loop extends Action {

	use Trait_Premium_Step;

	public function get_name() {
		return 'Loop';
	}

	public function get_type() {
		return 'loop';
	}

	public function get_description() {
		return 'Loop back to previous step within the funnel.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic-loop.svg';
	}

	public function get_sub_group() {
		return 'delay';
	}

	public function is_legacy() {
		return true;
	}
}
