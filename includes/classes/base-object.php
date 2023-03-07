<?php

namespace Groundhogg;

use Groundhogg\Classes\Note;
use Groundhogg\DB\DB;
use JsonSerializable;
use Serializable;
use ArrayAccess;

abstract class Base_Object extends Supports_Errors implements Serializable, ArrayAccess, JsonSerializable {

	/**
	 * The ID of the object
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The regular data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var DB
	 */
	protected $db;

	/**
	 * Base_Object constructor.
	 *
	 * @param $identifier_or_args int|string the identifier to look for
	 * @param $field              string the file to query
	 *
	 * @return void
	 */
	public function __construct( $identifier_or_args = 0, $field = null ) {

		if ( ! $identifier_or_args ) {
			return;
		}

		// Fallback plan
		if ( ! are_dbs_initialised() ) {
			emergency_init_dbs();
		}

		if ( ! $field ) {
			$field = $this->get_identifier_key();
		}

		// Assume we are creating an object...
		if ( is_array( $identifier_or_args ) || is_object( $identifier_or_args ) ) {

			// Primary key is available, maybe it's a full raw object?
			if ( $primary = get_array_var( $identifier_or_args, $this->get_identifier_key() ) ) {

				if ( is_object( $identifier_or_args ) ) {
					$object = $identifier_or_args;
				} else {
					$object = $this->get_from_db( $this->get_identifier_key(), $primary );

					if ( empty( $object ) || ! is_object( $object ) ) {
						return;
					}
				}

				$this->setup_object( $object );

				return;
			}

			// Only get 1 record
			$identifier_or_args['limit'] = 1;

			$query = $this->get_db()->query( $identifier_or_args );

			$object = array_shift( $query );

			if ( ! empty( $object ) && is_object( $object ) ) {

				$this->setup_object( $object );

			} else {

				$primary_key = $this->get_db()->get_primary_key();

				// ignore if the primary key is passed in the args
				if ( ! key_exists( $primary_key, $identifier_or_args ) ) {
					$this->create( $identifier_or_args );
				}

			}

		} else {

			$object = $this->get_from_db( $field, $identifier_or_args );

			if ( empty( $object ) || ! is_object( $object ) ) {
				return;
			}

			$this->setup_object( $object );
		}
	}

	/**
	 * @return int
	 */
	public function get_id() {
		$identifier = $this->get_identifier_key();

		return $this->$identifier;
	}

	/**
	 * @param $id
	 */
	protected function set_id( $id ) {
		$identifier        = $this->get_identifier_key();
		$this->$identifier = $id;
	}

	/**
	 * @return string
	 */
	protected function get_identifier_key() {
		return $this->get_db()->get_primary_key();
	}

	/**
	 * Delete the object from the DB
	 *
	 * @return bool
	 */
	public function delete() {

		$id = $this->get_id();

		/**
		 * Fires before the object deleted...
		 *
		 * @param int         $object_id the ID of the object
		 * @param mixed[]     $data      just to make it compatible with the other crud actions
		 * @param Base_Object $object    the object class
		 */
		do_action( "groundhogg/{$this->get_object_type()}/pre_delete", $this->get_id(), $this->data, $this );

		if ( $this->get_db()->delete( $this->get_id() ) ) {
			unset( $this->data );
			unset( $this->ID );

			/**
			 * Fires after the object deleted...
			 *
			 * @param int $object_id the ID of the object
			 */
			do_action( "groundhogg/{$this->get_object_type()}/post_delete", $id );

			return true;
		}

		return false;
	}

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

		$this->data[ $this->get_identifier_key() ] = $this->get_id();

		$this->post_setup();

		return true;

	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	abstract protected function post_setup();

	/**
	 * Set an object property.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->$name = $value;
		} else {
			$this->data[ $name ] = $value;
		}
	}

	/**
	 * Get an object property
	 *
	 * @param $name
	 *
	 * @return bool|mixed
	 */
	public function __get( $name ) {
		// Check main data first
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		// Check data array
		if ( ! empty( $this->data ) && key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}

		if ( method_exists( $this, $name ) && is_callable( [ $this, $name ] ) ) {
			return call_user_func( [ $this, $name ] );
		}

		return false;
	}

