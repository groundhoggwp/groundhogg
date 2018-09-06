<?php
/**
 * Timer Functions
 *
 * Anything to do with saving, manipulating, and running timer functions in the event queue
 *
 * @package     groundhogg
 * @subpackage  Includes/Timers
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Translate the delay timer settings into a timestamp and enqueue the action so it runs eventually.
 *
 * @param $step_id int the timer steps ID
 * @param $contact_id int the Contact ID
 */
function wpgh_enqueue_delay_timer_action( $step_id, $contact_id )
{
    //todo sanitize and evaluate data...
    $amount = wpgh_get_step_meta( $step_id, 'delay_amount', true );
    $type = wpgh_get_step_meta( $step_id, 'delay_type', true );
    $run_when = wpgh_get_step_meta( $step_id, 'run_when', true );
    $run_time = wpgh_get_step_meta( $step_id, 'run_time', true );

    if ( $run_when == 'now' ){
        $time_string = '+ ' . $amount . ' ' . $type;
    } else {
        $time_string = '+ ' . $amount . ' ' . $type;
        $base_time = strtotime( $time_string );
        $formatted_date = date( 'Y-m-d', $base_time );
        $time_string = $formatted_date . ' ' . $run_time;
        if ( strtotime( $time_string ) < time() ){
            $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
            $time_string = $formatted_date . ' ' . $run_time;
        }
    }

    $funnel_id = wpgh_get_step_funnel( $step_id );
    /* convert to utc */
    $final_time = strtotime( $time_string ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
    wpgh_enqueue_event( $final_time, $funnel_id, $step_id, $contact_id );
}

add_action( 'wpgh_enqueue_next_funnel_action_delay_timer', 'wpgh_enqueue_delay_timer_action', 10, 2 );

/**
 * Translate the date timer settings into a timestamp and enqueue the action so it runs eventually.
 *
 * @param $step_id int the timer steps ID
 * @param $contact_id int the Contact ID
 */
function wpgh_enqueue_date_timer_action( $step_id, $contact_id )
{
    //todo sanitize and evaluate data...
    $run_when = wpgh_get_step_meta( $step_id, 'run_date', true );
    $run_time = wpgh_get_step_meta( $step_id, 'run_time', true );
    $time_string = $run_when . ' ' . $run_time;
    $funnel_id = wpgh_get_step_funnel( $step_id );
    /* convert to UTC */
    $final_time = strtotime( $time_string ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
    wpgh_enqueue_event( $final_time, $funnel_id, $step_id, $contact_id );
}

add_action( 'wpgh_enqueue_next_funnel_action_date_timer', 'wpgh_enqueue_date_timer_action', 10, 2 );

/**
 * run the timer events, we use this function for both the date timer and the delay timer.
 *
 * @param $step_id int the timer's step ID
 * @param $contact_id int the Contact's ID
 */
function wpgh_run_timer_event_function( $step_id, $contact_id )
{
    //todo
    //does nothing for now, will probably log some form of reporting...
}

add_action( 'wpgh_do_action_delay_timer', 'wpgh_run_timer_event_function', 10, 2 );
add_action( 'wpgh_do_action_date_timer', 'wpgh_run_timer_event_function', 10, 2 );