<?php

namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Campaign extends Base_Object {

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
