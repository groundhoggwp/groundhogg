<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Email_Click_Rate extends Base_Quick_Stat_Percent {

	public function get_link() {

		$filter = [
			'type'       => 'email_link_clicked',
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

		$db = get_db( 'activity' );

		$query = [
			'groupby'       => 'contact_id',
			'activity_type' => Activity::EMAIL_CLICKED,
			'before'        => $end,
			'after'         => $start
		];

		if ( $this->get_email_id() ) {
			$query['email_id'] = $this->get_email_id();
		}

		if ( $this->get_funnel_id() ) {
			$query['funnel_id'] = $this->get_funnel_id();
		}


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
		$report = new Email_Open_Rate( $start, $end );

		return $report->query( $start, $end );
	}
}
