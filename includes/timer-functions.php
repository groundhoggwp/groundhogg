<?php
/**
 * Timer Functions
 *
 * Anything to do with saving, manipulating, and running timer functions in the event queue
 *
 * @package     wp-funnels
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
function wpfn_enqueue_delay_timer_action( $step_id, $contact_id )
{
    //todo sanitize and evaluate data...
    $amount = wpfn_get_step_meta( $step_id, 'delay_amount', true );
    $type = wpfn_get_step_meta( $step_id, 'delay_type', true );
    $run_when = wpfn_get_step_meta( $step_id, 'run_when', true );
    $run_time = wpfn_get_step_meta( $step_id, 'run_time', true );

    if ( $run_when == 'now' ){
        $time_string = '+ ' . $amount . ' ' . $type;
    } else {
        $time_string = '+ ' . $amount . ' ' . $type;
        $formated_date = date( 'Y-m-d', strtotime( $time_string ) );
        $time_string = $formated_date . ' ' . $run_time;
    }

    $funnel_id = wpfn_get_step_funnel( $step_id );

    wpfn_enqueue_event( strtotime( $time_string ), $funnel_id, $step_id, $contact_id );
}

add_action( 'wpfn_enqueue_next_funnel_action_delay_timer', 'wpfn_enqueue_delay_timer_action' );

/**
 * Translate the date timer settings into a timestamp and enqueue the action so it runs eventually.
 *
 * @param $step_id int the timer steps ID
 * @param $contact_id int the Contact ID
 */
function wpfn_enqueue_date_timer_action( $step_id, $contact_id )
{
    //todo sanitize and evaluate data...
    $run_when = wpfn_get_step_meta( $step_id, 'run_date', true );
    $run_time = wpfn_get_step_meta( $step_id, 'run_time', true );

    $time_string = $run_when . ' ' . $run_time;
    $funnel_id = wpfn_get_step_funnel( $step_id );

    wpfn_enqueue_event( strtotime( $time_string ), $funnel_id, $step_id, $contact_id );
}

add_action( 'wpfn_enqueue_next_funnel_action_date_timer', 'wpfn_enqueue_date_timer_action' );

/**
 * run the timer events, we use this function for both the date timer and the
 * delay timer as there is no reason to use different ones since they do the same this.
 * The only thing that actually happens is that the timer enqueus the next event, well, because thats what timers do.
 *
 * @param $step_id int the timer's step ID
 * @param $contact_id int the Contact's ID
 */
function wpfn_run_timer_event_function( $step_id, $contact_id )
{
    wpfn_enqueue_next_funnel_action( $step_id, $contact_id );
}

add_action( 'wpfn_do_action_delay_timer', 'wpfn_run_timer_event_function' );
add_action( 'wpfn_do_action_date_timer', 'wpfn_run_timer_event_function' );