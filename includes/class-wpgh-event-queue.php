<?php
/**
 * Event Queue
 *
 * This adds the cron schdule and cron job to process events every 10 minutes.
 * Also, since most AJAX is async, we can hook in to ALL ajax reuests and process events that way.
 * Assuming there are never thousands of events which need to be run at once since the addition of an event generally means the execution of at least 1 past event, this is a viable solution.
 * The only way this could be totally bogged down is by adding a broadcast for 1000s of people.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Event_Queue
{

    const ACTION = 'wpgh_cron_event';

    /**
     * @var WPGH_Contact the current contact in the event
     */
    public $contact;

    /**
     * @var object|WPGH_Event the current event
     */
    public $cur_event;

    /**
     * All the events queued for processing
     *
     * @var array of events
     */
    public $events;

    /**
     * @var bool whether the queue is being processed or nah
     */
    private $doing_queue = false;

    /**
     * Setup the cron jobs
     * Add new short term schedule
     * setup the action for the cron job
     */
    public function __construct()
    {

        add_filter( 'cron_schedules', array( $this, 'setup_cron_schedules' ) );
        add_action( 'init', array( $this, 'setup_cron_jobs' ) );
        add_action( self::ACTION , array( $this, 'process' ) );
        add_action( 'admin_init', array( $this, 'ajax_process' ) );

        if ( isset( $_REQUEST[ 'process_queue' ] ) && is_admin() ){

            add_action( 'admin_init' , array( $this, 'process' ) );
//            wp_die();

        }

    }

    /**
     * Add the new 10 minute schedule to the list of schedules
     *
     * @param $schedules array of cron schedules
     * @return mixed
     */
    public function setup_cron_schedules( $schedules )
    {
        $schedules[ 'every_10_minutes' ] = array(
          'interval'    => 10 * MINUTE_IN_SECONDS,
          'display'     => __( 'Every 10 Minutes', 'groundhogg' )
        );

        return $schedules;
    }

    /**
     * Add the event cron job
     */
    public function setup_cron_jobs()
    {
        if ( ! wp_next_scheduled( self::ACTION ) ){
            wp_schedule_event( time(), 'every_10_minutes', self::ACTION );
        }
    }

    /**
     * Get a list of events that are up for completion
     */
    public function prepare_events()
    {

        $events = WPGH()->events->get_queued_events();

        foreach ( $events as $event ) {

            $this->events[] = new WPGH_Event( $event->ID );
//            $this->events[] = new WPGH_Event( $event );

        }

//        var_dump( $events );
//        wp_die();

        return $this->events;
    }

    /**
     * Hook into ALL ajax requests to process events
     */
    public function ajax_process()
    {
        if ( wp_doing_ajax() )
            $this->process();

    }


    /**
     * Iterate through the list of events and process them via the EVENTS api
     * For now just uses the standard plugins api
     *
     * @return bool whether the queue was processed or not
     */
    public function process()
    {
        $this->prepare_events();

        if ( empty( $this->events ) ){

            return false;

        }

        do_action( 'wpgh_process_event_queue_before', $this );

        $this->doing_queue = true;

        while ( $this->has_events() ) {

            $this->cur_event = $this->get_next();

            $this->cur_event->run();

        }

        $this->doing_queue = false;

        do_action( 'wpgh_process_event_queue_after', $this );

        return true;
    }

    /**
     * Get the next event in the queue to run.
     */
    public function get_next()
    {

        return array_pop( $this->events );

    }

    /**
     * Is the queue empty of nah?
     *
     * @return bool whether the event array is empty
     */
    public function has_events()
    {

       return ! empty( $this->events );

    }

    /**
     * Add an event to the event queue
     *
     * @param $event array of event attributes
     *
     * @return int the ID of the new event
     */
    public function add( $event )
    {
        $e = wp_parse_args( $event, array(
            'time'          => 0,
            'contact_id'    => 0,
            'step_id'       => 0,
            'funnel_id'     => 0,
        ) );

        return WPGH()->events->add( $e );
    }

    /**
     * Return whether a similar event exists
     *
     * @param $event array of event attributes
     *
     * @return bool whether the event exists
     */
    public function exists( $event )
    {

        // does this make sense?
        /* Yes it does... */
        return WPGH()->events->event_exists( $event );

    }

}