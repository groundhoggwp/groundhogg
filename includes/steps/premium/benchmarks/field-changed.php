<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Field_Changed extends Benchmark {

	use Trait_Premium_Step;

	public function get_name() {
		return _x( 'Field Changed', 'step_name', 'groundhogg-pro' );
	}

	public function get_type() {
		return 'field_changed';
	}

	public function get_sub_group() {
		return 'crm';
	}

	public function get_description() {
		return _x( "Runs whenever a value of selected custom field changes.", 'step_description', 'groundhogg-pro' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/crm/field-changed.svg';
	}
}
