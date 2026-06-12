<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Campaign extends Base_Object {

	public function __construct( $identifier_or_args = 0, $field = null ) {

		// use slug if not numeric
		if ( $field === null && is_string( $identifier_or_args ) && ! is_numeric( $identifier_or_args ) ){
			$field = 'slug';
		}

		parent::__construct( $identifier_or_args, $field );
	}

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'campaigns' );
	}

	protected function get_relationships_db() {
		return get_db( 'object_relationships' );
	}

	protected function get_object_type() {
		return 'campaign';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	public function is_public(){
		return $this->visibility === 'public';
	}
}
