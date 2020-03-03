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


	protected function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}

	function get_random_color() {
		return '#' . $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}


	protected function get_line_style() {

		return [
			"fill"                      => false,
			'lineTension'               => 0,
			'fillOpacity'               => 0.2,
			'pointRadius'               => 4,
			'pointBackgroundColor'      => '#FFF',
			'hoverRadius'               => 1,
			'pointHoverBackgroundColor' => '#FFF',
			'pointHoverBorderWidth'     => 4,
			'pointHoverRadius'          => 6,
			"borderColor"               => $this->get_random_color(),
		];
	}
}
