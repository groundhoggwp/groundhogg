<?php

namespace Groundhogg\Reporting\New_Reports;

abstract class Base_Doughnut_Chart_Report extends Base_Chart_Report {

	/**
	 * @return string
	 */
	protected function get_type() {
		return 'doughnut';
	}

	/**
	 * @return array|array[]
	 */
	protected function get_datasets() {

		$data = $this->get_chart_data();

		return [
			'labels'   => $data['label'],
			'datasets' => [
				[
					'data'            => $data['data'],
					'backgroundColor' => $data['color']
				]
			]
		];
	}

	abstract protected function get_chart_data();

	/**
	 * Get the pie chart options
	 *
	 * @return array|array[]
	 */
	protected function get_options() {
		return $this->get_pie_chart_options();
	}

	/**
	 * Normalize data for doughnut chart
	 *
	 * @param $data
	 *
	 * @return array
	 */
	protected function normalize_data( $data ) {

		$values = wp_list_pluck( $data, 'meta_value' );
		$counts = array_count_values( $values );

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $counts as $key => $datum ) {
			$normalized = $this->normalize_datum( $key, $datum );
			$label []   = $normalized ['label'];
			$data[]     = $normalized ['data'];
			$color[]    = $normalized ['color'];

		}

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
	abstract protected function normalize_datum( $item_key, $item_data );

}