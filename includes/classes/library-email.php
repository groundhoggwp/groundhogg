<?php

namespace Groundhogg;

class Library_Email extends Email {

	public function __construct( $object ) {
		$this->data = (array) $object->data;
		$this->meta = (array) $object->meta;
		$this->ID = 'library-' . $object->ID;
	}

	public function create( $data = [] ) {
		return false;
	}

	public function update( $data = [] ) {
		return false;
	}

	public function delete() {
		return false;
	}

	public function update_meta( $key, $value = false ) {
		return false;
	}

	public function add_meta( $key, $value = false ) {
		return false;
	}

	public function delete_meta( $key ) {
		return false;
	}
}