	/**
	 * is triggered by calling isset() or empty() on inaccessible members.
	 *
	 * @param $name string
	 *
	 * @return bool
	 * @link https://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
	 */
	public function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Get the original data
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Checks if the data from the DB checks out.
	 *
	 * @return bool
	 */
	public function exists() {
		$has_data = false;

		foreach ( $this->data as $key => $datum ) {
			if ( ! empty( $datum ) ) {
				$has_data = true;
			}
		}

		return $has_data;
	}

	/**
	 * Get the object from the associated db.
	 *
	 * @param $field
	 * @param $search
	 *
	 * @return object
	 */
	protected function get_from_db( $field = '', $search = '' ) {
		return $this->get_db()->get_by( $field, $search );
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	abstract protected function get_db();

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return $this->get_db()->get_object_type();
	}

	/**
	 * Update the object
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		$old_data = $this->data;

		/**
		 * Fires before the object is updated...
		 *
		 * @param int         $object_id the ID of the object
		 * @param mixed[]     $new_data  the new data being saved
		 * @param Base_Object $object    the object class
		 * @param mixed[]     $old_data  the current data
		 */
		do_action( "groundhogg/{$this->get_object_type()}/pre_update", $this->get_id(), $data, $this, $old_data );

		if ( $updated = $this->get_db()->update( $this->get_id(), $data, $this->get_identifier_key() ) ) {

			$object = $this->get_from_db( $this->get_identifier_key(), $this->get_id() );
			$this->setup_object( $object );

			/**
			 * Fires after the object is updated...
			 *
			 * @param int         $object_id the ID of the object
			 * @param mixed[]     $new_data  the new data being saved
			 * @param Base_Object $object    the object class
			 * @param mixed[]     $old_data  the current data
			 */
			do_action( "groundhogg/{$this->get_object_type()}/post_update", $this->get_id(), $data, $this, $old_data );

		}

