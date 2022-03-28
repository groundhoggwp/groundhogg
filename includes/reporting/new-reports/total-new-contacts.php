<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;

class Total_New_Contacts extends Base_Quick_Stat {

	public function get_url( $start, $end ) {
		$start = Plugin::instance()->utils->date_time->convert_to_local_time( $start );
		$end   = Plugin::instance()->utils->date_time->convert_to_local_time( $end );

		return admin_page_url( 'gh_contacts', [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $start ),
				'before' => date( 'Y-m-d H:i:s', $end ),
			]
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

		$start = Plugin::instance()->utils->date_time->convert_to_local_time( $start );
		$end   = Plugin::instance()->utils->date_time->convert_to_local_time( $end );

		return $query->query( [
			'count'      => true,
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $start ),
				'before' => date( 'Y-m-d H:i:s', $end ),
			]
		] );
	}
}
