<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Reporting\Email_Reports;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Replacer;

class Daily_Actions {

	public function __construct() {
		add_action( 'init', [ $this, 'schedule_event' ] );

//		add_action( 'groundhogg/daily', [ $this, 'send_status_report' ] );
		add_action( 'groundhogg/daily', [ $this, 'send_broadcast_reports' ] );
		add_action( 'groundhogg/daily', [ $this, 'maybe_send_overview_report' ] );
	}

	/**
	 * Add the daily actions cron event
	 *
	 * @return void
	 */
	public function schedule_event() {
		if ( wp_next_scheduled( 'groundhogg/daily' ) ) {
			return;
		}

		$date = new DateTimeHelper( 'tomorrow 9:00 AM' );

		wp_schedule_event( $date->getTimestamp(), 'daily', 'groundhogg/daily' );
	}

	public function send_status_report() {
//		Email_Reports::send_status_report();
	}

	/**
	 * Send reports for broadcasts sent the previous day.
	 *
	 * @return void
	 */
	public function send_broadcast_reports() {

		$yesterday = new DateTimeHelper( 'yesterday 00:00:00' );
		$yesterdayEod = (clone $yesterday)->modify('23:59:59');

		Email_Reports::send_broadcast_report( $yesterday, $yesterdayEod );
	}

	/**
	 * On mondays, send the overview report
	 *
	 * @return void
	 */
	public function maybe_send_overview_report() {

		$today = new DateTimeHelper();

		// 1st of the month
		// Send month in review
		if ( $today->format( 'j' ) === '1' ) {
			$after  = new DateTimeHelper( 'first day of last month 00:00:00' );
			$before = new DateTimeHelper( 'last day of last month 23:59:59' );
			$subject = sprintf( '[%s] Your month in review', white_labeled_name() );
		} // --
		// Mondays,
		// send last 7 days
		else if ( $today->format( 'l' ) === 'Monday' ) {
			$after  = new DateTimeHelper( '7 days ago 00:00:00' );
			$before = new DateTimeHelper( 'yesterday 23:59:59' );
			$subject = sprintf( '[%s] Review last week\'s performance', white_labeled_name() );
		} //
		// Otherwise do nothing
		else {
			return;
		}

		Email_Reports::send_overview_report( $after, $before, $subject );
	}
}
