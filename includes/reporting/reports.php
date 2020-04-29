<?php

namespace Groundhogg;

use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_country;
use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_Optin_Status;
use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_Region;
use Groundhogg\Reporting\New_Reports\Chart_Donut_Email_Stats;
use Groundhogg\Reporting\New_Reports\Chart_Email_Activity;
use Groundhogg\Reporting\New_Reports\Chart_Funnel_Breakdown;
use Groundhogg\Reporting\New_Reports\Chart_Last_Broadcast;
use Groundhogg\Reporting\New_Reports\Chart_New_Contacts;
use Groundhogg\Reporting\New_Reports\Email_Click_Rate;
use Groundhogg\Reporting\New_Reports\Email_Open_Rate;
use Groundhogg\Reporting\New_Reports\Table_Benchmark_Conversion_Rate;
use Groundhogg\Reporting\New_Reports\Table_Broadcast_Link_Clicked;
use Groundhogg\Reporting\New_Reports\Table_Broadcast_Stats;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Country;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Lead_Source;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Search_Engines;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Social_Media;
use Groundhogg\Reporting\New_Reports\Table_Contacts_By_Source_Pages;
use Groundhogg\Reporting\New_Reports\Table_Email_Links_Clicked;
use Groundhogg\Reporting\New_Reports\Table_Email_Stats;
use Groundhogg\Reporting\New_Reports\Table_Form_Activity;
use Groundhogg\Reporting\New_Reports\Table_Funnel_Stats;
use Groundhogg\Reporting\New_Reports\Table_Top_Converting_Funnels;
use Groundhogg\Reporting\New_Reports\Table_Top_Performing_Broadcasts;
use Groundhogg\Reporting\New_Reports\Table_Top_Performing_Emails;
use Groundhogg\Reporting\New_Reports\Table_Worst_Performing_Emails;
use Groundhogg\Reporting\New_Reports\Total_Abandonment_Rate;
use Groundhogg\Reporting\New_Reports\Total_Active_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Benchmark_Conversion_Rate;
use Groundhogg\Reporting\New_Reports\Total_Bounces_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Complaints_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Confirmed_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Contacts_In_Funnel;
use Groundhogg\Reporting\New_Reports\Total_Emails_Sent;
use Groundhogg\Reporting\New_Reports\Total_Funnel_Conversion_Rate;
use Groundhogg\Reporting\New_Reports\Total_New_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Spam_Contacts;
use Groundhogg\Reporting\New_Reports\Total_Unsubscribed_Contacts;

class Reports {

	/**
	 * @var int
	 */
	public $start;

	/**
	 * @var int
	 */
	public $end;

	/**
	 * Report data
	 *
	 * @var array[]
	 */
	protected $reports = [];

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

