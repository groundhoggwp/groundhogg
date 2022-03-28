<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_db;

class Donut_Chart_Contact_Engagement extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$this->start = convert_to_local_time( $this->start );
		$this->end   = convert_to_local_time( $this->end );

		$engaged = get_db( 'activity' )->count( [
			'select'   => 'contact_id',
			'distinct' => true,
			'where'    => [
				'relationship' => 'AND',
				// Start
				[
					'col'     => 'timestamp',
					'val'     => $this->start,
					'compare' => '>='
				],
				// END
				[
					'col'     => 'timestamp',
					'val'     => $this->end,
					'compare' => '<='
				],
			]
		] );

		$all_contacts = get_db( 'contacts' )->count();

		$data = [
			$engaged,
			$all_contacts - $engaged
		];

		$label = [
			__( 'Engaged', 'groundhogg' ),
			__( 'Unengaged', 'groundhogg' )
		];

		$color = [
			$this->get_random_color(),
			$this->get_random_color()
		];

		return [
			'label' => $label,
			'data'  => $data,
			'color' => $color
		];

	}

	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	protected function normalize_datum( $item_key, $item_data ) {
	}
}