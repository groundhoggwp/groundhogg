<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\DB\DB;
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

	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'locale' => [
				'time_diff' => human_time_diff( $this->timestamp, time() )
			]
		]);
	}
}