		return $updated;
	}

	/**
	 * Update the object
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function create( $data = [] ) {
		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		/**
		 * Fires before the object is created...
		 *
		 * @param int         $object_id the ID of the object
		 * @param mixed[]     $new_data  the new data being saved
		 * @param Base_Object $object    the object class, at this point it's pretty useless though
		 */
		do_action( "groundhogg/{$this->get_object_type()}/pre_create", $data, $this );

		if ( $id = $this->get_db()->add( $data ) ) {

			$object = $this->get_from_db( $this->get_identifier_key(), $id );

			if ( ! $object ) {
				return false;
			}

			$this->setup_object( $object );

			/**
			 * Fires after the object is created...
			 *
			 * @param int         $object_id the ID of the object
			 * @param mixed[]     $new_data  the new data being saved
			 * @param Base_Object $object    the object class
			 */
			do_action( "groundhogg/{$this->get_object_type()}/post_create", $this->get_id(), $data, $this );

		}

		return $id;
	}

	/**
	 * Duplicate an object
	 *
	 * @param array $overrides
	 *
	 * @return Base_Object
	 */
	public function duplicate( $overrides = [] ) {

		$data = $this->data;

		// Remove primary key from array
		unset( $data[ $this->get_db()->get_primary_key() ] );

		$class = get_class( $this );

		/**
		 * @var $object Base_Object
		 */
		$object = new $class;

		$object->create( array_merge( $data, $overrides ) );

		/**
		 * @param $new  Base_Object the new object
		 * @param $orig Base_Object the original object
		 */
		do_action( "groundhogg/{$this->get_object_type()}/duplicated", $object, $this );

		return $object;
	}

	/**
	 * Merge one object with another
	 *
	 * @param $other Base_Object
	 */
	public function merge( $other ) {

		// Dont merge with itself
		// Dont merge with objects of a different type
		if ( $other->get_id() === $this->get_id() || $other->get_object_type() !== $this->get_object_type() ) {
			return false;
		}

		/**
		 * Before an object is merged
		 *
		 * @param Base_Object $original
		 * @param Base_Object $other
		 */
		do_action( "groundhogg/{$this->get_object_type()}/pre_merge", $this, $other );

		// Update the date
		$this->update( array_merge( array_filter( $this->data ), array_filter( $other->data ) ) );

		/**
		 * When an object is merged
		 *
		 * @param Base_Object $original
		 * @param Base_Object $other
		 */
		do_action( "groundhogg/{$this->get_object_type()}/merged", $this, $other );

		// Delete the other as it is no longer relevant
		$other->delete();

		return true;
	}

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
	 * String representation of object
	 *
	 * @link  https://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->__serialize() );
	}

	/**
	 * For PHP 8.0
	 *
	 * @return array
	 */
	public function __serialize() {
		return $this->get_as_array();
	}

	/**
	 * Unserialize stuff
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->__unserialize( unserialize( $serialized ) );
	}

	/**
	 * Unserialize stuff
	 *
	 * For PHP 8.0
	 */
	public function __unserialize( $data ) {

		if ( ! is_array( $data ) ){
			return;
		}

		$this->setup_object( $data['data'] );
	}

	/**
	 * Whether a offset exists
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset <p>
	 *                      An offset to check for.
	 *                      </p>
	 *
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		if ( property_exists( $this, $offset ) ) {
			return $this->$offset !== null;
		}

		return isset( $this->data[ $offset ] );
	}

	/**
	 * Offset to retrieve
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to retrieve.
	 *                      </p>
	 *
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		if ( property_exists( $this, $offset ) ) {
			return $this->$offset;
		}

		return isset( $this->data[ $offset ] ) ? $this->data[ $offset ] : null;
	}

	/**
	 * Offset to set
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to assign the value to.
	 *                      </p>
	 * @param mixed $value  <p>
	 *                      The value to set.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	/**
	 * Offset to unset
	 *
	 * @link  https://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset <p>
	 *                      The offset to unset.
	 *                      </p>
	 *
	 * @return void
	 * @since 5.0.0
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		if ( property_exists( $this, $offset ) ) {
			$this->$offset = null;
		}

		unset( $this->data[ $offset ] );
	}

	/**
	 * Wrapper function for returning an array of the object...
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->get_as_array();
	}

	/**
	 * @return array
	 */
	public function get_as_array() {

		/**
		 * Filters the array function...
		 *
		 * @param mixed[] $array the array of args fot the object...
		 */
		return apply_filters( "groundhogg/{$this->get_object_type()}/get_as_array", [
			'ID'    => $this->get_id(),
			'data'  => $this->data,
			'admin' => $this->admin_link()
		] );
	}

	/**
	 * Serialize to json
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->get_as_array();
	}

	public function admin_link() {
		return admin_page_url( 'gh_' . $this->get_object_type() . 's', [
			$this->get_object_type() => $this->get_id(),
			'action'                 => 'edit'
		] );
	}

	protected function get_rel_db() {
		return get_db( 'object_relationships' );
	}

	/**
	 * Get any objects related to this object
	 *
	 * @param false $secondary_type
	 * @param bool  $is_primary
	 *
	 * @return array|DB_Object[]|DB_Object_With_Meta[]
	 */
	public function get_related_objects( $secondary_type = false, $is_primary = true ) {

		$relationships = $this->get_rel_db()->query( array_filter( [
			$is_primary ? 'primary_object_id' : 'secondary_object_id'     => $this->get_id(),
			$is_primary ? 'primary_object_type' : 'secondary_object_type' => $this->get_object_type(),
			$is_primary ? 'secondary_object_type' : 'primary_object_type' => $secondary_type
		] ) );

		return array_map( function ( $rel ) use ( $is_primary ) {

			if ( $is_primary ) {
				return create_object_from_type( $rel->secondary_object_id, $rel->secondary_object_type );
			} else {
				return create_object_from_type( $rel->primary_object_id, $rel->primary_object_type );
			}

		}, $relationships );
	}

	/**
	 * @param      $other
	 * @param bool $is_primary
	 *
	 * @return false|int
	 */
	public function is_related( $other, $is_primary = true ) {
		if ( ! is_object( $other ) || ! method_exists( $other, 'exists' ) || ! $this->exists() || ! $other->exists() ) {
			return false;
		}

		return $this->get_rel_db()->exists( [
			$is_primary ? 'primary_object_id' : 'secondary_object_id'     => $this->get_id(),
			$is_primary ? 'primary_object_type' : 'secondary_object_type' => $this->get_object_type(),
			$is_primary ? 'secondary_object_id' : 'primary_object_id'     => $other->get_id(),
			$is_primary ? 'secondary_object_type' : 'primary_object_type' => $other->get_object_type(),
		] );
	}

	/**
	 * Create a relationship between this object and another object
	 *
	 * @param      $other Base_Object
	 * @param bool $is_primary
	 *
	 * @return false|int
	 */
	public function create_relationship( $other, $is_primary = true ) {

		if ( ! is_object( $other ) || ! method_exists( $other, 'exists' ) || ! $this->exists() || ! $other->exists() ) {
			return false;
		}

		return $this->get_rel_db()->add( [
			$is_primary ? 'primary_object_id' : 'secondary_object_id'     => $this->get_id(),
			$is_primary ? 'primary_object_type' : 'secondary_object_type' => $this->get_object_type(),
			$is_primary ? 'secondary_object_id' : 'primary_object_id'     => $other->get_id(),
			$is_primary ? 'secondary_object_type' : 'primary_object_type' => $other->get_object_type(),
		] );
	}

	/**
	 * Delete a relationship between this object and another object
	 *
	 * @param      $other Base_Object
	 * @param bool $is_primary
	 *
	 * @return false|int
	 */
	public function delete_relationship( $other, $is_primary = true ) {

		if ( ! is_object( $other ) || ! method_exists( $other, 'exists' ) || ! $this->exists() || ! $other->exists() ) {
			return false;
		}

		return $this->get_rel_db()->delete( [
			$is_primary ? 'primary_object_id' : 'secondary_object_id'     => $this->get_id(),
			$is_primary ? 'primary_object_type' : 'secondary_object_type' => $this->get_object_type(),
			$is_primary ? 'secondary_object_id' : 'primary_object_id'     => $other->get_id(),
			$is_primary ? 'secondary_object_type' : 'primary_object_type' => $other->get_object_type(),
		] );
	}

	/**
	 * Adds notes to the contact
	 *
	 * @param String   $note
	 * @param string   $context
	 * @param bool|int $user_id
	 *
	 * @return $note
	 */
	public function add_note( $note, $context = 'system', $user_id = false ) {
		if ( ! is_string( $note ) && ! is_array( $note ) ) {
			return false;
		}

		if ( is_string( $note ) ) {
			$note_data = [
				'object_id'   => $this->get_id(),
				'object_type' => $this->get_object_type(),
				'context'     => $context,
				'content'     => wp_kses_post( $note ),
				'user_id'     => $user_id ?: get_current_user_id(),
			];

			if ( $context == 'user' && ! $user_id ) {
				$note_data['user_id'] = get_current_user_id();
			}

			$note = new Note( $note_data );

		} else if ( is_array( $note ) ) {

			// Undefined content
			if ( empty( $note['content'] ) ){
				return false;
			}

			// imported note
			$note_data = array_merge( $note, [
				'object_id'   => $this->get_id(),
				'object_type' => $this->get_object_type(),
			] );

			$note = new Note( $note_data );
		}

		/**
		 * Triggered when a new note is added and related to an object
		 *
		 * @param $id int the ID of the current object
		 * @param $note Note the object of the note that was just created
		 * @param $object Base_Object the primary object
		 */
		do_action( "groundhogg/{$this->get_object_type()}/note/added", $this->ID, $note, $this );

		return $note;
	}
}
