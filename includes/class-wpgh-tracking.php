<?php
/**
 * Tracking
 *
 * Maintain information about the contact, events, funnels, etc...
 * Uses cookies.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Tracking
{
    /**
     * This is a cookie that will be in the contact's browser
     */
    const COOKIE = 'gh_tracking';

    /**
     * Cookie expiry time in days
     *
     * @var int
     */
    private $cookie_expiry = 7;

    /**
     * @var WPGH_Contact the current contact if it exists
     */
    private $contact;

    /**
     * @var object the current funnel if it exists
     */
    private $funnel;

    /**
     * @var object the step if it exists
     */
    private $step;

    /**
     * @var object|WPGH_Email the email if it exists
     */
    private $email;

    /**
     * @var object the current event if it exists
     */
    private $event;

    /**
     * @var string the url to redirect to... (optional)
     */
    private $ref = '';

    /**
     * @var string the referring url
     */
    public $lead_source = '';

    /**
     * @var string the UTM campaign
     */
    public $utm_campaign = '';

    /**
     * @var string  the UTM source
     */
    public $utm_source = '';

    /**
     * @var string the UTM medium
     */
    public $utm_medium = '';

    /**
     * @var string the UTM content
     */
    public $utm_content = '';

    /**
     * @var string the UTM term
     */
    public $utm_term = '';

    /**
     * Two vars to tell which is the current action being taken by the contact
     *
     * @var bool
     * @var bool
     */
    private $doing_open = false;
    private $doing_click = false;
    private $doing_confirmation = false;

    /**
     * WPGH_Tracking constructor.
     *
     *
     * Look at the current URL and depending on that setup the vars and enqueue the appropriate elements if any
     */
    public function __construct()
    {

        if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-tracking/email/click' ) !== false ){

            add_action( 'plugins_loaded', array( $this, 'fix_tracking_ssl' ) );
            add_action( 'plugins_loaded', array( $this, 'setup_url_vars' ) );
            add_action( 'plugins_loaded', array( $this, 'parse_utm' ) );
            add_action( 'template_redirect', array( $this, 'email_link_clicked' ) );
            $this->doing_click = true;

        } else if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-tracking/link/click' ) !== false ){

            add_action( 'plugins_loaded', array( $this, 'deconstruct_cookie' ) );
            add_action( 'plugins_loaded', array( $this, 'extract_from_login' ) );
            add_action( 'plugins_loaded', array( $this, 'parse_utm' ) );
            add_action( 'template_redirect', array( $this, 'link_clicked' ) );
            $this->doing_click = true;

        } else if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-tracking/email/open' ) !== false  ) {

            add_action( 'plugins_loaded', array( $this, 'fix_tracking_ssl' ) );
            add_action( 'plugins_loaded', array( $this, 'setup_url_vars' ) );
            add_action( 'plugins_loaded', array( $this, 'parse_utm' ) );
            add_action( 'init', array( $this, 'email_opened' ) );
            $this->doing_open = true;

        } else if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-confirmation/via/email' ) !== false ) {

            add_action( 'plugins_loaded', array( $this, 'fix_tracking_ssl' ) );
            add_action( 'plugins_loaded', array( $this, 'deconstruct_cookie' ) );
            add_action( 'plugins_loaded', array( $this, 'parse_utm' ) );
            add_action( 'init', array( $this, 'email_confirmed' ) );
            $this->doing_confirmation = true;

        } else {

            add_action( 'plugins_loaded', array( $this, 'deconstruct_cookie' ) );
            add_action( 'plugins_loaded', array( $this, 'extract_from_login' ) );
            add_action( 'plugins_loaded', array( $this, 'parse_utm' ) );

            if ( isset( $_COOKIE[ 'gh_referer' ] ) ) {
                $this->lead_source = esc_url_raw( $_COOKIE[ 'gh_referer' ] );
            }

        }

        add_action( 'groundhogg/submission/after', array( $this, 'form_filled' ), 10, 3 );

    }

    /**
     * For some reason emails are being sent out with http instead of https...
     * Redirect to ssl if https is in the url.
     */
    public function fix_tracking_ssl()
    {

        $site = get_option( 'siteurl' );

        if ( strpos( $site, 'https://' ) !== false && ! is_ssl() ){
            $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            wp_redirect( $actual_link );
            die();
        }

    }

    /**
     * Setup the vars based on a url if the client is clicking from an email
     */
    public function setup_url_vars()
    {

        if ( isset( $_REQUEST[ 'u' ] ) )
        {
            $uid = absint( intval( hexdec( $_REQUEST[ 'u' ] ) ) );

            if ( WPGH()->contacts->exists( $uid, 'ID' ) ){
                $contact = wpgh_get_contact( $uid );
                $this->contact = $contact;
            }

        }

        if ( isset( $_REQUEST[ 'e' ] ) )
        {
            $eid = hexdec( $_REQUEST[ 'e' ] );

            $event = WPGH()->events->get( $eid );

            if ( is_object( $event ) ){
                $this->event = new WPGH_Event( $event->ID );

                if ( ! $this->event->funnel_id !== WPGH_BROADCAST ){
                    $this->funnel = WPGH()->funnels->get( $event->funnel_id );
                }

                $this->step   = wpgh_get_funnel_step( $event->step_id );
            }
        }

        if ( isset( $_REQUEST[ 'i' ] ) )
        {
            $eid = hexdec( $_REQUEST[ 'i' ] );

            $email = WPGH()->emails->get( $eid );

            if ( is_object( $email ) && ! empty( $email ) ){
                $this->email = new WPGH_Email( $email->ID );
            }
        }


        if ( isset( $_REQUEST[ 'ref' ] ) ) {
            $this->ref = esc_url_raw( urldecode( $_REQUEST[ 'ref' ] ) );
            $this->ref = str_replace( 'amp;', '', $this->ref );

            if ( empty( $this->ref ) ){
                $this->ref = site_url();
            }
        }

    }

    /**
     * Build a tracking cookie based on the available information.
     */
    public function build_cookie()
    {

    	$cookie = array();

        if ( $this->contact ){
            $cookie[ 'contact' ] = $this->contact->ID;
        }

        if ( $this->funnel ){
            $cookie[ 'funnel' ] = $this->funnel->ID;
        }

        if ( $this->step ){
            $cookie[ 'step' ] = $this->step->ID;
        }

        if ( $this->event ){
            $cookie[ 'event' ] = $this->event->ID;
        }

        if ( $this->email ){
            $cookie[ 'email' ] = $this->email->ID;
        }

        $utm = array(
            'utm_campaign',
            'utm_content',
            'utm_source',
            'utm_medium',
            'utm_term',
        );

        foreach ( $utm as $utm_var ){

            if ( $this->$utm_var ){
                $cookie[ $utm_var ] = $this->$utm_var;
            }

        }

//        var_dump( $cookie );
//        wp_die();
//
        $cookie = json_encode( $cookie );
        $cookie = wpgh_encrypt_decrypt( $cookie, 'e' );

        $expiry = apply_filters( 'wpgh_tracking_cookie_expiry', $this->cookie_expiry ) * DAY_IN_SECONDS;

        return setcookie(
            self::COOKIE,
            $cookie,
            time() + $expiry,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl()
        );

    }

    /**
     * If the tracking cookie exists, deconstruct it into parts
     *
     * @return bool
     */
    public function deconstruct_cookie()
    {
        if ( ! isset( $_COOKIE[ self::COOKIE ] ) )
            return false;

        $cookie = wpgh_encrypt_decrypt( $_COOKIE[ self::COOKIE ], 'd' );
        $cookie = json_decode( $cookie );

//        var_dump( $cookie );
//        wp_die( );

        if ( isset( $cookie->contact ) ){
            $this->contact  = wpgh_get_contact( $cookie->contact );
        }

        if ( isset( $cookie->email ) ){
            $this->email    = new WPGH_Email( $cookie->email );
        }

        if ( isset( $cookie->event ) ){
            $this->event    = WPGH()->events->get( $cookie->event );
        }

        if ( isset( $cookie->step ) ){
            $this->step     = wpgh_get_funnel_step( $cookie->step );
        }

        if ( isset( $cookie->funnel ) ){
            $this->funnel   = WPGH()->funnels->get( $cookie->funnel );
        }

        $utm = array(
            'utm_campaign',
            'utm_content',
            'utm_source',
            'utm_medium',
            'utm_term',
        );

        foreach ( $utm as $utm_var ){

            if ( isset( $cookie->$utm_var ) ){
                $this->$utm_var = $cookie->$utm_var;
            }

        }

        return true;
    }

    /**
     * Setup the tracking cookie based on login info from a logged in user
     */
    public function extract_from_login()
    {
        /* exit out if we have a contact or the user is not logged in */
        if ( ! is_user_logged_in() || $this->contact instanceof WPGH_Contact ){
            return;
        }

        /* we have a user, get the associated contact record if it exists */

        $user = wp_get_current_user();

        $contact = wpgh_get_contact( $user->user_email );

        if ( ! $contact || ! $contact->exists() ){
            return;
        }

        $this->contact = $contact;

        $this->build_cookie();

        return true;
    }

    /**
     * @param $key
     * @param bool $default
     *
     * @return string
     */
    private function get_url_var( $key, $default=false )
    {
        if ( wp_doing_ajax() ){
            $url = wp_get_referer();

            $val = wpgh_extract_query_arg( $url, $key );

            if ( $val ){
                return $val;
            }

        } else {

            if ( isset( $_GET[ 'key' ] ) ){
                return sanitize_text_field( stripslashes( urldecode( $_GET[ $key ] ) ) );
            }

        }


        return $default;
    }

    /**
     * Check if the URL has UTM vars
     *
     * @return bool
     */
    private function url_has_utm()
    {
        if ( wp_doing_ajax() ){
            return strpos( wp_get_referer(),'utm_' ) !== false;
        } else {
            $query = implode( '|', array_keys( $_GET ) );
            return strpos( $query,'utm_' ) !== false;
        }
    }

    /**
     * IF the URL contains UTM variables save them to meta.
     *
     * @return bool
     */
    public function parse_utm()
    {

        if ( $this->url_has_utm() ){
            /* Set props */
            $this->utm_campaign = $this->get_url_var( 'utm_campaign' );
            $this->utm_source   = $this->get_url_var( 'utm_source' );
            $this->utm_medium   = $this->get_url_var( 'utm_medium' );
            $this->utm_content  = $this->get_url_var( 'utm_content' );
            $this->utm_term     = $this->get_url_var( 'utm_term' );

        }

        if ( ! $this->contact instanceof WPGH_Contact ){
            return false;
        }

        $utm = array(
            'utm_campaign' => $this->utm_campaign,
            'utm_content'  => $this->utm_content,
            'utm_source'   => $this->utm_source,
            'utm_medium'   => $this->utm_medium,
            'utm_term'     => $this->utm_term,
        );

        $utm_string = implode( '', $utm );
        if ( ! empty( $utm_string ) ){
            foreach ( $utm as $utm_var => $utm_val ){
                if ( $utm_val ){
                    $this->contact->update_meta( $utm_var, $utm_val );
                }
            }
        }

        return true;
    }


    /**
     * Return the step contact
     *
     * @return WPGH_Contact | false
     */
    public function get_contact()
    {
        if ( $this->contact instanceof WPGH_Contact )
            return $this->contact;

        return false;
    }

    /**
     * Set the contact and rebuild the cookie
     *
     * @param $contact_id int the ID of a contact
     */
    public function set_contact( $contact_id )
    {
        $contact = wpgh_get_contact( $contact_id );

        if ( $contact->exists() )
        {
            $this->contact = $contact;

            $this->build_cookie();
        }
    }

    /**
     * Return the step funnel
     *
     * @return object
     */
    public function get_funnel()
    {
        return $this->funnel;
    }

    /**
     * Set the funnel to the given ID
     *
     * @param $funnel_id int the ID of a funnel;
     */
    public function set_funnel( $funnel_id )
    {
        $funnel = WPGH()->funnels->get( $funnel_id );

        if ( $funnel )
        {
            $this->funnel = $funnel;

            $this->build_cookie();
        }
    }

    /**
     * Return the step object
     *
     * @return object
     */
    public function get_step()
    {
        return $this->step;
    }

    /**
     * Set the step to the given ID
     *
     * @param $step_id int the ID of a step;
     */
    public function set_step( $step_id )
    {
        $step = WPGH()->steps->get( $step_id );

        if ( $step )
        {
            $this->step = $step;

            $this->build_cookie();
        }
    }

    /**
     * Return the email in question
     *
     * @return WPGH_Email
     */
    public function get_email()
    {
        return $this->email;
    }
    
    /**
     * Set the email to the given ID
     *
     * @param $email_id int the ID of a email;
     */
    public function set_email( $email_id )
    {
        $email = new WPGH_Email( $email_id );

        if ( $email->exists() )
        {
            $this->email = $email;

            $this->build_cookie();
        }
    }
    
    /**
     * Return the object related to the current event in progress
     *
     * @return object|WPGH_Event
     */
    public function get_event()
    {
        return $this->event;
    }

    /**
     * When an email is opened this function will be called at the INIT stage
     */
    public function email_opened()
    {

        if ( ! $this->event ){
            if ( $this->doing_open ){
                /* thanks for coming! */
                wp_redirect( WPGH_ASSETS_FOLDER . 'images/email-open.png' );
                die();
            } else {
                return;
            }
        }

        $args = array(
            //'timestamp'     => time(),
            'contact_id'    => $this->contact->ID,
            'funnel_id'     => ( $this->funnel )? $this->funnel->ID : WPGH_BROADCAST,
            'step_id'       => $this->step->ID,
            'activity_type' => 'email_opened',
            'email_id'      => $this->email->ID,
            'event_id'      => $this->event->ID,
            'referer'       => ''
        );

        //wp_die();

        if ( ! $this->exists( $args ) ) {
            $args[ 'timestamp' ] = time();

            $this->add(
                $args
            );

            do_action( 'wpgh_email_opened', $this );
            do_action( 'groundhogg/tracking/email/opened', $this );
        }

        /* only fire if actually doing an open as this may be called by the email_link_clicked method */
        if ( $this->doing_open ){
            /* thanks for coming! */
            wp_redirect( WPGH_ASSETS_FOLDER . 'images/email-open.png' );
            die();
        }
    }

    /**
     * Tracking for the link click benchmark.
     */
    public function link_clicked()
    {

        if ( ! isset( $_REQUEST[ 'id' ] ) ){
            return;
        }

        $step_id = intval( $_REQUEST[ 'id' ] );

        $step = wpgh_get_funnel_step( $step_id );

        if ( $this->get_contact() ){
            do_action( 'wpgh_link_clicked', $step, $this->get_contact() );
            do_action( 'groundhogg/tracking/becnhmark_link/click', $step, $this->get_contact() );
            $redirect_to = WPGH()->replacements->process( $step->get_meta( 'redirect_to' ), $this->get_contact()->ID );

            /* Check unsub page */
            if ( wpgh_is_global_multisite() ){
                switch_to_blog( get_site()->site_id );
            }

            $unsub_page = get_permalink( wpgh_get_option( 'gh_unsubscribe_page' ) );
            if ( $redirect_to === $unsub_page ){
                $redirect_to = sprintf( '%s?u=%s', $unsub_page, dechex( $this->contact->ID ) );
            }

            if ( is_multisite() && ms_is_switched() ){
                restore_current_blog();
            }


        } else {
            $redirect_to = $step->get_meta( 'redirect_to' );
        }

        wp_redirect( $redirect_to );
        die();
    }

    /**
     * When tracking a link click redirect the user to the destination after performing the necessary tracking
     */
    public function email_link_clicked()
    {
        /* track every click as an open */
        $this->email_opened();

        //wp_die();

        if ( ! $this->event ){
            /* thanks for coming! */
            wp_redirect( $this->ref );

            die();
        }

        $args = array(
            'timestamp'     => time(),
            'contact_id'    => $this->contact->ID,
            'funnel_id'     => ( $this->funnel )? $this->funnel->ID : WPGH_BROADCAST,
            'email_id'      => $this->email->ID,
            'step_id'       => $this->step->ID,
            'activity_type' => 'email_link_click',
            'event_id'      => $this->event->ID,
            'referer'       => $this->ref
        );

        $this->add(
            $args
        );

        do_action( 'wpgh_email_link_click', $this );
        do_action( 'groundhogg/tracking/email/click', $this );

        $this->build_cookie();

        /* thanks for coming! */
        wp_redirect( $this->ref );

        die();
    }

	/**
	 * Sets the cookie upon a form fill.
	 *
	 * @param $step_id int
	 * @param $contact WPGH_Contact
	 * @param $submission WPGH_Submission
	 */
    public function form_filled( $step_id, $contact, $submission )
    {
    	$step = wpgh_get_funnel_step( $step_id );

    	$this->funnel = WPGH()->funnels->get( $step->funnel_id );
    	$this->step = $step;
    	$this->contact = $contact;

    	$this->build_cookie();
    }

    /**
     * Runs whenever a contact confirms their email address via a link click!
     */
    public function email_confirmed()
    {

        if ( ! $this->contact )
            wp_die( 'Hmmm, something went wrong. Please make sure you have cookies enabled.' );

        $this->contact->change_marketing_preference( WPGH_CONFIRMED );

        if ( wpgh_is_global_multisite() ){
            switch_to_blog( get_site()->site_id );
        }

        $conf_page = get_permalink( wpgh_get_option( 'gh_confirmation_page' ) );
        $conf_page = sprintf( '%s?u=%s', $conf_page, dechex( $this->contact->ID ) );

        if ( is_multisite() && ms_is_switched() ){
            restore_current_blog();
        }

        /**
         * @type $contact WPGH_Contact
         * @type $funnel_id int
         */

        if ( $this->funnel ){
            do_action( 'wpgh_email_confirmed', $this->contact, $this->funnel->ID );
            do_action( 'groundhogg/tracking/email/confirmed', $this->contact, $this->funnel->ID );
        }

        wp_redirect( $conf_page );
        die();
    }

    /**
     * Add the activity to the log
     *
     * @param $array array
     * @return int The Activity ID
     */
    public function add( $array )
    {
        return WPGH()->activity->add( $array );
    }

    /**
     * Check if an activity with certain details exists.
     *
     * @param $array
     * @return bool
     */
    public function exists( $array )
    {
        return WPGH()->activity->activity_exists( $array );
    }

}