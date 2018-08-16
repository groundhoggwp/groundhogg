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
 * Enqueue the scripts for the event runner process.
 * Appears on front-end & backend as it will be run by traffic to the site.
 */
function wpfn_enqueue_event_scripts()
{
    wp_enqueue_script( 'wpfn-event-doer', WPFN_ASSETS_FOLDER . '/js/event-doer.js' , array('jquery') );
    wp_localize_script( 'wpfn-event-doer', 'wpfn_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'wpfn_enqueue_event_scripts' );
add_action( 'admin_enqueue_scripts', 'wpfn_enqueue_event_scripts' );

/**
 * Do the ajax function for running events.
 */
function wpfn_process_queue_ajax()
{
    //todo security check.
    if ( ! wp_doing_ajax() )
        wp_die( wpfn_get_random_groundhogday_quote() );

    $ran = wpfn_do_queued_events();

    if ( $ran )
        wp_die( $ran );
    else
        wp_die( wpfn_get_random_groundhogday_quote() );

}

add_action( 'wp_ajax_wpfn_event_queue_start', 'wpfn_process_queue_ajax' );
add_action( 'wp_ajax_nopriv_wpfn_event_queue_start', 'wpfn_process_queue_ajax' );

/**
 * Run all events that have yet to be run.
 *
 * @return int|bool the number of completed steps or false if no events were run
 */
function wpfn_do_queued_events()
{
    /* do not run simultaneous events*/
    if ( get_transient( 'wpfn_doing_events' ) )
        return false;

    $time = strtotime( 'now' );

    /* Get any events that should have already been run. */
	$events = wpfn_get_queued_events( 1, $time );

    /* Obviously don't run if their are no events to perform */
    if ( empty( $events ) )
        return false;

    /* inform WP that the process is running and another process should not be initiated for at least 60 seconds. */
    set_transient( 'wpfn_doing_events', true, 60 );

    $dequeued = wpfn_dequeue_events( 1, $time );

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

        $step_type = wpfn_get_step_type( $step_id );

        do_action( 'wpfn_do_action_' . $step_type, $step_id, $contact_id );

        if ( $callback )
            call_user_func( $callback, $contact_id, $arg1, $arg2, $arg3 );

        /* run the next step only if the funnel is active. */
        if ( wpfn_is_funnel_active( $funnel_id ) ){
            $next_step_id = wpfn_enqueue_next_funnel_action( $step_id, $contact_id );
            do_action( 'wpfn_step_queued', $next_step_id );
        }
    }

    /* allow a restart of the event queue by signaling new processes this it's available. */
    delete_transient( 'wpfn_doing_events' );

    return key( $events ) + 1;
}