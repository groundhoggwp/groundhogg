<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-14
 * Time: 12:37 PM
 */

class WPFN_Event_Report
{

    /**
     * @var $funnel int the ID of the funnel
     */
    var $funnel;

    /**
     * @var $step int the ID of the step
     */
    var $step;

    /**
     * @var $start int the seconds to go back
     */
    var $start;

    /**
     * @var $end int where to end searching
     */
    var $end;

    /**
     * @var $unique, whether the report should offer DISTINCT rows
     */
    var $unique;

    function __construct( $funnel, $step=0, $start_time=0, $end_time=0 )
    {
        $this->funnel = intval( $funnel );
        $this->step = intval( $step );
        $this->start = intval( $start_time );
        $this->end = intval( $end_time );
    }

    /**
     * Get the events of a particular status
     *
     * @param $status string the staus of an event
     * @return array the events
     */
    function getEvents( $status )
    {
        global $wpdb;

        $table_name = $wpdb->prefix . WPFN_EVENTS;

        return $wpdb->get_results(
            $wpdb->prepare(
                "
         SELECT * FROM $table_name
		 WHERE funnel_id = %d AND step_id = %d AND %d <= time AND time <= %d AND status = %s
		",
                $this->funnel, $this->step, $this->start, $this->end, $status
            ), ARRAY_A
        );
    }

    /**
     * Get the number of events of a particular status
     *
     * @param $status string the staus of an event
     * @return int the number of events
     */
    function getEventsCount( $status )
    {
        global $wpdb;

        $table_name = $wpdb->prefix . WPFN_EVENTS;

        return $wpdb->get_var(
            $wpdb->prepare(
                "
         SELECT COUNT(*) FROM $table_name
		 WHERE funnel_id = %d AND step_id = %d AND %d <= time AND time <= %d AND status = %s
		",
                $this->funnel, $this->step, $this->start, $this->end, $status
            )
        );
    }

    /**
     * Get the queued events for the given funnel step
     *
     * @return array the events
     */
    function getQueuedEvents()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . WPFN_EVENTS;

        return $wpdb->get_results(
            $wpdb->prepare(
                "
         SELECT * FROM $table_name
		 WHERE funnel_id = %d AND step_id = %d AND status = %s
		",
                $this->funnel, $this->step, 'waiting'
            ), ARRAY_A
        );
    }

    /**
     * Get the number of queued events for the given funnel step
     *
     * @return int the number of events
     */
    function getQueuedEventsCount()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . WPFN_EVENTS;

        return $wpdb->get_var(
            $wpdb->prepare(
                "
         SELECT COUNT(*) FROM $table_name
		 WHERE funnel_id = %d AND step_id = %d AND status = %s
		",
                $this->funnel, $this->step, 'waiting'
            )
        );
    }

    /**
     * Get the completed events for the given funnel step
     *
     * @return array the events
     */
    function getCompletedEvents()
    {
        return $this->getEvents( 'complete' );

    }

    /**
     * Get the number of completed events for the given funnel step
     *
     * @return int the number of events
     */
    function getCompletedEventsCount()
    {
        return $this->getEventsCount( 'complete' );
    }
}