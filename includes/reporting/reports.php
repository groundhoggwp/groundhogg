<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\Form\Form_v2;
use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_country;
use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_Optin_Status;
use Groundhogg\Reporting\New_Reports\Chart_Contacts_By_Region;
use Groundhogg\Reporting\New_Reports\Chart_Donut_Email_Stats;
use Groundhogg\Reporting\New_Reports\Chart_Email_Activity;
use Groundhogg\Reporting\New_Reports\Chart_Funnel_Breakdown;
use Groundhogg\Reporting\New_Reports\Chart_Last_Broadcast;
use Groundhogg\Reporting\New_Reports\Chart_New_Contacts;
use Groundhogg\Reporting\New_Reports\Donut_Chart_Contact_Engagement;
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
use Groundhogg\Reporting\New_Reports\Table_Email_Funnels_Used_In;
use Groundhogg\Reporting\New_Reports\Table_Email_Links_Clicked;
use Groundhogg\Reporting\New_Reports\Table_Email_Stats;
use Groundhogg\Reporting\New_Reports\Table_Form_Activity;
use Groundhogg\Reporting\New_Reports\Table_Funnel_Stats;
use Groundhogg\Reporting\New_Reports\Table_List_Engagement;
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
use Groundhogg\Steps\Actions\Send_Email;
use Groundhogg\Steps\Benchmarks\Form_Filled;

/**
 * Encode a query so that it might be usable in a URL
 *
 * @param $query
 *
 * @return string
 */
