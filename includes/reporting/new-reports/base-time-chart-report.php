<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;

abstract class Base_Time_Chart_Report extends Base_Chart_Report{

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	abstract  protected function query( $start, $end );

	/**
	 * @return array[]
	 */
	protected function get_datasets() {

		$current_contacts = $this->query( $this->start, $this->end );

		return [
			[
				'label' => 'Monday',
				'data'  => [
					'x' => 10,
					'y' => 10
				]
			],
		];
	}

	/**
	 * @return string[]
	 */
	protected function get_labels() {
		return [];
	}

	protected function get_options() {
		return [
			'responsive' => true,
			'scales'     => [
				'xAxes' => [
					'type' => 'time',
					'time' => [
						'parser'        => 'YYYY-MM-DD HH:mm:ss',
						'tooltipFormat' => "1 HH:mm"
					],
					'scaleLabel' => [
						'display'     => true,
						'labelString' => 'Date',
					]
				],
				'yAxes' => [
					'scaleLabel' => [
						'display'     => true,
						'labelString' => 'Date',
					]
				]
			]
		];
	}
}
