<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-24
 * Time: 2:19 PM
 */

/* for semantics the BROADCAST tool is the FUNNEL with ID 1 */
define( 'WPFN_BROADCAST', 1 );

/**
 * Schedule a broadcast after adding it in the Admin.
 *
 */
function wpfn_schedule_broadcast()
{
    $email = isset( $_POST['email_id'] )? intval( $_POST[ 'email_id' ] ) : null;

    $tags = isset( $_POST[ 'tags' ] )? $_POST['tags'] : array();

    if ( empty( $tags ) || ! is_array( $tags ) )
    {
        wp_die( __( 'Please select a tag to send this broadcast to.', 'groundhogg' ) );
    }

    $send_date = isset( $_POST['date'] )? $_POST['date'] : date( 'd-m-Y', strtotime( 'tomorrow' ) );
    $send_time = isset( $_POST['time'] )? $_POST['time'] : '09:30';

    $time_string = $send_date . ' ' . $send_time;

    $send_at = strtotime( $time_string );

    $broadcast_id = wpfn_insert_broadcast( $email, $tags, $send_at );

    if ( ! $broadcast_id )
        wp_die( 'Something went wrong' );

    global $wpdb;

    $contacts_table = $wpdb->prefix . WPFN_CONTACTS;
    $tags_table = $wpdb->prefix . WPFN_CONTACT_TAG_RELATIONSHIPS;

    foreach ( $tags as $tag_id )
    {
        $where[] = sprintf( "t.tag_id = %d", $tag_id );
    }

    $where = implode( ' OR ', $where );

    $contacts = $wpdb->get_results("
    SELECT DISTINCT c.ID FROM $tags_table t 
    LEFT JOIN $contacts_table c ON t.contact_id = c.ID
    WHERE $where
    ", ARRAY_A );

    if ( ! $contacts )
        wp_die( 'No contacts were selected.' );

    foreach ( $contacts as $i => $contact )
    {
        wpfn_enqueue_event( $send_at, WPFN_BROADCAST, $broadcast_id, intval( $contact['ID'] ) );
    }
}

add_action( 'wpfn_add_broadcast', 'wpfn_schedule_broadcast' );

/**
 * Send the email to the contact VIA the broadcast function.
 *
 * @param $broadcast_id
 * @param $contact_id
 */
function wpfn_send_broadcast( $broadcast_id, $contact_id )
{
    $broadcast = wpfn_get_broadcast_by_id( $broadcast_id );

    $email_id =  intval( $broadcast['email_id'] );
    /* send the email */
    wpfn_send_email( $contact_id, $email_id, WPFN_BROADCAST, $broadcast_id );
    /* change status to sent once emails for this broadcast start going out. */
    wpfn_update_broadcast( $broadcast_id, 'broadcast_status', 'sent' );
}

add_action( 'wpfn_do_action_broadcast', 'wpfn_send_broadcast' );
