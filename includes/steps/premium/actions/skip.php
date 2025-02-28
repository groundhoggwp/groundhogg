<?php

namespace Groundhogg\Steps\Premium\Actions;

class Skip extends Loop {

	public function get_name() {
		return 'Skip';
	}

	public function get_type() {
		return 'skip';
	}

	public function is_legacy() {
		return true;
	}

	public function get_description() {
		return 'Skip to a proceeding step within the funnel.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic-skip.svg';
	}
}
