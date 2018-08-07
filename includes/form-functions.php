<?php

/**
 * Listens for basic contact information whenever the post variable is exists.
 */
function wpfn_form_submit_listener()
{
    if ( empty( $_POST ) )
        return;

    $email = '';
    $first_name = '';
    $last_name = '';
    $phone = '';

    foreach ( $_POST as $name => $value )
    {
        if ( preg_match( '/email/i', $name ) ){
            $email = sanitize_email( $value );
        }

        if ( preg_match( '/first/i', $name ) && preg_match( '/name/i', $name ) ){
            $first_name = sanitize_text_field( $value );
        }

        if ( preg_match( '/last/i', $name ) && preg_match( '/name/i', $name ) ){
            $last_name = sanitize_text_field( $value );
        }

        if ( preg_match( '/phone/i', $name ) || preg_match( '/text/i', $name ) ){
            $phone = sanitize_text_field( $value );
        }
    }

    if ( ! $email )
        return;

    $id = wpfn_quick_add_contact( $email, $first_name, $last_name, $phone );

    if ( ! $id ){
        $contact = new WPFN_Contact( $email );
        $id = $contact->getId();
    }

    do_action( 'wpfn_caught_form_submit', $id );
}

add_action( 'init', 'wpfn_form_submit_listener' );