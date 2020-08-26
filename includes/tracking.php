<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
class Tracking {

	/**
	 * This is a cookie that will be in the contact's browser
	 */
	const TRACKING_COOKIE = 'groundhogg-tracking';
	const LEAD_SOURCE_COOKIE = 'groundhogg-lead-source';
	const FORM_IMPRESSIONS_COOKIE = 'groundhogg-form-impressions';

	/**
	 * Cookie expiry time in days
	 *
	 * @var int
	 */
	const COOKIE_EXPIRY = 14;

	/**
	 * Array representing the various elements of the tracking cookie.
	 *
	 * @var array
	 */
	protected $cookie = [];

	/**
	 * Arbitrary data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var string the referring url
	 */
	protected $lead_source = '';

	/**
	 * Holds any UTM params.
	 *
	 * @var string[]
	 */
	protected $utm = [];

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
	 * @var bool|Event
	 */
	private $event;

	/**
	 * WPGH_Tracking constructor.
	 *
	 *
	 * Look at the current URL and depending on that setup the vars and enqueue the appropriate elements if any
	 */
	public function __construct() {
		//Actions when cookie should be destroyed
		add_action( 'groundhogg/preferences/erase_profile', [ $this, 'stop_tracking' ] );

		// Actions which build the tracking cookie.
		add_action( 'wp_login', [ $this, 'wp_login' ], 10, 2 );

		add_action( 'after_setup_theme', [ $this, 'deconstruct_tracking_cookie' ], 1 );
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'parse_utm' ] );
		}

		add_filter( 'request', [ $this, 'parse_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		add_action( 'template_redirect', [ $this, 'fix_tracking_ssl' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );

		add_action( 'groundhogg/after_form_submit', [ $this, 'form_filled' ], 10, 1 );
		add_action( 'groundhogg/contact/preferences/unsubscribed', [ $this, 'contact_unsubscribed' ], 10, 1 );

		add_action( 'groundhogg/preferences/erase_profile', [ $this, 'remove_tracking_cookie' ] );

	}

	/**
	 * Remove any cookies.
	 */
	public function remove_tracking_cookie() {
		delete_cookie( self::TRACKING_COOKIE );
		delete_cookie( self::LEAD_SOURCE_COOKIE );
	}

	/**
	 * Adds the rewrite rules for tracking.
	 */
	public function add_rewrite_rules() {
		// Short tracking structure.
		// With Ref attribute
		add_managed_rewrite_rule(
			'tracking/([^/]*)/([^/]*)/([^/]*)/([^/]*)/([^/]*)/(.+)$',
			'subpage=tracking&tracking_via=$matches[1]&tracking_action=$matches[2]&contact_id=$matches[3]&event_id=$matches[4]&email_id=$matches[5]&target_url=$matches[6]'
		);

		// New tracking structure.
		// No Ref attribute
		add_managed_rewrite_rule(
			'tracking/([^/]*)/([^/]*)/([^/]*)/([^/]*)/([^/]*)/?$',
			'subpage=tracking&tracking_via=$matches[1]&tracking_action=$matches[2]&contact_id=$matches[3]&event_id=$matches[4]&email_id=$matches[5]'
		);

		// Long tracking structure.
		// With Ref attribute
		add_managed_rewrite_rule(
			'tracking/([^/]*)/([^/]*)/u/([^/]*)/e/([^/]*)/i/([^/]*)/ref/(.+)$',
			'subpage=tracking&tracking_via=$matches[1]&tracking_action=$matches[2]&contact_id=$matches[3]&event_id=$matches[4]&email_id=$matches[5]&target_url=$matches[6]'
		);

		// New tracking structure.
		// No Ref attribute
		add_managed_rewrite_rule(
			'tracking/([^/]*)/([^/]*)/u/([^/]*)/e/([^/]*)/i/([^/]*)/?$',
			'subpage=tracking&tracking_via=$matches[1]&tracking_action=$matches[2]&contact_id=$matches[3]&event_id=$matches[4]&email_id=$matches[5]'
		);
	}

	/**
	 * Add the query vars.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		// Tracking vars
		$vars[] = 'subpage';
		$vars[] = 'tracking_via';
		$vars[] = 'tracking_action';
		$vars[] = 'contact_id';
		$vars[] = 'event_id';
		$vars[] = 'email_id';
		$vars[] = 'target_url';

		return $vars;
	}

	/**
	 * @param $array
	 * @param $key
	 * @param $func
	 */
	public function map_query_var( &$array, $key, $func ) {
		if ( ! function_exists( $func ) ) {
			return;
		}

		if ( isset_not_empty( $array, $key ) ) {
			$array[ $key ] = call_user_func( $func, $array[ $key ] );
		}
	}

	/**
	 * Parse the Ids from hex to int.
	 *
	 * @param $vars array
	 *
	 * @return array
	 */
	public function parse_request( $vars ) {
		if ( get_array_var( $vars, 'subpage' ) === 'tracking' ) {
			$this->map_query_var( $vars, 'contact_id', 'hexdec' );
			$this->map_query_var( $vars, 'event_id', 'hexdec' );
			$this->map_query_var( $vars, 'email_id', 'hexdec' );

			//Decode & Decode
			$this->map_query_var( $vars, 'target_url', 'urldecode' );
			$this->map_query_var( $vars, 'target_url', 'base64_decode' );
		}

		return $vars;
	}

	/**
	 * Do a tracking redirect during the template_redirect hook
	 */
	public function template_redirect() {
		if ( ! is_managed_page() ) {
			return;
		}

		$subpage = get_query_var( 'subpage' );

		if ( $subpage !== 'tracking' ) {
			return;
		}

		$tracking_via    = get_query_var( 'tracking_via' );
		$tracking_action = get_query_var( 'tracking_action' );

		$contact_id = absint( get_query_var( 'contact_id' ) );
		$email_id   = absint( get_query_var( 'email_id' ) );
		$event_id   = absint( get_query_var( 'event_id' ) );
		$target_url = get_query_var( 'target_url' );

		// Clean the URL, wonky encoding sometimes...
		$target_url = str_replace( '&#038;', '&', $target_url );

		if ( empty( $target_url ) ){
			$target_url = home_url();
		}

		// Add the tracking cookie params.
		$this->add_tracking_cookie_param( 'contact_id', $contact_id );
		$this->add_tracking_cookie_param( 'email_id', $email_id );
		$this->add_tracking_cookie_param( 'event_id', $event_id );
		$this->add_tracking_cookie_param( 'source', $tracking_via );
		$this->add_tracking_cookie_param( 'action', $tracking_action );

		apply_filters( 'groundhogg/tracking/target_url', $target_url );

		switch ( $tracking_via ) {
			case 'email':
				switch ( $tracking_action ) {
					case 'open':
						$this->doing_open = true;
						$this->email_opened();
						break;
					case 'click':
						$this->doing_click = true;
						$this->email_link_clicked( $target_url );
						break;
				}

				break;
		}

		$this->build_tracking_cookie();
		die();
	}

	/**
	 * Add a param to te tracking cookie.
	 *
	 * @param $key
	 * @param $value
	 */
	public function add_tracking_cookie_param( $key, $value ) {
		$this->cookie[ $key ] = $value;
	}

	/**
	 * Remove a param from the tracking cookie.
	 *
	 * @param $key
	 *
	 * @return void
	 */
	public function remove_tracking_cookie_param( $key ) {
		unset( $this->cookie[ $key ] );
	}

	/**
	 * Get a param from the tracking cookie.
	 *
	 * @param $key
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function get_tracking_cookie_param( $key, $default = false ) {
		if ( isset_not_empty( $this->cookie, $key ) ) {
			return $this->cookie[ $key ];
		}

		return $default;
	}

	/**
	 * @return string
	 */
	public function get_leadsource() {
		return sanitize_text_field( get_cookie( self::LEAD_SOURCE_COOKIE ) );
	}

	/**
	 * Get the contact which is currently being tracked.
	 *
	 * @return Contact|false
	 */
	public function get_current_contact() {
		$id_or_email = absint( $this->get_tracking_cookie_param( 'contact_id' ) );

		// Override if the user is logged in.
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {

			$ignore_user_precedence = is_ignore_user_tracking_precedence_enabled();

			// You can have user precedence if the id_or_email is false and the user is logged in or if the disable option is not enabled.
			if ( ! $ignore_user_precedence || ! $id_or_email ) {
				$id_or_email = wp_get_current_user()->user_email;
			}
		}

		if ( ! $id_or_email ) {
			return false;
		}

		return get_contactdata( $id_or_email );
	}

	/**
	 * Set the current contact
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function set_current_contact( $contact ){

		if ( ! is_a_contact( $contact ) ){
			return false;
		}

		$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );
		$this->build_tracking_cookie();

		return true;
	}

	/**
	 * @return int
	 */
	public function get_current_contact_id() {
		$id = absint( $this->get_tracking_cookie_param( 'contact_id' ) );

		// Get from the user if logged in and the ID is not available.
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {

			$contact = $this->get_current_contact();

			if ( $contact ) {
				$id = $contact->get_id();
			}
		}

		return $id;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return get_array_var( $this->data, $name );
	}

	/**
	 * Get the contact which is currently being tracked.
	 *
	 * @return Event|false
	 */
	public function get_current_event() {

		$id = absint( $this->get_tracking_cookie_param( 'event_id' ) );

		if ( $this->event && $this->event->get_id() === $id ){
			return $this->event;
		}

		// It's likely that the event is being set by an email link click,
		// so reference the `queued_id` rather than the actual event `ID`
		$event = get_event_by_queued_id( $id );

		if ( ! $event || ! $event->exists() ){
			return false;
		}

		$this->event = $event;

		return $event;
	}

	/**
	 * @return bool|int
	 */
	public function get_current_step_id() {

		if ( ! $this->get_current_event() ) {
			return false;
		}

		return $this->get_current_event()->get_step_id();
	}

	/**
	 * @return bool|int
	 */
	public function get_current_funnel_id() {

		if ( ! $this->get_current_event() ) {
			return false;
		}

		return $this->get_current_event()->get_funnel_id();
	}

	/**
	 * @return int
	 */
	public function get_current_email_id() {
		return absint( $this->get_tracking_cookie_param( 'email_id' ) );
	}

	/**
	 * For some reason emails are being sent out with http instead of https...
	 * Redirect to ssl if https is in the url.
	 */
	public function fix_tracking_ssl() {
		$site = get_option( 'siteurl' );
		if ( strpos( $site, 'https://' ) !== false && ! is_ssl() ) {
			$actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			wp_safe_redirect( $actual_link );
			die();
		}
	}

	/**
	 * If the tracking cookie exists, deconstruct it into parts
	 */
	public function deconstruct_tracking_cookie() {
		if ( ! isset_not_empty( $_COOKIE, self::TRACKING_COOKIE ) ) {
			return;
		}

		$enc_cookie   = $_COOKIE[ self::TRACKING_COOKIE ];
		$dec_cookie   = Plugin::$instance->utils->encrypt_decrypt( $enc_cookie, 'd' );
		$cookie_vars  = json_decode( $dec_cookie, true );
		$cookie_vars  = apply_filters( 'groundhogg/tracking/get_cookie_vars', $cookie_vars );
		$this->cookie = $cookie_vars;
	}

	/**
	 * Build a tracking cookie based on the available information.
	 */
	protected function build_tracking_cookie() {
		$cookie_vars = apply_filters( 'groundhogg/tracking/set_cookie_vars', $this->cookie );

		$cookie = wp_json_encode( $cookie_vars );
		$cookie = Plugin::$instance->utils->encrypt_decrypt( $cookie, 'e' );

		$expiry = apply_filters( 'groundhogg/tracking/cookie_expiry', self::COOKIE_EXPIRY ) * DAY_IN_SECONDS;

		return set_cookie( self::TRACKING_COOKIE, $cookie, $expiry );
	}

	/**
	 * If we want to start tracking a new contact we can overwrite any current cookie
	 * or just start with a new cookie by calling this function.
	 *
	 * @param $contact Contact
	 * @param $source string
	 */
	public function start_tracking( $contact, $source = 'manual' ) {
		if ( ! $contact ) {
			return;
		}

		// Remove any previous tracking...
		$this->cookie = [];

		$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );
		$this->add_tracking_cookie_param( 'source', $source );

		// Rebuild the cookie.
		$this->build_tracking_cookie();
	}

	/**
	 * Delete the current tracking cookie.
	 */
	public function stop_tracking() {
		if ( isset( $_COOKIE[ self::TRACKING_COOKIE ] ) ) {
			delete_cookie( self::TRACKING_COOKIE );
		}
	}

	/**
	 * Setup the tracking cookie vars for when a user logs in.
	 *
	 * @param $user_login string
	 * @param $user \WP_User
	 */
	public function wp_login( $user_login, $user ) {
		$this->add_tracking_cookie_param( 'user_login', $user_login );
		$this->add_tracking_cookie_param( 'user_id', $user->ID );
		$this->add_tracking_cookie_param( 'source', 'login' );

		$contact = Plugin::$instance->utils->get_contact( $user->user_email );

		if ( $contact ) {
			$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );
		}

		$this->build_tracking_cookie();
	}

	/**
	 * IF the URL contains UTM variables save them to meta.
	 *
	 * @return void
	 */
	public function parse_utm() {

		$utm_defaults = array(
			'utm_campaign' => '',
			'utm_content'  => '',
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_term'     => '',
		);

		$utm = array_intersect_key( $_GET, $utm_defaults );

		$has_utm = array_filter( array_values( $utm_defaults ) );

		if ( ! $has_utm ) {
			return;
		}

		if ( $this->get_current_contact() ) {

			// If there is a contact, update their UTM stats to the one provided by the campaign
			foreach ( $utm as $utm_var => $utm_val ) {
				if ( ! empty( $utm_val ) ) {
					$this->get_current_contact()->update_meta(
						$utm_var,
						sanitize_text_field( $utm_val )
					);
				}
			}
		} else {

			// Save the UTM stuff as a cookie for future use.
			set_cookie( 'groundhogg_utm_tacking', wp_json_encode( $utm ), MONTH_IN_SECONDS );

		}

	}

	/**
	 * Output the tracking image for the browser
	 */
	protected function output_tracking_image() {
		/* thanks for coming! */
		$file = GROUNDHOGG_ASSETS_PATH . 'images/email-open.png';
		$type = 'image/png';

		header( 'Content-Type:' . $type );
		header( 'Content-Length:' . filesize( $file ) );
		status_header( 200 );
		readfile( $file );

		die();
	}

	/**
	 * When an email is opened this function will be called at the INIT stage
	 */
	public function email_opened() {

		$event = $this->get_current_event();

		if ( ! $event || ! $event->exists() ) {
			if ( $this->doing_open ) {
				$this->output_tracking_image();
			} else {
				return;
			}
		}

		$args = array(
			'contact_id'    => $event->get_contact_id(),
			'funnel_id'     => $event->get_funnel_id(),
			'step_id'       => $event->get_step_id(),
			'email_id'      => $this->get_tracking_cookie_param( 'email_id', 0 ),
			'activity_type' => Activity::EMAIL_OPENED,
			'event_id'      => $event->get_id(),
		);

		if ( ! get_db( 'activity' )->exists( $args ) ) {

			$args['timestamp'] = time();

			if ( Plugin::$instance->dbs->get_db( 'activity' )->add( $args ) ) {
				do_action( 'groundhogg/tracking/email/opened', $this );
			}
		}

		/* only fire if actually doing an open as this may be called by the email_link_clicked method */
		if ( $this->doing_open ) {
			$this->output_tracking_image();
		}
	}

	/**
	 * When tracking a link click redirect the user to the destination after performing the necessary tracking
	 *
	 * @param $target string where to send the subscriber
	 */
	protected function email_link_clicked( $target = '' ) {
		/* track every click as an open */
		$this->email_opened();

		$event    = $this->get_current_event();
		$redirect = add_query_arg( [ 'key' => wp_create_nonce() ], $target );

		/**
		 * @since 2.1
		 *
		 * If the event is not found, don't show an error
		 * Just keep moving them to the desired page.
		 *
		 * Event Ids can go missing for a variety of reason, its unreasonable to assume the data wil remain integral
		 * always.
		 */
		if ( ! $event || ! $event->exists() ) {
			wp_redirect( $redirect );

			return;
		}

		$args = array(
			'timestamp'     => time(),
			'contact_id'    => $event->get_contact_id(),
			'funnel_id'     => $event->get_funnel_id(),
			'step_id'       => $event->get_step_id(),
			'email_id'      => $this->get_tracking_cookie_param( 'email_id', 0 ),
			'activity_type' => Activity::EMAIL_CLICKED,
			'event_id'      => $event->get_id(),
			'referer'       => $target,
			'referer_hash'  => generate_referer_hash( $target )
		);

		if ( get_db( 'activity' )->add( $args ) ) {
			do_action( 'groundhogg/tracking/email/click', $this );

			wp_redirect( $redirect );

			return;
		}

		// Tracking not available.
		wp_die( __( 'Oops... This link is currently unavailable.', 'groundhogg' ) );
	}

	/**
	 * Sets the cookie upon a form fill.
	 *
	 * @param $contact Contact
	 */
	public function form_filled( $contact ) {
		if ( is_user_logged_in() || headers_sent() ) {
			return;
		}

		if ( ! isset_not_empty( $_COOKIE, self::TRACKING_COOKIE ) ) {
			$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );
			$this->build_tracking_cookie();
		}
	}

	/**
	 * Track the activity if the contact unsubscribed
	 *
	 * @param $contact_id
	 */
	public function contact_unsubscribed( $contact_id ) {

		// Check if we have an email ID/step ID that we can attribute it too...

		$event = $this->get_current_event();

		if ( ! $event || ! $event->exists() ) {
			return;
		}

		$args = array(
			'timestamp'     => time(),
			'contact_id'    => $contact_id,
			'funnel_id'     => $event->get_funnel_id(),
			'step_id'       => $event->get_step_id(),
			'email_id'      => $this->get_tracking_cookie_param( 'email_id', 0 ),
			'activity_type' => Activity::UNSUBSCRIBED,
			'event_id'      => $event->get_id(),
		);

		get_db( 'activity' )->add( $args );
	}

}