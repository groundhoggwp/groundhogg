<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Summary_Widget extends WPGH_Dashboard_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_summary_widget';
        $this->name = _x( 'Groundhogg Summary', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    /**
     * @param $start_time int unix timestamp of when to start the report
     * @param $end_time int unix timestamp of when to end the report
     *
     * @return array
     */
    private function get_time_range_stats( $start_time, $end_time )
    {

    }


    public function widget()
    {

        /* This Month */
        /* ============= */
        /* New contacts*/
        /* Emails Sent */
        /* Emails Opened */
        /* Emails Clicked */
        /* CTR */

        /* Vs last month */
        /* ============= */
        /* New contacts */
        /* Emails Sent */
        /* Emails Opened */
        /* CTR */

        /* Today         */
        /* ============= */
        /* New contacts */
        /* Emails Sent */
        /* Emails Opened */
        /* CTR */

        /* ALL TIME */
        /* ============= */
        /* Contacts */
        /* Emails Sent */
        /* */





        /* Vs the month before */
        /* Emails Sent Today*/

        /* Emails Opened This month */
        /* Emails Opened Last month */
        /* Emails Opened Today */



    }
}