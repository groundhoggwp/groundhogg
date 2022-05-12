<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;

class Total_Emails_Sent extends Base_Quick_Stat {

	static $cache = [];

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	public function query( $start, $end ) {

		$cache_key = "$start:$end";

		if ( isset_not_empty( self::$cache, $cache_key ) ) {
			return self::$cache[ $cache_key ];
		}

		global $wpdb;

		$events_table = get_db( 'events' )->get_table_name();
		$steps_table  = get_db( 'steps' )->get_table_name();

		$total = intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d
                        ORDER BY time DESC"
			, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
			$start, $end ) )
		);

		self::$cache[ $cache_key ] = $total;

		return $total;
	}
}
