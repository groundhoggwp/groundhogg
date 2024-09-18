<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Event;
use Groundhogg\Reporting\New_Reports\Traits\Funnel_Conversion_Stats;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;

class Total_Funnel_Conversions extends Base_Quick_Stat {

	use Funnel_Conversion_Stats;

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
		return $this->get_funnel_conversions( $this->get_funnel(), $start, $end );
	}


}
