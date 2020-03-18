<?php

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class GH_UnitTest_Factory_For_Event extends GH_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'time'           => new GH_UnitTest_Time_Generator(),
			'time_scheduled' => time(),
			'funnel_id'      => 1,
			'step_id'        => 1,
			'contact_id'     => 1,
			'event_type'     => Event::TEST_SUCCESS,
			'error_code'     => '',
			'error_message'  => '',
			'status'         => 'waiting',
			'priority'       => 100,
			'claim'          => '',
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return \Groundhogg\Event The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return new \Groundhogg\Event( $object_id );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	protected function get_db_name() {
		return 'events';
	}
}
