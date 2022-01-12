<?php

namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-07
 * Time: 1:51 PM
 */
class Submission extends Base_Object_With_Meta {

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'submissions' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'submissionmeta' );
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'submission';
	}

	/**
	 * @return int
	 */
	public function get_step_id() {
		return absint( $this->step_id );
	}

	/**
	 * @return int
	 */
	public function get_form_id() {
		return $this->get_step_id();
	}

	public function get_date_created() {
		return date_i18n( get_date_time_format(), strtotime( $this->date_created ) );
	}

	public function get_contact_id() {
		return absint( $this->contact_id );
	}

	public function get_contact() {
		return get_contactdata( $this->get_contact_id() );
	}

	/**
	 * Adds a bulk array of posted data from a submission.
	 *
	 * @param $array array
	 */
	public function add_posted_data( $array ) {

		$array = is_array( $array ) ? $array : [ $array ];

		foreach ( $array as $item => $value ) {
			$this->add_meta( $item, $value );
		}
	}

	/**
	 * Modify return
	 *
	 * @return array
	 */
	public function get_as_array() {
		$array = parent::get_as_array();

		$array['data']['time'] = convert_to_utc_0( date_as_int( $this->get_date_created() ) );
		$array['form']         = new Step( $this->get_form_id() );
		$array['locale']       = [
			'diff_time' => human_time_diff( $array['data']['time'], time() )
		];

		return $array;
	}
}