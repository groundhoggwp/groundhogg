<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Total_Contacts_Added_To_Funnel extends Base_Quick_Stat {

	public function get_link() {
		$funnel_id = $this->get_funnel_id();

		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( array_map( function ( $step_id ) use ( $funnel_id ) {
				return [
					[
						'type'       => 'funnel_history',
						'funnel_id'  => $funnel_id,
						'step_id'    => $step_id,
						'status'     => 'complete',
						'date_range' => 'between',
						'before'     => $this->endDate->ymd(),
						'after'      => $this->startDate->ymd()
					]
				];
			}, $this->get_funnel()->get_entry_step_ids() ) )
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

		$entry_steps = $this->get_funnel()->get_entry_step_ids();

		if ( empty( $entry_steps ) ) {
			return 0;
		}

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $this->get_funnel()->get_id(), 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $entry_steps, 'compare' => 'IN' ],
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
