<?php

namespace Groundhogg\Reporting\Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Meta_DB;
use Groundhogg\Event;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */
class Form_Impressions extends Report {

	/**
	 * Get the report ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'form_impressions';
	}

	/**
	 * Get the report name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Form Impressions', 'groundhogg' );
	}

	/**
	 * Get the report data
	 *
	 * @return array
	 */
	public function get_data() {
		$db = get_db( 'form_impressions' );

		$data = $db->query( [
			'before' => $this->get_end_time(),
			'after'  => $this->get_start_time()
		] );

		$counts = [];

		// Normalize data so reports don't have to change...
		foreach ( $data as $datum ) {
			$count = absint( $datum->count );
			for ( $i = 0; $i < $count; $i ++ ) {
				$counts[] = [ 'timestamp' => absint( $datum->timestamp ) ];
			}
		}

		return $counts;
	}
}