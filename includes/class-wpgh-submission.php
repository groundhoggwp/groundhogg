<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-02
 * Time: 8:24 AM
 */


class WPGH_Submission
{

    /**
     * Acts as an alias for the $_POST variable
     *
     * @var array
     */
    private $data;

    /**
     * These are the EXPECTED Fields given by the form shortcode present
     *
     * @var array
     */
    private $fields;

    /**
     * @var string this is set to the referrer which is also the source page
     */
    private $source;

    /**
     * @var int this ends up being the ID of the form
     */
    private $id;

    /**
     * @var WPGH_Contact
     */
    public $contact;

    /**
     * WPGH_Submission constructor.
     *
     * If the GH_SUBMIT nonce is active than a from submission is
     * underway and must be completed.
     */
    public function __construct()
    {
        if ( isset( $_POST[ 'gh_submit_nonce' ] ) )
        {

            $this->data = $_POST;

            $this->source = wp_get_referer();

            /* set the expected fields for the submission */
            $this->fields = get_post_meta(
                url_to_postid( $this->source ),
                'gh_fields_' . $this->id,
                true

            );

            /* set the form ID as the submission ID */

            if ( isset( $this->step_id ) ) {

                $this->id = $this->step_id;

                unset( $this->step_id );

            } else {

                $this->die();

            }

            add_action( 'init', array( $this, 'process' ) );

        }
    }

    /**
     * Magic method GET to access $_POST
     *
     * @param $key
     * @return bool
     */
    public function __get( $key )
    {
        if ( property_exists( $this, $key ) ) {

            return $this->$key;

        } else if ( isset( $this->data[ $key ] ) ) {

            return $this->data[ $key ];

        }

        return false;
    }

    /**
     * Set the data to the given value
     *
     * @param $key
     * @param $value
     */
    public function __set( $key, $value )
    {

        $this->data[ $key ] = $value;

    }

    /**
     * IS this data set
     *
     * @param $name
     * @return bool;
     */
    public function __isset( $name )
    {
        return isset( $this->data[ $name ] );
    }

    /**
     * Unset the data
     *
     * @param $name
     */
    public function __unset( $name )
    {
        unset( $this->data[ $name ] );
    }

    /**
     * Verify the visitor with the nonce check
     * if it fails, return to the previous page.
     *
     * Also performs other various checks,
     * GDPR, reCaptcha & Terms are both checked here as well.
     *
     * @return true on success
     */
    public function verify()
    {
        if( ! wp_verify_nonce( $_POST[ 'gh_submit_nonce' ], 'gh_submit' ) ) {

            $this->leave();
            // or $this->go_back();

        }

        unset( $_POST[ 'gh_submit_nonce' ] );

        if ( empty( $this->fields ) ) {

            $this->leave();

        }

        //todo consider GDPR checking in the future
        if ( wpgh_is_gdpr()
            && $this->has_field( 'gdpr_consent' )
            && ! isset( $this->gdpr_consent )
        ) {

            $this->leave( 'You must provide consent to sign up.' );

        }

        //todo check recaptcha elsewhere?
        if ( wpgh_is_recaptcha_enabled()
            && $this->has_field( 'g-recaptcha' )
        ) {

            if ( ! isset( $this->data[ 'g-recaptcha-response' ] ) ) {

                $this->leave( 'You did not pass the robot test.' );

            }

            $file_name = sprintf(
                "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
                get_option( 'gh_recaptcha_secret_key' ),
                $this->data[ 'g-recaptcha-response' ]
            );

            $verifyResponse = file_get_contents( $file_name );
            $responseData = json_decode( $verifyResponse );

            if( $responseData->success == false ){

                $this->leave( 'You did not pass the robot test.' );

            }
        }

        //todo check terms elswehere?
        if ( $this->has_field( 'agree_terms' )
            && ! isset( $this->agree_terms )
        ){

            $this->leave( 'Your must agree to the terms of service.' );

        }

        // Check the IP against the spam list
        if ( $this->is_spam( wpgh_get_visitor_ip() ) ) {

            $this->leave();

        }

        // Check all the POST data against the blacklist
        foreach ( $this->data as $key => $value ) {

            if ( $this->is_spam( $value ) ){

                $this->leave();

            }

        }

        return apply_filters( 'wpgh_submission_verify_check', true, $this );
    }

    /**
     * Return whether the form has a given field
     *
     * @param $field string The field in quetion
     *
     * @return bool true if field exists, false otherwise
     */
    private function has_field( $field )
    {
        return in_array( trim( $field ), $this->fields );
    }

    /**
     * Return to the previous page
     */
    private function go_back()
    {
        wp_redirect( $this->source );

        die();
    }

    /**
     * Exit our of the submission process
     * Execute WP_DIE with a custom message
     *
     * @param $message string
     */
    private function leave( $message = '' )
    {

        if ( empty( $message ) )
        {
            $message = 'Unable to complete your request. Please contact the site administrator for assistance.';
        }

        wp_die(
            __( $message, 'groundhogg' ),
            __( 'Submission failed...', 'groundhogg' )
        );
    }

