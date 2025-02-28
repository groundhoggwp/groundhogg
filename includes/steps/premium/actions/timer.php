<?php

namespace Groundhogg\Steps\Premium\Actions;

use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

abstract class Timer extends Action {

	use Trait_Premium_Step;

	public function get_sub_group() {
		return 'delay';
	}
}