function base64_encode_query( $query ) {
	return base64_json_encode( [ $query ] );
}

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

	protected $params = [];

	/**
	 * Reports constructor.
	 *
	 * @param $start int unix timestamps
	 * @param $end   int unix timestamps
	 */
	public function __construct( $start = '', $end = '', $params = [] ) {

		$this->params = $params;

		$this->start = new \DateTime( ! empty( $start ) ? $start : '30 days ago 00:00:00', wp_timezone() );
		$this->end   = new \DateTime( ! empty( $end ) ? $end : 'now', wp_timezone() );
		$this->end->modify( '23:59:59' );

		$time_diff = $this->start->diff( $this->end );

		$this->prev_end   = new \DateTime( $this->start->format( 'Y-m-d H:i:s' ), wp_timezone() );
		$this->prev_start = clone $this->prev_end;
		$this->prev_start->sub( $time_diff );

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
				'id'       => 'funnel_unsubscribes',
				'callback' => [ $this, 'funnel_unsubscribes' ]
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
				'id'       => 'total_bounces',
				'callback' => [ $this, 'total_bounces' ]
			],
			[
				'id'       => 'total_complaints_contacts',
				'callback' => [ $this, 'total_complaints_contacts' ]
			],
			[
				'id'       => 'active_contacts_in_funnel',
				'callback' => [ $this, 'active_contacts_in_funnel' ]
			],
			[
				'id'       => 'total_funnel_conversion_rate',
				'callback' => [ $this, 'total_funnel_conversion_rate' ]
			],
			[
				'id'       => 'link_clicks',
				'callback' => [ $this, 'link_clicks' ]
			],
			[
				'id'       => 'table_funnel_performance',
				'callback' => [ $this, 'table_funnel_performance' ]
			],
			[
				'id'       => 'table_broadcast_performance',
				'callback' => [ $this, 'table_broadcast_performance' ]
			],
			[
				'id'       => 'table_form_activity',
				'callback' => [ $this, 'table_form_activity' ]
			],
			[
				'id'       => 'table_funnel_stats',
				'callback' => [ $this, 'table_funnel_stats' ]
			],
			[
				'id'       => 'funnel_emails_sent',
				'callback' => [ $this, 'funnel_emails_sent' ]
			],
			[
				'id'       => 'funnel_opens',
				'callback' => [ $this, 'funnel_opens' ]
			],
			[
				'id'       => 'funnel_open_rate',
				'callback' => [ $this, 'funnel_open_rate' ]
			],
			[
				'id'       => 'funnel_clicks',
				'callback' => [ $this, 'funnel_clicks' ]
			],
			[
				'id'       => 'funnel_click_rate',
				'callback' => [ $this, 'funnel_click_rate' ]
			],
			[
				'id'       => 'funnel_email_performance',
				'callback' => [ $this, 'funnel_email_performance' ]
			],
			[
				'id'       => 'funnel_forms',
				'callback' => [ $this, 'funnel_forms' ]
			],
			[
				'id'       => 'donut_chart_contact_engagement',
				'callback' => [ $this, 'donut_chart_contact_engagement' ]
			],
			[
				'id'       => 'all_form_submissions',
				'callback' => [ $this, 'all_form_submissions' ]
			],
			[
				'id'       => 'form_engagement_rate',
				'callback' => [ $this, 'form_engagement_rate' ]
			],
			[
				'id'       => 'campaigns_table',
				'callback' => [ $this, 'campaigns_table' ]
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

		$report = call_user_func( $this->reports[ $report_id ]['callback'] );

		if ( is_array( $report ) ) {
			return $report;
		}

		return $report->get_data();
	}

	public function get_data_3_0( $report_id ) {
		if ( ! isset_not_empty( $this->reports, $report_id ) ) {
			return false;
		}

		$report = call_user_func( $this->reports[ $report_id ]['callback'] );

		if ( ! is_object( $report ) || ! method_exists( $report, 'get_data_3_0' ) ) {
			return $report;
		}

		return $report->get_data_3_0();
	}

	/**
	 * Return the total new contacts
	 *
	 * @return array
	 */
	public function total_new_contacts() {

		$query = new Contact_Query();

		return [
			'query' => base64_encode_query( [
				[
					'type'       => 'date_created',
					'date_range' => 'between',
					'after'      => $this->start->format( 'Y-m-d' ),
					'before'     => $this->end->format( 'Y-m-d' )
				]
			] ),
			'curr'  => $query->count( [
				'date_query' => [
					'after'  => $this->start->format( 'Y-m-d H:i:s' ),
					'before' => $this->end->format( 'Y-m-d H:i:s' )
				]
			] ),
			'prev'  => $query->count( [
				'date_query' => [
					'after'  => $this->prev_start->format( 'Y-m-d H:i:s' ),
					'before' => $this->prev_end->format( 'Y-m-d H:i:s' ),
				]
			] ),
		];
	}

	/**
	 * Total amount of new confirmed contacts
	 *
	 * @return array
	 */
	public function all_form_submissions() {

		return [
			'curr' => get_db( 'submissions' )->count( [
				'after'  => $this->start->format( 'Y-m-d H:i:s' ),
				'before' => $this->end->format( 'Y-m-d H:i:s' )
			] ),
			'prev' => get_db( 'submissions' )->count( [
				'after'  => $this->prev_start->format( 'Y-m-d H:i:s' ),
				'before' => $this->prev_end->format( 'Y-m-d H:i:s' ),
			] ),
		];
	}

	/**
	 * Total amount of new confirmed contacts
	 *
	 * @return array
	 */
	public function form_engagement_rate() {

		/**
		 * @param $start \DateTime
		 * @param $end   \DateTime
		 *
		 * @return int
		 */
		$helper = function ( $start, $end ) {

			$submissions = get_db( 'submissions' )->count( [
				'select' => 'DISTINCT(contact_id)',
				'after'  => $start->format( 'Y-m-d H:i:s' ),
				'before' => $end->format( 'Y-m-d H:i:s' ),
			] );

			$activities = get_db( 'activity' )->count( [
				'select' => 'DISTINCT(contact_id)',
				'where'  => [
					[ 'activity_type', '=', 'email_opened' ],
					[
						'contact_id',
						'IN',
						get_db( 'submissions' )->get_sql( [
							'select' => 'DISTINCT(contact_id)',
							'where'  => [
								[ 'date_created', '<=', $end->format( 'Y-m-d H:i:s' ) ],
								[ 'date_created', '>=', $start->format( 'Y-m-d H:i:s' ) ]
							]
						] )
					]
				]
			] );

			return percentage( $submissions, $activities );
		};

		return [
			'curr' => $helper( $this->start, $this->end ),
			'prev' => $helper( $this->prev_start, $this->prev_end ),
		];
	}

	/**
	 * Total amount of new confirmed contacts
	 *
	 * @return array
	 */
	public function total_confirmed_contacts() {
		$query = new Contact_Query();

		$query->set_date_key( 'date_optin_status_changed' );

		return [
			'query' => base64_encode_query( [
				[
					'type'       => 'confirmed',
					'date_range' => 'between',
					'after'      => $this->start->format( 'Y-m-d' ),
					'before'     => $this->end->format( 'Y-m-d' )
				]
			] ),
			'curr'  => $query->count( [
				'optin_status' => Preferences::CONFIRMED,
				'date_query'   => [
					'after'  => $this->start->format( 'Y-m-d H:i:s' ),
					'before' => $this->end->format( 'Y-m-d H:i:s' )
				]
			] ),
			'prev'  => $query->count( [
				'optin_status' => Preferences::CONFIRMED,
				'date_query'   => [
					'after'  => $this->prev_start->format( 'Y-m-d H:i:s' ),
					'before' => $this->prev_end->format( 'Y-m-d H:i:s' ),
				]
			] ),
		];
	}

	/**
	 * Total Number of Active Contacts
	 *
	 * @return array
	 */
	public function total_engaged_contacts() {

		return [
			'query' => base64_encode_query( [
				[
					'type'       => 'was_active',
					'date_range' => 'between',
					'after'      => $this->start->format( 'Y-m-d' ),
					'before'     => $this->end->format( 'Y-m-d' )
				]
			] ),
			'curr'  => get_db( 'activity' )->count( [
				'select'   => 'contact_id',
				'distinct' => true,
				'before'   => $this->end->getTimestamp(),
				'after'    => $this->start->getTimestamp(),
			] ),
			'prev'  => get_db( 'activity' )->count( [
				'select'   => 'contact_id',
				'distinct' => true,
				'before'   => $this->prev_end->getTimestamp(),
				'after'    => $this->prev_start->getTimestamp(),
			] ),
		];

	}

	/**
	 * Total Number of Unsubscribes
	 *
	 * @return array
	 */
	public function total_unsubscribed_contacts() {
		$query = new Contact_Query();

		$query->set_date_key( 'date_optin_status_changed' );

		return [
			'query' => base64_encode_query( [
				[
					'type'       => 'unsubscribed',
					'date_range' => 'between',
					'after'      => $this->start->format( 'Y-m-d' ),
					'before'     => $this->end->format( 'Y-m-d' )
				]
			] ),
			'curr'  => $query->count( [
				'optin_status' => Preferences::UNSUBSCRIBED,
				'date_query'   => [
					'after'  => $this->start->format( 'Y-m-d H:i:s' ),
					'before' => $this->end->format( 'Y-m-d H:i:s' )
				]
			] ),
			'prev'  => $query->count( [
				'optin_status' => Preferences::UNSUBSCRIBED,
				'date_query'   => [
					'after'  => $this->prev_start->format( 'Y-m-d H:i:s' ),
					'before' => $this->prev_end->format( 'Y-m-d H:i:s' ),
				]
			] ),
		];
	}

	/**
	 * @return mixed
	 */
	public function total_bounces() {

		$query = new Contact_Query();

		$query->set_date_key( 'date_optin_status_changed' );

		return [
			'curr' => $query->count( [
				'optin_status' => Preferences::HARD_BOUNCE,
				'date_query'   => [
					'after'  => $this->start->format( 'Y-m-d H:i:s' ),
					'before' => $this->end->format( 'Y-m-d H:i:s' )
				]
			] ),
			'prev' => $query->count( [
				'optin_status' => Preferences::HARD_BOUNCE,
				'date_query'   => [
					'after'  => $this->prev_start->format( 'Y-m-d H:i:s' ),
					'before' => $this->prev_end->format( 'Y-m-d H:i:s' ),
				]
			] ),
		];

	}

	/**
	 * Return the total emails sent
	 *
	 * @return array
	 */
	public function total_emails_sent() {

		$report = new Total_Emails_Sent();

		return [
			'curr' => $report->query( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			'prev' => $report->query( $this->prev_start->getTimestamp(), $this->prev_end->getTimestamp() )
		];
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function email_open_rate() {

		$sent = $this->total_emails_sent();

		$report = new Email_Open_Rate();

		return [
			'curr' => percentage( $sent['curr'], $report->query( $this->start->getTimestamp(), $this->end->getTimestamp() ) ),
			'prev' => percentage( $sent['prev'], $report->query( $this->prev_start->getTimestamp(), $this->prev_end->getTimestamp() ) ),
		];
	}


	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function email_click_rate() {

		$report       = new Email_Click_Rate();
		$other_report = new Email_Open_Rate();

		return [
			'curr' => percentage( $other_report->query( $this->start->getTimestamp(), $this->end->getTimestamp() ), $report->query( $this->start->getTimestamp(), $this->end->getTimestamp() ) ),
			'prev' => percentage( $other_report->query( $this->prev_start->getTimestamp(), $this->prev_end->getTimestamp() ), $report->query( $this->prev_start->getTimestamp(), $this->prev_end->getTimestamp() ) ),
		];
	}

	/**
	 * @return Chart_New_Contacts
	 */
	public function chart_new_contacts() {
		return new Chart_New_Contacts( $this->start, $this->end );
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
	public function chart_contacts_by_optin_status() {
		return new Chart_Contacts_By_Optin_Status( $this->start, $this->end );
	}


	/**
	 * @return mixed
	 */
	public function donut_chart_contact_engagement() {
		return new Donut_Chart_Contact_Engagement( $this->start, $this->end );
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

		$query = new Contact_Query( [
			'select'     => 'ID',
			'date_query' => [
				'after'  => $this->start->format( 'Y-m-d H:i:s' ),
				'before' => $this->end->format( 'Y-m-d H:i:s' )
			]
		] );

		$sql = $query->get_sql();

		$where = [
			[ 'meta_key', '=', 'lead_source' ],
			[ 'meta_value', '!=', '' ],
			[ 'contact_id', 'IN', $sql ],
		];

		$records = get_db( 'contactmeta' )->query( [
			'select'  => 'meta_value as value, COUNT(*) as count',
			'where'   => $where,
			'groupby' => 'value',
			'orderby' => 'count'
		] );

		$parsed = [];

		foreach ( $records as $record ) {

			if ( filter_var( $record->value, FILTER_VALIDATE_URL ) ) {

				$test_lead_source = wp_parse_url( $record->value, PHP_URL_HOST );
				$test_lead_source = str_replace( 'www.', '', $test_lead_source );

				foreach ( yaml_load_socials() as $network => $urls ) {
					if ( in_array( $test_lead_source, $urls ) ) {
						if ( isset( $parsed[ $network ] ) ) {
							$parsed[ $network ] += $record->count;
						} else {
							$parsed[ $network ] = $record->count;
						}
						continue 2;
					}
				}

				foreach ( yaml_load_search_engines() as $engine_name => $atts ) {
					$urls = $atts[0]['urls'];
					if ( $this->in_urls( $test_lead_source, $urls ) ) {
						if ( isset( $parsed[ $engine_name ] ) ) {
							$parsed[ $engine_name ] += $record->count;
						} else {
							$parsed[ $engine_name ] = $record->count;
						}
						continue 2;
					}
				}

				if ( isset( $parsed[ $test_lead_source ] ) ) {
					$parsed[ $test_lead_source ] += $record->count;
				} else {
					$parsed[ $test_lead_source ] = $record->count;
				}

				continue;
			}

			$parsed[ $record->value ] = $record->count;

		}

		$parsed = array_values( array_map_with_keys( $parsed, function ( $v, $k ) {
			return [
				'count' => $v,
				'value' => $k,
				'query' => base64_json_encode( [
					[
						[
							'type'    => 'meta',
							'meta'    => 'lead_source',
							'compare' => 'contains',
							'value'   => $k,
						],
						[
							'type'       => 'date_created',
							'date_range' => 'between',
							'after'      => $this->start->format( 'Y-m-d' ),
							'before'     => $this->end->format( 'Y-m-d' )
						]
					]
				] ),
			];
		} ) );

		usort( $parsed, function ( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return $parsed;

	}

	/**
	 * Special search function for comparing lead sources to potential search engine matches.
	 *
	 * @param $search string the URL in question
	 * @param $urls   array list of string potential matches...
	 *
	 * @return bool
	 */
	private function in_urls( $search, $urls ) {

		foreach ( $urls as $url ) {

			/* Given YAML dataset uses .{} as sequence for match all expression, convert into regex friendly */
			$url     = str_replace( '.{}', '\.{1,3}', $url );
			$url     = str_replace( '{}.', '.{1,}?\.?', $url );
			$pattern = '#' . $url . '#';
			if ( preg_match( $pattern, $search ) ) {
				return true;
			}
		}

		return false;
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

		$query = new Contact_Query( [
			'select'     => 'ID',
			'date_query' => [
				'after'  => $this->start->format( 'Y-m-d H:i:s' ),
				'before' => $this->end->format( 'Y-m-d H:i:s' )
			]
		] );

		$sql = $query->get_sql();

		$where = [
			[ 'meta_key', '=', 'source_page' ],
			[ 'meta_value', '!=', '' ],
			[ 'contact_id', 'IN', $sql ],
		];

		$records = get_db( 'contactmeta' )->query( [
			'select'  => 'meta_value as value, COUNT(*) as count',
			'where'   => $where,
			'groupby' => 'value',
			'orderby' => 'count'
		] );

		$parsed = [];

		foreach ( $records as $record ) {
			if ( $record->value && filter_var( $record->value, FILTER_VALIDATE_URL ) ) {
				$page = wp_parse_url( $record->value, PHP_URL_PATH );

				if ( isset( $parsed[ $page ] ) ) {
					$parsed[ $page ] += $record->count;
				} else {
					$parsed[ $page ] = $record->count;
				}
			}

		}

		$parsed = array_values( array_map_with_keys( $parsed, function ( $v, $k ) {
			return [ 'count' => $v, 'value' => $k ];
		} ) );

		usort( $parsed, function ( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return $parsed;

	}

	/**
	 * @return mixed
	 */
	public function table_contacts_by_countries() {

		$report = new Table_Contacts_By_Country( $this->start, $this->end );

		return $report->get_data();

	}

	/**
	 * @return Table_Top_Performing_Emails
	 */
	public function table_top_performing_emails() {
		return new Table_Top_Performing_Emails( $this->start, $this->end );
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
	public function total_spam_contacts() {

		$report = new Total_Spam_Contacts( $this->start, $this->end );

		return $report->get_data();

	}


	/**
	 * @return mixed
	 */
	public function active_contacts_in_funnel() {

		$funnel = new Funnel( $this->params[1] );
		if ( ! $funnel->exists() ) {
			return 0;
		}

		$func = function ( $start, $end ) use ( $funnel ) {

			$query = new Contact_Query( [
				'report' => [
					'funnel_id' => $funnel->get_id(),
					'after'     => $start->getTimestamp(),
					'before'    => $end->getTimestamp(),
				]
			] );

			return $query->count();
		};

		return [
			'curr'  => $func( $this->start, $this->end ),
			'prev'  => $func( $this->prev_start, $this->prev_end ),
			'query' => base64_json_encode( [
				[
					[
						'type'       => 'funnel_history',
						'date_range' => 'between',
						'after'      => $this->start->format( 'Y-m-d' ),
						'before'     => $this->end->format( 'Y-m-d' ),
						'funnel_id'  => $funnel->get_id(),
						'status'     => Event::COMPLETE
					]
				]
			] ),
		];
	}


	/**
	 * @return mixed
	 */
	public function total_funnel_conversion_rate() {

		$funnel = new Funnel( $this->params[1] );
		if ( ! $funnel->exists() ) {
			return 0;
		}

		return [
			'curr' => $funnel->get_conversion_rate( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			'prev' => $funnel->get_conversion_rate( $this->prev_start->getTimestamp(), $this->prev_end->getTimestamp() ),
		];
	}

	/**
	 * @return mixed
	 */
	public function link_clicks() {

		$query = [
			'select'        => 'COUNT(DISTINCT(contact_id)) as count, referer',
			'activity_type' => Activity::EMAIL_CLICKED,
			'groupby'       => 'referer'
		];

		switch ( $this->params[0] ) {
			case 'funnels':

				$funnel             = new Funnel( $this->params[1] );
				$query['funnel_id'] = $funnel->get_id();
				$query['step_id']   = absint( $this->params[3] );
				$query['after']     = $this->start->getTimestamp();
				$query['before']    = $this->end->getTimestamp();

				break;

			case 'broadcasts':

				$broadcast          = new Broadcast( $this->params[1] );
				$query['funnel_id'] = $broadcast->get_funnel_id();
				$query['step_id']   = $broadcast->get_id();

				break;

		}

		$clicks = get_db( 'activity' )->query( $query );

		$data = [];

		foreach ( $clicks as $click ) {

			if ( ! is_this_site( $click->referer ) ) {
				$data[ $click->referer ] = $click->count;
				continue;
			}

			$path = wp_parse_url( $click->referer, PHP_URL_PATH );

			if ( empty( $path ) ) {
				continue;
			}

			if ( isset( $data[ $path ] ) ) {
				$data[ $path ] += $click->count;
			} else {
				$data[ $path ] = $click->count;
			}

		}

		$parsed = array_values( array_map_with_keys( $data, function ( $v, $k ) {
			return [ 'count' => $v, 'value' => $k ];
		} ) );

		usort( $parsed, function ( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return $parsed;
	}

	public function table_funnel_performance() {

		$query = [
			'status' => 'active'
		];

		if ( $this->params[0] === 'campaigns' ) {
			$campaign    = new Campaign( $this->params[1] );
			$query['ID'] = get_object_ids( $campaign->get_related_objects( 'funnel', false ) );
			if ( empty( $query['ID'] ) ) {
				return [];
			}
		}

		$funnels = get_db( 'funnels' )->query( $query );

		$data = [];

		foreach ( $funnels as $funnel ) {
			$funnel = new Funnel( $funnel );

			$conversion_rate = $funnel->get_conversion_rate( $this->start->getTimestamp(), $this->end->getTimestamp() );

			if ( $conversion_rate === false ) {
				continue;
			}

			$data[] = [
				'id'         => $funnel->get_id(),
				'title'      => $funnel->get_title(),
				'active'     => get_db( 'contacts' )->count( [
					'report' => [
						'funnel_id' => $funnel->get_id(),
						'status'    => Event::COMPLETE,
						'after'     => $this->start->getTimestamp(),
						'before'    => $this->end->getTimestamp(),
					]
				] ),
				'query'      => base64_json_encode( [
					[
						[
							'type'       => 'funnel_history',
							'date_range' => 'between',
							'after'      => $this->start->format( 'Y-m-d' ),
							'before'     => $this->end->format( 'Y-m-d' ),
							'funnel_id'  => $funnel->get_id(),
							'status'     => Event::COMPLETE
						]
					]
				] ),
				'conversion' => $conversion_rate
			];
		}

		usort( $data, function ( $a, $b ) {
			return $b['active'] - $a['active'];
		} );

		return $data;
	}

	/**
	 * @return array
	 */
	public function table_broadcast_performance() {

		$query = [
			'status'      => 'sent',
			'object_type' => 'email',
			'before'      => $this->end->getTimestamp(),
			'after'       => $this->start->getTimestamp(),
			'orderby'     => 'send_time',
			'order'       => 'DESC'
		];

		if ( $this->params[0] === 'campaigns' ) {
			$campaign    = new Campaign( $this->params[1] );
			$query['ID'] = get_object_ids( $campaign->get_related_objects( 'broadcast', false ) );
			if ( empty( $query['ID'] ) ) {
				return [];
			}
		}

		$broadcasts = get_db( 'broadcasts' )->query( $query );

		$data = [];

		foreach ( $broadcasts as $broadcast ) {
			$broadcast = new Broadcast( $broadcast );

			$data[] = [
				'id'     => $broadcast->get_id(),
				'title'  => $broadcast->get_title(),
				'report' => $broadcast->get_report_data()
			];
		}

		return $data;
	}

	public function table_form_activity() {

		$form_ids = array_keys( get_form_list() );

		$forms = array_map_to_class( $form_ids, Form_v2::class );

		return array_map( function ( $form ) {
			return [
				'id'          => $form->get_id(),
				'funnel_id'   => $form->get_funnel_id(),
				'name'        => $form->get_name(),
				'submissions' => $form->get_submissions_count( $this->start->format( 'Y - m - d H:i:s' ), $this->end->format( 'Y - m - d H:i:s' ) ),
				'impressions' => $form->get_impressions_count( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			];
		}, $forms );
	}

	/**
	 * @return mixed
	 */
	public function chart_funnel_breakdown() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		$steps = $funnel->get_steps( [
			'step_group' => Step::BENCHMARK
		] );

		return array_map( function ( $step ) {
			return [
				'name'     => wp_strip_all_tags( $step->get_title() ),
				'complete' => $step->count_complete( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			];
		}, $steps );

	}

	public function table_funnel_stats() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		$steps = $funnel->get_steps();

		return array_map( function ( $step ) {
			return [
				'id'       => $step->get_id(),
				'funnel'   => $step->get_funnel_id(),
				'group'    => $step->get_group(),
				'type'     => $step->get_type(),
				'name'     => $step->get_title(),
				'waiting'  => $step->count_waiting(),
				'complete' => $step->count_complete( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			];
		}, $steps );
	}

	/**
	 * @return array
	 */
	public function funnel_emails_sent() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return [];
		}

		// If doing a specific email step
		if ( isset( $this->params[2] ) && $this->params[2] === 'email' ) {
			$step_ids = wp_parse_id_list( $this->params[3] );
		} else {
			$steps = $funnel->get_steps();

			$steps = array_values( array_filter( $steps, function ( $step ) {
				return $step->type_is( Send_Email::TYPE );
			} ) );

			$step_ids = get_object_ids( $steps );
		}

		$func = function ( $start, $end ) use ( $funnel, $step_ids ) {
			return get_db( 'events' )->count( [
				'funnel_id' => $funnel->get_id(),
				'status'    => Event::COMPLETE,
				'step_id'   => $step_ids,
				'after'     => $start->getTimestamp(),
				'before'    => $end->getTimestamp(),
			] );
		};

		return [
			'curr' => $func( $this->start, $this->end ),
			'prev' => $func( $this->prev_start, $this->prev_end ),
		];
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function funnel_opens() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$opened = function ( $start, $end ) use ( $funnel ) {

			$query = [
				'funnel_id'     => $funnel->get_id(),
				'activity_type' => Activity::EMAIL_OPENED,
				'after'         => $start->getTimestamp(),
				'before'        => $end->getTimestamp(),
			];

			// If doing a specific email step
			if ( isset( $this->params[2] ) && $this->params[2] === 'email' ) {
				$query['step_id'] = absint( $this->params[3] );
			}

			return get_db( 'activity' )->count( $query );
		};

		return [
			'curr' => $opened( $this->start, $this->end ),
			'prev' => $opened( $this->prev_start, $this->prev_end ),
		];
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function funnel_open_rate() {

		$sent = $this->funnel_emails_sent();

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$opened = function ( $start, $end ) use ( $funnel ) {

			$query = [
				'funnel_id'     => $funnel->get_id(),
				'activity_type' => Activity::EMAIL_OPENED,
				'after'         => $start->getTimestamp(),
				'before'        => $end->getTimestamp(),
			];

			// If doing a specific email step
			if ( isset( $this->params[2] ) && $this->params[2] === 'email' ) {
				$query['step_id'] = absint( $this->params[3] );
			}

			return get_db( 'activity' )->count( $query );
		};

		return [
			'curr' => percentage( $sent['curr'], $opened( $this->start, $this->end ) ),
			'prev' => percentage( $sent['prev'], $opened( $this->prev_start, $this->prev_end ) ),
		];
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function funnel_clicks() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$func = function ( $start, $end ) use ( $funnel ) {

			$query = [
				'select'        => 'DISTINCT(contact_id)',
				'funnel_id'     => $funnel->get_id(),
				'activity_type' => Activity::EMAIL_CLICKED,
				'after'         => $start->getTimestamp(),
				'before'        => $end->getTimestamp(),
			];

			// If doing a specific email step
			if ( isset( $this->params[3] ) ) {
				$query['step_id'] = absint( $this->params[3] );
			}

			return get_db( 'activity' )->count( $query );
		};

		return [
			'curr' => $func( $this->start, $this->end ),
			'prev' => $func( $this->prev_start, $this->prev_end ),
		];
	}

	/**
	 * The email open rate
	 *
	 * @return array
	 */
	public function funnel_click_rate() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$func = function ( $type, $start, $end ) use ( $funnel ) {

			$query = [
				'select'        => 'DISTINCT(contact_id)',
				'funnel_id'     => $funnel->get_id(),
				'activity_type' => $type,
				'after'         => $start->getTimestamp(),
				'before'        => $end->getTimestamp(),
			];

			// If doing a specific email step
			if ( isset( $this->params[2] ) && $this->params[2] === 'email' ) {
				$query['step_id'] = absint( $this->params[3] );
			}

			return get_db( 'activity' )->count( $query );
		};

		return [
			'curr' => percentage( $func( Activity::EMAIL_OPENED, $this->start, $this->end ), $func( Activity::EMAIL_CLICKED, $this->start, $this->end ) ),
			'prev' => percentage( $func( Activity::EMAIL_OPENED, $this->prev_start, $this->prev_end ), $func( Activity::EMAIL_CLICKED, $this->prev_start, $this->prev_end ) ),
		];
	}

	/**
	 * Total Number of Unsubscribes
	 *
	 * @return array
	 */
	public function funnel_unsubscribes() {
		$funnel = new Funnel( $this->params[1] );

		if ( ! $funnel->exists() ) {
			return [];
		}

		$func = function ( $start, $end ) use ( $funnel ) {

			$query = new Contact_Query();

			$_query = [
				'activity_type' => Activity::UNSUBSCRIBED,
				'funnel_id'     => $funnel->get_id(),
				'before'        => $this->end->getTimestamp(),
				'after'         => $this->start->getTimestamp(),
			];

			// If doing a specific email step
			if ( isset( $this->params[2] ) && $this->params[2] === 'email' ) {
				$_query['step_id'] = absint( $this->params[3] );
			}

			return $query->count( [
				'activity' => $_query
			] );

		};

		return [
			'curr' => $func( $this->start, $this->end ),
			'prev' => $func( $this->prev_start, $this->prev_end ),
		];
	}

	/**
	 * @return array
	 */
	public function funnel_email_performance() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );
		if ( ! $funnel->exists() ) {
			return [];
		}

		$steps = $funnel->get_steps();

		$steps = array_values( array_filter( $steps, function ( $step ) {
			return $step->type_is( Send_Email::TYPE );
		} ) );

		return array_map( function ( $step ) {
			$email_id       = $step->get_meta( 'email_id' );
			$email          = new Email( $email_id );
			$stats          = $email->get_email_stats( $this->start->getTimestamp(), $this->end->getTimestamp(), [ $step->get_id() ] );
			$stats['title'] = $email->get_title();
			$stats['id']    = $step->get_id();

			return $stats;
		}, $steps );
	}

	public function funnel_forms() {

		$funnel_id = $this->params[1];

		$funnel = new Funnel( $funnel_id );
		if ( ! $funnel->exists() ) {
			return 0;
		}

		$form_ids = $funnel->get_step_ids( [
			'step_type' => 'form_fill'
		] );

		$forms = array_map_to_class( $form_ids, Form_v2::class );

		return array_map( function ( $form ) {
			return [
				'id'          => $form->get_id(),
				'funnel_id'   => $form->get_funnel_id(),
				'name'        => $form->get_name(),
				'submissions' => $form->get_submissions_count( $this->start->format( 'Y - m - d H:i:s' ), $this->end->format( 'Y - m - d H:i:s' ) ),
				'impressions' => $form->get_impressions_count( $this->start->getTimestamp(), $this->end->getTimestamp() ),
			];
		}, $forms );
	}

	public function campaigns_table() {

		$campaigns = get_db( 'campaigns' )->query();
		$campaigns = array_map_to_class( $campaigns, Campaign::class );

		$data = [];

		/**
		 * @var $campaign Campaign
		 */
		foreach ( $campaigns as $campaign ) {

			$data[] = [
				'id'         => $campaign->get_id(),
				'name'       => $campaign->get_name(),
				'funnels'    => count( array_filter( $campaign->get_related_objects( 'funnel', false ), function ( $funnel ) {
					return $funnel->is_active();
				} ) ),
				// todo this can be optimized probably
				'broadcasts' => count( array_filter( $campaign->get_related_objects( 'broadcast', false ), function ( $broadcast ) {
					return $broadcast->is_sent() && $broadcast->sent_within( $this->start->getTimestamp(), $this->end->getTimestamp() );
				} ) ),
			];
		}

		return $data;
	}


}
