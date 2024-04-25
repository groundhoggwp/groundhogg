<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\get_array_var;
use function Groundhogg\get_unsub_reasons;

class Chart_Unsub_Reasons extends Base_Doughnut_Chart_Report {

	protected function get_datasets() {

		$query = new Table_Query( 'activity' );
		$alias = $query->joinMeta( 'reason' );
		$query->setSelect( [ 'COUNT(ID)', 'total' ], [ "$alias.meta_value", 'reason' ] )
		      ->setGroupby( 'reason' )
		      ->setOrderby( 'total' )
		      ->setOrder( 'DESC' )
		      ->where()
		      ->equals( 'activity_type', Activity::UNSUBSCRIBED )
		      ->greaterThanEqualTo( 'timestamp', $this->start )
		      ->lessThanEqualTo( 'timestamp', $this->end );

		$results = $query->get_results();

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $results as $row ) {

			$reason = $row->reason;

			if ( ! empty( $reason ) ){
				$reason = get_array_var( get_unsub_reasons(), $reason, $reason );
			} else {
				$reason = __( 'Not set', 'groundhogg' );
			}

			$label[] = esc_html( $reason );
			$data[]  = $row->total;
			$color[] = $this->get_random_color();
		}

		return [
			'labels'   => $label,
			'datasets' => [
				[
					'data'            => $data,
					'backgroundColor' => $color
				],
			],
			'rawResults' => $results
		];
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

	protected function get_chart_data() {
		// TODO: Implement get_chart_data() method.
	}
}
