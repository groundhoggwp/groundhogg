<?php
/**
 * Contact Functions
 *
 * @package     wp-funnels
 * @subpackage  Includes
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Get the text explaenation for the optin staus of a contact
 * 0 = unconfirmed, can send email
 * 1 = confirmed, can send email
 * 2 = opted out, can't send email
 *
 * @param int $status OptIn Status Int of a contact
 *
 * @return bool|string
 */
function wpfn_get_optin_status_text( $status )
{

	if ( ! $status || ! is_numeric( $status ) )
		return false;

	$status = absint( $status );
	if ( ! $status )
		return false;

	switch ( $status ){

		case 0:
			return __( 'Unconfirmed. They will receive emails.', 'wp-funnels' );
			break;
		case 1:
			return __( 'Confirmed. They will receive emails.', 'wp-funnels' );
			break;
		case 2:
			return __( 'Opted Out. They will not receive emails.', 'wp-funnels' );
			break;
		default:
			return __( 'Unconfirmed. They will receive emails.', 'wp-funnels' );
			break;
	}
}

/**
 * Whether we can send emails to this contact.
 *
 * @param int $status OptIn stats of a contact
 *
 * @return bool
 */
function wpfn_can_send_emails_to_contact( $status )
{
	if ( ! $status || ! is_numeric( $status ) )
		return false;

	$status = absint( $status );
	if ( ! $status )
		return false;

	return $status === 1 || $status === 0;
}

function wpfn_log_contact_activity( $contact_id, $activity )
{
	if ( ! $activity || ! is_string( $activity ) )
		return false;

	$date_time = date( 'Y-m-d TH:i:s', strtotime( 'now' ) );

	$activity = sanitize_text_field( $activity );

	$last_activity = wpfn_get_contact_meta( $contact_id, 'activity_log' );

	$new_activity = $date_time . ' | ' . $activity . PHP_EOL . $last_activity;

	do_action( 'wpfn_contact_activity_logged', $contact_id );

	return wpfn_update_contact_meta( $contact_id, 'activity_log', $new_activity );
}