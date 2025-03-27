<?php

namespace Groundhogg\steps\premium\logic;

use Groundhogg\Steps\Logic\Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Logic_Skip extends Logic {

	use Trait_Premium_Step;

	public function get_name() {
		return 'Skip';
	}

	public function get_type() {
		return 'logic_skip';
	}

	public function get_description() {
		return 'Skip to a proceeding step within the flow.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/logic-skip.svg';
	}
}
