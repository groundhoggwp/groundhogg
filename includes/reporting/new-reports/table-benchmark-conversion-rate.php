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


	function only_show_top_10() {
		return false;
	}

	function column_title() {
		// TODO: Implement column_title() method.
	}


	/**
	 * @return array
	 */
	public function get_data() {
		return [
			'type'  => 'table',
			'label' => $this->get_label(),
			'data'  => $this->benchmark_conversion_rate()
		];
	}


	public function get_label() {
		return [
			__( 'Benchmark', 'groundhogg' ),
			__( 'Benchmark', 'groundhogg' ),
			__( 'Conversion Rate', 'groundhogg' )
		];

	}


	protected function get_funnel_id() {
		return get_request_var( 'data' )[ 'funnel_id' ];
	}


	protected function benchmark_conversion_rate() {
		//get list of benchmark
		$funnel = new Funnel( $this->get_funnel_id() );

		$steps = get_db( 'steps' )->query( [
			'step_group' => 'benchmark',
			'orderby '   => 'step_order',
			'funnel_id'  => $funnel->get_id()
		] );

		$steps = wp_parse_id_list( wp_list_pluck( $steps, 'ID' ) );

		if ( count( $steps ) > 1 ) {

			$data = [];

			for ( $i = 1; $i < count( $steps ); $i ++ ) {

				$current_step  = new Step( $steps[ $i ] );
				$previous_step = new Step( $steps[ $i - 1 ] );
				$data []       = [
					'label1' => $current_step->get_step_title(),
					'label2' => $previous_step->get_step_title(),
					'data'   => percentage( $this->get_total( $steps[ $i ], $this->start, $this->end ), $this->get_total( $steps[ $i - 1 ], $this->start - WEEK_IN_SECONDS, $this->end ) ) .'%'
				];
			}

			return array_reverse($data);
		} else {

			$current_step = new Step( $steps[ 0 ] );

			return [
				[
					'label1' => $current_step->get_step_title(),
					'label2' => $current_step->get_step_title(),
					'data'   => percentage( $this->get_total( $steps[ 0 ], $this->start, $this->end ), $this->get_total( $steps[ 0 ], $this->start - WEEK_IN_SECONDS, $this->end ) ) .'%'
				]
			];


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


	protected function get_total( $step_id, $start, $end ) {

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