<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;

class Total_New_Contacts extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'date_created',
						'date_range' => 'between',
						'before' => $this->endDate->ymd(),
						'after'  => $this->startDate->ymd()
					]
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

		$query = new Contact_Query();

		return $query->count( [
			'after'  => $start,
			'before' => $end,
		] );
	}
}
