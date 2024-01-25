<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

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

		$funnel = new Funnel( $this->get_funnel_id() );

		$conversion_steps = $funnel->get_conversion_step_ids();

		if ( ! $conversion_steps ) {
			return 0;
		}

		$where = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $conversion_steps, 'compare' => 'IN' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
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

		return $cquery->count( [
			'report' => [
				'funnel_id' => $funnel->get_id(),
				'step_id'   => $funnel->get_entry_step_ids(),
				'start'     => $start,
				'end'       => $end,
				'status'    => Event::COMPLETE
			]
		] );

	}

}
