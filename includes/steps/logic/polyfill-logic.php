<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Trait_Polyfill;

class Polyfill_Logic extends Logic {

	use Trait_Polyfill;

	/**
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
	public function get_logic_action( Contact $contact ) {
		return false;
	}
}
