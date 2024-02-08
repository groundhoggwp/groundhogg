<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Total_Contacts_In_Funnel extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'funnel_history',
						'funnel_id'  => $this->get_funnel()->ID,
						'status'     => 'complete',
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

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'COUNT(DISTINCT(contact_id))' )
		           ->where()
		           ->lessThanEqualTo( 'time', $end )
		           ->greaterThanEqualTo( 'time', $start )
		           ->equals( 'status', Event::COMPLETE )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $this->get_funnel_id() );

		return $eventQuery->get_var();
	}
}
