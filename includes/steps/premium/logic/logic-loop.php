<?php

namespace Groundhogg\Steps\Premium\Logic;

use Groundhogg\Contact;
use Groundhogg\Steps\Logic\Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Logic_Loop extends Logic {

	use Trait_Premium_Step;

	public function get_name() {
		return esc_html_x( 'Loop', 'step_name', 'groundhogg' );
	}

	public function get_type() {
		return 'logic_loop';
	}

	public function get_description() {
		return esc_html__( 'Loop back to previous step within the funnel.', 'groundhogg' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/logic-loop.svg';
	}

	public function get_logic_action( Contact $contact ) {
		return false;
	}
}
