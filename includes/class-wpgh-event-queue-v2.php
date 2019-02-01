<?php
/**
 * Event Queue
 *
 * This adds the cron schedule and cron job to process events every 5 minutes.
 * Runs recursively until all consecutive events are completed.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.0.18
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Event_Queue_v2
{

    const ACTION = 'wpgh_process_queue';

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
     * @var int
     */
    public $time_till_process;

    /**
     * @var array()
     */
    public $schedules = array();

    /**
     * Setup the cron jobs
     * Add new short term schedule
     * setup the action for the cron job
     */
    public function __construct()
    {
        add_action( 'plugins_loaded', array( $this, 'setup_cron_schedules' ) );
        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
        add_action( 'init', array( $this, 'setup_cron_jobs' ) );
        add_action( self::ACTION , array( $this, 'run_queue' ) );

        if ( isset( $_REQUEST[ 'process_queue' ] ) && is_admin() ){

            add_action( 'admin_init' , array( $this, 'run_queue_manually' ) );

        }

    }

    /**
     * Add the new 10 minute schedule to the list of schedules
     **/
    public function setup_cron_schedules()
    {
        $this->schedules[ 'every_10_minutes' ] = array(
            'interval'    => 10 * MINUTE_IN_SECONDS,
            'display'     => __( 'Every 10 Minutes', 'groundhogg' )
        );

        $this->schedules[ 'every_5_minutes' ] = array(
            'interval'    => 5 * MINUTE_IN_SECONDS,
            'display'     => __( 'Every 5 Minutes', 'groundhogg' )
        );

        $this->schedules[ 'every_1_minutes' ] = array(
            'interval'    => MINUTE_IN_SECONDS,
            'display'     => __( 'Every 1 Minutes', 'groundhogg' )
        );
    }

    /**
     * Add the scheules
     *
     * @param $schedules
     * @return array
     */
    public function add_cron_schedules( $schedules )
    {
        $schedules = array_merge( $schedules, $this->schedules );
        return $schedules;
    }

    /**
     * Add the event cron job
     *
     * @since 1.0.20.1 Added notice to check if there is something wrong with the cron system.
     */
    public function setup_cron_jobs()
    {

        $settings_queue_interval = wpgh_get_option( 'gh_queue_interval', 'every_5_minutes' );
        $real_queue_interval = wpgh_get_option( 'gh_real_queue_interval' );

        if ( ! wp_next_scheduled( self::ACTION ) || $settings_queue_interval !== $real_queue_interval ){
            wp_clear_scheduled_hook( 'wpgh_cron_event' );
            update_option( 'gh_real_queue_interval', $settings_queue_interval );
            wp_schedule_event( time(), apply_filters( 'wpgh_queue_interval', $settings_queue_interval ), self::ACTION );
        }

        $this->time_till_process = wp_next_scheduled( 'wpgh_process_queue' ) - time();

        $expected_max_time = $this->schedules[ $settings_queue_interval ][ 'interval' ];
        $expected_max_time_display = $this->schedules[ $settings_queue_interval ][ 'display' ];

        if ( ( $this->time_till_process > $expected_max_time + 1 ) && ( ! defined( 'DISABLE_WP_CRON' ) ||  DISABLE_WP_CRON === false ) ){

            $actual_time = human_time_diff( time(), $this->time_till_process );

            WPGH()->notices->add(
                'CRON_OVERRIDE',
                sprintf(
                    __(
                        'Event Queue Error: It appears that something is overriding the default timing of the event queue. The queue is expected to run %s but will not run for at least %s.',
                        'groundhogg'
                    ),
                    $expected_max_time_display,
                    $actual_time ),
                'warning'
            );
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
        }

        return $this->events;
    }

    /**
     * Run the queue Manually and provide a notice.
     */
    public function run_queue_manually(){
        WPGH()->notices->add( 'queue-complete', sprintf( "%d events have been completed.", $this->run_queue() ) );
    }

    /**
     * Decide which process to use...
     */
    public function run_queue()
    {
        return $this->process();
    }

    /**
     * Process the queue within a semaphore lock...
     */
    private function semaphore_process()
    {
        $key = ftok(__FILE__, 'G' );

        $semaphore = sem_get($key, 1);

        $result = 0;

        if ( $semaphore && sem_acquire( $semaphore, 1) !== false ) {

            $result = $this->process();

            sem_release($semaphore) ;

        }

        return $result;

    }

    /**
     * Recursive, Iterate through the list of events and process them via the EVENTS api
     * completes successive events quite since WP-Cron only happens once every 5 or 10 minutes depending on
     * the amount of traffic.
     *
     * @return int the number of events process, 0 if no events.
     */
    private function process()
    {

        /* Get 'er done */
        set_time_limit(0 );

        $this->prepare_events();

        if ( empty( $this->events ) ){

            return 0;

        }

        do_action( 'wpgh_process_event_queue_before', $this );

        $i = 0;

        $max_events = intval( wpgh_get_option( 'gh_max_events', 9999 ) );

        /* double check event setting */
        if ( $max_events === 0 ){
            $max_events = 9999;
        }

        /* Check to see if the current queue is still the most recent queue. If it's not Then finish up. */
        while ( $this->has_events() && $i < $max_events ) {

            $this->cur_event = $this->get_next();

            if ( $this->cur_event->run() && ! $this->cur_event->is_broadcast_event() ){

                $next_step = $this->cur_event->step->get_next_step();

                if ( $next_step instanceof WPGH_Step && $next_step->is_active() ){

                    $next_step->enqueue( $this->cur_event->contact );

                }

            }

            $i++;

        }

        do_action( 'wpgh_process_event_queue_after', $this );

        return $i + $this->process();
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