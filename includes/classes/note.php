<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\DB\DB;
use function Groundhogg\get_db;

class Note extends Base_Object{

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'contactnotes' );
	}
}
