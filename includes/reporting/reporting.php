<?php
namespace Groundhogg\Reporting;

use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reports\Complete_Funnel_Activity;
use Groundhogg\Reporting\Reports\Contacts_By_Country;
use Groundhogg\Reporting\Reports\Contacts_By_Lead_Source;
use Groundhogg\Reporting\Reports\Contacts_By_Optin_Status;
use Groundhogg\Reporting\Reports\Contacts_By_Region;
use Groundhogg\Reporting\Reports\Contacts_By_Search_Engine;
use Groundhogg\Reporting\Reports\Contacts_By_Social_Media;
use Groundhogg\Reporting\Reports\Contacts_By_Source_Page;
use Groundhogg\Reporting\Reports\Contacts_By_UTM_Campaign;
use Groundhogg\Reporting\Reports\Emails_Clicked;
use Groundhogg\Reporting\Reports\Emails_Opened;
use Groundhogg\Reporting\Reports\Emails_Sent;
use Groundhogg\Reporting\Reports\Form_Impressions;
use Groundhogg\Reporting\Reports\Form_Submissions;
use Groundhogg\Reporting\Reports\Last_Broadcast;
use Groundhogg\Reporting\Reports\New_Contacts;
use Groundhogg\Reporting\Reports\Report;
use Groundhogg\Reporting\Reports\Waiting_Funnel_Activity;
use function Groundhogg\isset_not_empty;
use function Groundhogg\search_and_replace_domain;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-15
 * Time: 10:46 AM
 */

class Reporting
{

    protected $start_time;
    protected $end_time;
    protected $points;
    protected $difference;
    protected $range;

    public function __construct()
    {
        add_action( 'init', [ $this, 'setup_reporting_times' ] );
        add_action( 'init', [ $this, 'setup_reports' ] );
    }

    public function get_start_time()
    {
        return $this->start_time;
    }

    public function get_end_time()
    {
        return $this->end_time;
    }

    public function get_points()
    {
        return $this->points;
    }

    public function get_difference()
    {
        return $this->difference;
    }

    public function get_range()
    {
        return $this->range;
    }

    /**
     * Get the time slots for the given time range...
     *
     * @return array
     */
    public static function get_date_points()
    {
        $points = Plugin::$instance->reporting->get_points();
        $start = Plugin::$instance->reporting->get_start_time();
        $diff = Plugin::$instance->reporting->get_difference();

        $date_points = [];

        for ( $i = 0;$i<$points;$i++){
            $start = Plugin::$instance->utils->date_time->round_to( $start, $diff );
            $date_points[ $start ] = [ $start * 1000, 0, date( 'Y-m-d H:i:s', $start ) ];
            $start+=$diff;
        }

        return $date_points;
    }

    /**
     * Group the given data into their respective time slots...
     *
     * @param $data array[] the data...
     * @param $date_key string the key to which the date is present
     * @param $map_callback string|callable|bool a callback function to map the date
     * @return array
     */
    public static function group_by_time( $data, $date_key='time', $map_callback=false ){

        $times = self::get_date_points();

        foreach ( $data as $datum ){
            $date = get_array_var( $datum, $date_key );
            $date_point = Plugin::$instance->utils->date_time->round_to(
                is_callable( $map_callback ) ? call_user_func( $map_callback, $date ) : $date,
                Plugin::$instance->reporting->get_difference()
            );

            if ( isset_not_empty( $times, $date_point ) ){
                $times[ $date_point ][ 1 ]++;
            }
        }

        return array_values( $times );
    }

