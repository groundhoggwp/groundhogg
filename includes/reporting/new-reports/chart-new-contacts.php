<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;

class Chart_New_Contacts extends Base_Time_Chart_Report {

	protected function get_datasets() {

		$new      = $this->normalize_data( $this->group_by_time( $this->get_new_contacts_in_time_period() ) );
		$previous = $this->get_previous_new_contact();

		$n = [];
		$p = [];

		/**
		 * adds labels in the data set to display during the hover action
		 */
		for ( $i = 0; $i < count( $new ); $i ++ ) {

			$n[] = [
				't'     => $new[ $i ][ 't' ],
				'label' => sprintf( " %s (%s): %s", __( 'Contacts', 'groundhogg' ), date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), strtotime( $new[ $i ][ 't' ] ) ), $new[ $i ][ 'y' ] ),
				'y'     => $new[ $i ][ 'y' ]
			];

			$p[] = [
				't'     => $new[ $i ][ 't' ],
				'label' => sprintf( " %s (%s): %s", __( 'Contacts', 'groundhogg' ), date( get_option( 'date_format' ) . " " . get_option( 'time_format' ), strtotime( $previous[ $i ][ 't' ] ) ), $previous[ $i ][ 'y' ] ),
				'y'     => $previous[ $i ][ 'y' ],
			];

		}

		/**
		 * Create a valid data set to plot in chart
		 */

		return [
			'datasets' => [
				array_merge( [
					'label' => __( sprintf( "%s - %s", date( get_option( 'date_format' ), $this->start ), date( get_option( 'date_format' ), $this->end ) ), 'groundhogg' ),
					'data'  => $n,
				], $this->get_line_style() ),
				array_merge( [
					'label' => __( sprintf( "%s - %s", date( get_option( 'date_format' ), $this->compare_start ), date( get_option( 'date_format' ), $this->compare_end ) ), 'groundhogg' ),
					'data'  => $p,
				], $this->get_line_style() )
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

		return $this->normalize_data($grouped_data);

	}
}
