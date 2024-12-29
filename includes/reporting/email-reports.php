<?php

namespace Groundhogg\Reporting;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Reports;
use Groundhogg\Templates\Notifications\Notification_Builder;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Replacer;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\code_it;
use function Groundhogg\db;
use function Groundhogg\filter_by_cap;
use function Groundhogg\get_hostname;
use function Groundhogg\get_request_var;
use function Groundhogg\has_premium_features;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use function Groundhogg\notices;
use function Groundhogg\percentage_change;
use function Groundhogg\white_labeled_name;

class Email_Reports extends Notification_Builder {

	public function __construct() {
//		add_action( 'init', [ $this, 'test_report' ] );
	}

	public function test_report() {

		if ( ! get_request_var( 'test_email_report' ) ) {
			return;
		}

		$after  = new DateTimeHelper( '7 days ago 00:00:00' );
		$before = new DateTimeHelper( 'yesterday 23:59:59' );

		self::send_overview_report( $after, $before );
		self::send_failed_events_report( time() - YEAR_IN_SECONDS );
	}

	/**
	 * Send the report via email
	 *
	 * @param string $report_type
	 * @param array  $recipients
	 * @param string $subject
	 * @param string $content
	 *
	 * @return bool
	 */
	protected static function mail_report( string $report_type, array $recipients, string $subject, string $content ) {

		$recipients = apply_filters( 'groundhogg/email_reports/recipients', $recipients, $report_type );
		$subject    = apply_filters( 'groundhogg/email_reports/subject', $subject, $report_type );
		$content    = apply_filters( 'groundhogg/email_reports/content', $content, $report_type );

		return \Groundhogg_Email_Services::send_wordpress( $recipients, $subject, $content, [
			'Content-Type: text/html',
		] );
	}

	/**
	 * Get the array of recipient email addresses based on whether they are opted into a specific report notification
	 *
	 * @param string $meta_key
	 *
	 * @return string[]
	 */
	public static function get_recipients( string $meta_key ) {

		$args = [
			'meta_key'   => $meta_key,
			'meta_value' => 1,
		];

		// Create a new query
		$user_query = new \WP_User_Query( $args );

		// Get the results
		$users  = filter_by_cap( $user_query->get_results(), 'view_reports' );
		$emails = wp_list_pluck( $users, 'user_email' );

		/**
		 * Filter the recipients of email reports
		 *
		 * @param $emails   string[] an array of emails
		 * @param $meta_key string typically the meta key used to identify which users to send the overview to.
		 */
		return apply_filters( 'groundhogg/email_reports/get_recipients', $emails, $meta_key );
	}

