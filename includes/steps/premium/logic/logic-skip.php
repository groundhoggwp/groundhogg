<?php

namespace Groundhogg\steps\premium\logic;

use Groundhogg\Contact;
use Groundhogg\Steps\Logic\Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Logic_Skip extends Logic {

	use Trait_Premium_Step;

	public function get_name() {
		return esc_html_x( 'Skip', 'step_name', 'groundhogg' );
	}

	public function get_type() {
		return 'logic_skip';
	}

	public function get_description() {
		return esc_html__( 'Skip to a proceeding step within the flow.', 'groundhogg' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/logic-skip.svg';
	}

	public function get_logic_action( Contact $contact ) {
		return false;
	}
}
