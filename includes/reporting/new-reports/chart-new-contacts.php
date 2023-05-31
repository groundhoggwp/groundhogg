<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use function Groundhogg\get_db;

class Chart_New_Contacts extends Base_Time_Chart_Report {

	protected function get_datasets() {

		$new = get_db( 'contacts' )->advanced_query( [
			'where'   => [
				[ 'date_created', '>=', $this->startDate->format( 'Y-m-d H:i:s' ) ],
				[ 'date_created', '<', $this->endDate->format( 'Y-m-d H:i:s' ) ]
			],
			'orderby' => 'date_created',
			'order'   => 'ASC',
			'select'  => "count(ID) y, DATE(date_created) t, CONCAT( DATE_FORMAT(date_created, '%b %e, %Y'), ': ', count(ID) ) label",
			'groupby' => 't'
		] );

		/**
		 * Create a valid data set to plot in chart
		 */

		return [
			'datasets' => [
				array_merge( [
					'label' => __('New contacts'),
					'data'  => $new,
				], $this->get_line_style() ),
			]
		];
	}


	/**
	 * Used to find date field form the list of array.
	 *
	 * @param $datum
	 *
	 * @return int
	 */
	public function get_time_from_datum( $datum ) {
		return strtotime( $datum->date_created );
	}

	/**
	 * Gets the contacts for the previous time period
	 *
	 * @return array
	 */
	public function get_previous_new_contact() {

		$query = new Contact_Query();

//		$data = $query->query( [
//			'date_query' => [
//				'after'  => date( 'Y-m-d H:i:s', Plugin::instance()->utils->date_time->convert_to_local_time( $this->compare_start ) ),
//				'before' => date( 'Y-m-d H:i:s', Plugin::instance()->utils->date_time->convert_to_local_time( $this->compare_end ) ),
//			]
//		] );
		$data = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->compare_start ),
				'before' => date( 'Y-m-d H:i:s', $this->compare_end ),
			]
		] );

		$grouped_data = $this->group_by_time( $data, true );

		return $this->normalize_data( $grouped_data );

	}
}
