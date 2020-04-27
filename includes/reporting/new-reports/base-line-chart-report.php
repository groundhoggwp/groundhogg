<?php

namespace Groundhogg\Reporting\New_Reports;

abstract class Base_Line_Chart_Report extends Base_Chart_Report{

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
			'tooltips'   => [
				'mode'            => 'index',
				'intersect'       => false,
				'backgroundColor' => '#FFF',
				'bodyFontColor'   => '#000',
				'borderColor'     => '#727272',
				'borderWidth'     => 2,
				'titleFontColor'  => '#000'
			],
			'scales'     => [
				'yAxes' => [
					0 => [
						'ticks'      => [
							'beginAtZero' => true,
						],
						'scaleLabel' => [
							'display'     => false,
							'labelString' => 'value',
						]
					]
				],

			]
		];
	}

	protected function get_line_style() {

		$color =   $this->get_random_color() ;
		return [
//			"fill"                      => false,
			'lineTension'               => 0,
			'fillOpacity'               => 0.2,
			'pointRadius'               => 4,
			'pointBackgroundColor'      => '#FFF',
			'hoverRadius'               => 1,
			'pointHoverBackgroundColor' => '#FFF',
			'pointHoverBorderWidth'     => 4,
			'pointHoverRadius'          => 6,
			"borderColor"               => $color,
			'backgroundColor'           => $color . '1A',
			'fill'                      => true

		];
	}
}
