<?php
/**
 * Event Queue Functions
 *
 * Functions to manipulate run events
 *
 * @package     groundhogg
 * @subpackage  Includes/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Do the ajax function for running events. only run asynchronously while doing an ajax request.
 */
function wpgh_process_queue_ajax()
{
    if ( ! wp_doing_ajax() )
        return;

    $ran = wpgh_do_queued_events();

    return;
}

add_action( 'admin_init', 'wpgh_process_queue_ajax' );

/**
 * Run all events that have yet to be run.
 *
 * @return int|bool the number of completed steps or false if no events were run
 */
function wpgh_do_queued_events()
{
    /* do not run simultaneous events*/
    if ( get_transient( 'wpgh_doing_events' ) )
        return false;

    $time = time();

    /* Get any events that should have already been run. */
	$events = wpgh_get_queued_events( 1, $time );

    /* Obviously don't run if their are no events to perform */
    if ( empty( $events ) )
        return false;

    /* inform WP that the process is running and another process should not be initiated for at least 60 seconds. */
    set_transient( 'wpgh_doing_events', true, 60 );

    $dequeued = wpgh_dequeue_events( 1, $time );

    /* Don't run if the events were not dequeued to avoid running them again*/
    if ( ! $dequeued )
        return false;

    /**
     * Iterate through the events and perform them
     *
     * @var $funnel_id  int The ID of the funnel the event was queued from
     * @var $step_id    int The ID of the step within the associated funnel
     * @var $contact_id int The Contact's ID
     * @var $callback   string a callback function to run when the event is triggered
     * @var $arg1       mixed  an optional argument to pass to the call back function
     * @var $arg2       mixed  an optional argument to pass to the call back function
     */
    foreach ( $events as $i => $event_args )
    {
        $funnel_id  = intval( $event_args['funnel_id'] );
        $step_id    = intval( $event_args['step_id'] );
        $contact_id = intval( $event_args['contact_id'] );
        $callback   = $event_args['callback'];
        $arg1       = maybe_unserialize( $event_args['arg1'] );
        $arg2       = maybe_unserialize( $event_args['arg2'] );
        $arg3       = maybe_unserialize( $event_args['arg3'] );

        if ( $funnel_id === WPGH_BROADCAST ){

            do_action( 'wpgh_do_action_broadcast', $step_id, $contact_id );

        } else {

            $step_type = wpgh_get_step_type( $step_id );

            do_action( 'wpgh_do_action_' . $step_type, $step_id, $contact_id );

            /* run the next step only if the funnel is active. */
            if ( wpgh_is_funnel_active( $funnel_id ) ){
                $next_step_id = wpgh_enqueue_next_funnel_action( $step_id, $contact_id );
                do_action( 'wpgh_step_queued', $next_step_id );
            }
        }

        if ( $callback )
            call_user_func( $callback, $contact_id, $arg1, $arg2, $arg3 );


    }

    /* schedule the next cron run in 11 minutes... its 11 so that it doesnt get caught by the 10 minute minimum.
    also has the affect of not allowing an ajax request to ALSO interfere with the CRON schedule. */
    if ( defined( 'DOING_CRON' ) ){
        wp_schedule_single_event( time() + ( 11 * MINUTE_IN_SECONDS ), 'wpgh_cron_event' );
    }

    /* allow a restart of the event queue by signaling new processes this it's available. */
    delete_transient( 'wpgh_doing_events' );

    return key( $events ) + 1;
}

/* add_action */
add_action( 'wpgh_cron_event', 'wpgh_do_queued_events' );
add_action( 'wpgh_cron_event_plus_10', 'wpgh_do_queued_events' );
add_action( 'wpgh_cron_event_plus_20', 'wpgh_do_queued_events' );
add_action( 'wpgh_cron_event_plus_30', 'wpgh_do_queued_events' );
add_action( 'wpgh_cron_event_plus_40', 'wpgh_do_queued_events' );
add_action( 'wpgh_cron_event_plus_50', 'wpgh_do_queued_events' );

function wpgh_schedule_single_cron_events()
{
	if ( ! wp_next_scheduled( 'wpgh_cron_event_plus_10' ) )
        wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'wpgh_cron_event_plus_10' );
	if ( ! wp_next_scheduled( 'wpgh_cron_event_plus_20' ) )
        wp_schedule_single_event( time() + 20 * MINUTE_IN_SECONDS, 'wpgh_cron_event_plus_20' );
	if ( ! wp_next_scheduled( 'wpgh_cron_event_plus_30' ) )
		wp_schedule_single_event( time() + 30 * MINUTE_IN_SECONDS, 'wpgh_cron_event_plus_30' );
	if ( ! wp_next_scheduled( 'wpgh_cron_event_plus_40' ) )
		wp_schedule_single_event( time() + 40 * MINUTE_IN_SECONDS, 'wpgh_cron_event_plus_40' );
	if ( ! wp_next_scheduled( 'wpgh_cron_event_plus_50' ) )
		wp_schedule_single_event( time() + 50 * MINUTE_IN_SECONDS, 'wpgh_cron_event_plus_50' );
}

add_action( 'wpgh_cron_event', 'wpgh_schedule_single_cron_events' );

/**
 * Kickstart the cron job for the event queue.
 */
function wpgh_schedule_cron_event()
{
    if ( ! wp_next_scheduled( 'wpgh_cron_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'wpgh_cron_event' );
    }
}

add_action( 'init', 'wpgh_schedule_cron_event' );