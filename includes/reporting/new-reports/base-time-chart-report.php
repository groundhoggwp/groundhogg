<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

abstract class Base_Time_Chart_Report extends Base_Line_Chart_Report {

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
					0 => [
						'type'       => 'time',
						'time'       => [
							'parser'        => "YYY-MM-DD HH:mm:ss",
							'tooltipFormat' => "l HH:mm"
						],
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Date',
						]
					]
				],
				'yAxes' => [
					0 => [
						'scaleLabel' => [
							'display'     => true,
							'labelString' => 'Date',
						]
					]
				]
			]
		];
	}




}
