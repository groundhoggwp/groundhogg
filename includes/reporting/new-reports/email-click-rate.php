<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use function Groundhogg\get_db;

class Email_Click_Rate extends Base_Quick_Stat_Percent {


	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$db = get_db( 'activity' );

		$data = $db->count( [
			'activity_type' => Activity::EMAIL_CLICKED,
			'before' => $end,
			'after' => $start
		] );

		return $data;

	}

	/**
	 * Query the vs results
	 *
	 * @param $start
	 * @param $end
	 *
	 * @return mixed
	 */
	protected function query_vs( $start, $end ) {

		$db = get_db( 'activity' );

		$data = $db->count( [
			'activity_type' => Activity::EMAIL_OPENED,
			'before' => $end,
			'after' => $start
		] );

		return $data;

	}
}
