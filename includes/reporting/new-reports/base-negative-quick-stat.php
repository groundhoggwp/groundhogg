<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\percentage;
use function Sodium\compare;

abstract class Base_Negative_Quick_Stat extends Base_Quick_Stat {

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
			$color     = 'red';
		} else if ( $current_data < $compare_data ) {
			$direction = 'down';
			$color     = 'green';
		}

		return [
			'direction' => $direction,
			'color'     => $color,
		];
	}

}
