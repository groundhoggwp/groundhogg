<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Contact;
use Groundhogg\Steps\Trait_Polyfill;

class Polyfill_Logic extends Logic {

	use Trait_Polyfill;

	public function get_logic_action( Contact $contact ) {
		return false;
	}
}
