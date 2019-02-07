<?php
/**
 * Submission
 *
 * Process a from submission if a form submission is in progress.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Submission
{

    /**
     * Acts as an alias for the $_POST variable
     *
     * @var array
     */
    public $data;

    /**
     * These are the EXPECTED Fields given by the form shortcode present
     *
     * @var array
     */
    public $fields;

    /**
     * The form config array object
     *
     * @var array
     */
    public $config;

    /**
     * @var string this is set to the referer which is also the source page
     */
    public $source;

    /**
     * @var int this ends up being the ID of the form
     */
    public $id;

	/**
	 * @var WPGH_Step the funnel's step, mostly here to use the is_active()
	 *
	 */
    public $step;

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
        if ( isset( $_POST[ 'gh_submit_nonce' ] ) ) {
//            add_action( 'init', array( $this, 'setup' ) );
            add_action( 'init', array( $this, 'process' ) );
        }
    }

    /**
     * Setup the vars.
     */
    public function setup(){

        $this->data = $_POST;

        $this->source = wpgh_get_referer();

        /* set the form ID as the submission ID */

        if ( isset( $this->step_id ) ) {

            $this->id = $this->step_id;

            $this->step = new WPGH_Step( $this->id );

            unset( $this->step_id );

            if ( ! $this->step->is_active() ){
                $this->leave( 'This form is not accepting submissions.' );
            }

            $this->fields = $this->step->get_meta( 'expected_fields' );
            $this->config = $this->step->get_meta( 'config' );

        } else {
            $this->id = 0;
        }

        if ( empty( $this->fields ) ){
            $this->leave( 'No fields available.' );
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

            $this->leave( 'Failed security verification.' );
            // or $this->go_back();

        }

        unset( $_POST[ 'gh_submit_nonce' ] );

        if ( empty( $this->fields ) ) {

            $this->leave( 'Expected a list of valid fields but got none.' );

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
                wpgh_get_option( 'gh_recaptcha_secret_key' ),
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

        if( !class_exists( 'Browser' ) )
            require_once WPGH_PLUGIN_DIR . 'includes/lib/browser.php';

        $browser = new Browser();

        if ( $browser->isRobot() || $browser->isAol() ){
            $this->leave( 'Are you a robot? It sure seems like it...' );
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
    public function has_field( $field )
    {
        return in_array( trim( $field ), $this->fields );
    }

    /**
     * Return the config object or false if it doesn't exist
     *
     * @param $field
     * @return bool|mixed
     */
    public function get_field_config( $field )
    {
        return isset( $this->config[$field] )? $this->config[$field] : false;
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
        $blacklist = wpgh_get_option( 'blacklist_keys', false );

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
     * @return WPGH_Contact|bool $contact the $contact
     *
     */
    public function create_contact()
    {
        if ( isset( $this->email ) ){
            $email = sanitize_email( $this->email );

            if ( empty( $email ) ){
                $this->leave( 'Please provide a valid email address' );
            }

//            if ( strpos( $this->first_name, ' ' ) !== false )

            $args = array(
                'email' => $email,
                'first_name' => sanitize_text_field( stripslashes( $this->first_name ) ),
                'last_name' => sanitize_text_field( stripslashes( $this->last_name ) )
            );

            if ( is_user_logged_in() ){
                $args[ 'user_id' ] = get_current_user_id();
            } else {
                $user = get_user_by( 'email', $email );
                if ( $user ){
                    $args[ 'user_id' ] = $user->ID;
                }
            }

            if ( WPGH()->contacts->exists( $email ) ){

//            var_dump( $args );

                $this->contact = new WPGH_Contact( $email );
                $this->contact->update( $args );

            } else{
                $cid = WPGH()->contacts->add( $args );

                if ( ! $cid ){
                    $this->leave();
                }

                $this->contact = new WPGH_Contact( $cid );
            }

            //unset used DATA from the data prop
            unset( $this->first_name );
            unset( $this->last_name );
            unset( $this->email );

            return $this->contact;

        } else if ( WPGH()->tracking->get_contact() instanceof WPGH_Contact) {

            $this->contact = WPGH()->tracking->get_contact();

            return $this->contact;

        } else {

            wp_die( 'Could not access or create a contact record.' );

        }

        return false;

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
    	$this->setup();

        if ( ! $this->verify() ) {
            $this->leave();
        }

        $c = $this->create_contact();

        if ( ! $c ){
            wp_die( 'Oops' );
        }

        $c->update_meta( 'ip_address', wpgh_get_visitor_ip() );

        if ( ! $c->get_meta( 'lead_source' ) ){

            $c->update_meta( 'lead_source', WPGH()->tracking->lead_source );

        }

        if ( ! $c->get_meta( 'source_page' ) ){

            $c->update_meta( 'source_page', $this->source );

        }

        if ( isset( $this->agree_terms ) ){

            $c->update_meta( 'terms_agreement', 'yes' );
            $c->update_meta( 'terms_agreement_date', date_i18n( wpgh_get_option( 'date_format' ) ) );

            do_action( 'wpgh_agreed_to_terms', $c, $this );

            unset( $this->agree_terms );

        }

        if ( isset( $this->gdpr_consent ) ){

            $c->update_meta( 'gdpr_consent', 'yes' );
            $c->update_meta( 'gdpr_consent_date', date_i18n( wpgh_get_option( 'date_format' ) ) );

            do_action( 'wpgh_gdpr_consented', $c, $this );

            unset( $this->gdpr_consent );

        }

        /* If the contact previously unsubed then reopt them back in.  */
        if ( $this->contact->optin_status === WPGH_UNSUBSCRIBED ) {
            $this->contact->change_marketing_preference(WPGH_UNCONFIRMED );
        }

        $c->update_meta( 'last_optin', time() );

        foreach ( $this->data as $key => $value ) {

            $key = sanitize_key( $key );

            if ( is_array( $value ) ){
                $value = implode( ', ', $value );
            }

            if ( strpos( $value, PHP_EOL  ) !== false ){
                $value = sanitize_textarea_field( stripslashes( $value ) );
            } else {
                $value = sanitize_text_field( stripslashes( $value ) );
            }

            if ( $this->has_field( $key ) ) {

                /* NEW: Pass the field's config object to a filter to sanitize it */
                if( $config = $this->get_field_config( $key ) ){
                    $value = apply_filters( 'wpgh_sanitize_submit_value', $value, $config );
                    $c->update_meta( $key, $value );
                }

            }

        }

        if ( ! empty( $_FILES ) ){
            $this->upload_files();
        }

        if ( isset( $_POST[ 'email_preferences_nonce' ] ) ){
            $this->process_email_preference_changes();
        }

	    $success = apply_filters( 'wpgh_form_submit_feed', true, $this->id, $c, $this );

        if ( $success && ! is_wp_error( $success ) ){

	        if ( $this->id ){
		        do_action( 'wpgh_form_submit', $this->id, $c, $this );
	        }

	        /* redirect to ensure cookie is set and can be used on the following page*/
	        $success_page = $this->step->get_meta('success_page' );

	        wp_redirect( $success_page );

	        die();

        } else if ( is_wp_error( $success ) ){

        	/* Failed: handle WP_Error */

        } else {

        	/* Default failure handling. */

        }


    }

    /**
     * Process any file uploads tht may be present.
     *
     * @return bool
     */
    public function upload_files()
    {

        if ( empty( $_FILES ) ){
            return false;
        }

        foreach ( $_FILES as $key => $file ) {

            $key = sanitize_key( $key );

            if ($this->has_field( $key ) ) {

                if ($config = $this->get_field_config($key)) {

                    if ($config['type'] === 'file') {

                        if ( $file = $this->handle_file_upload( $key, $config ) ) {

                            if ( is_wp_error($file) || ! is_array( $file ) ) {
                                wp_die($file);
                            }

                            $files = $this->contact->get_meta('files');

                            if (!$files) {
                                $files = array();
                            }

                            $file[ 'key' ] = $key;
                            /* Compat for local host WP filesystems */
                            $file = array_map( 'wp_normalize_path', $file );

                            $files[ $key ] = $file;
                            $this->contact->update_meta('files', $files);
                            $this->contact->update_meta($key, $file['url']);

                        } else {

                            wp_die( __( 'Could not upload file.' ) );

                        }

                    }

                }

            }

        }

        return true;

    }

    /**
     * Change the default upload directory
     *
     * @param $param
     * @return mixed
     */
    public function files_upload_dir( $param )
    {
        $mydir = '/groundhogg';

        if ( is_multisite() ){
            $mydir .= '/' . get_current_blog_id();
        }

        if ( $this->contact ){

            $mydir .= '/' . wpgh_encrypt_decrypt( $this->contact->email );

        } else if ( isset( $_GET[ 'id' ] ) ) {

            $id = intval( $_GET[ 'id' ] );

            $contact = wpgh_get_contact( $id );

            $mydir .= '/' . wpgh_encrypt_decrypt( $contact->email );

        } else {

            $user = wp_get_current_user();
            $mydir .= '/' . wpgh_encrypt_decrypt( $user->user_email );

        }

        $param['path'] = $param['basedir'] . $mydir;
        $param['url'] = $param['baseurl'] . $mydir;
        $param['subdir'] = $mydir;
        /* Compat for windows file systems */
//        $param = array_map( 'wp_normalize_path', $param );

        return $param;
    }

    /**
     * Upload a file to the Groundhogg file directory
     *
     * @param $key
     * @param $config
     * @return array|bool|WP_Error
     */
    private function handle_file_upload( $key, $config )
    {
        $file = $_FILES[ $key ];
        $size = $file[ 'size' ];

        if ( intval( $size ) > intval( $config[ 'max_file_size' ] ) ){
            return new WP_Error( 'FILE_TOO_BIG', __( 'The file you have uploaded is too big.' ) );
        }

        $extension = wp_check_filetype( $file[ 'name' ] );
        $mimes = explode( ',', $config[ 'file_types' ] );

        if ( ! in_array( '.' . $extension[ 'ext' ], $mimes ) ){
            return new WP_Error( 'INCORRECT_MIME', __( 'You are not permitted to upload this type of file.' ) );
        }

        $upload_overrides = array( 'test_form' => false );

        if ( !function_exists('wp_handle_upload') ) {
            require_once( ABSPATH . '/wp-admin/includes/file.php' );
        }

        add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
        $mfile = wp_handle_upload( $file, $upload_overrides );
        remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

        if( isset( $mfile['error'] ) ) {

            if ( empty( $mfile[ 'error' ] ) ){
                $mfile[ 'error' ] = __( 'Could not upload file.' );
            }

            return new WP_Error( 'BAD_UPLOAD', $mfile['error'] );
        }

        return $mfile;
    }

    /**
     * Get an array of allowed mime types for WP
     *
     * @param array $file_extensions
     * @return array
     */
    private function get_allow_mime_types( $file_extensions=array() )
    {

        $mimes = array();

        $wp_mimes = get_allowed_mime_types();

        if ( ! empty( $file_extensions ) ){

            foreach ( $file_extensions as $ext ){

                $ext = str_replace(  '.', '', $ext );

                foreach ( $wp_mimes as $exts => $mime ){

                    if ( preg_match( '/' . $ext . '/', $exts ) ){

                        $mimes[ $exts ] = $mime;

                    }

                    break;

                }

                $mimes[ $ext ] = 'application/text';

            }

            return $mimes;

        } else {

            return $wp_mimes;

        }

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

        //$contact->update_meta( 'preferences_changed', time() );

        if ( isset( $_POST[ 'delete_everything' ] ) && $_POST[ 'delete_everything' ] === 'yes' ) {

            do_action( 'wpgh_delete_everything', $contact->ID );

            WPGH()->contacts->delete( $contact->ID );

            $unsub_page = get_permalink( wpgh_get_option( 'gh_unsubscribe_page' ) );

            do_action( 'wpgh_preference_unsubscribe', $contact->ID );

            wp_redirect( $unsub_page );

            die();
        }

        $preference = isset( $_POST[ 'email_preferences' ] ) ? sanitize_text_field( $_POST[ 'email_preferences' ] ): '';

        switch ( $preference ){
            case 'none':
                if ( $contact->optin_status !== WPGH_CONFIRMED ){
                    /* If they already confirmed DON'T CHANGE IT! */
                    $contact->change_marketing_preference( WPGH_UNCONFIRMED );
                }

                break;
            case 'weekly':
                $contact->change_marketing_preference( WPGH_WEEKLY );
                break;
            case 'monthly':
                $contact->change_marketing_preference( WPGH_MONTHLY );
                break;
            case 'unsubscribe':
                $contact->unsubscribe();
                $unsub_page = get_permalink( wpgh_get_option( 'gh_unsubscribe_page' ) );
                wp_redirect( $unsub_page );
                die();

                break;
        }

    }


}