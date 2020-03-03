<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Funnel_Breakdown extends Base_Line_Chart_Report {

	protected function get_datasets() {

		$labels = [] ;
		foreach( $this->get_complete_activity()['data'] as $item )
		{
			$labels [] = $item ['x'];
		}
		return [
			'labels' => $labels ,
			'datasets' => [
				$this->get_complete_activity(),
				$this->get_waiting_activity()
			]
		];

	}

	protected function get_funnel_id()
	{
		return 91;
	}

	protected function get_complete_activity() {

		$funnel = new Funnel( $this->get_funnel_id() );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$steps   = $funnel->get_steps();
		$dataset = [];
		foreach ( $steps as $i => $step ) {
			$query     = new Contact_Query();
			$args      = array(
				'report' => array(
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => 'complete',
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);
			$count     = count( $query->query( $args ) );
			$dataset[] = [
				'x' => ( $i + 1 ) . '. ' . $step->get_title(),
				'y' => $count
			];
		}


 	return array_merge( [
			'label' => __( 'Complete Funnel Activity', 'groundhogg' ),
			'data'  => $dataset,

		], $this->get_line_style() );



	}

	protected function get_waiting_activity() {

		$funnel = new Funnel( $this->get_funnel_id() );

		if ( ! $funnel->exists() ){
			return [];
		}

		$steps = $funnel->get_steps();
		$dataset = [];

		foreach ( $steps as $i => $step ) {
			$query = new Contact_Query();
			$args = array(
				'report' => array(
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => 'waiting',
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);
			$count = count($query->query($args));
			$dataset[] = [
				'x' => ( $i + 1 ) . '. ' . $step->get_title(),
				'y' => $count
			];
		}


		return array_merge( [
			'label' => __( 'Waiting Funnel Activity', 'groundhogg' ),
			'data'  => $dataset,

		], $this->get_line_style() );


	}


}
