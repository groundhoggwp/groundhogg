<?php

namespace Groundhogg\Reporting\Reports;


use Groundhogg\DB\Meta_DB;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */
abstract class Objects_By_Meta extends Report {

	/**
	 * Return the meta_key used to query the DB
	 *
	 * @return string
	 */
	abstract public function get_meta_key();

	/**
	 * Get the DB
	 *
	 * @return Meta_DB
	 */
	abstract public function get_db();

	/**
	 * Get the query
	 *
	 * @return array
	 */
	public function get_query() {
		return apply_filters( "groundhogg/reporting/reports/{$this->get_id()}/query", [
			'meta_key' => $this->get_meta_key(),
		] );
	}

	/**
	 * Get the report data
	 *
	 * @return array
	 */
	public function get_data() {
		$rows   = $this->get_db()->query( $this->get_query() );
		$values = wp_list_pluck( $rows, 'meta_value' );
		$counts = array_count_values( $values );

		/**
		 * Will be format
		 *
		 * [
		 *  'value' => count
		 * ]
		 */
		return apply_filters( "groundhogg/reporting/reports/{$this->get_id()}/data", $counts );
	}
}