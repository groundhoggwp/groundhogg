<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Benchmark_Conversion_Rate extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'From', 'groundhogg' ),
			__( '# Contacts', 'groundhogg' ),
			__( 'To', 'groundhogg' ),
			__( '# Contacts', 'groundhogg' ),
			__( 'Conversion Rate', 'groundhogg' )
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {
		//get list of benchmark
		$funnel = new Funnel( $this->get_funnel_id() );
		$steps  = $funnel->get_steps( [
			'step_group' => Step::BENCHMARK
		] );

		if ( count( $steps ) > 1 ) {

			$data = [];

			for ( $i = 0; $i < count( $steps ) - 1; $i ++ ) {

				$current_step = $steps[ $i ];
				$next_step    = $steps[ $i + 1 ];

				$total1 = $this->get_num_of_completed_contacts( $current_step->get_id(), $this->start, $this->end );
				$total2 = $this->get_num_of_completed_contacts( $next_step->get_id(), $this->start - WEEK_IN_SECONDS, $this->end );

				$data [] = [
					'from'   => $current_step->get_step_title(),
					'total1' => $total1,
					'to'     => $next_step->get_step_title(),
					'total2' => $total2,
					'scr'    => percentage( $total1, $total2 ) . '%'
				];
			}

			return $data;
		} else {

			$current_step = $steps[0];

			if( $current_step ) {


				$total = $this->get_num_of_completed_contacts( $current_step->get_id(), $this->start, $this->end );

				return [
					[
						'from'   => $current_step->get_step_title(),
						'total1' => $total,
						'to'     => '',
						'total2' => '',
						'scr'    => percentage( $total, $total ) . '%'
					]
				];
			} else {

				return [];

			}


		}

	}


	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	protected function normalize_datum( $item_key, $item_data ) {

		//not used
	}


	protected function get_num_of_completed_contacts( $step_id, $start, $end ) {

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $step_id, 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $end, 'compare' => '<=' ],
		];

		$num_of_contacts = get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );

		return $num_of_contacts;
	}


}