<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Funnel_Breakdown extends Base_Chart_Report {

	protected function get_datasets() {

		$data = $this->get_complete_activity();

		return [
			'labels'   => $data['label'],
			'datasets' => [
				[
					'label'           => __( 'Completed', 'groundhogg' ),
					'data'            => $data['data'],
					'backgroundColor' => $this->get_random_color()
				]
//				$this->get_waiting_activity()
			]
		];

	}


	protected function get_type() {
		return 'bar';
	}

	protected function get_complete_activity() {

		$funnel = new Funnel( $this->get_funnel_id() );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$steps = $funnel->get_steps( [
			'step_group' => Step::BENCHMARK
		] );

		$dataset = [];

		foreach ( $steps as $i => $step ) {

			$query = new Contact_Query();
			$args  = array(
				'report' => array(
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => 'complete',
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);

			$count     = count( $query->query( $args ) );
			$label[]   = $step->get_title();
			$dataset[] = $count;

		}


		return [
			'label' => $label,
			'data'  => $dataset,
		];


	}

	/**
	 * @return array[]
	 */
	protected function get_options() {
		return [
			'responsive'          => true,
			'maintainAspectRatio' => false,
			'tooltips'            => [
				'backgroundColor' => '#FFF',
				'bodyFontColor'   => '#000',
				'borderColor'     => '#727272',
				'borderWidth'     => 2,
				'titleFontColor'  => '#000'
			],
			'scales'              => [
				'xAxes' => [
				],
				'yAxes' => [
					0 => [
						'ticks' => [
							'beginAtZero' => true,
						],
					]
				]
			]
		];
	}

}
