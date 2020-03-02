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

	public function generate_args( $args = array(), $generation_definitions = null, &$callbacks = null ) {
		$callbacks = array();
		if ( is_null( $generation_definitions ) ) {
			$generation_definitions = $this->default_generation_definitions;
		}

		// Use the same incrementor for all fields belonging to this object.
		$gen = new WP_UnitTest_Generator_Sequence();
		// Add leading zeros to make sure MySQL sorting works as expected.
		$incr = zeroise( $gen->get_incr(), 7 );

		foreach ( array_keys( $generation_definitions ) as $field_name ) {
			if ( ! isset( $args[ $field_name ] ) ) {
				$generator = $generation_definitions[ $field_name ];
				if ( is_scalar( $generator ) ) {
					$args[ $field_name ] = $generator;
				} elseif ( is_object( $generator ) && method_exists( $generator, 'call' ) ) {
					$callbacks[ $field_name ] = $generator;
				} elseif ( is_object( $generator ) && method_exists( $generator, 'generate' ) ) {
					$args[ $field_name ] = $generator->generate();
				} elseif ( is_object( $generator ) ) {
					$args[ $field_name ] = sprintf( $generator->get_template_string(), $incr );
				} else {
					return new WP_Error( 'invalid_argument', 'Factory default value should be either a scalar or an generator object.' );
				}
			}
		}

		return $args;
	}
}
