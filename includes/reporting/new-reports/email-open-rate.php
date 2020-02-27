<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use function Groundhogg\get_db;

class Email_Open_Rate extends Base_Quick_Stat_Percent {


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
			'activity_type' => Activity::EMAIL_OPENED,
			'before' => $end,
			'after' => $start
		] );

//		wp_send_json( $data );

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

		global $wpdb;

		$events_table = get_db('events')->get_table_name();
		$steps_table  = get_db('steps')->get_table_name();

		$data = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d
                        ORDER BY time DESC"
			, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
			$start, $end )
		);

//		wp_send_json( $data );

		return $data;

	}
}
