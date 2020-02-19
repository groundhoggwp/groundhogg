<?php

use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class GH_UnitTest_Factory_For_Step extends GH_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'step_title'     => new WP_UnitTest_Generator_Sequence( 'Steps %s' ),
			'step_status'    => 'ready',
			'step_type'      => 'send_email',
			'step_group'     => 'action',
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return Step The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return new Step( $object_id );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	protected function get_db_name() {
		return 'steps';
	}
}
