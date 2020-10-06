<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use Groundhogg\Preferences;

class Total_Spam_Contacts extends Base_Negative_Quick_Stat {

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
			'optin_status' => Preferences::SPAM,
			'date_query'   => [
				'after'  => date( 'Y-m-d H:i:s', $start ),
				'before' => date( 'Y-m-d H:i:s', $end ),
			]
		] );
	}
}
