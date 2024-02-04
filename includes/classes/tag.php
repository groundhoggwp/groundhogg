<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Table_Query;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 01-May-19
 * Time: 4:34 PM
 */
class Tag extends Base_Object {

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	protected function get_db() {
		return get_db( 'tags' );
	}

	protected function get_relationships_db() {
		return get_db( 'tag_relationships' );
	}

	protected function get_object_type() {
		return 'tag';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->tag_name;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return absint( $this->tag_id );
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->tag_description;
	}

	/**
	 * @return int
	 */
	public function get_contact_count() {
		return get_db( 'tag_relationships' )->count( [ 'tag_id' => $this->get_id() ] );
	}

	/**
	 * @return bool
	 */
	public function is_preference_tag() {
		return boolval( $this->show_as_preference );
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->tag_slug;
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return $this->tag_id && $this->tag_name && $this->tag_slug;
	}

	/**
	 * @return int[]
	 */
	public function get_contact_ids() {

		$query = new Table_Query( 'tag_relationships' );
		$query->where('tag_id', $this->get_id() );

		return wp_parse_id_list( wp_list_pluck( $query->get_results(), 'contact_id' ) );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_name();
	}
}
