<?php

namespace Groundhogg\Reporting;

use Groundhogg\Reports;
use Groundhogg\Templates\Notifications\Notification_Builder;
use Groundhogg\Utils\DateTimeHelper;
use Groundhogg\Utils\Replacer;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\db;
use function Groundhogg\filter_by_cap;
use function Groundhogg\get_request_var;
use function Groundhogg\has_premium_features;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\is_white_labeled;
use function Groundhogg\notices;
use function Groundhogg\percentage_change;

class Email_Reports extends Notification_Builder {

	public function __construct() {
		add_action( 'init', [ $this, 'test_report' ] );
	}

	public function test_report() {

		if ( ! get_request_var( 'test_email_report' ) ) {
			return;
		}

		$after  = new DateTimeHelper( '7 days ago 00:00:00' );
		$before = new DateTimeHelper( 'yesterday 23:59:59' );

		self::send_overview_report( $after, $before );
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
		return apply_filters( 'groundhogg/email_reports/recipients', $emails, $meta_key );
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

		return \Groundhogg_Email_Services::send_wordpress( $recipients, '[Groundhogg] Yesterday\'s broadcast performance', $email_content, [
			'Content-Type: text/html',
		] );
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
			$subject = '[Groundhogg] Your performance overview';
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

		if ( ! empty( $notices ) ) {
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

		echo $email_content;
		die();

		// Send the report
		return \Groundhogg_Email_Services::send_wordpress( $recipients, $subject, $email_content, [
			'Content-Type: text/html',
		] );

	}
}
