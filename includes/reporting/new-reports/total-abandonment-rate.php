<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\percentage;

class Total_Abandonment_Rate extends Base_Quick_Stat_Percent {


	protected function get_funnel_id() {
		return absint( get_request_var( 'data' )[ 'funnel_id' ] );
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

		$funnel = new Funnel( $this->get_funnel_id() );

		$conversion_step = $funnel->get_conversion_step();

		if ( ! $conversion_step ) {
			$conversion_step = $funnel->get_first_step();
		}

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $conversion_step, 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		$num_of_conversions = get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );

		return absint( $this->get_total( $start, $end ) ) - absint( $num_of_conversions );

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

		return $this->get_total( $start, $end );
	}


	protected function get_total( $start, $end ) {

		$funnel = new Funnel( $this->get_funnel_id() );

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $funnel->get_first_step(), 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		$num_of_contacts = get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );


		return $num_of_contacts;
	}

}
