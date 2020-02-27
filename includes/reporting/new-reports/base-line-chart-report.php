<?php

namespace Groundhogg\Reporting\New_Reports;

abstract class Base_Line_Chart_Report extends Base_Report {

	/**
	 * @return array[]
	 */
	abstract protected function get_datasets();

	/**
	 * @return string
	 */
	protected function get_type() {
		return 'line';
	}

	/**
	 * @return array[]
	 */
	protected function get_options() {
		return [
			'responsive' => true,
			'scales'     => [
				'yAxes' => [
					0 => [
						'ticks'      => [
							'beginAtZero' => true,
						],
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'value',
						]
					]
				]
			]
		];
	}

	/**
	 * Get the report data
	 *
	 * @return mixed
	 */
	public function get_data() {

		return [
			'type'  => 'chart',
			'chart' => [
				'type'    => $this->get_type(),
				'data'    => $this->get_datasets(),
				'options' => $this->get_options()
			]
		];
	}
}
