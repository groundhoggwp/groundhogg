<?php

function wpfn_form_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'fields' => 'first,last,email,phone',
        'submit' => __( 'Submit' ),
        'success' => '',
        'labels' => 'on',
        'id' => 0
    ), $atts );

    $fields = array_map( 'trim', explode( ',', $a['fields'] ) );

    $form = '<div class="gh-form-wrapper">';

    $form .= "<form method='post' class='gh-form' action='" . esc_url_raw( $a['success'] ) . "'>";

    $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

    $form .="<input type='hidden' name='step_id' value='" . $a['id'] . "'>";

    foreach ( $fields as $type ){

        $form .= '<div class="gh-form-field"><p>';

        $id = uniqid( 'gh-' );

        switch ( $type ) {
            case 'first':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'First Name', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="text" name="first_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . __( 'First Name', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'last':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Last Name', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="text" name="last_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . __( 'Last Name', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'email':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Email', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="email" name="email" id="' . $id . '" title="' . __( 'Email', 'groundhogg' ) . '" placeholder="' . __( 'Email', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'phone':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . __( 'Phone', 'groundhogg' );
                $form .= ' <input class="gh-form-input" type="tel" name="phone" id="' . $id . '" title="' . __( 'Phone', 'groundhogg' ) . '" placeholder="' . __( 'Phone', 'groundhogg' ). '" required>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
        }

        $form .= '</p></div>';
    }

    $form .= "<div class='gh-submit-field'><p><input type='submit' name='submit' value='" . $a['submit'] . "'></p></div>";
    $form .= '</form>';
    $form .= '</div>';

    return $form;
}

add_shortcode( 'gh_form', 'wpfn_form_shortcode' );


/**
 * Listens for basic contact information whenever the post variable is exists.
 */
function wpfn_form_submit_listener()
{
    /* verify real user */
    if ( ! isset( $_POST[ 'gh_submit_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'gh_submit_nonce' ], 'gh_submit' ) )
        return;

    /* verify email exists */
    if ( ! isset( $_POST['email'] ) || ! isset( $_POST[ 'step_id' ] ) )
        return;

    if ( isset( $_POST[ 'first_name' ] ) )
        $args['first'] = sanitize_text_field( $_POST[ 'first_name' ] );

    if ( isset( $_POST[ 'last_name' ] ) )
        $args['last'] = sanitize_text_field( $_POST[ 'last_name' ] );

    if ( isset( $_POST[ 'email' ] ) )
        $args['email'] = sanitize_email( $_POST[ 'email' ] );

    if ( isset( $_POST[ 'phone' ] ) )
        $args['phone'] = sanitize_text_field( $_POST[ 'phone' ] );


    wp_parse_args( $args, array(
        'first' => '',
        'last'  => '',
        'email' => '',
        'phone' => '',
    ));

    $contact = new WPFN_Contact( $args[ 'email' ] );

    if ( $contact->getEmail() ){

        wpfn_update_contact( $contact->getId(), 'first_name', $args['first'] );
        wpfn_update_contact( $contact->getId(), 'last_name', $args['last'] );
        wpfn_update_contact_meta( $contact->getId(), 'phone_primary', $args['phone'] );
        $id = $contact->getId();

    } else {
        $id = wpfn_quick_add_contact( $args['email'], $args['first'], $args['last'], $args['phone'] );
    }

    $step = intval( $_POST[ 'step_id' ] );

    do_action( 'wpfn_form_submit', $step, $id );
}

add_action( 'init', 'wpfn_form_submit_listener' );