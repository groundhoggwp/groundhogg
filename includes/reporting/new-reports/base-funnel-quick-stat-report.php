<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Funnel;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;

abstract class Base_Funnel_Quick_Stat_Report extends Base_Quick_Stat_Percent {

	/**
	 * The number of contacts which completed a step in the given time frame
	 *
	 * @param $step_id
	 *
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	protected function get_num_contacts_by_step( $step_id, $start = 0, $end = 0 ) {

		$start = $start ?: $this->start;
		$end   = $end ?: $this->end;

		return get_db( 'events' )->count( [
			'where'  => [
				'relationship' => "AND",
				[ 'col' => 'step_id', 'val' => $step_id, 'compare' => '=' ],
				[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
				[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
				[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
			],
			'select' => 'DISTINCT contact_id'
		] );
	}

}
