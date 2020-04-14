<?php

namespace Groundhogg\Queue;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Event_Process;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Test_Event_Failure implements Event_Process {

	/**
	 * Always return false
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool
	 */
	public function run( $contact, $event = null ) {
		return false;
	}

	/**
	 * Just return true for now cuz I'm lazy...
	 *
	 * @return bool
	 */
	public function can_run() {
		return true;
	}

	public function get_funnel_title() {
		return 'test-event';
	}

	public function get_step_title() {
		return 'test-failure';
	}
}