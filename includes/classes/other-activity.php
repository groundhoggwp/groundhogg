<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object_With_Meta;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_db;

class Other_Activity extends Base_Object_With_Meta {

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

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'other_activity' );
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'other_activitymeta' );
	}

	public function get_as_array() {
		$array = parent::get_as_array();

		$date = new DateTimeHelper( $this->get_timestamp() );

		$i18n = [
			'diff_time' => $date->i18n(),
			'wp_date'   => $date->wpDateTimeFormat(),
		];

		$array['i18n'] = $i18n;

		return $array;
	}
}
