<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;

class Total_Active_Contacts extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'was_active',
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

		$query = new Table_Query( 'activity' );
		$query->setSelect( [ 'COUNT(DISTINCT(contact_id))', 'unique_contacts' ] );
		$query->where()
		      ->greaterThanEqualTo( 'timestamp', $start )
		      ->lessThanEqualTo( 'timestamp', $end );

		return $query->get_var();
	}
}
