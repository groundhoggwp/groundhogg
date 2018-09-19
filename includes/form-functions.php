<?php

/**
 * Check if Recaptcha is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpgh_is_recaptcha_enabled()
{
    return ( 'on' == get_option( 'gh_enable_recaptcha', '' ) );
}

/**
 * Output the form html based on the settings.
 *
 * @param $atts array the shortcode attributes
 * @return string the form html
 */
function wpgh_form_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'fields' => 'first,last,email,phone,terms',
        'required' => 'first,last,email,phone,terms',
        'submit' => __( 'Submit' ),
        'success' => '',
        'labels' => 'on',
        'id' => 0,
        'classes' => '',
        'first' => __( 'First Name' ),
        'last' => __( 'Last Name' ),
        'email' => __( 'Email' ),
        'phone' => __( 'Phone' ),
        'terms' =>__( 'I agree to the Terms of Service.' , 'groundhogg' ),
        'gdpr' => __( 'I consent to receive marketing & transactional information from ' . get_option( 'gh_business_name' ) . '.' , 'groundhogg' )
    ), $atts );

    $fields = array_map( 'trim', explode( ',', $a['fields'] ) );
    $required_fields = array_map( 'trim', explode( ',', $a['required'] ) );

    $form = '<div class="gh-form-wrapper">';

    $form .= "<form method='post' class='gh-form " . $a[ 'classes' ] ."' action='" . esc_url_raw( $a['success'] ) . "'>";

    $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

    $form .="<input type='hidden' name='step_id' value='" . $a['id'] . "'>";

    foreach ( $fields as $type ){

        $form .= '<div class="gh-form-field"><p>';

        $id = uniqid( 'gh-' );

        $required = in_array( $type, $required_fields )? 'required' : "" ;

        switch ( $type ) {
            case 'first':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'first' ];
                $form .= ' <input class="gh-form-input" type="text" name="first_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . $a[ 'first' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'last':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' .  $a[ 'last' ];
                $form .= ' <input class="gh-form-input" type="text" name="last_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . $a[ 'last' ]. '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'email':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'email' ];
                $form .= ' <input class="gh-form-input" type="email" name="email" id="' . $id . '" title="' . $a[ 'email' ] . '" placeholder="' . $a[ 'email' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'phone':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'phone' ];
                $form .= ' <input class="gh-form-input" type="tel" name="phone" id="' . $id . '" title="' . __( 'Phone' ) . '" placeholder="' . $a[ 'phone' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'terms':
                $form .= '<label>';
                $form .= ' <input class="gh-form-input" type="checkbox" name="agree_terms" id="' . $id . '" title="' . __( 'Terms Agreement' ) . '" '.$required. '> ';
                $form .=  $a[ 'terms' ] . '</label>';
                break;
        }
        $form .= '</p></div>';
    }

    if ( wpgh_is_gdpr() )
    {

        $id = uniqid( 'gh-' );

        $form .= '<div class="gh-consent-field"><p>';

        $form .= '<label>';
        $form .= ' <input class="gh-form-input" type="checkbox" name="gdpr_consent" id="' . $id . '" title="' . __( 'Explicit Consent', 'groundhogg' ) . '" required> ';
        $form .=  $a[ 'gdpr' ] . '</label>';

        $form .= '</p></div>';
    }

    if ( wpgh_is_recaptcha_enabled() )
    {
        wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js' );
        $form .= '<div class="gh-recaptcha-field"><p>';
        $form .= sprintf( '<div class="g-recaptcha" data-sitekey="%s"></div>', get_option( 'gh_recaptcha_site_key', '' ) );
        $form .= '</p></div>';
    }

    $form = apply_filters( 'wpgh_form_shortcode', $form );

    $form .= "<div class='gh-submit-field'><p><input type='submit' name='submit' value='" . $a['submit'] . "'></p></div>";
    $form .= '</form>';
    $form .= '</div>';

    return $form;
}

