<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use function Groundhogg\get_db;

class Total_Active_Contacts extends Base_Quick_Stat {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$count = get_db( 'activity' )->count( [
			'select'   => 'contact_id',
			'distinct' => true,
			'where'    => [
				'relationship' => 'AND',
				// Start
				[
					'col'     => 'timestamp',
					'val'     => $start,
					'compare' => '>='
				],
				// END
				[
					'col'     => 'timestamp',
					'val'     => $end,
					'compare' => '<='
				],
				[
					'col'     => 'activity_type',
					'val'     => 'email_opened',
					'compare' => '='
				]
			]
		] );

		return $count;
	}
}