		$this->setup_default_reports();

	}

	/**
	 * Setup the default reports
	 */
	public function setup_default_reports() {
		$default_reports = [
			[
				'id'       => 'total_new_contacts',
				'callback' => [ $this, 'total_new_contacts' ]
			],
			[
				'id'       => 'total_confirmed_contacts',
				'callback' => [ $this, 'total_confirmed_contacts' ]
			],
			[
				'id'       => 'total_engaged_contacts',
				'callback' => [ $this, 'total_engaged_contacts' ]
			],
			[
				'id'       => 'total_unsubscribed_contacts',
				'callback' => [ $this, 'total_unsubscribed_contacts' ]
			],
			[
				'id'       => 'total_emails_sent',
				'callback' => [ $this, 'total_emails_sent' ]
			],
			[
				'id'       => 'email_open_rate',
				'callback' => [ $this, 'email_open_rate' ]
			],
			[
				'id'       => 'email_click_rate',
				'callback' => [ $this, 'email_click_rate' ]
			],
			[
				'id'       => 'chart_new_contacts',
				'callback' => [ $this, 'chart_new_contacts' ]
			],
			[
				'id'       => 'chart_email_activity',
				'callback' => [ $this, 'chart_email_activity' ]
			],
			[
				'id'       => 'chart_funnel_breakdown',
				'callback' => [ $this, 'chart_funnel_breakdown' ]
			],
			[
				'id'       => 'chart_contacts_by_optin_status',
				'callback' => [ $this, 'chart_contacts_by_optin_status' ]
			],
			[
				'id'       => 'chart_contacts_by_region',
				'callback' => [ $this, 'chart_contacts_by_region' ]
			],
			[
				'id'       => 'chart_contacts_by_country',
				'callback' => [ $this, 'chart_contacts_by_country' ]
			],
			[
				'id'       => 'chart_last_broadcast',
				'callback' => [ $this, 'chart_last_broadcast' ]
			],
			[
				'id'       => 'table_contacts_by_lead_source',
				'callback' => [ $this, 'table_contacts_by_lead_source' ]
			],
			[
				'id'       => 'table_contacts_by_search_engines',
				'callback' => [ $this, 'table_contacts_by_search_engines' ]
			],
			[
				'id'       => 'table_contacts_by_social_media',
				'callback' => [ $this, 'table_contacts_by_social_media' ]
			],
			[
				'id'       => 'table_contacts_by_source_page',
				'callback' => [ $this, 'table_contacts_by_source_page' ]
			],
			[
				'id'       => 'table_contacts_by_countries',
				'callback' => [ $this, 'table_contacts_by_countries' ]
			],
			[
				'id'       => 'table_top_performing_emails',
				'callback' => [ $this, 'table_top_performing_emails' ]
			],
			[
				'id'       => 'table_worst_performing_emails',
				'callback' => [ $this, 'table_worst_performing_emails' ]
			],
			[
				'id'       => 'table_top_performing_broadcasts',
				'callback' => [ $this, 'table_top_performing_broadcasts' ]
			],
			[
				'id'       => 'total_spam_contacts',
				'callback' => [ $this, 'total_spam_contacts' ]
			],
			[
				'id'       => 'total_bounces_contacts',
				'callback' => [ $this, 'total_bounces_contacts' ]
			],
			[
				'id'       => 'total_complaints_contacts',
				'callback' => [ $this, 'total_complaints_contacts' ]
			],
			[
				'id'       => 'total_contacts_in_funnel',
				'callback' => [ $this, 'total_contacts_in_funnel' ]
			],
			[
				'id'       => 'total_funnel_conversion_rate',
				'callback' => [ $this, 'total_funnel_conversion_rate' ]
			],
			[
				'id'       => 'total_benchmark_conversion_rate',
				'callback' => [ $this, 'total_benchmark_conversion_rate' ]
			],
			[
				'id'       => 'total_abandonment_rate',
				'callback' => [ $this, 'total_abandonment_rate' ]
			],
			[
				'id'       => 'table_broadcast_stats',
				'callback' => [ $this, 'table_broadcast_stats' ]
			],
			[
				'id'       => 'table_broadcast_link_clicked',
				'callback' => [ $this, 'table_broadcast_link_clicked' ]
			],
			[
				'id'       => 'table_benchmark_conversion_rate',
				'callback' => [ $this, 'table_benchmark_conversion_rate' ]
			],
			[
				'id'       => 'table_top_converting_funnels',
				'callback' => [ $this, 'table_top_converting_funnels' ]
			],
			[
				'id'       => 'table_form_activity',
				'callback' => [ $this, 'table_form_activity' ]
			],
			[
				'id'       => 'table_email_stats',
				'callback' => [ $this, 'table_email_stats' ]
			],
			[
				'id'       => 'table_email_links_clicked',
				'callback' => [ $this, 'table_email_links_clicked' ]
			],
			[
				'id'       => 'chart_donut_email_stats',
				'callback' => [ $this, 'chart_donut_email_stats' ]
			],
			[
				'id'       => 'table_funnel_stats',
				'callback' => [ $this, 'table_funnel_stats' ]
			]
		];

		foreach ( $default_reports as $report ) {
			$this->add( $report['id'], $report['callback'] );
		}

		do_action( 'groundhogg/reports/setup_default_reports/after', $this );
	}

	/**
	 * Add a new report.
	 *
	 * @param string $id
	 * @param string $callback
	 *
	 * @return bool
	 */
	public function add( $id = '', $callback = '' ) {
		if ( ! $id || ! $callback ) {
			return false;
		}

		if ( is_callable( $callback ) ) {
			$this->reports[ $id ] = array(
				'id'       => $id,
				'callback' => $callback,
			);

			return true;
		}

		return false;
	}

	/**
	 * Get the a report result
	 *
	 * @param $report_id
	 *
	 * @return mixed
	 */
	public function get_data( $report_id ) {

		if ( ! isset_not_empty( $this->reports, $report_id ) ) {
			return false;
		}

		$results = call_user_func( $this->reports[ $report_id ]['callback'] );

		return $results;
	}

	/**
	 * Return the total new contacts
	 *
	 * @return array
	 */
	public function total_new_contacts() {
		$report = new Total_New_Contacts( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * Total amount of new confirmed contacts
	 *
	 * @return array
	 */
	public function total_confirmed_contacts() {
		$report = new Total_Confirmed_Contacts( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * Total Number of Active Contacts
	 *
	 * @return array
	 */
	public function total_engaged_contacts() {
		$report = new Total_Active_Contacts( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * Total Number of Unsubscribes
	 *
	 * @return array
	 */
	public function total_unsubscribed_contacts() {
		$report = new Total_Unsubscribed_Contacts( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * Return the total emails sent
	 *
	 * @return array
	 */
	public function total_emails_sent() {
		$report = new Total_Emails_Sent( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function email_open_rate() {
		$report = new Email_Open_Rate( $this->start, $this->end );

		return $report->get_data();
	}


	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function email_click_rate() {
		$report = new Email_Click_Rate( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * @return mixed
	 */
	public function chart_new_contacts() {
		$report = new Chart_New_Contacts( $this->start, $this->end );

		return $report->get_data();
	}


	/**
	 * @return mixed
	 */
	public function chart_email_activity() {
		$report = new Chart_Email_Activity( $this->start, $this->end );

		return $report->get_data();
	}


	/**
	 * @return mixed
	 */
	public function chart_funnel_breakdown() {
		$report = new Chart_Funnel_Breakdown( $this->start, $this->end );

		return $report->get_data();
	}


	/**
	 * @return mixed
	 */
	public function chart_contacts_by_optin_status() {

		$report = new Chart_Contacts_By_Optin_Status( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function chart_contacts_by_region() {

		$report = new Chart_Contacts_By_Region( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function chart_contacts_by_country() {

		$report = new Chart_Contacts_By_Country( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function chart_last_broadcast() {

		$report = new Chart_Last_Broadcast( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_lead_source() {

		$report = new Table_Contacts_By_Lead_Source( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_search_engines() {

		$report = new Table_Contacts_By_Search_Engines( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_social_media() {

		$report = new Table_Contacts_By_Social_Media( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_source_page() {

		$report = new Table_Contacts_By_Source_Pages( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_countries() {

		$report = new Table_Contacts_By_Country( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_top_performing_emails() {

		$report = new Table_Top_Performing_Emails( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_worst_performing_emails() {

		$report = new Table_Worst_Performing_Emails( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_top_performing_broadcasts() {

		$report = new Table_Top_Performing_Broadcasts( $this->start, $this->end );

		return $report->get_data();

	}


	/**
	 * @return mixed
	 */
	public function total_complaints_contacts() {

		$report = new Total_Complaints_Contacts( $this->start, $this->end );

		return $report->get_data();

	}


	/**
	 * @return mixed
	 */
	public function total_bounces_contacts() {

		$report = new Total_Bounces_Contacts( $this->start, $this->end );

		return $report->get_data();

	}


	/**
	 * @return mixed
	 */
	public function total_spam_contacts() {

		$report = new Total_Spam_Contacts( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function total_funnel_conversion_rate() {

		$report = new Total_Funnel_Conversion_Rate( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function total_contacts_in_funnel() {

		$report = new Total_Contacts_In_Funnel( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function total_benchmark_conversion_rate() {

		$report = new Total_Benchmark_Conversion_Rate( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function total_abandonment_rate() {

		$report = new Total_Abandonment_Rate( $this->start, $this->end );

		return $report->get_data();

	}


	/**
	 * @return mixed
	 */
	public function table_broadcast_stats() {

		$report = new Table_Broadcast_Stats( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return mixed
	 */
	public function table_broadcast_link_clicked() {
		$report = new Table_Broadcast_Link_Clicked( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * @return mixed
	 */
	public function table_benchmark_conversion_rate() {
		$report = new Table_Benchmark_Conversion_Rate( $this->start, $this->end );

		return $report->get_data();
	}

	/**
	 * @return mixed
	 */
	public function table_top_converting_funnels() {
		$report = new Table_Top_Converting_Funnels( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_form_activity(){
		$report = new Table_Form_Activity( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_email_stats(){
		$report = new Table_Email_Stats( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_email_links_clicked(){
		$report = new Table_Email_Links_Clicked( $this->start, $this->end );

		return $report->get_data();
	}

	public function chart_donut_email_stats(){
		$report = new Chart_Donut_Email_Stats( $this->start, $this->end );

		return $report->get_data();
	}

	public function table_funnel_stats(){
		$report = new Table_Funnel_Stats( $this->start, $this->end );

		return $report->get_data();
	}


}