add_shortcode( 'gh_form', 'wpgh_form_shortcode' );


/**
 * Listens for basic contact information whenever the post variable is exists.
 */
function wpgh_form_submit_listener()
{
    /* verify real user */
    if ( ! isset( $_POST[ 'gh_submit_nonce' ] ) )
        return;

    if( ! wp_verify_nonce( $_POST[ 'gh_submit_nonce' ], 'gh_submit' ) )
        wp_redirect( wp_get_referer() );

    if ( wpgh_is_gdpr() ){
        if ( ! isset( $_POST[ 'gdpr_consent' ] ) )
            wp_die( __( 'You must consent to sign up.', 'groundhogg' ) );
    }

    if ( wpgh_is_recaptcha_enabled() )
    {
        if ( ! isset( $_POST[ 'g-recaptcha-response' ] ) )
            wp_redirect( wp_get_referer() );

        $file_name = sprintf( "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s", get_option( 'gh_recaptcha_secret_key' ), $_POST['g-recaptcha-response'] );

        $verifyResponse = file_get_contents( $file_name );
        $responseData = json_decode( $verifyResponse );
        if( $responseData->success == false ){
            wp_die( __( 'You did not pass the robot test.', 'groundhogg' ) );
        }
    }

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

    if ( ! is_email( $args['email'] ) )
        wp_redirect( wp_get_referer() );

    $args = wp_parse_args( $args, array(
        'first' => '',
        'last'  => '',
        'email' => '',
        'phone' => '',
    ));

    $id = wpgh_quick_add_contact( $args['email'], $args['first'], $args['last'], $args['phone'] );

    if ( ! $id ) {
        wp_die( __( 'Something went wrong... ' ) );
    }

    if ( is_wp_error( $id ) )
        wp_die( $id );

    $contact = new WPGH_Contact( $id );

    /* Set the IP address of the contact */
    wpgh_update_contact_meta( $id, 'ip_address', wpgh_get_visitor_ip() );

    /* Set the Leadsource if it doesn't exist */
    if ( ! wpgh_get_contact_meta( $id, 'source_page', true) )
        wpgh_update_contact_meta( $id, 'source_page', wp_get_referer() );

    if ( isset( $_COOKIE[ 'gh_leadsource' ] ) )
        wpgh_update_contact_meta( $id, 'leadsource', esc_url_raw( $_COOKIE[ 'gh_leadsource' ] ) );

    /* if the contact previously unsubscribed, set them to unconfirmed. */
    if ( $contact->get_optin_status() === WPGH_UNSUBSCRIBED )
        wpgh_update_contact( $id, 'optin_status', WPGH_UNCONFIRMED );

    /* get the terms agreement */
    if ( isset( $_POST[ 'agree_terms' ] ) ){
        wpgh_update_contact_meta( $id, 'terms_agreement', 'yes' );
        wpgh_update_contact_meta( $id, 'terms_agreement_date', date_i18n( get_option( 'date_format' ) ) );
        do_action( 'wpgh_agreed_to_terms', $contact->get_id() );
    }

    /* if gdpr is enabled, make sure that the consent box is checked */
    if ( wpgh_is_gdpr() && isset( $_POST[ 'gdpr_consent' ] ) ){
        wpgh_update_contact_meta( $id, 'gdpr_consent', 'yes' );
        wpgh_update_contact_meta( $id, 'gdpr_consent_date', date_i18n( get_option( 'date_format' ) ) );
        do_action( 'wpgh_gdpr_consented', $contact->get_id() );
    }

    /* set the last optin date */
    wpgh_update_contact_meta( $id, 'last_optin', time() );

    $step = intval( $_POST[ 'step_id' ] );

    /* make sure the funnel for the step is active*/
    if ( ! wpgh_get_funnel_step_by_id( $step ) || ! wpgh_is_funnel_active( wpgh_get_step_funnel( $step ) ) )
        wp_die( __( 'This form is not accepting submissions right now.', 'groundhogg' ) );

    do_action( 'wpgh_form_submit', $step, $id );

    /* redirect to ensure cookie is set and can be used on the following page*/
    wp_redirect( $_SERVER['REQUEST_URI'] );
    die();
}

