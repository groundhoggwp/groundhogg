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
	public function query( $start, $end ) {

		$db = get_db( 'activity' );

		$query = [
			'select'        => 'DISTINCT contact_id',
			'activity_type' => Activity::EMAIL_CLICKED,
			'before'        => $end,
			'after'         => $start
		];

		return $db->count( $query );
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

		$query = [
			'select'        => 'DISTINCT contact_id',
			'activity_type' => Activity::EMAIL_OPENED,
			'before'        => $end,
			'after'         => $start
		];

		if ( $this->get_email_id() ) {
			$query['email_id'] = $this->get_email_id();
		}

		return $db->count( $query );

	}
}
