<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
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
			'select'  => "COUNT(ID) y, DATE(date_created) t, CONCAT( DATE_FORMAT(date_created, '%b %e, %Y'), ': ', count(ID) ) label",
			'groupby' => 't'
		] );

		$confirmed = get_db( 'contacts' )->advanced_query( [
			'where'   => [
				[ 'optin_status', '=', Preferences::CONFIRMED ],
				[ 'date_optin_status_changed', '>=', $this->startDate->format( 'Y-m-d H:i:s' ) ],
				[ 'date_optin_status_changed', '<', $this->endDate->format( 'Y-m-d H:i:s' ) ]
			],
			'orderby' => 'date_optin_status_changed',
			'order'   => 'ASC',
			'select'  => "COUNT(ID) y, DATE(date_optin_status_changed) t, CONCAT( DATE_FORMAT(date_optin_status_changed, '%b %e, %Y'), ': ', count(ID) ) label",
			'groupby' => 't'
		] );

		$unsubscribes = get_db( 'contacts' )->advanced_query( [
			'where'   => [
				[ 'optin_status', '=', Preferences::UNSUBSCRIBED ],
				[ 'date_optin_status_changed', '>=', $this->startDate->format( 'Y-m-d H:i:s' ) ],
				[ 'date_optin_status_changed', '<', $this->endDate->format( 'Y-m-d H:i:s' ) ]
			],
			'orderby' => 'date_optin_status_changed',
			'order'   => 'ASC',
			'select'  => "COUNT(ID) y, DATE(date_optin_status_changed) t, CONCAT( DATE_FORMAT(date_optin_status_changed, '%b %e, %Y'), ': ', count(ID) ) label",
			'groupby' => 't'
		] );

//		$unsubscribes = get_db( 'activity' )->advanced_query( [
//			'where'   => [
//				[ 'activity_type', '=', Activity::UNSUBSCRIBED ],
//				[ 'timestamp', '>=', $this->startDate->getTimestamp() ],
//				[ 'timestamp', '<', $this->endDate->getTimestamp() ]
//			],
//			'orderby' => 'timestamp',
//			'order'   => 'ASC',
//			'select'  => "COUNT(ID) y, DATE(FROM_UNIXTIME(timestamp)) t, CONCAT( DATE_FORMAT(FROM_UNIXTIME(timestamp), '%b %e, %Y'), ': ', count(ID) ) label",
//			'groupby' => 't'
//		] );

		/**
		 * Create a valid data set to plot in chart
		 */

		return [
			'datasets' => [
				array_merge( $this->get_line_style(), [
					'label'                => __( 'New contacts' ),
					'data'                 => $new,
					"pointBackgroundColor" => 'rgb(0, 117, 255)',
					"borderColor"          => 'rgb(0, 117, 255)',
					'backgroundColor'      => 'rgba(0, 117, 255, 0.1)',
					'spanGaps'             => false,
				] ),
				array_merge( $this->get_line_style(), [
					'label'                => __( 'Confirmed' ),
					'data'                 => $confirmed,
					"pointBackgroundColor" => 'rgb(158, 206, 56)',
					"borderColor"          => 'rgb(158, 206, 56)',
					'backgroundColor'      => 'rgba(158, 206, 56, 0.2)',
					'spanGaps'             => false,
				] ),
				array_merge( $this->get_line_style(), [
					'label'                => __( 'Unsubscribed' ),
					'data'                 => $unsubscribes,
					"pointBackgroundColor" => 'rgb(233, 31, 79)',
					"borderColor"          => 'rgb(233, 31, 79)',
					'backgroundColor'      => 'rgba(233, 31, 79, 0.2)',
					'spanGaps'             => false,
				] ),
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
