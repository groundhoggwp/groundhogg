<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;

class Total_Confirmed_Contacts extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'optin_status_changed',
						'value'      => [ Preferences::CONFIRMED ],
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

		$query = new Contact_Query( [
			'optin_status' => Preferences::CONFIRMED,
			'date_query'   => [
				'date_key' => 'date_optin_status_changed',
				'after'    => $start,
				'before'   => $end,
			]
		] );

		return $query->count();
	}
}