	/**
	 * Generate the table for the broadcasts performance
	 *
	 * @param array    $performance
	 * @param callable $ignoreRow
	 *
	 * @return string
	 */
	private static function generate_performance_table_html( array $performance, callable $ignoreRow ) {

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

		if ( empty( $rows ) ) {
			return '';
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
	 * @return bool
	 */
	public static function send_broadcast_report( DateTimeHelper $after, DateTimeHelper $before ) {

		$recipients = self::get_recipients( 'gh_broadcast_results' );

		if ( empty( $recipients ) ) {
			return false;
		}

		$reports     = new Reports( $after->getTimestamp(), $before->getTimestamp() );
		$performance = $reports->get_data( 'table_all_broadcasts_performance' );

		// No broadcasts were sent
		if ( empty( $performance['data'] ) ) {
			return false;
		}

		$table_html = self::generate_performance_table_html( $performance, '__return_false' );

		$replacer = new Replacer( [
			'broadcast_results_table' => $table_html,
			'full_report_link'        => admin_page_url( 'gh_reporting', [
				'tab'   => 'broadcasts',
				'start' => $after->ymd(),
				'end'   => $before->ymd(),
			] ),
		] );

		$email_content = $replacer->replace( self::get_general_notification_template_html( 'broadcast-results' ) );

		return self::mail_report(
			'broadcast-results',
			$recipients,
			sprintf( '[%s] Yesterday\'s broadcast performance', white_labeled_name() ),
			$email_content,
		);
	}

	/**
	 * Generates html for the up/down arrow and red/green number
	 *
	 * @param $performance
	 *
	 * @return string
	 */
	private static function generate_compare_html( $performance ) {

		$percentage   = percentage_change( absint( $performance['data']['compare'] ), absint( $performance['data']['current'] ) );
		$down_is_good = ( $performance['compare']['arrow']['color'] === 'red' && $performance['compare']['arrow']['direction'] === 'up' ) || ( $performance['compare']['arrow']['color'] === 'green' && $performance['compare']['arrow']['direction'] === 'down' );

		return html()->percentage_change( $percentage, $down_is_good );
	}

	/**
	 * On mondays, send the overview report
	 *
	 * @param DateTimeHelper $after
	 * @param DateTimeHelper $before
	 * @param string         $subject
	 *
	 * @return bool
	 */
	public static function send_overview_report( DateTimeHelper $after, DateTimeHelper $before, string $subject = '' ) {

		$recipients = self::get_recipients( 'gh_weekly_overview' );

		// No recipients, don't bother sending
		if ( empty( $recipients ) ) {
			return false;
		}

		if ( empty( $subject ) ) {
			$subject = sprintf( '[%s] Your performance overview', white_labeled_name() );
		}

		$reports = new Reports( $after->getTimestamp(), $before->getTimestamp() );

		$broadcastPerformance = $reports->get_data( 'table_all_broadcasts_performance' );
		$funnelPerformance    = $reports->get_data( 'table_all_funnels_performance_without_email' );
		$totalNewContacts     = $reports->get_data( 'total_new_contacts' );
		$totalEngagedContacts = $reports->get_data( 'total_engaged_contacts' );
		$totalUnsubContacts   = $reports->get_data( 'total_unsubscribed_contacts' );

		$broadcasts_table = self::generate_performance_table_html( $broadcastPerformance, '__return_false' );

		// If no broadcasts
		if ( empty( $broadcasts_table ) ) {
			$broadcasts_table = self::get_template_part( 'no-broadcasts' );
		}

		$funnels_table = self::generate_performance_table_html( $funnelPerformance, function ( array $row ) {
			return $row['orderby'][2] == 0;
		} );

		// If no funnels with active contacts
		if ( empty( $funnels_table ) ) {
			// No active funnels
			if ( db()->funnels->count( [ 'status' => 'active' ] ) === 0 ) {
				$funnels_table = self::get_template_part( 'no-funnels' );
			} //
			// No active contacts
			else {
				$funnels_table = self::get_template_part( 'no-active-contacts-in-funnels' );
			}
		}

		// Do not fetch notices if the site is white labeled
		$notices = is_white_labeled() ? [] : notices()->fetch_remote_notices();

		if ( ! empty( $notices ) && ! is_white_labeled() ) {
			$notices = array_reduce( $notices, function ( $html, $notice ) {

				$replacer = new Replacer( [
					'cta_url'          => $notice['acf']['cta_url'],
					'cta_text'         => $notice['acf']['cta_text'],
					'title_rendered'   => $notice['title']['rendered'],
					'content_rendered' => $notice['content']['rendered'],
					'#admin#'          => untrailingslashit( admin_url() ),
					'#home#'           => untrailingslashit( home_url() ),
					'#name#'           => get_bloginfo(),
					'#display_name#'   => wp_get_current_user()->display_name || 'Admin',
				] );

				return $html . $replacer->replace( self::get_template_part( 'remote-notice' ) );

			}, '' );
		} else {
			$notices = self::get_template_part( 'up-to-date' );
		}

		$replacer = new Replacer( [
			'compare_text'             => $totalNewContacts['compare']['text'],
			'broadcast_results_table'  => $broadcasts_table,
			'funnel_results_table'     => $funnels_table,
			'site_name'                => get_bloginfo(),
			'site_url'                 => home_url(),
			// New contacts
			'new_contacts_compare'     => self::generate_compare_html( $totalNewContacts ),
			'new_contacts_num'         => $totalNewContacts['number'],
			// Engaged
			'engaged_contacts_compare' => self::generate_compare_html( $totalEngagedContacts ),
			'engaged_contacts_num'     => $totalEngagedContacts['number'],
			// Unsubscribes
			'unsub_contacts_compare'   => self::generate_compare_html( $totalUnsubContacts ),
			'unsub_contacts_num'       => $totalUnsubContacts['number'],
			'logo_url'                 => is_white_labeled() ? '' : GROUNDHOGG_ASSETS_URL . 'images/groundhogg-logo-email-footer.png',
			'time_range'               => $after->human_time_diff( $before ),
			'start_date'               => $after->wpDateFormat(),
			'end_date'                 => $before->wpDateFormat(),
			'full_report_link'         => admin_page_url( 'gh_reporting', [
				'start' => $after->ymd(),
				'end'   => $before->ymd()
			] ),
			'funnels_report_url'       => admin_page_url( 'gh_reporting', [
				'tab'   => 'funnels',
				'start' => $after->ymd(),
				'end'   => $before->ymd()
			] ),
			'broadcasts_report_url'    => admin_page_url( 'gh_reporting', [
				'tab'   => 'broadcasts',
				'start' => $after->ymd(),
				'end'   => $before->ymd()
			] ),
			'send_new_broadcast'       => admin_page_url( 'gh_emails', [ 'action' => 'add' ] ),
			'review_your_funnels'      => admin_page_url( 'gh_funnels', [ 'view' => 'active' ] ),
			'create_new_funnel'        => admin_page_url( 'gh_funnels', [ 'action' => 'add' ] ),
			'create_better_journey'    => '#',
			'what_funnel_to_create'    => '#',
			'remote_notices'           => $notices,
			'get_pro'                  => has_premium_features() ? '' : self::get_template_part( 'funnels-upgrade-to-pro' ),
		] );

		$email_content = self::get_general_notification_template_html( 'overview' );
		$email_content = $replacer->replace( $email_content );

		return self::mail_report(
			'performance-overview',
			$recipients,
			$subject,
			$email_content,
		);
	}

	/**
	 * Send a report of recent failed events since a specific time marker.
	 *
	 * @param $since
	 *
	 * @return bool
	 */
	public static function send_failed_events_report( $since = 0 ) {

		if ( ! $since ) {
			$since = time() - DAY_IN_SECONDS;
		}

		// ignore some errors
		$ignore_errors = array_map( function ( $error ) {
			return sanitize_key( trim( $error, " \n\r\t\v\0," ) );
		}, explode( PHP_EOL, get_option( 'gh_ignore_event_errors', '' ) ) );

		$newErrorQuery = new Table_Query( 'events' );
		$newErrorQuery->where()
		              ->equals( 'status', Event::FAILED )
		              ->greaterThanEqualTo( 'time', $since );

		if ( ! empty( $ignore_errors ) ) {
			$newErrorQuery->where()->notIn( 'error_code', $ignore_errors );
		}

		$new_errors_count = $newErrorQuery->count();

		// no new errors, don't send a notification
		if ( $new_errors_count === 0 ) {
			return false;
		}

		$recipient = get_option( 'gh_event_failure_notification_email' ) ?: get_bloginfo( 'admin_email' );

		if ( ! is_email( $recipient ) ) {
			return false;
		}

		$subject = sprintf( _n( '[%s] %s new failed event on %s', '[%s] %s new failed events on %s', $new_errors_count, 'groundhogg' ), white_labeled_name(), _nf( $new_errors_count ), get_hostname() );

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'error_code', 'error_message', [ 'count(ID)', 'total' ] )
		           ->setLimit( 20 ) // up to 20 different errors
		           ->setGroupby( 'error_code', 'error_message' )
		           ->where( 'status', Event::FAILED )->notEmpty( 'error_code' )
		           ->greaterThanEqualTo( 'time', $since );

		if ( ! empty( $ignore_errors ) ) {
			$eventQuery->where()->notIn( 'error_code', $ignore_errors );
		}

		$errors = $eventQuery->get_results();

		// format the results
		foreach ( $errors as &$error ) {
			$error->total      = html()->a( admin_page_url( 'gh_events', [ 'status' => Event::FAILED, 'error_code' => $error->error_code ] ), _nf( $error->total ) );
			$error->error_code = code_it( $error->error_code );
			$error             = (array) $error; // format to array
		}

		$table = Notification_Builder::generate_list_table_html( [
			__( 'Error Code' ),
			__( 'Error Message' ),
			__( 'Events' ),
		], $errors );

		$replacer = new Replacer( [
			'failed_events_count'   => _nf( $new_errors_count ),
			'errors_table'          => $table,
			'all_failed_events_url' => admin_page_url( 'gh_events', [ 'status' => Event::FAILED ] )
		] );

		$email_content = self::get_general_notification_template_html( 'failed-events', [
			'the_footer'        => self::get_template_part( 'settings-footer' ),
			'misc_settings_url' => admin_page_url( 'gh_settings', [ 'tab' => 'misc' ] )
		] );

		$email_content = $replacer->replace( $email_content );

		return \Groundhogg_Email_Services::send_wordpress( $recipient, $subject, $email_content, [
			'Content-Type: text/html',
		] );

	}
}
