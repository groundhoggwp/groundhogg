<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Step;
use Groundhogg\Steps\Trait_Polyfill;

class Polyfill_Benchmark extends Benchmark {

	use Trait_Polyfill;

	public function __construct( Step $step ) {
		$this->set_current_step( $step );
	}

	protected function get_complete_hooks() {
		return [];
	}

	protected function get_the_contact() {
		return false;
	}

	protected function can_complete_step() {
		return false;
	}
}