add_action( 'init', 'wpgh_form_submit_listener' );

/**
 * Ouput the html for the email preferences form.
 *
 * @return string
 */
function wpgh_email_preferences_form()
{

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return __( 'No email to manage.' );

    ob_start();

    ?>
    <div class="gh-form-wrapper">
        <p><?php _e( 'Hi' )?> <strong><?php echo $contact->get_first(); ?></strong>,</p>
        <p><?php _e( 'You are managing your email preferences for the email address: ', 'groundhogg' ) ?> <strong><?php echo $contact->get_email(); ?></strong></p>
        <form id="email-preferences" class="gh-form" method="post" action="">
            <?php wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce' ) ?>
            <?php if ( ! empty( $_POST ) ):
                ?><div class="gh-notice"><p><?php _e( 'Preferences Updated!', 'groundhogg' ); ?></p></div><?php
            endif;
            ?>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="none" required> <?php _e( apply_filters( 'gh_no_limits_preferences_text', 'I love you guys. Send email whenever you want!' ), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="weekly" > <?php _e( apply_filters( 'gh_weekly_preferences_text', 'It\'s a bit much. Start sending me emails weekly.' ), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="monthly" > <?php _e( apply_filters( 'gh_monthly_preferences_text','Distance makes the heart grow fonder. Only send emails monthly.'), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="unsubscribe" > <?php _e( apply_filters( 'gh_unsubscribe_preferences_text','I no longer wish to receive any emails. Unsubscribe me!'), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <input type='submit' name='change_preferences' value='<?php _e( apply_filters( 'gh_change_preferences_text', 'Change Preferences' ),'groundhogg'); ?>' >
                    <?php if ( wpgh_is_gdpr() ):?>
                        <input type='submit' name='delete_everything' value='<?php _e(apply_filters( 'gh_gdpr_delete_prteferences_text', 'Delete Everything You Know About Me' ), 'groundhogg'); ?>' >
                    <?php endif; ?>
                </p>
            </div>
        </form>
    </div>

    <?php

    $form = ob_get_contents();

    ob_end_clean();

    return $form;

}

add_shortcode( 'gh_email_preferences', 'wpgh_email_preferences_form' );

/**
 * Process changes to the subscription status of a contact.
 */
function wpgh_process_email_preferences_changes()
{
    if ( ! isset( $_POST[ 'email_preferences_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'email_preferences_nonce' ], 'change_email_preferences' ) )
        return;

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return;

    if ( isset( $_POST[ 'delete_everything' ] ) )
    {

        do_action( 'wpgh_delete_everything', $contact->get_id() );

        wpgh_delete_contact( $contact->get_id() );

        $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

        do_action( 'wpgh_preference_unsubscribe', $contact->get_id() );

        wp_redirect( $unsub_page );
        die();
    }

    $preference = isset( $_POST[ 'preference' ] ) ? $_POST[ 'preference' ] : '';

    switch ( $preference ){
        case 'none':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_CONFIRMED );

            do_action( 'wpgh_preference_none', $contact->get_id() );

            break;
        case 'weekly':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_WEEKLY );

            do_action( 'wpgh_preference_weekly', $contact->get_id() );

            break;
        case 'monthly':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_MONTHLY );

            do_action( 'wpgh_preference_monthly', $contact->get_id() );

            break;
        case 'unsubscribe':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_UNSUBSCRIBED );

            $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

            do_action( 'wpgh_preference_unsubscribe', $contact->get_id() );

            wp_redirect( $unsub_page );
            die();
            break;
    }
}

add_action( 'init', 'wpgh_process_email_preferences_changes' );
