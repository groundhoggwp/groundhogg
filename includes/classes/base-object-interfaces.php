<?php

namespace Groundhogg;

interface Base_Object_Interface {

	public function exists();

	public function get_id();

	public function get_object_type();

	public function update( $data = [] );

	public function delete();
}

interface Base_Object_With_Meta_Interface extends Base_Object_Interface {

	public function get_all_meta();

	public function get_meta( $key = false, $single = true );

	public function add_meta( $key, $value = true );

	public function update_meta_if_empty( $key, $value = false );

	public function update_meta( $key, $value = false );

	public function delete_meta( $key );
}
