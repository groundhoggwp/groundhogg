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
     * @var bool
     */
    private $processing_queue;

    /**
     * @var float
     */
    private $max_process_time;

    /**
     * @var int
     */
    private $events_completed = 0;

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

            add_action( 'init' , array( $this, 'run_queue_manually' ) );

        }

    }

    /**
     * Add the new 10 minute schedule to the list of schedules
     **/
    public function setup_cron_schedules()
    {
        $this->schedules[ 'every_10_minutes' ] = array(
            'interval'    => 10 * MINUTE_IN_SECONDS,
            'display'     => _x( 'Every 10 Minutes', 'cron_schedule', 'groundhogg' )
        );

        $this->schedules[ 'every_5_minutes' ] = array(
            'interval'    => 5 * MINUTE_IN_SECONDS,
            'display'     => _x( 'Every 5 Minutes', 'cron_schedule', 'groundhogg' )
        );

        $this->schedules[ 'every_1_minutes' ] = array(
            'interval'    => MINUTE_IN_SECONDS,
            'display'     => _x( 'Every 1 Minutes', 'cron_schedule', 'groundhogg' )
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
        WPGH()->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $this->run_queue(), wpgh_get_option( 'gh_queue_last_execution_time' ) ) );
    }

    /**
     * Decide which process to use...
     */
    public function run_queue()
    {
        $start = microtime(true);

        $this->set_php_timeout_limit();
        $this->max_process_time = $start + $this->get_max_execution_time();
        $result =  $this->process();
        $end = microtime(true);
        $process_time = $end - $start;

        $times_executed = intval( wpgh_get_option( 'gh_queue_times_executed', 0 ) );
        $average_execution_time = floatval( wpgh_get_option( 'gh_average_execution_time', 0.0 ) );

        $average = $times_executed * $average_execution_time;
        $average += $process_time;
        $times_executed++;
        $average_execution_time = $average / $times_executed;

        wpgh_update_option( 'gh_queue_last_execution_time', $process_time );
        wpgh_update_option( 'gh_queue_times_executed', $times_executed );
        wpgh_update_option( 'gh_average_execution_time', $average_execution_time );

        return $result;
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
     * Whether the queue is processing.
     *
     * @return bool
     */
    public function is_processing()
    {
       return $this->processing_queue;
    }

    /**
     * The time we have in seconds to process the queue.
     *
     * @return float|int
     */
    public function get_max_execution_time()
    {
        $real_queue_interval = wpgh_get_option( 'gh_real_queue_interval', 'every_5_minutes' );

        switch ( $real_queue_interval ){
            case 'every_10_minutes':
                return 10 * MINUTE_IN_SECONDS;
                break;
            default;
            case 'every_5_minutes':
                return 5 * MINUTE_IN_SECONDS;
                break;
            case 'every_1_minutes':
                return MINUTE_IN_SECONDS;
                break;
        }

    }

    /**
     * Set a PHP time limit so as to not overun the queue as a fail safe.
     * Add 1 second to give the process time to clean up after itself.
     */
    public function set_php_timeout_limit()
    {
        set_time_limit( $this->get_max_execution_time() + 1 );
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

        $this->prepare_events();

        if ( empty( $this->events ) ){
            return 0;
        }

        do_action( 'wpgh_process_event_queue_before', $this );
        do_action( 'groundhogg/queue/run/before', $this );

        $i = 0;

        $max_events = intval( wpgh_get_option( 'gh_max_events', 9999 ) );

        /* double check event setting */
        if ( $max_events === 0 ){
            $max_events = 9999;
        }

        $this->processing_queue = true;

        /* Only run within given time allotment. DO NOT run past the time interval provided */
        while ( $this->has_events() && $i < $max_events && microtime( true ) < $this->max_process_time ) {
            $this->cur_event = $this->get_next();
            if ( $this->cur_event->run() && $this->cur_event->is_funnel_event() ){
                $next_step = $this->cur_event->step->get_next_step();
                if ( $next_step instanceof WPGH_Step && $next_step->is_active() ){
                    $next_step->enqueue( $this->cur_event->contact );
                }
            }
            $i++;
        }

        $this->processing_queue = false;

        do_action( 'wpgh_process_event_queue_after', $this );
        do_action( 'groundhogg/queue/run/after', $this );

        if ( microtime( true ) >= $this->max_process_time ){
            return $i;
        }

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
        return WPGH()->events->add( $event );
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
        return WPGH()->events->event_exists( $event );
    }

}