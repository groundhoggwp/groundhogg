<?php

namespace Groundhogg\steps\premium\logic;

use Groundhogg\Contact;
use Groundhogg\Steps\Logic\Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Logic_Jump extends Logic {

	use Trait_Premium_Step;
	public function get_name() {
		return esc_html_x( 'Reroute', 'step_name', 'groundhogg' );
	}

	public function get_type() {
		return 'logic_jump';
	}

	public function get_description() {
		return esc_html__( 'Go to any other action node in the flow', 'groundhogg' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/jump.svg';
	}

	public function get_logic_action( Contact $contact ) {
		return false;
	}
}
