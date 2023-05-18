<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\DB\DB;
use function Groundhogg\create_object_from_type;
use function Groundhogg\get_db;

class Note extends Base_Object {

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'notes' );
	}

	public function get_owner_id() {
		return absint( $this->user_id );
	}

	protected function sanitize_columns( $data = [] ) {
		foreach ( $data as $col => &$val ) {
			switch ( $col ) {
				case 'content':
					$val = wp_kses_post( $val );
					break;
			}
		}

		return $data;
	}

	/**
	 * Gets the related object
	 *
	 * @return \Groundhogg\DB_Object|\Groundhogg\DB_Object_With_Meta
	 */
	public function get_associated_object(){
		return create_object_from_type( $this->object_id, $this->object_type );
	}

	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'locale' => [
				'time_diff' => human_time_diff( $this->timestamp, time() )
			]
		]);
	}
}
