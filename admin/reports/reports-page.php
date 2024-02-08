<?php

namespace Groundhogg\Admin\Reports;

use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Reports;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\get_array_var;
use function Groundhogg\get_cookie;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\is_white_labeled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\set_cookie;
use function Groundhogg\white_labeled_name;

class Reports_Page extends Tabbed_Admin_Page {

	/**
	 * Add Ajax actions...
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_refresh_dashboard_reports', [ $this, 'refresh_report_data' ] );
	}

	/**
	 * Adds additional actions.
	 */
	protected function add_additional_actions() {
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_reporting';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Reporting';
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_reports';
	}

	public function get_priority() {
		return 2;
	}

	/**
	 * Get the item type for this page
	 */
	public function get_item_type() {
	}

	/**
	 * Converts php DateTime format to Javascript Moment format.
	 *
	 * @param string $phpFormat
	 *
	 * @return string
	 */
	public function convertPhpToJsMomentFormat( string $phpFormat ): string {
		$replacements = [
			'A' => 'A',      // for the sake of escaping below
			'a' => 'a',      // for the sake of escaping below
			'B' => '',       // Swatch internet time (.beats), no equivalent
			'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601
			'D' => 'ddd',
			'd' => 'DD',
			'e' => 'zz',     // deprecated since version 1.6.0 of moment.js
			'F' => 'MMMM',
			'G' => 'H',
			'g' => 'h',
			'H' => 'HH',
			'h' => 'hh',
			'I' => '',       // Daylight Saving Time? => moment().isDST();
			'i' => 'mm',
			'j' => 'D',
			'L' => '',       // Leap year? => moment().isLeapYear();
			'l' => 'dddd',
			'M' => 'MMM',
			'm' => 'MM',
			'N' => 'E',
			'n' => 'M',
			'O' => 'ZZ',
			'o' => 'YYYY',
			'P' => 'Z',
			'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822
			'S' => 'o',
			's' => 'ss',
			'T' => 'z',      // deprecated since version 1.6.0 of moment.js
			't' => '',       // days in the month => moment().daysInMonth();
			'U' => 'X',
			'u' => 'SSSSSS', // microseconds
			'v' => 'SSS',    // milliseconds (from PHP 7.0.0)
			'W' => 'W',      // for the sake of escaping below
			'w' => 'e',
			'Y' => 'YYYY',
			'y' => 'YY',
			'Z' => '',       // time zone offset in minutes => moment().zone();
			'z' => 'DDD',
		];

		// Converts escaped characters.
		foreach ( $replacements as $from => $to ) {
			$replacements[ '\\' . $from ] = '[' . $from . ']';
		}

		return strtr( $phpFormat, $replacements );
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {

		switch ( $this->get_current_tab() ) {
			default:
				wp_enqueue_style( 'groundhogg-admin-reporting' );
				wp_enqueue_style( 'groundhogg-admin-loader' );
				wp_enqueue_style( 'baremetrics-calendar' );
				wp_enqueue_script( 'groundhogg-admin-reporting' );

				$dates = sanitize_text_field( get_cookie( 'groundhogg_reporting_dates', '' ) );

				if ( ! $dates ) {
					$dates = [
						'start_date' => date( 'Y-m-d', time() - MONTH_IN_SECONDS ),
						'end_date'   => date( 'Y-m-d', time() ),
					];
				} else {
					$dates = explode( '|', $dates );

					$dates = [
						'start_date' => $dates[0],
						'end_date'   => $dates[1],
					];
				}

				wp_localize_script( 'groundhogg-admin-reporting', 'GroundhoggReporting', [
					'reports'     => $this->get_reports_per_tab(),
					'dates'       => $dates,
					'date_format' => self::convertPhpToJsMomentFormat( get_option( 'date_format' ) ),
					'other'       => [
						'funnel_id'    => get_url_var( 'funnel' ),
						'broadcast_id' => get_url_var( 'broadcast' ),
						'email_id'     => get_url_var( 'email' ),
						'step_id'      => get_url_var( 'step' ),
					]
				] );

				break;

			case 'custom':
				enqueue_filter_assets();
				wp_enqueue_style( 'groundhogg-admin-reporting' );
				wp_enqueue_script( 'groundhogg-admin-custom-reports' );

				break;
		}
	}

	protected function get_funnel_reports() {

		if ( get_url_var( 'funnel' ) ) {
			return [
				'chart_funnel_breakdown',
//				'table_top_performing_emails',
//				'table_worst_performing_emails',
				'total_funnel_conversion_rate',
				'total_funnel_conversions',
				'total_contacts_added_to_funnel',
				'total_contacts_in_funnel',
				'table_form_activity',
				'table_funnel_stats',
				'table_all_funnel_emails_performance',
				'total_emails_sent',
				'email_open_rate',
				'email_click_rate',
			];
		}

		if ( get_url_var( 'step' ) ) {
			return [
				'table_email_stats',
				'table_email_links_clicked',
				'total_emails_sent',
				'email_open_rate',
				'email_click_rate',
				'chart_donut_email_stats',
			];
		}

		return [
			'table_all_funnels_performance'
		];

	}

	protected function get_reports_per_tab() {

		$reports_to_load = [
			'overview'   => [
				'chart_new_contacts',
				'total_new_contacts',
				'total_confirmed_contacts',
				'total_engaged_contacts',
				'total_unsubscribed_contacts',
				'total_emails_sent',
				'email_open_rate',
				'email_click_rate',
				'chart_contacts_by_optin_status',
				'table_top_performing_emails',
				'table_contacts_by_countries',
				'table_contacts_by_lead_source',
				'table_top_converting_funnels',
			],
			'contacts'   => [
				'chart_new_contacts',
				'total_new_contacts',
				'total_confirmed_contacts',
				'total_engaged_contacts',
				'total_unsubscribed_contacts',
				'chart_contacts_by_optin_status',
//				'chart_contacts_by_region',
				'chart_contacts_by_country',
				'table_contacts_by_lead_source',
				'table_contacts_by_search_engines',
				'table_contacts_by_source_page',
				'table_contacts_by_social_media',
				'table_list_engagement'
			],
			'email'      => [
				'chart_email_activity',
				'total_emails_sent',
				'email_open_rate',
				'email_click_rate',
				'total_unsubscribed_contacts',
				'total_spam_contacts',
				'total_bounces_contacts',
				'total_complaints_contacts',
				'chart_last_broadcast',
				'table_top_performing_emails',
				'table_worst_performing_emails',
				'table_top_performing_broadcasts',
				'table_broadcast_stats'
			],
			'funnels'    => $this->get_funnel_reports(),
			'broadcasts' => get_url_var( 'broadcast' ) ? [
				'chart_last_broadcast',
				'table_broadcast_stats',
				'table_broadcast_link_clicked',
			] : [
				'table_all_broadcasts_performance'
			],
			'forms'      => [
				'table_form_activity',
			]
		];

		$reports_to_load = apply_filters( 'groundhogg/admin/reports/reports_to_load', $reports_to_load );

		$custom_tab     = get_array_var( $this->custom_tabs, $this->get_current_tab() );
		$custom_reports = get_array_var( $custom_tab, 'reports', [] );

		$reports = get_array_var( $reports_to_load, $this->get_current_tab(), [] );
		$reports = array_unique( array_merge( $custom_reports, $reports ) );
		$reports = apply_filters( 'groundhogg/admin/reports/tab', $reports );

		return $reports;

	}

	/**
	 * @var array
	 */
	protected $custom_tabs = [];

	/**
	 * Add a custom report tab
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function add_custom_report_tab( $args ) {

		$args = wp_parse_args( $args, [
			'name'     => '',
			'slug'     => '',
			'reports'  => [],
			'callback' => ''
		] );

		if ( ! is_callable( $args['callback'] ) || ! $args['slug'] ) {
			return false;
		}

		$this->custom_tabs[ $args['slug'] ] = $args;

		return true;
	}


	/**
	 * Add any help items
	 */
	public function help() {
	}

	/**
	 * array of [ 'name', 'slug' ]
	 *
	 * @return array[]
	 */
	protected function get_tabs() {

		$tabs = [
			[
				'name' => __( 'Overview', 'groundhogg' ),
				'slug' => 'overview'
			],
			[
				'name' => __( 'Contacts', 'groundhogg' ),
				'slug' => 'contacts'
			],
			[
				'name' => __( 'Email', 'groundhogg' ),
				'slug' => 'email'
			],
			[
				'name' => __( 'Funnels', 'groundhogg' ),
				'slug' => 'funnels'
			],
			[
				'name' => __( 'Broadcasts', 'groundhogg' ),
				'slug' => 'broadcasts'
			],
			[
				'name' => __( 'Forms', 'groundhogg' ),
				'slug' => 'forms'
			],
			[
				'name' => __( 'Custom', 'groundhogg' ),
				'slug' => 'custom'
			],
		];

		// Add the custom registered tabs...
		foreach ( $this->custom_tabs as $custom_tab ) {
			$tabs[] = [
				'name' => $custom_tab['name'],
				'slug' => $custom_tab['slug']
			];
		}

		return apply_filters( 'groundhogg/admin/reporting/tabs', $tabs );
	}

	protected function get_title_actions() {
		return [];
	}

	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}", $this );
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}", $this );

		include __DIR__ . '/views/functions.php';

		?>
		<div class="loader-wrap">
			<div class="gh-loader-overlay" style="display:none;"></div>
			<div class="gh-loader" style="display: none"></div>
		</div>
		<div class="wrap blurred">
			<?php if ( ! is_white_labeled() ): ?>
				<h1 class="wp-heading-inline"><?php groundhogg_logo( 'black' ); ?></h1>
			<?php else: ?>
				<h1 class="wp-heading-inline"><?php printf( "%s Reporting", esc_html( white_labeled_name() ) ); ?></h1>
			<?php endif; ?>
			<?php $this->do_title_actions(); ?>
			<?php $this->range_picker(); ?>
			<?php $this->notices(); ?>
			<hr class="wp-header-end">
			<?php $this->do_page_tabs(); ?>
			<?php

			$method        = sprintf( '%s_%s', $this->get_current_tab(), $this->get_current_action() );
			$backup_method = sprintf( '%s_%s', $this->get_current_tab(), 'view' );

			if ( isset_not_empty( $this->custom_tabs, $this->get_current_tab() ) ) {
				// Callback for custom tabs
				$tab_args = get_array_var( $this->custom_tabs, $this->get_current_tab() );
				call_user_func( $tab_args['callback'] );

			} else if ( method_exists( $this, $method ) ) {
				// Standard method
				call_user_func( [ $this, $method ] );

			} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}" ) ) {
				// Action
				do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );

			} else if ( method_exists( $this, $backup_method ) ) {
				// Backup method
				call_user_func( [ $this, $backup_method ] );

			}

			?>
		</div>
		<?php

	}

	/**
	 * Output the date range picker
	 */
	protected function range_picker() {
		?>
		<div id="groundhogg-datepicker-wrap">
			<div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker"></div>
		</div>
		<!--        <div id="groundhogg-datepicker-wrap">-->
		<!--            <div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker-compare"></div>-->
		<!--        </div>-->
		<?php
	}

	/**
	 * Overview
	 */
	public function overview_view() {
		include __DIR__ . '/views/overview.php';
	}


	/**
	 * Contacts
	 */
	public function contacts_view() {
		include __DIR__ . '/views/contacts.php';
	}

	/**
	 * Contacts
	 */
	public function custom_view() {
		include __DIR__ . '/views/custom.php';
	}


	/**
	 * Email
	 */
	public function email_view() {
		include __DIR__ . '/views/email.php';
	}


	/**
	 * Contacts
	 */
	public function funnels_view() {
		include __DIR__ . '/views/funnels.php';
	}

	/**
	 * Broadcasts
	 */
	public function broadcasts_view() {
		include __DIR__ . '/views/broadcasts.php';
	}

	/**
	 * Forms
	 */
	public function forms_view() {
		include __DIR__ . '/views/forms.php';
	}

	public function email_step_view() {
		include __DIR__ . '/views/email-step.php';
	}


	public function refresh_report_data() {

		$start = new \DateTime( get_post_var( 'start' ) . ' 00:00:00', wp_timezone() );
		$end   = new \DateTime( get_post_var( 'end' ) . ' 23:59:59', wp_timezone() );

		$saved = [
			'start_date' => $start->format( 'Y-m-d' ),
			'end_date'   => $end->format( 'Y-m-d' ),
		];

		set_cookie( 'groundhogg_reporting_dates', implode( '|', $saved ) );

		$reports = map_deep( get_post_var( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start->getTimestamp(), $end->getTimestamp() );

		$results = [];

		foreach ( $reports as $report_id ) {
			$results[ $report_id ] = $reporting->get_data( $report_id );
		}

		wp_send_json_success( [
			'start'   => $start,
			'end'     => $end,
			'reports' => $results
		] );
	}
}
