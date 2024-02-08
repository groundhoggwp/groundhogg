<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;

class Total_Funnel_Conversion_Rate extends Base_Quick_Stat_Percent {

	public function get_link() {

		$funnel = $this->get_funnel();

		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( array_values( array_map( function ( $step_id ) use ( $funnel ) {
				return [
					[
						'type'       => 'funnel_history',
						'funnel_id'  => $funnel->ID,
						'step_id'    => $step_id,
						'status'     => 'complete',
						'date_range' => 'between',
						'before'     => $this->endDate->ymd(),
						'after'      => $this->startDate->ymd()
					]
				];
			}, $funnel->get_conversion_step_ids() ) ) )
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

		$conversion_steps = $this->get_funnel()->get_conversion_step_ids();

		if ( empty( $conversion_steps ) ) {
			return 0;
		}

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'COUNT(DISTINCT(contact_id))' )
		           ->where()
		           ->lessThanEqualTo( 'time', $end )
		           ->greaterThanEqualTo( 'time', $start )
		           ->equals( 'status', Event::COMPLETE )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $this->get_funnel_id() )
		           ->in( 'step_id', $conversion_steps );

		return $eventQuery->get_var();

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
		$report = new Total_Contacts_In_Funnel( $start, $end );

		return $report->query( $start, $end );
	}

}
