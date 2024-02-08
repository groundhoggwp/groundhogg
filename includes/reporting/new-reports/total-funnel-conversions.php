<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Event;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Total_Funnel_Conversions extends Base_Quick_Stat {

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
						'before' => $this->endDate->ymd(),
						'after'  => $this->startDate->ymd()
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

		$where = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $this->get_funnel()->get_id(), 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $conversion_steps, 'compare' => 'IN' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		return get_db( 'events' )->count( [
			'where'  => $where,
			'select' => 'DISTINCT contact_id'
		] );

	}


}
