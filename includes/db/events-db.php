<?php
/**
 * Event Queue database functions
 *
 * Functions to manipulate and retrieve data from the database.
 *
 * @package     groundhogg
 * @subpackage  Includes/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Get events in between the times given
 *
 * @param $time_from int the time to start searching from, can be 0
 * @param $time_to   int the time to end searching
 *
 * @return array|false List of events on success, false on failure
 */
function wpgh_get_queued_events( $time_from, $time_to )
{
	global $wpdb;

	if ( ! $time_to || ! is_int( $time_to ) )
		return false;

	$time_from = absint( $time_from );
	$time_to = absint( $time_to );

	if ( ! $time_to )
		return false;

	$table_name = $wpdb->prefix . WPGH_EVENTS;

	return $wpdb->get_results(
		$wpdb->prepare(
			"
         SELECT * FROM $table_name
		 WHERE %d <= time AND time <= %d AND status = %s
		",
			$time_from, $time_to, 'waiting'
		), ARRAY_A
	);
}

/**
 * Remove events from the queue within a specific time range.
 *
 * @param $time_from int A start time for the events to be deleted
 * @param $time_to   int An end time
 *
 * @return int|false The number or events deleted, or false if there were no rows affected.
 */
function wpgh_dequeue_events( $time_from, $time_to )
{
	global $wpdb;

	if ( ! $time_from || ! is_int( $time_from ) || ! $time_to || ! is_int( $time_to ) )
		return false;

	$time_from = absint( $time_from );
	$time_to = absint( $time_to );

	if ( ! $time_from || ! $time_to )
		return false;

	$table_name = $wpdb->prefix . WPGH_EVENTS;

	return $wpdb->query(
		$wpdb->prepare(
			"
         UPDATE $table_name
         SET status = %s
		 WHERE %d <= time AND time <= %d AND status = %s
		",
			'complete', $time_from, $time_to, 'waiting'
		)
	);
}

/**
 * Remove specific funnel events for a contact from the queue.
 * Since funnels are linear, we can simply remove any existing funnel events.
 * There should only be one event per contact per funnel, but we'll delete multiple if necessary
 *
 * @param $funnel_id  int The funnel's ID
 *
 * @return int|false The number or events deleted, or false if there were no rows affected.
 */
function wpgh_dequeue_funnel_events( $funnel_id )
{
    global $wpdb;

    if ( ! $funnel_id || ! is_int( $funnel_id ) )
        return false;

    $funnel_id = absint( $funnel_id );

    if ( ! $funnel_id )
        return false;

    $table_name = $wpdb->prefix . WPGH_EVENTS;

    return $wpdb->query(
        $wpdb->prepare(
            "
         UPDATE $table_name
         SET status = %s
		 WHERE funnel_id = %d AND status = %s
		",
            'skipped', $funnel_id, 'waiting'
        )
    );
}

/**
 * Remove specific funnel events for a contact from the queue.
 * Since funnels are linear, we can simply remove any existing funnel events.
 * There should only be one event per contact per funnel, but we'll delete multiple if necessary
 *
 * @param $step_id int The step's ID
 * @param $funnel_id  int The funnel's ID
 *
 * @return int|false The number or events deleted, or false if there were no rows affected.
 */
function wpgh_dequeue_funnel_step_events( $funnel_id, $step_id )
{
    global $wpdb;

    if ( ! $step_id || ! is_int( $step_id ) || ! $funnel_id || ! is_int( $funnel_id ) )
        return false;

    $step_id = absint( $step_id );
    $funnel_id = absint( $funnel_id );

    if ( ! $step_id || ! $funnel_id )
        return false;

    $table_name = $wpdb->prefix . WPGH_EVENTS;

    return $wpdb->query(
        $wpdb->prepare(
            "
         UPDATE $table_name
         SET status = %s
		 WHERE step_id = %d AND funnel_id = %d AND status = %s
		",
            'skipped', $step_id, $funnel_id, 'waiting'
        )
    );
}

/**
 * Remove specific funnel events for a contact from the queue.
 * Since funnels are linear, we can simply remove any existing funnel events.
 * There should only be one event per contact per funnel, but we'll delete multiple if necessary
 *
 * @param $contact_id int The contact's ID
 * @param $funnel_id  int The funnel's ID
 *
 * @return int|false The number or events deleted, or false if there were no rows affected.
 */
