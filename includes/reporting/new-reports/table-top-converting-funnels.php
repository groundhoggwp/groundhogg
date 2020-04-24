<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Top_Converting_Funnels extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Funnel', 'groundhogg' ),
			__( 'Conversion Rate', 'groundhogg' )
		];

	}

	protected function get_table_data() {

		//  get list of funnels and plot it conversion rate
		$funnels = get_db( 'funnels' )->query( [] );
		if ( empty( $funnels ) ) {
			return [];
		}

		$list = [];
		foreach ( $funnels as $funnel ) {
			$list [] = [
				'label' => $funnel->title,
				'data'  => $this->get_conversion_rate( $funnel->ID ),
				'url'   => admin_url( sprintf( 'admin.php?page=gh_funnels&action=edit&funnel=%s', $funnel->ID ) ),
			];
		}


		$list = $this->normalize_data( $list );

		foreach ( $list as $i => $datum ) {


			$datum['label'] = html()->wrap( $datum['label'], 'a', [
				'href'  => $datum['url'],
				'class' => 'number-total'
			] );
			$datum['data']  = $datum['data'] . '%';

			unset( $datum['url'] );
			$data[ $i ] = $datum;
		}

		return $data;

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

		return [
			'label' => $item_data ['label'],
			'data'  => $item_data ['data'],
			'url'   => $item_data ['url'],
		];
	}


	protected function get_conversion_rate( $funnel_id ) {

		$funnel = new Funnel( $funnel_id );

		$conversion_step = $funnel->get_conversion_step();

		if ( ! $conversion_step ) {
			$conversion_step = $funnel->get_first_step();
		}

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $conversion_step, 'compare' => '=' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $this->end, 'compare' => '<=' ],
		];

		$num_of_conversions = get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );

		$start = $this->start - MONTH_IN_SECONDS;

		$first_step = absint( $funnel->get_first_step() );

		$cquery = new Contact_Query();

		$num_events_completed = $cquery->query( [
			'count'  => true,
			'report' => [
				'start'  => $start,
				'end'    => $this->end,
				'step'   => $first_step,
				'status' => 'complete'
			]
		] );

		return percentage( $num_events_completed, $num_of_conversions );


	}


}