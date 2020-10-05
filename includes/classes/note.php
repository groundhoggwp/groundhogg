<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use function Groundhogg\get_db;
use function Groundhogg\map_func_to_attr;

class Note extends Base_Object {

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'notes' );
	}

	/**
	 * Sanitize note data
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function sanitize_columns( $data = [] ) {

		map_func_to_attr( $data, 'object_id', 'absint' );
		map_func_to_attr( $data, 'user_id', 'absint' );
		map_func_to_attr( $data, 'context', 'sanitize_text_field' );
		map_func_to_attr( $data, 'object_type', 'sanitize_text_field' );
		map_func_to_attr( $data, 'content', 'wp_kses_post' );

		return $data;
	}
}
