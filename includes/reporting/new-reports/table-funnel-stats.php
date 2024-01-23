<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\DB\Query;
use Groundhogg\Event;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_find;
use function Groundhogg\html;

class Table_Funnel_Stats extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'Steps', 'groundhogg' ),
			__( 'Completed', 'groundhogg' ),
			__( 'Waiting', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {
		//get list of benchmark
		$funnel = $this->get_funnel();

		$steps = $funnel->get_steps();

		$data = [];

		$query = new Query( 'events' );

		$query->setSelect( [ 'COUNT(ID)', 'total' ], 'step_id' )
		      ->setGroupby( 'step_id' )
		      ->where( 'funnel_id', $funnel->get_id() )
		      ->equals( 'status', Event::COMPLETE )
		      ->equals( 'event_type', Event::FUNNEL )
		      ->greaterThanEqualTo( 'time', $this->start )
		      ->lessThanEqualTo( 'time', $this->end );

		$completed_results = $query->get_results();

		$query = new Query( 'event_queue' );

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

			$url_waiting = admin_page_url( 'gh_contacts', [
				'report' => [
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => Event::WAITING,
				]
			] );

			$url_completed = admin_page_url( 'gh_contacts', [
				'report' => [
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => Event::COMPLETE,
				]
			] );

			$img = html()->e( 'img', [
				'src'   => $step->icon(),
				'class' => implode( ' ', [
					'step-icon',
					$step->get_group()
				] )
			] );

			$edit = html()->e( 'a', [
				'class'  => 'step-title',
				'href'   => admin_page_url( 'gh_funnels', [
					'action' => 'edit',
					'funnel' => $step->get_funnel_id()
				], $step->ID ),
				'target' => '_blank'
			], $step->get_title() );

			$title = sprintf( '%s%s<br/><span class="step-type pill %s">%s</span>', $img, $edit, $step->get_group(), $step->get_type_name() );

			$data[] = [
				'step'      => $title,
				'completed' => html()->wrap( _nf( $count_completed ), 'a', [
					'href'  => $url_completed,
					'class' => 'number-total'
				] ),
				'waiting'   => html()->wrap( _nf( $count_waiting ), 'a', [
					'href'  => $url_waiting,
					'class' => 'number-total'
				] )
			];

		}


		return $data;

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}
