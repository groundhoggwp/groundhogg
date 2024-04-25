<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );

include_once __DIR__ . '/../managed-page.php';

$contact         = get_contactdata();
$permissions_key = get_permissions_key( 'view_archive', true );

if ( ! $contact ) {

	// Create a new contact record for the current user if they are an admin
	if ( is_user_logged_in() && current_user_can( 'add_contacts' ) ) {
		$contact = create_contact_from_user( wp_get_current_user() );
	}

	// if still no contact, die
	if ( ! $contact ) {
		wp_die( 'No contact record available for preview...' );
	}
}

// Check permissions...
// can view emails
// is logged in
// Or has permissions key
if ( current_user_can( 'view_emails' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact, 'view_archive' ) ):

	$use_queued = get_query_var( 'use_queued' );
	$event_id   = absint( get_query_var( 'event_id' ) );
	$event      = new Event( $event_id, 'events', $use_queued ? 'queued_id' : 'ID' );

	// Event does not exist, or mismatched contact ID
	if ( ! $event->exists() || $event->get_contact_id() !== $contact->get_id() ) {

        if ( current_user_can( 'view_emails' ) ){
	        wp_die( 'The view in browser feature does not work for tests and previews because it is not associated with an event. It only works when the email is sent to a recipient from a broadcast or funnel.' );
        }

		wp_die( 'Unable to view archive...' );
	}

	$email_id = $event->email_id;

	$email = new Email( $email_id );

	if ( ! $email->exists() ) {
		wp_die( __( 'Could not load email...' ) );
	}

	$email->set_contact( $contact );
	$email->set_event( $event );

	echo $email->build();

else:

	include __DIR__ . '/../preferences.php';

endif;
