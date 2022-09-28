<?php

namespace Groundhogg;

class Event_Queue_Item extends Event {

	public function __construct( $identifier_or_args = 0, $field = 'ID' ) {
		parent::__construct( $identifier_or_args, 'event_queue', $field );
	}

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

			$object = (object) array_merge( $old_data, $data );

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

}
