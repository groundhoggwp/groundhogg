<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\percentage;
use function Groundhogg\Ymd_His;

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
						'before'     => Ymd_His( $this->end ),
						'after'      => Ymd_His( $this->start )
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

		$where = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $conversion_steps, 'compare' => 'IN' ],
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



}
