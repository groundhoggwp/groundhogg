<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\html;
use function Groundhogg\percentage;
use function Groundhogg\percentage_change;

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
		$current_data    = $this->query( $this->start, $this->end );
		$current_data_vs = $this->query_vs( $this->start, $this->end );

		// Calc percentage
		$current_percentage = percentage( $current_data_vs, $current_data, 0 );

		// Get the old data
		$compare_data    = $this->query( $this->compare_start, $this->compare_end );
		$compare_data_vs = $this->query_vs( $this->compare_start, $this->compare_end );

		// Calc percentage
		$compare_percentage = percentage( $compare_data_vs, $compare_data, 0 );

		// Calc that percentage
		$percentage = percentage_change( $compare_percentage, $current_percentage, 0 );

		// Get arrow props
		$arrow = $this->get_arrow_properties( $current_percentage, $compare_percentage );

		$output = esc_html( number_format_i18n( $current_percentage ) . '%' );

		return [
			'type'    => 'quick_stat',
			'number' => $this->get_link() ? html()->e( 'a', [
				'href'   => $this->get_link(),
				'target' => '_blank'
			], $output, false ) : $output,
			'compare' => [
				'arrow'   => [
					'direction' => $arrow['direction'],
					'color'     => $arrow['color'],
				],
				'percent' => absint( $percentage ) . '%',
				/* translators: %s: the previous time range, like "30 days" */
				'text'    => sprintf( __( '.vs prev %s', 'groundhogg' ), $this->get_human_time_diff() )
			],
			'data'    => [
				'current' => $current_percentage,
				'compare' => $compare_percentage,
			]
		];

	}

}
