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

	$time = strtotime( 'now' );

	$events = wpfn_get_queued_events( 0, $time );

	/**
	 * Obviously don't run if their are no events to perform
	 */
	if ( empty( $events ) )
		return false;

	$dequeued = wpfn_dequeue_events( 0, $time );

	/**
	 * Don't run if the events were not dequeued to avoid running them again
	 */
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
	foreach ( $events as $event_args )
	{
		$funnel_id  = intval( $event_args['funnel_id'] );
		$step_id    = intval( $event_args['step_id'] );
		$contact_id = intval( $event_args['contact_id'] );
		$callback   = $event_args['callback'];
		$arg1       = maybe_unserialize( $event_args['arg1'] );
		$arg2       = maybe_unserialize( $event_args['arg2'] );
		$arg3       = maybe_unserialize( $event_args['arg3'] );

		/**
		 * Run the action specified...
		 *
		 * For example: call_user_func( Function: 'wpfn_send_email', Contact Id: 42, Email Id: 30, null, null )
		 */

		call_user_func( $callback, $contact_id, $arg1, $arg2, $arg3 );
	}

	return key( $events ) + 1;
}