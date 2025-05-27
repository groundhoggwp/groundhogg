<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;

abstract class Base_Funnel_Quick_Stat_Report extends Base_Quick_Stat_Percent {

	/**
	 * The number of contacts which completed a step in the given time frame
	 *
	 * @param     $step_id
	 *
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	protected function get_num_contacts_by_step( $step_id, $start = 0, $end = 0 ) {

		$start = $start ?: $this->start;
		$end   = $end ?: $this->end;

		if ( empty( $step_id ) ){
			return 0;
		}

		return get_db( 'events' )->count( [
			'where'  => [
				'relationship' => "AND",
				[ 'col' => 'step_id', 'val' => $step_id, 'compare' => is_array( $step_id ) ? 'IN' : '=' ],
				[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
				[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
				[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
			],
			'groupby' => 'contact_id'
		] );
	}

}
