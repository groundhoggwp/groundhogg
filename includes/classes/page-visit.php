<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use function Groundhogg\get_db;

class Page_Visit extends Base_Object {

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	public function get_timestamp() {
		return absint( $this->timestamp );
	}

	public function get_time() {
		return $this->get_timestamp();
	}

	public function get_contact_id() {
		return absint( $this->contact_id );
	}

	/**
	 * Gets the date time
	 *
	 * @return \DateTime
	 * @throws \Exception
	 */
	public function get_date() {
		$date = new \DateTime();
		$date->setTimestamp( $this->get_timestamp() );
		$date->setTimezone( wp_timezone() );

		return $date;
	}

	/**
	 * Return the url
	 *
	 * @return string
	 */
	public function get_url() {
		return home_url( $this->path . ( ! empty( $this->query ) ? '?' . $this->query : '' ) . ( ! empty( $this->fragment ) ? '#' . $this->fragment : '' ) );
	}

	/**
	 * Return the url
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path . ( ! empty( $this->query ) ? '?' . $this->query : '' ) . ( ! empty( $this->fragment ) ? '#' . $this->fragment : '' );
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'page_visits' );
	}

	public function get_as_array() {
		$array = parent::get_as_array();

		$array['locale'] = [
			'diff_time' => human_time_diff( $this->get_timestamp(), time() )
		];

		return $array;
	}
}