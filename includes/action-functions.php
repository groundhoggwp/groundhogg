<?php
/**
 * Action Functions
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
 * Return a list of slugs for the available funnel actions
 *
 * @return array()
 */
function wpgh_get_funnel_actions()
{
    $actions = array();

    $actions['send_email']  = array( 'title' => __( 'Send Email', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/send-email.png' );
    $actions['apply_note']  = array( 'title' => __( 'Apply Note', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/apply-a-note.png' );
    $actions['notification']  = array( 'title' => __( 'Notification', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/admin-notification.png' );
    $actions['apply_tag']   = array( 'title' => __( 'Apply Tag', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/apply-tag.png' );
    $actions['remove_tag']  = array( 'title' => __( 'Remove Tag', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/remove-tag.png' );
//    $actions['delete_user'] = array( 'title' => __( '', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/.png' );
    $actions['date_timer']  = array( 'title' => __( 'Date Timer', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/date-timer.png' );
    $actions['delay_timer'] = array( 'title' => __( 'Delay Timer', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/delay-timer.png' );
    $actions['create_user'] = array( 'title' => __( 'Create User', 'groundhogg' ), 'icon' => WPGH_ASSETS_FOLDER . '/images/builder-icons/create-account.png' );

    return apply_filters( 'wpgh_funnel_actions', $actions );
}

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
        $final_time = strtotime( $time_string );
    } else {
        $time_string = '+ ' . $amount . ' ' . $type;
        $base_time = strtotime( $time_string );
        $formatted_date = date( 'Y-m-d', $base_time );
        $time_string = $formatted_date . ' ' . $run_time;
        if ( strtotime( $time_string ) < time() ){
            $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
            $time_string = $formatted_date . ' ' . $run_time;
        }

        /* convert to utc */
        $final_time = strtotime( $time_string ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
    }

    $funnel_id = wpgh_get_step_funnel( $step_id );
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
 * Queue the email in the event queue. Does Basically it runs immediately but is queued for the sake of semantics.
 *
 * @param $step_id int The Id of the step
 * @param $contact_id int the Contact's ID
 */
function wpgh_enqueue_immediate_action( $step_id, $contact_id )
{
    $funnel_id = wpgh_get_step_funnel( $step_id );
    wpgh_enqueue_event( time() + 10, $funnel_id,  $step_id, $contact_id );
}

add_action( 'wpgh_enqueue_next_funnel_action_apply_note', 'wpgh_enqueue_immediate_action', 10, 2 );
add_action( 'wpgh_enqueue_next_funnel_action_notification', 'wpgh_enqueue_immediate_action', 10, 2 );
add_action( 'wpgh_enqueue_next_funnel_action_send_email', 'wpgh_enqueue_immediate_action', 10, 2 );
add_action( 'wpgh_enqueue_next_funnel_action_apply_tag', 'wpgh_enqueue_immediate_action', 10, 2 );
add_action( 'wpgh_enqueue_next_funnel_action_remove_tag', 'wpgh_enqueue_immediate_action', 10, 2 );
add_action( 'wpgh_enqueue_next_funnel_action_create_user', 'wpgh_enqueue_immediate_action', 10, 2 );

/**
 * Applies a note to the contact from a funnel step.
 *
 * @param $step_id int the ID of the step
 * @param $contact_id int the ID of the contact
 */
function wpgh_apply_note_to_contact( $step_id, $contact_id  )
{

    $note = wpgh_get_step_meta( $step_id, 'note_text', true );
    $finished_note = wpgh_do_replacements( $contact_id, $note );
    $current_notes = wpgh_get_contact_meta( $contact_id, 'notes', true );

    $current_notes .= "\n\n===== " . date_i18n( 'Y-m-d h:i A' ) . " =====\n\n";
    $current_notes .= $finished_note;

    $current_notes = sanitize_textarea_field( $current_notes );

    wpgh_update_contact_meta( $contact_id, 'notes', $current_notes );
}

add_action( 'wpgh_do_action_apply_note', 'wpgh_apply_note_to_contact', 10, 2 );

/**
 * Sends a notification about a contact to a given email address.
 *
 * @param $step_id int ID of the step
 * @param $contact_id int ID opf the contact
 */
function wpgh_send_admin_notification( $step_id, $contact_id )
{

    $note = wpgh_get_step_meta( $step_id, 'note_text', true );
    $finished_note = sanitize_textarea_field( wpgh_do_replacements( $contact_id, $note ) );

    $finished_note .= sprintf( "\n\n%s: %s", __( 'Manage Contact', 'groundhogg' ), admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact_id ) );

    $subject = wpgh_get_step_meta( $step_id, 'subject', true );
    $subject = sanitize_text_field(  wpgh_do_replacements( $contact_id, $subject ) );

    $send_to = wpgh_get_step_meta( $step_id, 'send_to', true );
    if ( ! is_email( $send_to ) ){
        $send_to = sanitize_email( wpgh_do_replacements( $contact_id, $send_to ) );
    }

    if ( ! $send_to )
    {
        return;
    }

    wp_mail( $send_to, $subject, $finished_note );
}

add_action( 'wpgh_do_action_notification', 'wpgh_send_admin_notification', 10, 2 );