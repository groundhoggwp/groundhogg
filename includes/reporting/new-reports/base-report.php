<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\percentage;

abstract class Base_Report {

	/**
	 * @var int
	 */
	protected $start;

	/**
	 * @var int
	 */
	protected $end;

	/**
	 * @var int
	 */
	protected $compare_start;

	/**
	 * @var int
	 */
	protected $compare_end;

	/**
	 * @var int
	 */
	protected $num_days;

	/**
	 * Reports constructor.
	 *
	 * @param $start int unix timestamps
	 * @param $end int unix timestamps
	 */
	public function __construct( $start, $end ) {

		if ( is_string( $start ) ){
			$start = strtotime( $start );
		}

		if ( is_string( $end ) ){
			$end = strtotime( $end );
		}

		$this->start = absint( $start );
		$this->end   = absint( $end );

		$this->set_compare_dates();
	}

	/**
	 * Set the appropriate time interval for the comparison period.
	 */
	protected function set_compare_dates(){
		// Calculate the difference in days
		$date_diff = $this->end - $this->start;
		$num_days  = floor( $date_diff / DAY_IN_SECONDS ) + 1;

		$this->num_days = $num_days;

		// Get the comparison
		$startdate = date_create( date( 'Y-m-d H:i:s', $this->start ) );
		$enddate   = date_create( date( 'Y-m-d H:i:s', $this->end ) );

		// subtract number of days
		$previous_start = date_sub( $startdate, date_interval_create_from_date_string( $num_days . " days" ) );
		$previous_end   = date_sub( $enddate, date_interval_create_from_date_string( $num_days . " days" ) );

		// previous period
		$this->compare_start = $previous_start->format( 'U' );
		$this->compare_end   = $previous_end->format( 'U' );
	}


	protected function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}

	function get_random_color() {
		return '#' . $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}


}