    /**
     * Get the reporting ranges...
     *
     * @return mixed|void
     */
    public function get_reporting_ranges()
    {
        return apply_filters( 'groundhogg/reporting/ranges', [
            'today'         => _x( 'Today', 'reporting_range', 'groundhogg' ),
            'yesterday'     => _x( 'Yesterday', 'reporting_range', 'groundhogg' ),
            'this_week'     => _x( 'This Week', 'reporting_range', 'groundhogg' ),
            'last_week'     => _x( 'Last Week', 'reporting_range', 'groundhogg' ),
            'last_30'       => _x( 'Last 30 Days', 'reporting_range', 'groundhogg' ),
            'this_month'    => _x( 'This Month', 'reporting_range', 'groundhogg' ),
            'last_month'    => _x( 'Last Month', 'reporting_range', 'groundhogg' ),
            'this_quarter'  => _x( 'This Quarter', 'reporting_range', 'groundhogg' ),
            'last_quarter'  => _x( 'Last Quarter', 'reporting_range', 'groundhogg' ),
            'this_year'     => _x( 'This Year', 'reporting_range', 'groundhogg' ),
            'last_year'     => _x( 'Last Year', 'reporting_range', 'groundhogg' ),
            'all_time'      => _x( 'All Time', 'reporting_range', 'groundhogg' ),
            'custom'        => _x( 'Custom Range', 'reporting_range', 'groundhogg' ),
        ] );
    }

