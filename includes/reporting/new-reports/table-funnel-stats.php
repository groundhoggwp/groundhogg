<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
use function Groundhogg\contact_filters_link;

class Table_Funnel_Stats extends Base_Report {

	protected $per_page = 99;

	public function get_label() {
		return [
			esc_html__( 'Steps', 'groundhogg' ),
			esc_html__( 'Completed', 'groundhogg' ),
			esc_html__( 'Waiting', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	public function get_data() {
		//get list of benchmark
		$funnel = $this->get_funnel();

		$steps = $funnel->get_steps();

		$data = [];

		$query = new Table_Query( 'events' );

		$query->setSelect( [ 'COUNT(ID)', 'total' ], 'step_id' )
		      ->setGroupby( 'step_id' )
		      ->where( 'funnel_id', $funnel->get_id() )
		      ->equals( 'status', Event::COMPLETE )
		      ->equals( 'event_type', Event::FUNNEL )
		      ->greaterThanEqualTo( 'time', $this->start )
		      ->lessThanEqualTo( 'time', $this->end );

		$completed_results = $query->get_results();

		$query = new Table_Query( 'event_queue' );

		$query->setSelect( [ 'COUNT(ID)', 'total' ], 'step_id' )
		      ->setGroupby( 'step_id' )
		      ->where( 'funnel_id', $funnel->get_id() )
		      ->equals( 'status', Event::WAITING )
		      ->equals( 'event_type', Event::FUNNEL );

		$waiting_results = $query->get_results();

		foreach ( $steps as $i => $step ) {

			$complete_result = array_find( $completed_results, function ( $result ) use ( $step ) {
				return $result->step_id == $step->get_id();
			} );

			$count_completed = $complete_result ? absint( $complete_result->total ) : 0;

			$waiting_result = array_find( $waiting_results, function ( $result ) use ( $step ) {
				return $result->step_id == $step->get_id();
			} );

			$count_waiting = $waiting_result ? absint( $waiting_result->total ) : 0;

			$data[] = [
				'step'      => $step->ID,
				'complete' => contact_filters_link( _nf( $count_completed ), [
					// Group
					[
						// Filter
						[
							'type'       => 'funnel_history',
							'funnel_id'  => $funnel->get_id(),
							'step_id'    => $step->get_id(),
							'date_range' => 'between',
							'before'     => $this->endDate->ymd(),
							'after'      => $this->startDate->ymd(),
						]
					]
				], $count_completed ),
				'waiting'   => contact_filters_link( _nf( $count_waiting ), [
					// Group
					[
						// Filter
						[
							'type'      => 'funnel_history',
							'status'    => Event::WAITING,
							'funnel_id' => $funnel->get_id(),
							'step_id'   => $step->get_id(),
						]
					]
				], $count_waiting )
			];
		}

		return [
			'type'     => 'funnel',
			'stepData' => $data
		];

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
