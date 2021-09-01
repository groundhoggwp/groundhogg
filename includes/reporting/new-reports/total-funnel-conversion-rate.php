<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\percentage;

class Total_Funnel_Conversion_Rate extends Base_Quick_Stat_Percent {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$funnel = new Funnel( $this->get_funnel_id() );

		$conversion_step = $funnel->get_conversion_step_ids();

		$where = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $conversion_step, 'compare' => 'IN' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		$num_of_conversions = get_db( 'events' )->count( [
			'where'  => $where,
			'select' => 'DISTINCT contact_id'
		] );

		return $num_of_conversions;

	}

	/**
	 * Query the vs results
	 *
	 * @param $start
	 * @param $end
	 *
	 * @return mixed
	 */
	protected function query_vs( $start, $end ) {

		$funnel = new Funnel( $this->get_funnel_id() );
		$cquery = new Contact_Query();

		return $cquery->query( [
			'count'  => true,
			'report' => [
				'funnel_id' => $funnel->get_id(),
				'step_id'   => $funnel->get_first_step_id(),
				'start'     => $start,
				'end'       => $end,
				'status'    => Event::COMPLETE
			]
		] );

	}

}
