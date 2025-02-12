<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Campaign;
use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Reports;
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
	 * Swaps start with compare_start because I'm a lazy coder.
	 *
	 * @return void
	 */
	public function swap_range_with_compare_dates() {
		$origStart     = $this->start;
		$origEnd       = $this->end;
		$origStartDate = $this->startDate;
		$origEndDate   = $this->endDate;

		$this->start     = $this->compare_start;
		$this->end       = $this->compare_end;
		$this->startDate = $this->startDateCompare;
		$this->endDate   = $this->endDateCompare;

		$this->compare_start    = $origStart;
		$this->compare_end      = $origEnd;
		$this->startDateCompare = $origStartDate;
		$this->endDateCompare   = $origEndDate;
	}

	/**
	 * Retrieve the human diff time string for the reporting comparison
	 *
	 * @return mixed|string
	 */
	protected function get_human_time_diff() {
		$diff = $this->startDate->human_time_diff( $this->endDate );

		$parts = explode( ' ', $diff );
		if ( absint( $parts[0] ) === 1 ){
			return $parts[1];
		}

		return $diff;
	}

	/**
	 * Set the appropriate time interval for the comparison period.
	 */
	protected function set_compare_dates() {
		$interval = $this->startDate->diff( $this->endDate );

		$this->startDateCompare = clone $this->startDate;
		$this->endDateCompare   = clone $this->endDate;

		// We modify by an additional second because the interval is 1 second short of the full deal
		$this->startDateCompare->sub( $interval )->modify( '-1 second' );
		$this->endDateCompare->sub( $interval )->modify( '-1 second' );

		$this->compare_end   = $this->endDateCompare->getTimestamp();
		$this->compare_start = $this->startDateCompare->getTimestamp();
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

	protected function get_campaign() {
		return new Campaign( $this->get_campaign_id() );
	}

	protected function get_other_report_params( $key, $default = false ) {
		return Reports::get_param( $key );
	}

	/**
	 * @return Funnel
	 */
	protected function get_funnel() {
		return new Funnel( $this->get_funnel_id() );
	}

	protected static $funnel_id;
	protected static $step_id;
	protected static $email_id;
	protected static $broadcast_id;

	/**
	 * Get the funnel IDs if available
	 *
	 * @return mixed
	 */
	protected function get_funnel_id() {

		if ( self::$funnel_id ) {
			return self::$funnel_id;
		}

		if ( $this->get_step_id() ) {
			$step = new Step( $this->get_step_id() );
			if ( $step->exists() ) {
				self::$funnel_id = $step->get_funnel_id();

				return self::$funnel_id;
			}
		}

		self::$funnel_id = absint( Reports::get_param( 'funnel_id' ) );

		return self::$funnel_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_email_id() {

		if ( self::$email_id ) {
			return self::$email_id;
		}

		if ( $this->get_step_id() ) {
			$step = new Step( $this->get_step_id() );
			if ( $step->exists() && $step->type_is( 'send_email' ) ) {

				self::$email_id = absint( $step->get_meta( 'email_id' ) );

				return self::$email_id;
			}
		}

		self::$email_id = absint( Reports::get_param( 'email_id' ) );

		return self::$email_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_step_id() {

		if ( self::$step_id ) {
			return self::$step_id;
		}

		self::$step_id = absint( Reports::get_param( 'step_id' ) );

		return self::$step_id;
	}

	/**
	 * @return mixed
	 */
	protected function get_broadcast_id() {

		if ( self::$broadcast_id ) {
			return self::$broadcast_id;
		}

		self::$broadcast_id = absint( Reports::get_param( 'broadcast_id' ) );

		return self::$broadcast_id;
	}

}