    /**
     * @return array Get just the IDs of the contacts
     */
    public function get_contact_ids_created_within_time_range()
    {
        $contacts = get_db( 'contacts' )->query( [
            'date_query' => [
                'after' => date( 'Y-m-d H:i:s', $this->get_start_time() ),
                'before' => date( 'Y-m-d H:i:s', $this->get_end_time() ),
            ]
        ] );

        return wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );
    }

    public function setup_reporting_times()
    {
        $this->range = get_request_var( 'range', 'this_week' );

        switch ( $this->range ){
            case 'today';
                $this->start_time   = strtotime( 'today' );
                $this->end_time     = ( $this->start_time + DAY_IN_SECONDS ) - 1;
                $this->points       = 24;
                $this->difference   = HOUR_IN_SECONDS;
                break;
            case 'yesterday';
                $this->start_time   = strtotime( 'yesterday' );
                $this->end_time     = ( $this->start_time + DAY_IN_SECONDS ) - 1;
                $this->points       = 24;
                $this->difference   = HOUR_IN_SECONDS;
                break;
            default:
            case 'this_week';
                $this->start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1);
                $this->end_time     = ( $this->start_time + WEEK_IN_SECONDS ) - 1;
                $this->points       = 7;
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_week';
                $this->start_time   = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1) - WEEK_IN_SECONDS;
                $this->end_time     = ( $this->start_time + WEEK_IN_SECONDS ) - 1;
                $this->points       = 7;
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_30';
                $this->start_time   = Plugin::$instance->utils->date_time->round_to_day( time() - MONTH_IN_SECONDS );
                $this->end_time     = Plugin::$instance->utils->date_time->round_to_day( time() ) - 1;
                $this->points       = ceil( ($this->end_time - $this->start_time  ) / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'this_month';
                $this->start_time   = strtotime( 'first day of ' . date( 'F Y' ) );
                $this->end_time     = strtotime( 'first day of ' . date( 'F Y', time() + MONTH_IN_SECONDS ) ) - 1;
                $this->points       = ceil( ( $this->end_time - $this->start_time ) / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'last_month';
                $this->start_time   = strtotime( 'first day of ' . date( 'F Y' , time() - MONTH_IN_SECONDS ) );
                $this->end_time     = strtotime( 'last day of ' . date( 'F Y', time() - MONTH_IN_SECONDS ) ) + DAY_IN_SECONDS - 1;
                $this->points       = ceil( ( $this->end_time - $this->start_time ) / DAY_IN_SECONDS );
                $this->difference   = DAY_IN_SECONDS;
                break;
            case 'this_quarter';
                $quarter            = Plugin::$instance->utils->date_time->get_dates_of_quarter();
                $this->start_time   = $quarter[ 'start' ];
                $this->end_time     = $quarter[ 'end' ];
                $this->points       = ceil( ( $quarter[ 'end' ] - $quarter[ 'start' ] ) / WEEK_IN_SECONDS );
                $this->difference   = WEEK_IN_SECONDS;
                break;
            case 'last_quarter';
                $quarter            = Plugin::$instance->utils->date_time->get_dates_of_quarter( 'previous' );
                $this->start_time   = $quarter[ 'start' ];
                $this->end_time     = $quarter[ 'end' ];
                $this->points       = ceil( ( $quarter[ 'end' ] - $quarter[ 'start' ] ) / WEEK_IN_SECONDS );
                $this->difference   = WEEK_IN_SECONDS;
                break;
            case 'this_year';
                $this->start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' ) );
                $this->end_time     = $this->start_time + YEAR_IN_SECONDS;
                $this->points       = 12;
                $this->difference   = MONTH_IN_SECONDS;
                break;
            case 'last_year';
                $this->start_time   = mktime(0, 0, 0, 1, 1, date( 'Y' , time() - YEAR_IN_SECONDS ));
                $this->end_time     = $this->start_time + YEAR_IN_SECONDS;
                $this->points       = 12;
                $this->difference   = MONTH_IN_SECONDS;
                break;
            case 'all_time';
                $this->start_time   = mysql2date('U', get_user_option('user_registered', 1));
                $this->end_time     = time();
                $range = $this->end_time - $this->start_time;
                $this->points       = ceil( $range  / $this->get_time_diff( $range ) );
                $this->difference   = $this->get_time_diff( $range );
                break;
            case 'custom';
                $this->start_time   = Plugin::$instance->utils->date_time->round_to_day( strtotime( get_request_var( 'custom_date_range_start' ) ) );
                $this->end_time     = Plugin::$instance->utils->date_time->round_to_day( strtotime( get_request_var( 'custom_date_range_end' ) ) ) + DAY_IN_SECONDS - 1;
                $range = $this->end_time - $this->start_time;
                $this->points       = ceil( $range  / $this->get_time_diff( $range ) );
                $this->difference   = $this->get_time_diff( $range );
                break;
        }
    }

    /**
     * Get the difference in time between points given a time range...
     *
     * @param $range
     * @return int
     */
    private function get_time_diff( $range )
    {

        if ( $range <= DAY_IN_SECONDS ){
            return HOUR_IN_SECONDS;
        } else if ( $range <= WEEK_IN_SECONDS ) {
            return DAY_IN_SECONDS;
        } else if ( $range <= MONTH_IN_SECONDS ){
            return WEEK_IN_SECONDS;
        } else if ( $range <= 2 * YEAR_IN_SECONDS ){
            return MONTH_IN_SECONDS;
        }

        return YEAR_IN_SECONDS;

    }

    /**
     * @var Report[]
     */
    protected $reports = [];

    /**
     * Setup the default reports.
     */
    public function setup_reports()
    {
        $reports = [
            new Contacts_By_Optin_Status(),
            new Contacts_By_Country(),
            new Contacts_By_Region(),
            new Contacts_By_Lead_Source(),
            new Contacts_By_Search_Engine(),
            new Contacts_By_Social_Media(),
            new Contacts_By_Lead_Source(),
            new Contacts_By_Source_Page(),
            new Contacts_By_UTM_Campaign(),
            new Last_Broadcast(),
            new New_Contacts(),
            new Emails_Sent(),
            new Emails_Opened(),
            new Emails_Clicked(),
            new Form_Impressions(),
            new Form_Submissions(),
            new Waiting_Funnel_Activity(),
            new Complete_Funnel_Activity(),
        ];

        $reports = apply_filters( 'groundhogg/reporting/reports', $reports );

        foreach ( $reports as $report ){
            $this->add_report( $report );
        }
    }

    /**
     * Get a report.
     *
     * @param $id
     * @return Report|false
     */
    public function get_report( $id ){
        return get_array_var( $this->reports, $id );
    }

    /**
     * @param $report Report
     */
    public function add_report( $report )
    {
        $this->reports[ $report->get_id() ] = $report;
    }
}