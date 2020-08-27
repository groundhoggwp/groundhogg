<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use function Groundhogg\percentage;
use function Groundhogg\set_cookie;

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
	 * @var array
	 */
	protected $request_data ;

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
	public function __construct( $start, $end  ,$request_data = [] ) {

		if ( is_string( $start ) ) {
			$start = strtotime( $start );
		}

		if ( is_string( $end ) ) {
			$end = strtotime( $end );
		}

		$this->start = absint( $start );
		$this->end   = absint( $end );
		$this->request_data = $request_data;

		$this->set_compare_dates();
	}

	/**
	 * Set the appropriate time interval for the comparison period.
	 */
	protected function set_compare_dates() {
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
		$this->compare_start = absint( $previous_start->format( 'U' ) );
		$this->compare_end   = absint( $previous_end->format( 'U' ) );
	}


	protected function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}


	protected $count = 0;

	function get_random_color() {

		if ( $this->count < 8 ) {
			$arr   = [
				'#F18F01',
				'#006E90',
				'#99C24D',
				'#F46036',
				'#41BBD9',
				'#ADCAD6',
				'#336699',
				'#2F4858'
			];
			$color = $arr [ $this->count ];
			$this->count ++;

			return $color;
		}

		return '#' . $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}

	/**
	 * Ge the chart data
	 *
	 * @return mixed
	 */
	abstract public function get_data();

	/**
	 * List of contact ids created in this timer period
	 *
	 * @return array
	 */
	protected function get_new_contact_ids_in_time_period() {
		$this->start = Plugin::instance()->utils->date_time->convert_to_local_time( $this->start );
		$this->end   = Plugin::instance()->utils->date_time->convert_to_local_time( $this->end );

		$query = new Contact_Query();

		$contacts = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		return wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );
	}

	/**
	 * List of contact ids created in this timer period
	 *
	 * @return array
	 */
	protected function get_new_contacts_in_time_period() {
		$this->start = Plugin::instance()->utils->date_time->convert_to_local_time( $this->start );
		$this->end   = Plugin::instance()->utils->date_time->convert_to_local_time( $this->end );

		$query = new Contact_Query();

		$contacts = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		return $contacts;
	}

	/**
	 * Get the funnel IDs if available
	 *
	 * @return mixed
	 */
	protected function get_funnel_id() {

//		$funnel_id = absint( get_array_var( get_request_var( 'data', [] ), 'funnel_id' ) );
//		set_cookie( 'gh_reporting_funnel_id', $funnel_id, MINUTE_IN_SECONDS );


		return absint( get_array_var( $this->request_data , 'ddl_funnels' ));


	}

	/**
	 * @return Funnel
	 */
	protected function get_funnel() {
		return new Funnel( $this->get_funnel_id() );
	}

	/**
	 * @return mixed
	 */
	protected function get_email_id() {
		return absint( get_array_var( get_request_var( 'data', [] ), 'email_id' ) );
	}

	/**
	 * @return mixed
	 */
	protected function get_step_id() {
		return absint( get_array_var( get_request_var( 'data', [] ), 'step_id' ) );
	}

	protected function get_title() {
		return  'STATIC TITLE';
	}


}
