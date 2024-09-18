<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Funnel;
use function Groundhogg\get_db;
use function Groundhogg\percentage;

class Total_Benchmark_Conversion_Rate extends Base_Quick_Stat {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		//get list of benchmark
		$funnel = new Funnel( $this->get_funnel_id() );

		$steps = get_db( 'steps' )->query( [
			'step_group' => 'benchmark',
			'orderby'    => 'step_order',
			'funnel_id'  => $funnel->get_id()
		] );

		$steps = wp_parse_id_list( wp_list_pluck( $steps, 'ID' ) );

		if ( count( $steps ) > 1 ) {

			$per = [];

			for ( $i = 1; $i < count( $steps ); $i ++ ) {
				$per [] = percentage( $this->get_total( $steps[ $i ], $start, $end ), $this->get_total( $steps[ $i - 1 ], $start - WEEK_IN_SECONDS, $end ) );
			}

			return array_sum( $per ) / count( $per );
		} else {

			return percentage( $this->get_total( $steps[0], $start, $end ), $this->get_total( $steps[0], $start - WEEK_IN_SECONDS, $end ) );

		}

	}

	protected function get_total( $step_id, $start, $end ) {

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $step_id, 'compare' => '=' ],
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
