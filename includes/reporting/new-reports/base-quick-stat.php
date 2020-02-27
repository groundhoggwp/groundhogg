<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\percentage;
use function Sodium\compare;

abstract class Base_Quick_Stat {

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

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end int
	 *
	 * @return mixed
	 */
	abstract protected function query( $start, $end );

	/**
	 * Get the arrow properties
	 *
	 * @param $current_data int
	 * @param $compare_data int
	 *
	 * @return array
	 */
    protected function get_arrow_properties( $current_data, $compare_data ){

    	$direction = '';
    	$color     = '';

	    if ( $current_data > $compare_data ) {
		    $direction = 'up';
		    $color     = 'green';
	    } else if ( $current_data < $compare_data  ) {
		    $direction = 'down';
		    $color     = 'red';
	    }

	    return [
	    	'direction' => $direction,
		    'color'     => $color,
	    ];
	}

	public function get_data() {

		$current_data = $this->query( $this->start, $this->end );
		$compare_data = $this->query( $this->compare_start, $this->compare_end );

		$compare_diff = $current_data - $compare_data;

		$percentage = percentage( $current_data, $compare_diff, 0 );

		$arrow = $this->get_arrow_properties( $current_data, $compare_data );

		return  [
			'type' => 'quick_stat',
			'number'  => number_format_i18n( $current_data ),
			'compare' => [
				'arrow'   => [
					'direction' => $arrow[ 'direction' ],
					'color'     => $arrow[ 'color' ],
				],
				'percent' => intval( $percentage ) . '%',
				'text'    => sprintf( __( '.vs Previous %s Days', 'groundhogg' ), $this->num_days )
			],
			'data' => [
				'current' => $current_data,
				'compare' => $compare_data
			]
		];

	}

}
