<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;

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
	 * @var DateTimeHelper
	 */
	protected $startDate;
	protected $endDate;
	protected $startDateCompare;
	protected $endDateCompare;

	/**
	 * Reports constructor.
	 *
	 * @param $start int unix timestamps
	 * @param $end   int unix timestamps
	 */
	public function __construct( $start, $end ) {

		if ( is_string( $start ) ) {
			$start = strtotime( $start );
		}

		if ( is_string( $end ) ) {
			$end = strtotime( $end );
		}

		$this->start = absint( $start );
		$this->end   = absint( $end );

		$this->startDate = new DateTimeHelper( $this->start );
		$this->endDate   = new DateTimeHelper( $this->end );

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

		$this->startDateCompare = new DateTimeHelper( $this->compare_start );
		$this->endDateCompare   = new DateTimeHelper( $this->compare_end );
	}


	protected function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}


	protected $count = 0;

	function get_random_color() {
		$predefined_colors = [
			'#F18F01',
			'#006E90',
			'#99C24D',
			'#F46036',
			'#41BBD9',
			'#ADCAD6',
			'#336699',
			'#2F4858'
		];

		// added filter to customize the Chart colors
		$predefined_colors = apply_filters( 'groundhogg/admin/reports/predefined_colors', $predefined_colors );

		if ( $this->count < count( $predefined_colors ) ) {

			$color = $predefined_colors [ $this->count ];
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

		$query = new Contact_Query();

		return $query->query( [
			'date_query' => [
				'after'  => $this->start,
				'before' => $this->end,
			]
		] );
	}

	protected function get_campaign_id() {
		return absint( $this->get_other_report_params( 'campaign' ) );
	}

	protected function get_other_report_params( $key, $default = false ) {
		return get_array_var( get_request_var( 'data', [] ), $key );
	}

	/**
	 * @return Funnel
	 */
	protected function get_funnel() {
		return new Funnel( $this->get_funnel_id() );
	}

	protected $funnel_id;
	protected $step_id;
	protected $email_id;
	protected $broadcast_id;

	/**
	 * Get the funnel IDs if available
	 *
	 * @return mixed
	 */
	protected function get_funnel_id() {

		if ( $this->funnel_id ) {
			return $this->funnel_id;
		}

		if ( $this->get_step_id() ) {
			$step = new Step( $this->get_step_id() );
			if ( $step->exists() ) {
				$this->funnel_id = $step->get_funnel_id();

				return $this->funnel_id;
			}
		}

		$this->funnel_id = absint( get_array_var( get_request_var( 'data', [] ), 'funnel_id' ) );

		return $this->funnel_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_email_id() {

		if ( $this->email_id ) {
			return $this->email_id;
		}

		if ( $this->get_step_id() ) {
			$step = new Step( $this->get_step_id() );
			if ( $step->exists() && $step->type_is( 'send_email' ) ) {

				$this->email_id = absint( $step->get_meta( 'email_id' ) );

				return $this->email_id;
			}
		}

		$this->email_id = absint( get_array_var( get_request_var( 'data', [] ), 'email_id' ) );

		return $this->email_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_step_id() {

		if ( $this->step_id ) {
			return $this->step_id;
		}

		$this->step_id = absint( get_array_var( get_request_var( 'data', [] ), 'step_id' ) );

		return $this->step_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_broadcast_id() {

		if ( $this->broadcast_id ) {
			return $this->broadcast_id;
		}

		$this->broadcast_id = absint( get_array_var( get_request_var( 'data', [] ), 'broadcast_id' ) );

		return $this->broadcast_id;
	}

}