    /**
     * Check a given value for spam.
     * If it's in the blacklist, mark the contact as spam and die
     *
     * @param $value mixed
     * @return bool true if spam | false if pass
     */
    public function is_spam( $value )
    {
        $blacklist = get_option( 'blacklist_keys', false );

        if ( ! empty( $blacklist ) ) {

            $keys = explode(PHP_EOL, $blacklist );

            foreach ($keys as $key) {
                if ( strpos( $value, $key ) !== false ){

                    if( apply_filters( 'wpgh_spam_filter', true, $this, $value ) ){

                        return false;

                    }
                }
            }
        }

        return false;

    }

    /**
     * Create the contact record and return back the contact ID
     *
     * @return WPGH_Contact $contact the $contact
     */
    public function create_contact()
    {

        $cid = WPGH()->contacts->add( $this->data );

        if ( ! $cid ){

            $this->leave();

        }

        $this->contact = new WPGH_Contact( $cid );

        //unset used DATA from the data prop
        unset( $this->first_name );
        unset( $this->last_name );
        unset( $this->email );

        return $this->contact;

    }

    /**
     * Link a user record to the contact if it exists.
     */
    public function link_user()
    {

        $user = get_user_by( 'email', $this->email );

        if ( $user ){

            $this->contact->update( array( 'user_id', $user->ID ) );

        } else if ( is_user_logged_in() ) {

            $this->contact->update( array( 'user_id', get_current_user_id() ) );

        }

    }

    /**
     * Process the submission.
     *
     * Verify the submission should be processed, if not exit out and die.
     * Set the basic META fields
     *  leadsource
     *  source page
     *  GDPR
     *  Terms
     *  Ip address
     *
     * Add the rest of the meta from the DATA
     */
    public function process()
    {

        if ( ! $this->verify() ) {

            $this->leave();

        }

        $c = $this->create_contact();

        $this->link_user();

        $c->update_meta( 'ip_address', wpgh_get_visitor_ip() );

        if ( ! $c->get_meta( 'lead_source' ) ){

            $c->update_meta( 'lead_source', WPGH()->tracking->lead_source );

        }

        if ( ! $c->get_meta( 'source_page' ) ){

            $c->update_meta( 'source_page', $this->source );

        }

        if ( isset( $this->agree_terms ) ){

            $c->update_meta( 'terms_agreement', 'yes' );
            $c->update_meta( 'terms_agreement_date', date_i18n( get_option( 'date_format' ) ) );

            do_action( 'wpgh_agreed_to_terms', $c, $this );

            unset( $this->agree_terms );

        }

        if ( isset( $this->gdpr_consent ) ){

            $c->update_meta( 'gdpr_consent', 'yes' );
            $c->update_meta( 'gdpr_consent_date', date_i18n( get_option( 'date_format' ) ) );

            do_action( 'wpgh_gdpr_consented', $c, $this );

            unset( $this->gdpr_consent );

        }

        if ( ! $this->contact->optin_status === WPGH_UNSUBSCRIBED ) {

            $this->contact->update(
                array(
                    'optin_status' => WPGH_UNCONFIRMED
                )
            );

        }

        foreach ( $this->data as $key => $value ) {

            $key = sanitize_key( $key );
            $value = sanitize_textarea_field( $key );

            if ( $this->has_field( $key ) ) {

                $c->update_meta( $key, $value );

            }

        }

        if ( isset( $_POST[ 'email_preferences_nonce' ] ) ){

            $this->process_email_preference_changes();

        }

        do_action( 'wpgh_form_submit', $this->id, $c, $this );

        /* redirect to ensure cookie is set and can be used on the following page*/
        wp_redirect( $_SERVER['REQUEST_URI'] );

        die();

    }

    /**
     * If the contact is changing their preferences, process that change as well.
     */
    private function process_email_preference_changes()
    {
        if ( ! isset( $_POST[ 'email_preferences_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'email_preferences_nonce' ], 'change_email_preferences' ) )
            return;

        $contact = $this->contact;

        if ( ! $contact )
            return;

        if ( isset( $_POST[ 'delete_everything' ] ) && $_POST[ 'delete_everything' ] === 'yes' ) {

            do_action( 'wpgh_delete_everything', $contact->ID );

            WPGH()->contacts->delete( $contact->ID );

            $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

            do_action( 'wpgh_preference_unsubscribe', $contact->ID );

            wp_redirect( $unsub_page );

            die();
        }

        $preference = isset( $_POST[ 'email_preferences' ] ) ? sanitize_text_field( $_POST[ 'email_preferences' ] ): '';

        switch ( $preference ){
            case 'none':

                $args = array( 'optin_status' => WPGH_UNCONFIRMED );
                $contact->update( $args );

                do_action( 'wpgh_preference_none', $contact->ID );

                break;
            case 'weekly':

                $args = array( 'optin_status' => WPGH_MONTHLY );
                $contact->update( $args );

                do_action( 'wpgh_preference_weekly', $contact->ID );

                break;
            case 'monthly':

                $args = array( 'optin_status' => WPGH_MONTHLY );
                $contact->update( $args );

                do_action( 'wpgh_preference_monthly', $contact->ID );

                break;
            case 'unsubscribe':

                $args = array( 'optin_status' => WPGH_UNSUBSCRIBED );
                $contact->update( $args );

                $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

                do_action( 'wpgh_preference_unsubscribe', $contact->ID );

                wp_redirect( $unsub_page );
                die();

                break;
        }
    }


}