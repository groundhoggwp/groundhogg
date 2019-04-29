<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

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

class Event_Queue extends Supports_Errors
{

    const ACTION = 'groundhogg_process_queue';

    /**
     * @var Contact the current contact in the event
     */
    protected $current_contact;

    /**
     * @var object|Event the current event
     */
    protected $current_event;

    /**
     * All the events queued for processing
     *
     * @var Event[] of events
     */
    protected $events;

    /**
     * @var int
     */
    public $time_till_process;

    /**
     * @var array()
     */
    protected $schedules = array();

    /**
     * @var bool
     */
    private static $is_processing;

    /**
     * @var float
     */
    protected $max_process_time;

    /**
     * @var int
     */
    protected $events_completed = 0;

    /**
     * Setup the cron jobs
     * Add new short term schedule
     * setup the action for the cron job
     */
    public function __construct()
    {
        $this->setup_cron_schedules();

        add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
        add_action( 'init', array( $this, 'setup_cron_jobs' ) );
        add_action( self::ACTION , array( $this, 'run_queue' ) );

        if ( isset( $_REQUEST[ 'process_queue' ] ) && is_admin() ){
            add_action( 'init' , array( $this, 'run_queue_manually' ) );
        }
    }

    public function get_queue_execution_time()
    {

    }

    public function get_last_execution_time()
    {
        return Plugin::$instance->settings->get_option( 'queue_last_execution_time' );
    }

    public function get_total_executions()
    {

    }


    /**
     * Run the queue Manually and provide a notice.
     */
    public function run_queue_manually(){
        Plugin::$instance->notices->add( 'queue-complete', sprintf( "%d events have been completed in %s seconds.", $this->run_queue(), $this->get_last_execution_time() ) );
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
     * Add the schedules
     *
     * @param $schedules
     * @return array|false
     */
    public function add_cron_schedules( $schedules = [] )
    {
        if ( ! is_array( $schedules ) ){
            return $schedules;
        }

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
        $settings_queue_interval    = Plugin::$instance->settings->get_option( 'queue_interval', 'every_5_minutes' );
        $real_queue_interval        = Plugin::$instance->settings->get_option( 'real_queue_interval' );

        if ( ! wp_next_scheduled( self::ACTION ) || $settings_queue_interval !== $real_queue_interval ){
            wp_clear_scheduled_hook( self::ACTION );
            Plugin::$instance->settings->update_option( 'real_queue_interval', $settings_queue_interval );
            wp_schedule_event( time(), apply_filters( 'groundhogg/event_queue/queue_interval', $settings_queue_interval ), self::ACTION );
        }

        $this->time_till_process = wp_next_scheduled( self::ACTION ) - time();
    }

    /**
     * Get a list of events that are up for completion
     */
    public function prepare_events()
    {
        $events = Plugin::$instance->dbs->get_db( 'events' )->get_queued_event_ids();

        foreach ( $events as $event_id ) {
            $this->events[] = Plugin::$instance->utils->get_event( $event_id );
        }

        return $this->events;
    }

    /**
     * Decide which process to use...
     */
    public function run_queue()
    {
        $start = microtime(true );

        $settings = Plugin::$instance->settings;

        // Give ourselves an extra 3 seconds to clean up if we need to.
        $this->max_process_time = $start + $this->get_max_execution_time() - 3;

        $result =  $this->process();
        $end = microtime(true );
        $process_time = $end - $start;

        $times_executed = intval( $settings->get_option( 'queue_times_executed', 0 ) );
        $average_execution_time = floatval( $settings->get_option( 'average_execution_time', 0.0 ) );

        $average = $times_executed * $average_execution_time;
        $average += $process_time;
        $times_executed++;
        $average_execution_time = $average / $times_executed;

        $settings->update_option( 'queue_last_execution_time', $process_time );
        $settings->update_option( 'queue_times_executed', $times_executed );
        $settings->update_option( 'average_execution_time', $average_execution_time );

        return $result;
    }

    /**
     * Set the processing state
     *
     * @param bool $bool
     */
    protected static function set_is_processing( $bool = true )
    {
       self::$is_processing = (bool) $bool;
    }

    /**
     * Whether the queue is processing.
     *
     * @return bool
     */
    public static function is_processing()
    {
        return (bool) static::$is_processing;
    }

    /**
     * The time we have in seconds to process the queue.
     *
     * @return float|int
     */
    public function get_max_execution_time()
    {
        $real_queue_interval = Plugin::$instance->settings->get_option( 'real_queue_interval', 'every_5_minutes' );

        switch ( $real_queue_interval ){
            case 'every_10_minutes':
                $max = 10 * MINUTE_IN_SECONDS;
                break;
            default;
            case 'every_5_minutes':
                $max = 5 * MINUTE_IN_SECONDS;
                break;
            case 'every_1_minutes':
                $max = MINUTE_IN_SECONDS;
                break;
        }

        $real_max = $this->get_real_max_execution_time();

        return min( $max, $real_max );
    }

    /**
     * @return int
     */
    public function get_real_max_execution_time()
    {
        return absint( ini_get('max_execution_time') );
    }

    /**
     * @param $event Event
     */
    protected function set_current_event( &$event )
    {
        $this->current_event = $event;
    }

    /**
     * @return Event
     */
    public function get_current_event()
    {
        return $this->current_event;
    }

    /**
     * @param $contact Contact
     */
    protected function set_current_contact( &$contact )
    {
        $this->current_contact = $contact;
    }

    /**
     * @return Contact
     */
    public function get_current_contact()
    {
        return $this->current_contact;
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

        do_action( 'groundhogg/event_queue/process/before', $this );

        $i = 0;

        $max_events = intval( Plugin::$instance->settings->get_option( 'max_events', 9999 ) );

        /* double check event setting */
        if ( $max_events === 0 ){
            $max_events = 9999;
        }

        self::set_is_processing( true );

        /* Only run within given time allotment. DO NOT run past the time interval provided */
        while ( $this->has_events() && $i < $max_events && microtime( true ) < $this->max_process_time ) {

            $event = $this->get_next_event();
            $this->set_current_event( $event );
            $this->set_current_contact( $event->get_contact() );

            if ( $event->run() && $event->is_funnel_event() ){
                $next_step = $event->get_step()->get_next_action();
                if ( $next_step instanceof Step && $next_step->is_active() ){
                    $next_step->enqueue( $event->get_contact() );
                }
            }
            $i++;
        }

        self::set_is_processing( false );

        do_action( 'groundhogg/event_queue/process/after', $this );

        if ( microtime( true ) >= $this->max_process_time ){
            return $i;
        }

        return $i + $this->process();
    }

    /**
     * Get the next event in the queue to run.
     */
    public function get_next_event()
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

}