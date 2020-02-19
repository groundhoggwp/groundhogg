<?php

use Groundhogg\DB\DB;
use function Groundhogg\get_db;

/**
 * Class GH_UnitTest_Factory_For_Thing
 */
abstract class GH_UnitTest_Factory_For_Thing extends WP_UnitTest_Factory_For_Thing
{

	/**
	 * @var GH_UnitTest_Factory
	 */
	public $factory;

	/**
	 * @return GH_UnitTest_Factory
	 */
	public function getFactory(): GH_UnitTest_Factory {
		return $this->factory;
	}

	/**
	 * Get the DB for this factory
	 *
	 * @return DB
	 */
	public function get_db(){
		return get_db( $this->get_db_name() );
	}

	/**
	 * Get the DB name
	 *
	 * @return string
	 */
	abstract  protected function get_db_name();

	/**
	 * Creates an object.
	 *
	 * @param array $args The arguments.
	 *
	 * @return mixed The result. Can be anything.
	 */
	public function create_object( $args ) {
		return $this->get_db()->add( $args );
	}

	/**
	 * Updates an existing object.
	 *
	 * @param int   $object The object ID.
	 * @param array $fields The values to update.
	 *
	 * @return mixed The result. Can be anything.
	 */
	public function update_object( $object, $fields ) {
		return $this->get_db()->update( $object, $fields );
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return mixed The object. Can be anything.
	 */
	public function get_object_by_id( $object_id ) {
		return $this->get_db()->get( $object_id );
	}
}
