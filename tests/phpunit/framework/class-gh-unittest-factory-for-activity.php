<?php

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class GH_UnitTest_Factory_For_Activity extends GH_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'ID'            => 0,
			'timestamp'     => new GH_UnitTest_Time_Generator(),
			'funnel_id'     => new GH_UnitTest_ID_Generator(),
			'step_id'       => new GH_UnitTest_ID_Generator(),
			'contact_id'    => new GH_UnitTest_ID_Generator(),
			'email_id'      => new GH_UnitTest_ID_Generator(),
			'event_id'      => new GH_UnitTest_ID_Generator(),
			'activity_type' => 'email_opened',
			'referer'       => '',
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return Activity The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return new Activity( $object_id );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	protected function get_db_name() {
		return 'activity';
	}
}
