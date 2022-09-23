<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\Ymd_His;

class Total_Confirmed_Contacts extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'optin_status_changed',
						'value'      => [ Preferences::CONFIRMED ],
						'date_range' => 'between',
						'before'     => Ymd_His( $this->end ),
						'after'      => Ymd_His( $this->start )
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

		$query->set_date_key( 'date_optin_status_changed' );

		$start = Plugin::instance()->utils->date_time->convert_to_local_time( $start );
		$end   = Plugin::instance()->utils->date_time->convert_to_local_time( $end );

		return $query->query( [
			'count'        => true,
			'optin_status' => Preferences::CONFIRMED,
			'date_query'   => [
				'after'  => date( 'Y-m-d H:i:s', $start ),
				'before' => date( 'Y-m-d H:i:s', $end ),
			]
		] );
	}
}
