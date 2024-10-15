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
				$this->unsubscribes(),
				$this->soft_bounce(),
				$this->hard_bounce(),
				$this->complaints(),
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

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Sent', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(0, 117, 255)',
			"borderColor"          => 'rgb(0, 117, 255)',
			'backgroundColor'      => 'rgba(0, 117, 255, 0.1)',
			'spanGaps'             => false,
		] );
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

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Opens', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(158, 206, 56)',
			"borderColor"          => 'rgb(158, 206, 56)',
			'backgroundColor'      => 'rgba(158, 206, 56, 0.2)',
			'spanGaps'             => false,
		]);

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

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Clicks', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(0, 117, 255)',
			"borderColor"          => 'rgb(0, 117, 255)',
			'backgroundColor'      => 'rgba(0, 117, 255, 0.1)',
			'spanGaps'             => false,
		] );

	}

	protected function soft_bounce() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::SOFT_BOUNCE,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Soft Bounces', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(255, 238, 88)',
			"borderColor"          => 'rgb(255, 238, 88)',
			'backgroundColor'      => 'rgba(255, 238, 88, 0.2)',
			'spanGaps'             => false,
		] );

	}

	protected function hard_bounce() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::BOUNCE,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Hard Bounces', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(245, 129, 21)',
			"borderColor"          => 'rgb(245, 129, 21)',
			'backgroundColor'      => 'rgba(245, 129, 21, 0.2)',
			'spanGaps'             => false,
		] );

	}

	protected function unsubscribes() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::UNSUBSCRIBED,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Unsubscribes', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(233, 31, 79)',
			"borderColor"          => 'rgb(233, 31, 79)',
			'backgroundColor'      => 'rgba(233, 31, 79, 0.2)',
			'spanGaps'             => false,
		] );

	}

	protected function complaints() {
		$db = get_db( 'activity' );

		$data = $db->query( [
			'select'        => 'COUNT(ID) as y, DATE(FROM_UNIXTIME(timestamp)) as t',
			'activity_type' => Activity::COMPLAINT,
			'before'        => $this->end,
			'after'         => $this->start,
			'orderby'       => 't',
			'order'         => 'asc',
			'groupby'       => 't'
		] );

		return array_merge( $this->get_line_style(), [
			'label' => __( 'Complaints', 'groundhogg' ),
			'data'  => $data,

			"pointBackgroundColor" => 'rgb(108, 25, 173)',
			"borderColor"          => 'rgb(108, 25, 173)',
			'backgroundColor'      => 'rgba(108, 25, 173, 0.1)',
			'spanGaps'             => false,
		] );

	}

}
