<?php

namespace Groundhogg\Admin\Reports;

use Groundhogg\Admin\Reports\Views\Overview;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Contact_Query;
use Groundhogg\Reports;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\enqueue_step_type_assets;
use function Groundhogg\get_array_var;
use function Groundhogg\get_cookie;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\is_white_labeled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\set_cookie;
use function Groundhogg\white_labeled_name;
use Groundhogg\Plugin;

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
		add_filter( 'screen_options_show_screen', '__return_false' );
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
	 * Enqueue any scripts
	 */
	public function scripts() {

		wp_enqueue_style( 'groundhogg-admin-reporting-v3' );
		wp_enqueue_style( 'groundhogg-admin-reporting' );
		wp_enqueue_script( 'groundhogg-admin-custom-reports' );
		wp_enqueue_script( 'groundhogg-admin-reporting-v3' );
		wp_localize_script( 'groundhogg-admin-reporting-v3', 'GroundhoggReporting', array_merge( get_request_query(), [
                'custom_reports' => []
        ] ) );

        enqueue_step_type_assets();
		enqueue_filter_assets();
		wp_enqueue_style( 'groundhogg-admin-reporting' );
//		wp_enqueue_script( 'groundhogg-admin-custom-reports' );
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
				'chart_contacts_by_region',
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
			'funnels'    => [
				'chart_funnel_breakdown',
				'table_top_performing_emails',
				'table_worst_performing_emails',
				'total_funnel_conversion_rate',
				'total_benchmark_conversion_rate',
				'total_abandonment_rate',
				'total_contacts_in_funnel',
				'table_benchmark_conversion_rate',
				'table_form_activity',
				'table_funnel_stats',
			],
			'broadcasts' => [
				'chart_last_broadcast',
				'table_broadcast_stats',
				'table_broadcast_link_clicked',
			],
			'email_step' => [
				'table_email_stats',
				'table_email_links_clicked',
				'total_emails_sent',
				'email_open_rate',
				'email_click_rate',
				'chart_donut_email_stats',
				'table_email_funnels_used_in'
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
				'name' => __( 'Version 3.0', 'groundhogg' ),
				'slug' => 'v3'
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

		include __DIR__ . '/views/v3.php';

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

		$start = strtotime( sanitize_text_field( get_post_var( 'start' ) ) );
		$end   = strtotime( sanitize_text_field( get_post_var( 'end' ) ) ) + ( DAY_IN_SECONDS - 1 );

		$saved = [
			'start_date' => date_i18n( 'Y-m-d', $start ),
			'end_date'   => date_i18n( 'Y-m-d', $end ),
		];

		set_cookie( 'groundhogg_reporting_dates', implode( '|', $saved ) );

		$reports = map_deep( get_post_var( 'reports' ), 'sanitize_key' );

		$reporting = new Reports( $start, $end );

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
