<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Replacer;

class Daily_Actions {

	public function __construct() {
		add_action( 'init', [ $this, 'schedule_event' ] );

		add_action( 'groundhogg/daily', [ $this, 'send_broadcast_reports_by_email' ] );

//		if ( get_request_var( 'test_email_report' ) ) {
//			add_action( 'template_redirect', [ $this, 'send_broadcast_reports_by_email' ] );
//			add_action( 'template_redirect', [ $this, 'maybe_send_overview_report' ] );
//		}

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

	/**
	 * Generate the table for the broadcasts performance
	 *
	 * @param array    $performance
	 * @param callable $ignoreRow
	 *
	 * @return string
	 */
	private function generate_performance_table_html( array $performance, callable $ignoreRow ) {

		$rows = [];

		foreach ( $performance['data'] as $i => $row ) {

			if ( call_user_func( $ignoreRow, $row ) ) {
				continue;
			}

			extract( $row );

			$cells = [];
			$k     = 0;

			foreach ( $row as $cellId => $value ) {

				if ( $cellId === 'cellClasses' || $cellId === 'orderby' ) {
					continue;
				}

				$classes = [
					$cellClasses[ $k ]
				];

				$style = [
					'padding' => '8px 8px 8px 12px',
				];

				if ( $k > 0 ) {
					$style['text-align'] = 'center';
					$classes[]           = 'num';
				}

				$cells[] = html()->e( 'td', [
					'style' => $style,
					'class' => $classes
				], $value );

				$k ++;
			}

			$rows[] = html()->e( 'tr', [
				'style' => [
					'background-color' => $i % 2 === 0 ? '#F6F9FB' : ''
				]
			], $cells );
		}

		return html()->e( 'table', [
			'style' => [
				'border-collapse' => 'collapse',
				'width'           => '100%',
				'table-layout'    => 'auto',
			],
			'width' => '100%'
		], [
			html()->e( 'thead', [], [
				html()->e( 'tr', [], array_map_with_keys( $performance['label'], function ( $header, $i ) {

					$style = [
						'padding' => '8px 8px 8px 12px',
					];

					if ( $i > 0 ) {
						$style['text-align'] = 'center';
					}

					return html()->e( 'th', [
						'style' => $style
					], $header );
				} ) )
			] ),
			html()->e( 'tbody', [], $rows )
		] );

	}

	/**
	 * Send reports for broadcasts sent the previous day.
	 *
	 * @return void
	 */
	public function send_broadcast_reports_by_email() {

		$yesterday   = new DateTimeHelper( 'yesterday' );
		$reports     = new Reports( $yesterday->getTimestamp(), $yesterday->getTimestamp() + DAY_IN_SECONDS - 1 );
		$performance = $reports->get_data( 'table_all_broadcasts_performance' );

		// No broadcasts were sent
		if ( empty( $performance['data'] ) ) {
			return;
		}

		$table_html = $this->generate_performance_table_html( $performance, '__return_false' );

		$replacer = new Replacer( [
			'logo_url'                => is_white_labeled() ? '' : GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png',
			'num_broadcasts'          => count( $performance['data'] ),
			'broadcast_results_table' => $table_html,
			'full_report_link'        => admin_page_url( 'gh_reporting', [
				'tab'   => 'broadcasts',
				'start' => $yesterday->ymd(),
				'end'   => $yesterday->ymd(),
			] ),
			'site_name'                => get_bloginfo(),
			'site_url'                 => home_url(),
		] );

		$email_content = file_get_contents( GROUNDHOGG_ASSETS_PATH . 'emails/broadcast-results.html' );
		$email_content = $replacer->replace( $email_content );

		\Groundhogg_Email_Services::send_wordpress( [
			get_option( 'admin_email' )
		], '[Groundhogg] Yesterday\'s broadcast performance', $email_content, [
			'Content-Type: text/html',
		] );

//		echo $email_content;
//		die();
	}

	private function generate_compare_html( $performance ) {

		$percentage   = percentage_change( absint( $performance['data']['compare'] ), absint( $performance['data']['current'] ) );
		$down_is_good = ( $performance['compare']['arrow']['color'] === 'red' && $performance['compare']['arrow']['direction'] === 'up' ) || ( $performance['compare']['arrow']['color'] === 'green' && $performance['compare']['arrow']['direction'] === 'down' );

		return html()->percentage_change( $percentage, $down_is_good );
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
			$subject = '[Groundhogg] Your month in review';
		} // --
		// Mondays,
		// send last 7 days
		else if ( $today->format( 'l' ) === 'Monday' ) {
			$after  = new DateTimeHelper( '7 days ago 00:00:00' );
			$before = new DateTimeHelper( 'yesterday 23:59:59' );
			$subject = '[Groundhogg] Review last week\'s performance';
		} //
		// Otherwise do nothing
		else {
			$after  = new DateTimeHelper( '7 days ago 00:00:00' );
			$before = new DateTimeHelper( 'yesterday 23:59:59' );
			$subject = '[Groundhogg] Review last week\'s performance';
//			return;
		}

		$reports = new Reports( $after->getTimestamp(), $before->getTimestamp() );

		$broadcastPerformance = $reports->get_data( 'table_all_broadcasts_performance' );
		$funnelPerformance    = $reports->get_data( 'table_all_funnels_performance_without_email' );
		$totalNewContacts     = $reports->get_data( 'total_new_contacts' );
		$totalEngagedContacts = $reports->get_data( 'total_engaged_contacts' );
		$totalUnsubContacts   = $reports->get_data( 'total_unsubscribed_contacts' );

		$replacer = new Replacer( [
			'logo_url'                 => is_white_labeled() ? '' : GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png',
			'time_range'               => $after->human_time_diff( $before ),
			'start_date'               => $after->wpDateFormat(),
			'end_date'                 => $before->wpDateFormat(),
			'compare_text'             => $totalNewContacts['compare']['text'],
			'broadcast_results_table'  => $this->generate_performance_table_html( $broadcastPerformance, '__return_false' ),
			'funnel_results_table'     => $this->generate_performance_table_html( $funnelPerformance, function ( $row ) {
				return $row['orderby'][2] == 0;
			} ),
			'full_report_link'         => admin_page_url( 'gh_reporting', [
				'start' => $after->ymd(),
				'end'   => $before->ymd()
			] ),
			'site_name'                => get_bloginfo(),
			'site_url'                 => home_url(),
			// New contacts
			'new_contacts_compare'     => $this->generate_compare_html( $totalNewContacts ),
			'new_contacts_num'         => $totalNewContacts['number'],
			// Engaged
			'engaged_contacts_compare' => $this->generate_compare_html( $totalEngagedContacts ),
			'engaged_contacts_num'     => $totalEngagedContacts['number'],
			// Unsubscribes
			'unsub_contacts_compare'   => $this->generate_compare_html( $totalUnsubContacts ),
			'unsub_contacts_num'       => $totalUnsubContacts['number'],
		] );

		$email_content = file_get_contents( GROUNDHOGG_ASSETS_PATH . 'emails/overview.html' );
		$email_content = $replacer->replace( $email_content );

		\Groundhogg_Email_Services::send_wordpress( [
			get_option( 'admin_email' )
		], $subject, $email_content, [
			'Content-Type: text/html',
		] );

//		echo $email_content;
//		die();
	}
}
