<?php
namespace Groundhogg;

$email_id = absint( get_query_var( 'email_id' ) );

if ( ! $email_id ){
    wp_die( __( 'Could not load this email', 'groundhogg' ), __( 'Error loading email.', 'groundhogg' ) );
}

$email = Plugin::$instance->utils->get_email( $email_id );

if ( ! $email ){
    wp_die( 'Invalid email.' );
}

$email->set_contact( Plugin::$instance->tracking->get_current_contact() );
$email->set_event( Plugin::$instance->tracking->get_current_event() );

//echo 'hi';
$content = $email->build();

echo $content;