function wpgh_dequeue_contact_funnel_events( $contact_id, $funnel_id )
{
	global $wpdb;

	if ( ! $contact_id || ! is_int( $contact_id ) || ! $funnel_id || ! is_int( $funnel_id ) )
		return false;

	$contact_id = absint( $contact_id );
	$funnel_id = absint( $funnel_id );

	if ( ! $contact_id || ! $funnel_id )
		return false;

	$table_name = $wpdb->prefix . WPGH_EVENTS;

	return $wpdb->query(
		$wpdb->prepare(
			"
         UPDATE $table_name
         SET status = %s
		 WHERE contact_id = %d AND funnel_id = %d AND status = %s
		",
            'skipped', $contact_id, $funnel_id, 'waiting'
		)
	);
}

/**
 * Cancel a single event for a particular contact.
 *
 * @param $time
 * @param $funnel_id
 * @param $step_id
 * @param $contact_id
 *
 * @return int|bool
 */
function wpgh_cancel_event( $time, $funnel_id, $step_id, $contact_id )
{
	global $wpdb;

	if ( ! $contact_id || ! is_int( $contact_id ) || ! $funnel_id || ! is_int( $funnel_id ) )
		return false;

	$contact_id = absint( $contact_id );
	$funnel_id = absint( $funnel_id );

	if ( ! $contact_id || ! $funnel_id )
		return false;

	$table_name = $wpdb->prefix . WPGH_EVENTS;

	return $wpdb->query(
		$wpdb->prepare(
			"
         UPDATE $table_name
         SET status = %s
		 WHERE time = %d AND contact_id = %d AND funnel_id = %d AND step_id = %d AND status = %s
		",
			'cancelled', $time, $contact_id, $funnel_id, $step_id, 'waiting'
		)
	);
}

/**
 * Insert a new event into the DB.
 * Set the default event status to waiting as it's waiting to be run.
 *
 * @param $time       int The time the event is to occur
 * @param $funnel_id  int The ID of the funnel the event was queued from
 * @param $step_id    int The ID of the step within the associated funnel
 * @param $contact_id int The Contact's ID
 * @param $callback   string a callback function to run when the event is triggered
 * @param $arg1       mixed  an optional argument to pass to the call back function
 * @param $arg2       mixed  an optional argument to pass to the call back function
 * @param $arg3       mixed  an optional argument to pass to the call back function
 *
 * @return int|bool 1 on success, false on failure
 */
function wpgh_enqueue_event( $time, $funnel_id, $step_id, $contact_id, $callback='', $arg1='', $arg2='', $arg3='' )
{
	global $wpdb;

	if ( ! $time || ! is_int( $time ) || ! $funnel_id || ! is_int( $funnel_id ) || ! $step_id || ! is_int( $step_id ) || ! $contact_id || ! is_int( $contact_id ) )
		return false;

	return $wpdb->insert(
		$wpdb->prefix . WPGH_EVENTS,
		array(
			'time'          => $time,
			'funnel_id'     => $funnel_id,
			'step_id'       => $step_id,
			'contact_id'    => $contact_id,
			'status'        => 'waiting',
			'callback'      => $callback,
			'arg1'          => maybe_serialize( $arg1 ),
			'arg2'          => maybe_serialize( $arg2 ),
			'arg3'          => maybe_serialize( $arg3 )
		)
	);
}

define( 'WPGH_EVENTS', 'event_queue' );
define( 'WPGH_EVENTS_DB_VERSION', '0.3' );

/**
 * Create the events database table.
 */
function wpgh_create_events_db()
{

	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$table_name = $wpdb->prefix . WPGH_EVENTS;

	if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpgh_events_db_version'), WPGH_EVENTS_DB_VERSION, '==' ) )
		return;

	$sql = "CREATE TABLE $table_name (
      time bigint(20) NOT NULL,
      funnel_id bigint(20) NOT NULL,
      step_id bigint(20) NOT NULL,
      contact_id bigint(20) NOT NULL,
      status varchar(20) NOT NULL,
      callback text NOT NULL,
      arg1 text NOT NULL,
      arg2 text NOT NULL,
      arg3 text NOT NULL,
      PRIMARY KEY (time,funnel_id,step_id,contact_id),
      KEY time (time),
      KEY funnel_id (funnel_id),
      KEY step_id (step_id),
      KEY contact_id (contact_id)
    ) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta( $sql );

	update_option( 'wpgh_events_db_version', WPGH_EVENTS_DB_VERSION );
}