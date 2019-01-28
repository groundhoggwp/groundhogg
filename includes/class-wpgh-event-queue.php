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
     * @var bool whether the queue is being processed or nah
     */
    private $doing_queue = false;

    /**
     * This is going to be a unique ID for the thread which is processing the queue.
     *
     * @var string
     */
    private $thread;

    /**
     * Setup the cron jobs
     * Add new short term schedule
     * setup the action for the cron job
     */
    public function __construct()
    {

        add_filter( 'cron_schedules', array( $this, 'setup_cron_schedules' ) );
        add_action( 'init', array( $this, 'setup_cron_jobs' ) );
        add_action( self::ACTION , array( $this, 'run_queue' ) );

//        add_action( 'admin_init', array( $this, 'ajax_process' ) );
//        add_action( 'wp_ajax_nopriv_gh_process_queue', array( $this, 'ajax_process' ) );
//        add_action( 'wp_ajax_gh_process_queue', array( $this, 'ajax_process' ) );
//        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
//        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

        if ( isset( $_REQUEST[ 'process_queue' ] ) && is_admin() ){

            add_action( 'admin_init' , array( $this, 'run_queue' ) );

        }

    }

    /**
     * Get the time interval in between queue requests when processing via ajax...
     */
    private function get_interval()
    {

        $last_request_time = intval( get_transient( 'gh_queue_last_request_time' ) );

        if ( ! $last_request_time ){
            /* in milliseconds */
            return 0;
        }

        $diff = time() - $last_request_time;

        /* If the difference is less than 60 seconds */
        if ( $diff < 60 ){

            return ( ( 60 - $diff ) + 60 ) * 1000;

        } else {

            return 0;

        }

    }

    public function scripts()
    {
        $last_request_time = intval( get_transient( 'gh_queue_last_request_time' ) );

        if ( ! $last_request_time ){
            /* in milliseconds */
             $last_request_time = time();
        }

        wp_register_script( 'wpgh-queue',   WPGH_ASSETS_FOLDER . 'js/admin/queue.min.js', array( 'jquery' ), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/queue.min.js' ) );
        wp_enqueue_script( 'wpgh-queue' );
        wp_localize_script( 'wpgh-queue', 'wpghQueue', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'timeInterval'  => $this->get_interval(),
            'lastRun'       => date( 'Y-m-d H:i:s', $last_request_time )
        ) );
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
            'interval'    => MINUTE_IN_SECONDS,
            'display'     => __( 'Every 10 Minutes', 'groundhogg' )
        );

        $schedules[ 'every_5_minutes' ] = array(
            'interval'    => MINUTE_IN_SECONDS,
            'display'     => __( 'Every 5 Minutes', 'groundhogg' )
        );

        $schedules[ 'every_1_minutes' ] = array(
            'interval'    => MINUTE_IN_SECONDS,
            'display'     => __( 'Every 1 Minutes', 'groundhogg' )
        );

        return $schedules;
    }

    /**
     * Add the event cron job
     */
    public function setup_cron_jobs()
    {
        if ( ! wp_next_scheduled( self::ACTION ) ){
            wp_schedule_event( time(), apply_filters( 'wpgh_queue_interval', 'every_5_minutes' ), self::ACTION );
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
     * Hook into ALL ajax requests to process events
     */
    public function ajax_process()
    {
        if ( wp_doing_ajax() ){

            /* Provide arg to skip the queue if no_process present in $_GET or $_POST*/
            if ( ! isset( $_REQUEST[ 'no_process' ] ) ){
                if ( $i = $this->run_queue() ) {

                    /* $i is the number of events run */

                    if ( is_wp_error( $i ) ){
                        wp_die( $i->get_error_message() );
                    }

                    $response = array(
                        'eventsCompleted' => $i,
                        'nextRequestTime' => $this->get_interval(),
                        'lastRun'         => date( 'Y-m-d H:i:s', time() )
                    );

                    wp_die( json_encode( $response ) );

                } else {

                    wp_die( __( 'Could not process queue for unknown reason.' ) );

                }
            }

        }

    }

    /**
     * Return whether the queue is currently running
     *
     * @return mixed
     */
    public function is_running()
    {
        $running = wpgh_get_transient( 'wpgh_doing_event_queue' );
        return $running === $this->thread;
    }

    /**
     * Return whether another instance of the queue is currently running
     *
     * @return mixed
     */
    public function another_is_running()
    {
        $running = wpgh_get_transient( 'wpgh_doing_event_queue' );
        return ! empty( $running );
    }

    /**
     * Set the queue to running. Don't allow other instances to start the queue
     */
    public function make_running()
    {
        wpgh_set_transient( 'wpgh_doing_event_queue', $this->thread, MINUTE_IN_SECONDS );
    }

    public function make_not_running()
    {
        wpgh_delete_transient( 'wpgh_doing_event_queue' );
    }

    /**
     * Decide which process to use...
     */
    public function run_queue()
    {
        if ( function_exists( 'ftok' ) && function_exists( 'sem_acquire' ) ){
            return $this->semaphore_process();
        } else {
            return $this->process();
        }


    }

    /**
     * Process the queue within a semaphore lock...
     */
    private function semaphore_process()
    {
        $key = ftok(__FILE__, 'G' );

        $semaphore = sem_get($key, 1);

        $result = 0;

        if (sem_acquire($semaphore, 1) !== false) {

            $result = $this->process();

            sem_release($semaphore) ;

        }

        return $result;

    }

    /**
     * Iterate through the list of events and process them via the EVENTS api
     * For now just uses the standard plugins api
     *
     * @return bool|WP_Error|int the number of events process, or WP_Error, or false
     */
    private function process()
    {

        set_transient( 'gh_queue_last_request_time', time(), HOUR_IN_SECONDS );

        $this->thread = uniqid( 'queue_', true );

        /* Check if for some weird reason the queue is running in another request. */
        if ( $this->another_is_running() ){

            /* The newest Queue always wins. */
            $this->make_not_running();

        }

        $this->make_running();

        $this->prepare_events();

        if ( empty( $this->events ) ){

            return new WP_Error( 'NO_EVENTS', __( 'No events are available to process.' ) );

        }

        /* Get 'er done */
        set_time_limit(0);

        do_action( 'wpgh_process_event_queue_before', $this );

        $this->doing_queue = true;

        $i = 0;

        $max_events = intval( wpgh_get_option( 'gh_max_events', 9999 ) );

        /* double check event setting */
        if ( $max_events === 0 ){
            $max_events = 9999;
        }

        /* Check to see if the current queue is still the most recent queue. If it's not Then finish up. */
        while ( $this->has_events() && $i < $max_events && $this->is_running() ) {

            $this->cur_event = $this->get_next();

            if ( $this->cur_event->run() && ! $this->cur_event->is_broadcast_event() ){

                $next_step = $this->cur_event->step->get_next_step();

                if ( $next_step instanceof WPGH_Step && $next_step->is_active() ){

                    $next_step->enqueue( $this->cur_event->contact );

                }

            }

            $i++;

        }

        $this->doing_queue = false;

        do_action( 'wpgh_process_event_queue_after', $this );

        if ( $this->is_running() ){
            $this->make_not_running();
        }

        return $i;
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