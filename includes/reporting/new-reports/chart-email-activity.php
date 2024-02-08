<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;

class Chart_Email_Activity extends Base_Time_Chart_Report {

	protected function get_datasets() {
		return [
			'datasets' => [
				$this->emails_sent(),
				$this->emails_opened(),
				$this->emails_clicked(),
			]
		];

	}


	/**
	 * @param $datum
	 *
	 * @return int
	 */
	public function get_time_from_datum( $datum ) {

		if ( isset_not_empty( $datum, 'time' ) ) {
			return absint( $datum->time );
		} else if ( isset_not_empty( $datum, 'timestamp' ) ) {
			return absint( $datum->timestamp );
		}

		return false;
	}


	protected function emails_sent() {

		global $wpdb;

		$events_table = get_db( 'events' )->get_table_name();
		$steps_table  = get_db( 'steps' )->get_table_name();

		$data = $wpdb->get_results( $wpdb->prepare(
			"SELECT COUNT(e.ID) y, DATE(FROM_UNIXTIME(e.time)) t FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d
						GROUP BY t ORDER BY t ASC

                        "
			, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
			$this->start, $this->end )
		);

//		var_dump( $wpdb->last_error );

		return array_merge( [
			'label' => __( 'Emails sent', 'groundhogg' ),
			'data'  => $data,
		], $this->get_line_style() );
	}

	protected function emails_opened() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::EMAIL_OPENED,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( [
			'label' => __( 'Emails Opened', 'groundhogg' ),
			'data'  => $data,

		], $this->get_line_style() );

	}

	protected function emails_clicked() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::EMAIL_CLICKED,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( [
			'label' => __( 'Emails Clicked', 'groundhogg' ),
			'data'  => $data,

		], $this->get_line_style() );

	}

}
