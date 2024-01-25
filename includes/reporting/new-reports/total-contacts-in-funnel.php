<?php

namespace Groundhogg\Reporting\New_Reports;

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


		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $this->get_funnel()->get_id(), 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $this->get_funnel()->get_entry_step_ids(), 'compare' => 'IN' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		return get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );
	}
}
