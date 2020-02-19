<?php

use Groundhogg\Contact;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class GH_UnitTest_Factory_For_Contact extends GH_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'first_name' => new WP_UnitTest_Generator_Sequence( 'First %s' ),
			'last_name'  => new WP_UnitTest_Generator_Sequence( 'Last %s' ),
			'email'      => new WP_UnitTest_Generator_Sequence( 'user_%s@example.org' ),
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return Contact The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return new Contact( $object_id );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	protected function get_db_name() {
		return 'contacts';
	}
}
