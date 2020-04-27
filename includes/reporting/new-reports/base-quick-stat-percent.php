<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\percentage;
use function Sodium\compare;

abstract class Base_Quick_Stat_Percent extends Base_Quick_Stat {

	/**
	 * Query the vs results
	 *
	 * @param $start
	 * @param $end
	 *
	 * @return mixed
	 */
	abstract protected function query_vs( $start, $end );

	/**
	 * Get the Data
	 *
	 * @return array
	 */
	public function get_data() {

    	// Get the new data
		$current_data = $this->query( $this->start, $this->end );
		$current_data_vs = $this->query_vs( $this->start, $this->end );

		// Calc percentage
		$current_percentage = percentage( $current_data_vs, $current_data, 0 );

		// Get the old data
		$compare_data = $this->query( $this->compare_start, $this->compare_end );
		$compare_data_vs = $this->query_vs( $this->compare_start, $this->compare_end );

		// Calc percentage
		$compare_percentage = percentage( $compare_data_vs, $compare_data, 0 );

		// Get the difference in the percentages
		$compare_diff = $current_percentage - $compare_percentage;

		// Calc that percentage
		$percentage = percentage( $compare_percentage, $compare_diff, 0 );

		// Get arrow props
		$arrow = $this->get_arrow_properties( $current_percentage, $compare_percentage );

		return  [
			'type' => 'quick_stat',
			'number'  => esc_html( number_format_i18n( $current_percentage ) . '%' ) ,
			'compare' => [
				'arrow'   => [
					'direction' => $arrow[ 'direction' ],
					'color'     => $arrow[ 'color' ],
				],
				'percent' => absint( $percentage ) . '%',
				'text'    => sprintf( __( '.vs Previous %s Days', 'groundhogg' ), $this->num_days )
			],
			'data' => [
				'current' => $current_percentage,
				'compare' => $compare_percentage,
			]
		];

	}

}
