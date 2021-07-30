<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Temp does not require DB connection
 *
 * Class Temp_Step
 *
 * @package Groundhogg
 */
class Temp_Step extends Step {

	protected $committing = false;

	public function __construct( $id_or_array, $data = [], $meta = [] ) {

		if ( is_array( $id_or_array ) ) {
			$id   = $id_or_array['ID'];
			$data = $id_or_array['data'];
			$meta = $id_or_array['meta'];
		} else {
			$id = $id_or_array;
		}

		$this->set_id( absint( $id ) );
		$this->data = $data;
		$this->meta = $meta;

		$this->post_setup();
	}

	/**
	 * Run the save method because it should validate all the meta
	 *
	 * If any errors crop up it will add those to this object.
	 */
	public function validate() {
		// Run the save method from the Funnel_Step()
		$this->save();
	}

	/**
	 * During the launch or update of a funnel, make the
	 * changes from the temp state of this step live.
	 */
	public function commit() {

		// We're committing this bad boy
		$this->committing = true;

		$temp_meta = $this->meta;

		// The step already existed, so we need to overwrite it with new settings.
		if ( $this->get_db()->exists( $this->get_id() ) ) {
			// Update the properties like step_order
			$this->update( $this->data );
		} // The step did not exist, so we need to create it
		else {
			// This will overwrite the meta property, so we store it in a temp variable
			$this->create( $this->data );
		}

		// Update all the step attributes
		$updated = $this->update_meta( $temp_meta );

		// No longer committing
		$this->committing = false;
	}

	/**
	 * Handle temp updates if we are not committing the changes yet
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		// If we are not committing, only update the data in the object, make no changes to the actual DB
		if ( ! $this->committing ) {
			$this->data = wp_parse_args( $this->sanitize_columns( $data ), $this->data );

			return true;
		}

		// Otherwise, update this sucker
		return parent::update( $data );
	}

	/**
	 * Should only fetch from meta data property in the object scope
	 *
	 * @param false $key
	 * @param bool  $single
	 *
	 * @return array|false|mixed
	 */
	public function get_meta( $key = false, $single = true ) {
		if ( ! $key ) {
			return $this->meta;
		}

		if ( key_exists( $key, $this->meta ) ) {
			return $this->meta[ $key ];
		}

		return false;
	}

	public function add_meta( $key, $value ) {

		if ( ! $this->committing ) {
			if ( is_array( $key ) && ! $value ) {
				foreach ( $key as $meta_key => $meta_value ) {
					if ( ! $this->add_meta( $meta_key, $meta_value ) ) {
						return false;
					}
				}
			} else {
				$this->meta[ $key ] = $value;

				return true;
			}

			return false;
		}

		return parent::add_meta( $key, $value );
	}

	/**
	 * We do not want to actually update the meta data in the DB unless we are committing, Funnel_Step save method
	 * Often attempts to update the meta data or data of the step, can't have that until we are sure about the commit.
	 *
	 * @param       $key
	 * @param false $value
	 *
	 * @return bool|mixed
	 */
	public function update_meta( $key, $value = false ) {

		// If we're not committing, only make changes to the meta data in the object, not the DB
		if ( ! $this->committing ) {

			if ( is_array( $key ) && ! $value ) {
				$updated = true;

				foreach ( $key as $meta_key => $meta_value ) {
					$updated = $this->update_meta( $meta_key, $meta_value ) && $updated;
				}

				return $updated;
			} else {
				$this->meta[ $key ] = $value;

				return true;
			}
		}

		// Otherwise we are committing and we'll use the parent method.
		return parent::update_meta( $key, $value );
	}

	/**
	 * Wrapper to handle committing/non-committing
	 *
	 * @param $key
	 *
	 * @return bool|mixed
	 */
	public function delete_meta( $key ) {

		if ( ! $this->committing ) {
			if ( is_array( $key ) ) {
				foreach ( $key as $meta_key ) {
					$this->delete_meta( $meta_key );
				}

				return true;
			}

			unset( $this->meta[ $key ] );

			return true;
		}

		return parent::delete_meta( $key );
	}

}