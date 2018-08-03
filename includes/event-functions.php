<?php
/**
 * Event Queue Functions
 *
 * Functions to manipulate run events
 *
 * @package     wp-funnels
 * @subpackage  Includes/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Run all events that have yet to be run.
 *
 * @return int|bool
 */
function wpfn_do_queued_events()
{
	# get the events
	# remove the events from the DB
	# perform the events
	# happy days

    //schedule the action set again.
    //wp_schedule_single_event( time() + 30, 'wpfn_do_queued_events' );

    $time = strtotime( 'now' );

    // Get any events that should have already been run.
	$events = wpfn_get_queued_events( 1, $time );

	//var_dump( $events );

    /**
	 * Obviously don't run if their are no events to perform
	 */
    if ( empty( $events ) )
        return false;

    $dequeued = wpfn_dequeue_events( 1, $time );

    /**
	 * Don't run if the events were not dequeued to avoid running them again
	 */
    if ( ! $dequeued )
        return false;

    //wp_die( 'Made It Here' );

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
    foreach ( $events as $event_args )
    {
        $funnel_id  = intval( $event_args['funnel_id'] );
        $step_id    = intval( $event_args['step_id'] );
        $contact_id = intval( $event_args['contact_id'] );
        $callback   = $event_args['callback'];
        $arg1       = maybe_unserialize( $event_args['arg1'] );
        $arg2       = maybe_unserialize( $event_args['arg2'] );
        $arg3       = maybe_unserialize( $event_args['arg3'] );

        $step_type = wpfn_get_step_type( $step_id );

        do_action( 'wpfn_do_action_' . $step_type, $step_id, $contact_id );

        if ( $callback )
            call_user_func( $callback, $contact_id, $arg1, $arg2, $arg3 );

        $next_step_id = wpfn_enqueue_next_funnel_action( $step_id, $contact_id );
    }

    return key( $events ) + 1;
}

add_action( 'wpfn_do_queued_events', 'wpfn_do_queued_events' );
add_action( 'init', 'wpfn_do_queued_events' );
add_action( 'admin_init', 'wpfn_do_queued_events' );

function wpfn_setup_queue_cron_event()
{
    wp_schedule_single_event( time() + 30, 'wpfn_do_queued_events' );
}

//add_action( 'init', 'wpfn_setup_queue_cron_event' );
//add_action( 'admin_init', 'wpfn_setup_queue_cron_event' );