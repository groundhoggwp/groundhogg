<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;

abstract class Base_Object_With_Meta extends Base_Object {

	/**
	 * The meta data
	 *
	 * @var array
	 */
	protected $meta = [];

	/**
	 * @var DB
	 */
	protected $meta_db;

	/**
	 * Setup the class
	 *
	 * @param $object
	 *
	 * @return bool
	 */
	protected function setup_object( $object ) {

		$object     = (object) $object;
		$identifier = $this->get_identifier_key();

		if ( ! is_object( $object ) || ! isset( $object->$identifier ) ) {
			return false;
		}

		$this->set_id( is_numeric( $object->$identifier ) ? absint( $object->$identifier ) : $object->$identifier );

		//Lets just make sure we all good here.
		$object = apply_filters( "groundhogg/{$this->get_object_type()}/setup", $object );

		// Setup the main data
		foreach ( $object as $key => $value ) {
			$this->$key = $value;
		}

		$this->data[$this->get_identifier_key()] = $this->get_id();

		// Get all the meta data.
		$this->get_all_meta();

		$this->post_setup();

		return true;

	}

	/**
	 * Get an object property
	 *
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function __get( $name ) {
		// Check meta data
		if ( key_exists( $name, $this->meta ) ) {
			return $this->meta[ $name ];
		}

		return parent::__get( $name );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	abstract protected function get_meta_db();

	/**
	 * Sanitize columns when updating the object
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function sanitize_columns( $data = [] ) {
		return $data;
	}

	/**
	 * Get all the meta data.
	 *
	 * @return array
	 */
	public function get_all_meta() {
		if ( ! empty( $this->meta ) ) {
			return $this->meta;
		}

		$meta = $this->get_meta_db()->get_meta( $this->get_id() );

//        var_dump( $meta );

		if ( ! $meta ) {
			return [];
		}

		foreach ( $meta as $meta_key => $array_values ) {
			$this->meta[ $meta_key ] = maybe_unserialize( array_shift( $array_values ) );
		}

		return $this->meta;
	}

	/**
	 * Get some meta
	 * If key is not specified return the meta data array.
	 *
	 * @param $key
	 * @param $single
	 *
	 * @return mixed
	 */
	public function get_meta( $key = false, $single = true ) {
		if ( ! $key ) {
			return $this->meta;
		}

		if ( key_exists( $key, $this->meta ) ) {
			return $this->meta[ $key ];
		}

		$val = $this->get_meta_db()->get_meta( $this->get_id(), $key, $single );

		$this->meta[ $key ] = $val;

		return $val;
	}

	/**
	 * Update some meta data
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function update_meta( $key, $value = false ) {

		if ( is_array( $key ) && ! $value ) {
			foreach ( $key as $meta_key => $meta_value ) {
				if ( ! $this->update_meta( $meta_key, $meta_value ) ) {
					return false;
				}
			}
		} else if ( $this->get_meta_db()->update_meta( $this->get_id(), $key, $value ) ) {
			$this->meta[ $key ] = $value;

			return true;
		}


		return false;
	}

	/**
	 * Add some meta
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function add_meta( $key, $value ) {
		if ( is_array( $key ) && ! $value ) {
			foreach ( $key as $meta_key => $meta_value ) {
				if ( ! $this->add_meta( $meta_key, $meta_value ) ) {
					return false;
				}
			}
		} else if ( $this->get_meta_db()->add_meta( $this->get_id(), $key, $value ) ) {
			$this->meta[ $key ] = $value;

			return true;
		}

		return false;
	}


	/**
	 * Delete some meta
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function delete_meta( $key ) {

		if ( is_array( $key ) ){
			foreach ( $key as $meta_key ){
				$this->delete_meta( $meta_key );
			}

			return true;
		}

		unset( $this->meta[ $key ] );

		return $this->get_meta_db()->delete_meta( $this->get_id(), $key );
	}

	/**
	 * @return array
	 */
	public function get_as_array() {
		return apply_filters( "groundhogg/{$this->get_object_type()}/get_as_array", [
			'ID'   => $this->get_id(),
			'data' => $this->data,
			'meta' => $this->meta
		] );
	}
}