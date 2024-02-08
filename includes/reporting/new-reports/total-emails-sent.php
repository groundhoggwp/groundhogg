<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Total_Emails_Sent extends Base_Quick_Stat {

	public function get_link() {

		$filter = [
			'type'       => 'email_received',
			'date_range' => 'between',
			'before'     => $this->endDate->ymd(),
			'after'      => $this->startDate->ymd()
		];

		if ( $this->get_email_id() ) {
			$filter['email_id'] = $this->get_email_id();
		}

		if ( $this->get_funnel_id() ) {
			$filter['funnel_id'] = $this->get_funnel_id();
		}

		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					$filter
				]
			] )
		] );
	}

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		if ( $this->get_email_id() ) {

			$email = new Email( $this->get_email_id() );
			$stats = $email->get_email_stats( $start, $end );
			$data  = $stats['sent'];

		} else if ( $this->get_funnel_id() ) {

			$query = new Table_Query( 'events' );
			$query->where( 'event_type', Event::FUNNEL )
			      ->equals( 'status', Event::COMPLETE )
			      ->equals( 'funnel_id', $this->get_funnel_id() )
			      ->greaterThanEqualTo( 'time', $start )
			      ->lessThanEqualTo( 'time', $end )
			      ->notEquals( 'email_id', 0 );

			$data = $query->count();

		} else {
			global $wpdb;

			$events_table = get_db( 'events' )->get_table_name();
			$steps_table  = get_db( 'steps' )->get_table_name();

			$data = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d"
				, 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
				$start, $end )
			);
		}

		return $data;
	}
}
