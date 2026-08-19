<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );

include_once __DIR__ . '/../managed-page.php';

$contact         = get_contactdata();

if ( ! $contact ) {

	// Create a new contact record for the current user if they are an admin
	if ( is_user_logged_in() && current_user_can( 'add_contacts' ) ) {
		$contact = create_contact_from_user( wp_get_current_user() );
	}

	// if still no contact, die
	if ( ! $contact ) {
		$contact = new Contact();
	}
}

$data = get_url_var( 'preview_email' );
[ $payload, $signature ] = explode( '.', $data );
$payload = base64url_decode( $payload );
$signature = base64url_decode( $signature );

if ( ! check_signature( $payload, $signature, 16 ) ){
	wp_die( esc_html__( 'Invalid signature...', 'groundhogg' ) );
}

[ $email_id, $expires ] = json_decode( $payload, true );

if ( time() > $expires ) {
	wp_die( esc_html__( 'This preview has expired...', 'groundhogg' ) );
}

$email = new Email( $email_id );

$GLOBALS['email'] = $email;

if ( ! $email->exists() ) {
	wp_die( esc_html__( 'Could not load email...', 'groundhogg' ) );
}

try {
	$email->set_contact( $contact );
	$email->set_event( new Event() );
} catch ( InvalidContactException|InvalidEventException $e ) {
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated HTML
echo $email->build();
exit;
