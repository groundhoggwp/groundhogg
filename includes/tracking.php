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
 * @since       File available since Release 0.9
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Tracking {

	/**
	 * This is a cookie that will be in the contact's browser
	 */
	const UTM_COOKIE = 'groundhogg-utm';
	const TRACKING_COOKIE = 'groundhogg-tracking';
	const LEAD_SOURCE_COOKIE = 'groundhogg-lead-source';
	const PAGE_VISITS_COOKIE = 'groundhogg-page-visits';
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

		// Actions which build the tracking cookie.
		add_action( 'wp_login', [ $this, 'wp_login' ], 10, 2 );
//		add_action( 'wp_logout', [ $this, 'wp_logout' ], 10, 2 );

		add_action( 'after_setup_theme', [ $this, 'deconstruct_tracking_cookie' ], 1 );
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'parse_utm' ] );
		}

		add_filter( 'request', [ $this, 'parse_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		add_action( 'template_redirect', [ $this, 'fix_tracking_ssl' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'template_redirect', [ $this, 'handle_failsafe_tracking' ] );

		add_action( 'groundhogg/after_form_submit', [ $this, 'form_filled' ], 10, 1 );

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

		// Shortened for clicks
		add_managed_rewrite_rule(
			'c/([^/]*)/([^/]*)/(.+)$',
			'subpage=tracking&tracking_via=email&tracking_action=click&contact_id=$matches[1]&event_id=$matches[2]&target_url=$matches[3]'
		);

		// Shortened for opens
		add_managed_rewrite_rule(
			'o/([^/]*)/([^/]*)/?$',
			'subpage=tracking&tracking_via=email&tracking_action=open&contact_id=$matches[1]&event_id=$matches[2]'
		);

		### LEGACY SUPPORT ###

		// With Ref attribute
		add_managed_rewrite_rule(
			'tracking/([^/]*)/([^/]*)/([^/]*)/([^/]*)/([^/]*)/(.+)$',
			'subpage=tracking&tracking_via=$matches[1]&tracking_action=$matches[2]&contact_id=$matches[3]&event_id=$matches[4]&email_id=$matches[5]&target_url=$matches[6]'
		);

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

		// Long tracking structure.
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
			$this->map_query_var( $vars, 'target_url', 'Groundhogg\base64url_decode' );
		}

		return $vars;
	}

	/**
	 * @param $array
	 * @param $key
	 * @param $func
	 */
	public function map_query_var( &$array, $key, callable $func ) {
		if ( ! function_exists( $func ) ) {
			return;
		}

		if ( isset_not_empty( $array, $key ) ) {
			$array[ $key ] = call_user_func( $func, $array[ $key ] );
		}
	}

	/**
	 * Bails during tracking stuff and outputs relevant headers
	 */
	protected function bail() {

		if ( $this->doing_click ) {
			$this->redirect_to_target();
		}

		if ( $this->doing_open ) {
			$this->output_tracking_image();
		}

		$tracking_action = get_query_var( 'tracking_action' );

		switch ( $tracking_action ) {
			case 'open':

				$this->output_tracking_image();

				break;
			case 'click':

				$this->redirect_to_target();

				break;
		}

		wp_die( 'This link is currently unavailable.' );
	}

	/**
	 * Parses params for `ge` and `identity` to set the tracking cookie
	 *
	 * @return void
	 */
	public function handle_failsafe_tracking() {

		// `identity` is the email address or id encrypted with the secret
		if ( $identity = get_url_var( 'gi' ) ) {
			$id_or_email = decrypt( base64url_decode( $identity ) );

			if ( ! $id_or_email ) {
				return;
			}

			$contact = get_contactdata( $id_or_email );

			if ( is_a_contact( $contact ) ) {
				$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );

				// `id` is the event ID in hexadecimal
				if ( $event_id = get_url_var( 'ge' ) ) {
					$event_id = absint( hexdec( $event_id ) );

					$event = get_event_by_queued_id( $event_id );

					// Make sure the event matches the identity
					if ( $event && $event->exists() && $event->get_contact_id() === $contact->get_id() ) {
						$this->add_tracking_cookie_param( 'event_id', $event->get_id() );
					}
				}

				$this->build_tracking_cookie();
			}
		}
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
		$event_id   = absint( get_query_var( 'event_id' ) );

		$contact = get_contactdata( $contact_id );

		// Contact does not exist
		if ( ! is_a_contact( $contact ) ) {
			$this->bail();
		}

		$event = get_event_by_queued_id( $event_id );

		// Event does not exist
		if ( ! $event || ! $event->exists() ) {
			$this->bail();
		}

		// Event and contact ID do not match
		if ( $event->get_contact_id() !== $contact->get_id() ) {
			$this->bail();
		}

		// Add the tracking cookie params.
		$this->add_tracking_cookie_param( 'contact_id', $contact_id );
		$this->add_tracking_cookie_param( 'event_id', $event->get_id() );
		$this->add_tracking_cookie_param( 'source', $tracking_via );
		$this->add_tracking_cookie_param( 'action', $tracking_action );

		switch ( $tracking_via ) {
			case 'email':
				switch ( $tracking_action ) {
					case 'open':
						$this->doing_open = true;
						$this->email_opened();
						break;
					case 'click':
						$this->doing_click = true;

						$this->build_tracking_cookie();
						$this->email_link_clicked();
						break;
				}

				break;
		}

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
	 * Whether the contact ID associated with the tracking cookie is the same as the logged in user.
	 *
	 * @return bool
	 */
	public function tracking_cookie_matches_logged_in_user() {
		$tracking_id_value  = absint( $this->get_tracking_cookie_param( 'contact_id' ) );
		$logged_in_id_value = false;

		// Get from the user if logged in and the ID is not available.
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			$contact = $this->get_current_contact();

			if ( ! $contact ) {
				return false;
			}

			$logged_in_id_value = $contact->get_id();
		}

		return $logged_in_id_value === $tracking_id_value;
	}

	/**
	 * Get a param from the tracking cookie.
	 *
	 * @param      $key
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

		$user_id    = function_exists( 'is_user_logged_in' ) && is_user_logged_in() ? wp_get_current_user()->ID : null;
		$contact_id = $this->get_tracking_cookie_param( 'contact_id', null );

		// If both are well-defined, use based on precedence setting
		if ( $user_id && $contact_id ) {
			return is_ignore_user_tracking_precedence_enabled() ? get_contactdata( $contact_id ) : get_contactdata( $user_id, true );
		}

		if ( $contact_id ) {
			return get_contactdata( $contact_id );
		}

		if ( $user_id ) {
			return get_contactdata( $user_id, true );
		}

		return false;
	}

	/**
	 * Set the current contact
	 *
	 * @param $contact Contact
	 *
	 * @return bool
	 */
	public function set_current_contact( $contact ) {

		if ( ! is_a_contact( $contact ) ) {
			return false;
		}

		$this->start_tracking( $contact );

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
	 * @return int
	 */
	public function get_current_event_id() {
		return absint( $this->get_tracking_cookie_param( 'event_id' ) );
	}

	/**
	 * Get the contact which is currently being tracked.
	 *
	 * @return Event|false
	 */
	public function get_current_event() {

		$id = absint( $this->get_tracking_cookie_param( 'event_id' ) );

		if ( $this->event && $this->event->get_id() === $id ) {
			return $this->event;
		}

		// It's likely that the event is being set by an email link click,
		// so reference the `queued_id` rather than the actual event `ID`
		$event = new Event( $id );

		if ( ! $event->exists() ) {
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
		if ( ! $this->get_current_event() ) {
			return false;
		}

		return $this->get_current_event()->get_email_id();
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

		$enc_cookie = get_cookie( self::TRACKING_COOKIE );

		if ( ! $enc_cookie ) {
			return;
		}

		$dec_cookie   = decrypt( $enc_cookie );
		$cookie_vars  = json_decode( $dec_cookie, true );
		$cookie_vars  = apply_filters( 'groundhogg/tracking/get_cookie_vars', $cookie_vars );
		$this->cookie = $cookie_vars;

//		var_dump( $this->cookie );
	}

	/**
	 * Build a tracking cookie based on the available information.
	 */
	protected function build_tracking_cookie() {

		$cookie_vars = apply_filters( 'groundhogg/tracking/set_cookie_vars', $this->cookie );
		$cookie      = wp_json_encode( $cookie_vars );
		$cookie      = encrypt( $cookie );
		$expiry      = apply_filters( 'groundhogg/tracking/cookie_expiry', self::COOKIE_EXPIRY * DAY_IN_SECONDS );

		return set_cookie( self::TRACKING_COOKIE, $cookie, $expiry );
	}

	/**
	 * If we want to start tracking a new contact we can overwrite any current cookie
	 * or just start with a new cookie by calling this function.
	 *
	 * @param       $contact Contact
	 * @param null  $deprecated
	 * @param array $more
	 */
	public function start_tracking( $contact, $deprecated = null, $more = [] ) {

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		// Already tracking this contact
		if ( $this->get_current_contact_id() === $contact->get_id() ) {
			return;
		}

		// Remove previous contact
		$this->cookie = [];

		$this->add_tracking_cookie_param( 'contact_id', $contact->get_id() );

		foreach ( $more as $key => $value ) {
			$this->add_tracking_cookie_param( $key, $value );
		}

		// Rebuild the cookie.
		$this->build_tracking_cookie();
	}

	/**
	 * Setup the tracking cookie vars for when a user logs in.
	 *
	 * @param $user_login string
	 * @param $user       \WP_User
	 */
	public function wp_login( $user_login, $user ) {
		$this->add_tracking_cookie_param( 'user_login', $user_login );
		$this->add_tracking_cookie_param( 'user_id', $user->ID );
		$this->add_tracking_cookie_param( 'source', 'login' );

		$contact = get_contactdata( $user->user_email );

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
		} else if ( ! is_option_enabled( 'gh_disable_unnecessary_cookies' ) && has_accepted_cookies() ) {
			// Save the UTM stuff as a cookie for future use.
			set_cookie( self::UTM_COOKIE, wp_json_encode( $utm ), DAY_IN_SECONDS );
		}
	}

	/**
	 * Output the tracking image for the browser
	 */
	protected function output_tracking_image() {
		/* thanks for coming! */

		status_header( 200 );
		header( 'Content-Type: image/png' );
		echo base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=' );

		die();
	}

	/**
	 * Temp cache the redirect URL here
	 *
	 * @var string
	 */
	protected $target_url;

	/**
	 * Allows getting the target URL at any point
	 *
	 * @return mixed|void
	 */
	protected function get_target_url() {

		if ( $this->target_url ) {
			return $this->target_url;
		}

		$target_url = get_query_var( 'target_url' );

		// Clean the URL, wonky encoding sometimes...
		$target_url = str_replace( '&#038;', '&', $target_url );

		if ( empty( $target_url ) ) {
			$target_url = '/';
		}

		// Edge case where there is no target page but there are UTM params or some other query string.
		if ( str_starts_with( $target_url, '?' ) ) {
			$target_url = '/' . $target_url; // Prepend slash to send to homepage
		}

		$this->target_url = apply_filters( 'groundhogg/tracking/target_url', $target_url );

		return $this->target_url;
	}

	/**
	 * Redirects to the target URL
	 */
	protected function redirect_to_target() {
		wp_redirect( $this->get_target_url(), $this->redirect_http_status_code() );
		die();
	}

	/**
	 * Clears cached report data when a new click or open happens.
	 *
	 * @param Event $event
	 *
	 * @return bool|mixed
	 */
	public function maybe_clear_cached_broadcast_report_data( Event $event ) {
		if ( ! $event->is_broadcast_event() ) {
			return false;
		}

		return $event->get_step()->clear_cached_report_data();
	}

	/**
	 * When an email is opened this function will be called at the INIT stage
	 */
	public function email_opened() {

		$event = $this->get_current_event();

		if ( ! $event || ! $event->exists() ) {
			$this->bail();
		}

		$open_delay = absint( get_option( 'gh_open_tracking_delay' ) );

		// if diff between current time and sent time is suspicious we should assume bot?
		if ( $open_delay && $event->is_broadcast_event() && time() - $event->get_time() < $open_delay ) {
			$this->bail();
		}

		$args = [
			'event_id'      => $event->get_id(),
			'activity_type' => Activity::EMAIL_OPENED,
		];

		// We've already tracked an open for this event
		if ( ! get_db( 'activity' )->exists( $args ) ) {

			$activity = track_event_activity( $event, Activity::EMAIL_OPENED, [], [
				'ip_address' => get_current_ip_address(),
				'user_agent' => get_current_user_agent_id()
			] );

			if ( $activity ) {

				/**
				 * When an email is opened and the tracking image is loaded
				 *
				 * @param Tracking $tracking
				 * @param Activity $activity
				 */
				do_action( 'groundhogg/tracking/email/opened', $this, $activity, $event );
			}

		}

		/* only fire if actually doing an open as this may be called by the email_link_clicked method */
		if ( $this->doing_open ) {
			$this->output_tracking_image();
		}
	}

	/**
	 * The redirect http status code to use
	 * 301, 302, 307, 308
	 *
	 * @return int
	 */
	protected function redirect_http_status_code() {
		return apply_filters( 'groundhogg/tracking/redirect_http_status_code', 307 );
	}

	/**
	 * When tracking a link click redirect the user to the destination after performing the necessary tracking
	 */
	protected function email_link_clicked() {

		$event = $this->get_current_event();

		/**
		 * @since 2.1
		 *
		 * If the event is not found, don't show an error
		 * Just keep moving them to the desired page.
		 *
		 * Event Ids can go missing for a variety of reason, it's unreasonable to assume the data wil remain integral
		 * always.
		 */
		if ( ! $event || ! $event->exists() ) {
			$this->bail();

			return;
		}

		$click_delay = absint( get_option( 'gh_click_tracking_delay' ) );

		// if diff between current time and sent time is suspicious we should assume bot?
		if ( $click_delay && $event->is_broadcast_event() && time() - $event->get_time() < $click_delay ) {
			$this->bail();

			return;
		}

		/* track every click as an open */
		$this->email_opened();

		$target   = $this->get_target_url();
		$activity = track_event_activity( $event, Activity::EMAIL_CLICKED, [], [
			'referer'      => $target,
			'referer_hash' => generate_referer_hash( $target ),
			'ip_address'   => get_current_ip_address(),
			'user_agent'   => get_current_user_agent_id()
		] );

		if ( ! $activity ) {
			// Tracking not available.
			wp_die( __( 'Oops... This link is currently unavailable.', 'groundhogg' ) );
		}

		/**
		 * When an email tracking link is clicked
		 *
		 * @param Tracking $tracking
		 * @param Activity $activity
		 */
		do_action( 'groundhogg/tracking/email/click', $this, $activity, $event );

		$this->redirect_to_target();
	}

	/**
	 * Sets the cookie upon a form fill.
	 *
	 * @param $contact Contact
	 */
	public function form_filled( $contact ) {
		$this->start_tracking( $contact );
	}
}
