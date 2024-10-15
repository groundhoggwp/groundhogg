<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\array_find;
use function Groundhogg\get_array_var;
use function Groundhogg\get_object_ids;

class Chart_Funnel_Breakdown extends Base_Chart_Report {

	protected function get_datasets() {

		$data = $this->get_complete_activity();

		return [
			'labels'   => get_array_var( $data, 'label', [] ),
			'datasets' => [
				[
					'label'           => __( 'Completed', 'groundhogg' ),
					'data'            => get_array_var( $data, 'data', [] ),
					'backgroundColor' => $this->get_random_color()
				]
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

		$datasets = [];
		$labels   = [];

		$query = new Table_Query( 'events' );

		$query->setSelect( [ 'COUNT(ID)', 'total' ], 'step_id' )
		      ->setGroupby( 'step_id' )
		      ->where( 'funnel_id', $funnel->get_id() )
		      ->equals( 'status', Event::COMPLETE )
		      ->equals( 'event_type', Event::FUNNEL )
		      ->in( 'step_id', get_object_ids( $steps ) )
		      ->greaterThanEqualTo( 'time', $this->start )
		      ->lessThanEqualTo( 'time', $this->end );

		$results = $query->get_results();

		foreach ( $steps as $step ) {

			$result = array_find( $results, function ( $result ) use ( $step ) {
				return $result->step_id == $step->get_id();
			} );

			$total = $result ? absint( $result->total ) : 0;

			$labels[]   = $step->get_title();
			$datasets[] = $total;
		}

		return [
			'label' => $labels,
			'data'  => $datasets,
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
					0 => [
						'maxBarThickness' => 100
					]
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
