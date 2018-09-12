<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-24
 * Time: 2:19 PM
 */

/* for semantics the BROADCAST tool is the FUNNEL with ID 1 */
define( 'WPGH_BROADCAST', 1 );

/**
 * Schedule a broadcast after adding it in the Admin.
 *
 */
function wpgh_schedule_broadcast()
{
    $email = isset( $_POST['email_id'] )? intval( $_POST[ 'email_id' ] ) : null;

    $tags = isset( $_POST[ 'tags' ] )? wpgh_validate_tags( $_POST['tags'] ): array();

    if ( empty( $tags ) || ! is_array( $tags ) )
    {
        wp_die( __( 'Please select one or more tags to send this broadcast to.', 'groundhogg' ) );
    }

    $contact_sum = 0;
    foreach ( $tags as $tag ){
        $tag = wpgh_get_tag( $tag );
        $contact_sum += wpgh_count_contact_tag_relationships( 'tag_id', intval( $tag[ 'tag_id' ] ) );
    }

    if ( $contact_sum === 0 )
        wp_die( __( 'Please select a tag with one or more associated contacts.' ) );

    $send_date = isset( $_POST['date'] )? $_POST['date'] : date( 'Y/m/d', strtotime( 'tomorrow' ) );
    $send_time = isset( $_POST['time'] )? $_POST['time'] : '09:30';

    $time_string = $send_date . ' ' . $send_time;

    /* convert to UTC */
    $send_at = strtotime( $time_string ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

    if ( $send_at < time() )
        wp_die( __( 'Please send at a time in the future!' ) );

    $broadcast_id = wpgh_insert_broadcast( $email, $tags, $send_at );

    if ( ! $broadcast_id )
        wp_die( 'Something went wrong' );

    global $wpdb;

    $contacts_table = $wpdb->prefix . WPGH_CONTACTS;
    $tags_table = $wpdb->prefix . WPGH_CONTACT_TAG_RELATIONSHIPS;

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
        wpgh_enqueue_event( $send_at, WPGH_BROADCAST, $broadcast_id, intval( $contact['ID'] ) );
    }
}

add_action( 'wpgh_add_broadcast', 'wpgh_schedule_broadcast' );

/**
 * Send the email to the contact VIA the broadcast function.
 *
 * @param $broadcast_id
 * @param $contact_id
 */
function wpgh_send_broadcast( $broadcast_id, $contact_id )
{
    $broadcast = wpgh_get_broadcast_by_id( $broadcast_id );

    $email_id =  intval( $broadcast['email_id'] );
    /* send the email */
    wpgh_send_email( $contact_id, $email_id, WPGH_BROADCAST, $broadcast_id );
    /* change status to sent once emails for this broadcast start going out. */
    wpgh_update_broadcast( $broadcast_id, 'broadcast_status', 'sent' );
    //todo remove eventually
    wpgh_add_note( $contact_id, "Sent Broadcast" );
}

add_action( 'wpgh_do_action_broadcast', 'wpgh_send_broadcast', 10, 2 );

/**
 * Get the number of opens for a broadcast email
 *
 * @param $broadcast_id int the broadcast ID
 * @return null|string
 */
function wpgh_get_broadcast_opens( $broadcast_id )
{
    global $wpdb;

    $table = $wpdb->prefix . WPGH_ACTIVITY;

    $opens = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table
        WHERE funnel_id = %d AND step_id = %d AND activity_type = %s",
        WPGH_BROADCAST, $broadcast_id, 'email_opened'
    ) );

    return $opens;
}

/**
 * Get the number of clicks for a broadcast link click
 *
 * @param $broadcast_id int the broadcast ID
 * @return null|string
 */
function wpgh_get_broadcast_clicks( $broadcast_id )
{
    global $wpdb;

    $table = $wpdb->prefix . WPGH_ACTIVITY;

    $opens = $wpdb->get_var( $wpdb->prepare(
        "SELECT count(*) FROM $table
        WHERE funnel_id = %d AND step_id = %d AND activity_type = %s",
        WPGH_BROADCAST, $broadcast_id, 'email_link_click'
    ) );

    return $opens;
}
