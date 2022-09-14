<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
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

		// Get list of funnels and plot it conversion rate
		// Only include active funnels
		$funnels = get_db( 'funnels' )->query( [
			'status' => 'active'
		] );

		if ( empty( $funnels ) ) {
			return [];
		}

		$list = [];
		foreach ( $funnels as $funnel ) {
			$list [] = [
				'label' => $funnel->title,
				'data'  => $this->get_conversion_rate( $funnel->ID ),
				'url'   => admin_page_url( 'gh_reporting', [ 'tab' => 'funnels', 'funnel' => $funnel->ID ] ),
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

		$funnel           = new Funnel( $funnel_id );
		$conversion_steps = $funnel->get_conversion_step_ids();

		$where = [
			'relationship' => "AND",
			[ 'col' => 'step_id', 'val' => $conversion_steps, 'compare' => 'IN' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $this->end, 'compare' => '<=' ],
		];

		$num_of_conversions = get_db( 'events' )->count( [
			'where'  => $where,
			'select' => 'DISTINCT contact_id'
		] );

		$cquery = new Contact_Query();

		$num_events_completed = $cquery->query( [
			'count'  => true,
			'report' => [
				'funnel_id' => $funnel->get_id(),
				'step_id'   => $funnel->get_first_step_id(),
				'start'     => $this->start,
				'end'       => $this->end,
				'status'    => Event::COMPLETE
			]
		] );

		return percentage( $num_events_completed, $num_of_conversions );

	}


}
