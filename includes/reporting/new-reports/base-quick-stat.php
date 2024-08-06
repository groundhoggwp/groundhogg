<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\_nf;
use function Groundhogg\html;
use function Groundhogg\percentage;
use function Groundhogg\percentage_change;
use function Sodium\compare;

abstract class Base_Quick_Stat extends Base_Report {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	abstract protected function query( $start, $end );

	/**
	 * Get the arrow properties
	 *
	 * @param $current_data int
	 * @param $compare_data int
	 *
	 * @return array
	 */
	protected function get_arrow_properties( $current_data, $compare_data ) {

		$direction = '';
		$color     = '';

		if ( $current_data > $compare_data ) {
			$direction = 'up';
			$color     = 'green';
		} else if ( $current_data < $compare_data ) {
			$direction = 'down';
			$color     = 'red';
		}

		return [
			'direction' => $direction,
			'color'     => $color,
		];
	}

	public function get_link() {
		return false;
	}

	/**
	 * Get the report data
	 *
	 * @return array|mixed
	 */
	public function get_data() {

		$current_data = $this->query( $this->start, $this->end );
		$compare_data = $this->query( $this->compare_start, $this->compare_end );

		$percentage   = percentage_change( $compare_data, $current_data);
		$arrow        = $this->get_arrow_properties( $current_data, $compare_data );

		return [
			'type'    => 'quick_stat',
			'number'  => $this->get_link() ? html()->e( 'a', [
				'href'   => $this->get_link(),
				'target' => '_blank'
			], _nf( $current_data ), false ) : _nf( $current_data ),
			'compare' => [
				'arrow'   => [
					'direction' => $arrow['direction'],
					'color'     => $arrow['color'],
				],
				'percent' => _nf( absint( $percentage ) ) . '%',
				'text'    => sprintf( __( '.vs prev %s', 'groundhogg' ), $this->get_human_time_diff() )
			],
			'data'    => [
				'current' => $current_data,
				'compare' => $compare_data,
			]
		];

	}

}
