<?php

namespace Groundhogg\steps\premium\logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Logic\Logic;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Logic_Stop extends Logic {

	use Trait_Premium_Step;

	public function get_name() {
		return esc_html_x( 'Stop', 'step_name', 'groundhogg' );
	}

	public function get_type() {
		return 'logic_stop';
	}

	public function get_description() {
		return esc_html__( 'Prevent a contact from continuing in a flow based on filters.', 'groundhogg' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/logic-end.svg';
	}

	public function get_logic_action( Contact $contact ) {
		return false;
	}

	/**
	 * Pro's sortable_item() draws a stop line under the step, the editor draws it from the stop layout instead
	 *
	 * @return bool
	 */
	public function uses_custom_sortable_item(): bool {
		return false;
	}

	/**
	 * @param Step $step
	 *
	 * @return array
	 */
	public function get_canvas_data( Step $step ) {

		$data = parent::get_canvas_data( $step );

		if ( parent::uses_custom_sortable_item() ) {
			$data['layout']      = 'stop';
			$data['has_filters'] = ! method_exists( $this, 'no_filters' ) || ! $this->no_filters();
		}

		return $data;
	}
}
