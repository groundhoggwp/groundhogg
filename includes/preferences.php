<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;

class Preferences {
	// Optin Statuses
	const UNCONFIRMED = 1;
	const CONFIRMED = 2;
	const UNSUBSCRIBED = 3;
	const WEEKLY = 4;
	const MONTHLY = 5;
	const HARD_BOUNCE = 6;
	const SPAM = 7;
	const COMPLAINED = 8;

	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		// Do last so precedence is given to Groundhogg
		add_filter( 'template_include', [ $this, 'template_include' ], 99 );
//		add_action( 'groundhogg/tracking/email/click', [ $this, 'set_temp_preferences_permissions_cookie' ] );
	}

	/**
	 * If the current state of things allows a user to change their email preferences...
	 *
	 * @return bool
	 */
	public function current_contact_can_modify_preferences() {

		$permissions_hash = get_cookie( 'gh-preferences-permission' );

		if ( ! $permissions_hash ) {
			return false;
		}

		$tracking = Plugin::$instance->tracking;

		// get the current state of things...
		$parts = array_filter( [
			$tracking->get_current_contact_id(),
			$tracking->get_current_email_id(),
			$tracking->get_current_funnel_id(),
			$tracking->get_current_event() ? $tracking->get_current_event()->get_id() : false,
		] );

		$value = wp_hash( encrypt( implode( '|', $parts ) ) );

		if ( $value !== $permissions_hash ) {
			return false;
		}

		return true;
	}

	/**
	 * Set a cookie which grants temp permissions to change the email preferences of a contact...
	 *
	 * @param $tracking Tracking
	 */
	public function set_temp_preferences_permissions_cookie( $tracking ) {

		$parts = array_filter( [
			$tracking->get_current_contact_id(),
			$tracking->get_current_email_id(),
			$tracking->get_current_funnel_id(),
			$tracking->get_current_event() ? $tracking->get_current_event()->get_id() : false,
		] );

		$value = wp_hash( encrypt( implode( '|', $parts ) ) );

		set_cookie( 'gh-preferences-permission', $value, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Add the rewrite rules required for the Preferences center.
	 */
	public function add_rewrite_rules() {
		add_managed_rewrite_rule( '?$', 'subpage=preferences', 'top' );
		add_managed_rewrite_rule( 'preferences(/?([^/?]*))?', 'subpage=preferences&action=$matches[2]', 'top' );
	}

	/**
	 * Add the query vars needed to manage the request.
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'subpage';
		$vars[] = 'action';

		return $vars;
	}

	/**
	 * Overwrite the existing template with the manage preferences template.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function template_include( $template ) {
		if ( ! is_managed_page() ) {
			return $template;
		}

		$page = get_query_var( 'subpage' );

		if ( $page !== 'preferences' ) {
			return $template;
		}

		$loader       = Plugin::$instance->rewrites->get_template_loader();
		$new_template = $loader->get_template_part( 'preferences', '', false );

		if ( file_exists( $new_template ) ) {
			return $new_template;
		}

		return $template;
	}

	/**
	 * Get the text explanation for the optin status of a contact
	 * 0 = unconfirmed, can send email
	 * 1 = confirmed, can send email
	 * 2 = opted out, can't send email
	 *
	 * @param $id_or_email int|string the contact in question
	 *
	 * @return bool|string
	 */
	public function get_optin_status_text( $id_or_email ) {
		$contact = get_contactdata( $id_or_email );

		if ( ! $contact ) {
			return _x( 'No Contact', 'notice', 'groundhogg' );
		}

		if ( $this->is_gdpr_enabled() && $this->is_gdpr_strict() ) {

			if ( ! $contact->has_gdpr_consent() || ! $contact->has_gdpr_consent( 'marketing' ) ) {
				return _x( 'This contact has not agreed to receive email marketing from you.', 'optin_status', 'groundhogg' );
			}
		}

		switch ( $contact->get_optin_status() ) {
			default:
			case self::UNCONFIRMED:
				if ( $this->is_confirmation_strict() ) {
					if ( ! $this->is_in_grace_period( $contact->ID ) ) {
						return _x( 'This contact will not receive emails, they are passed the email confirmation grace period.', 'optin_status', 'groundhogg' );
					}
				}

				return apply_filters( 'groundhogg/preferences/optin_status_text', _x( 'Will receive marketing.', 'optin_status', 'groundhogg' ), $contact );
				break;
			case self::CONFIRMED:
				return _x( 'They will receive marketing.', 'optin_status', 'groundhogg' );
				break;
			case self::UNSUBSCRIBED:
				return _x( 'They will not receive marketing.', 'optin_status', 'groundhogg' );
				break;
			case self::WEEKLY:
				return _x( 'This contact will only receive marketing weekly.', 'optin_status', 'groundhogg' );
				break;
			case self::MONTHLY:
				return _x( 'This contact will only receive marketing monthly.', 'optin_status', 'groundhogg' );
				break;
			case self::HARD_BOUNCE:
				return _x( 'This email address bounced, they will not receive marketing.', 'optin_status', 'groundhogg' );
				break;
			case self::SPAM:
				return _x( 'This contact was marked as spam. They will not receive marketing.', 'optin_status', 'groundhogg' );
				break;
			case self::COMPLAINED:
				return _x( 'This contact complained about your emails. They will not receive marketing.', 'optin_status', 'groundhogg' );
				break;
		}
	}

	/**
	 * Get all the preference names
	 *
	 * @return array
	 */
	public static function get_preference_names() {
		return [
			self::UNCONFIRMED  => _x( 'Unconfirmed', 'optin_status', 'groundhogg' ),
			self::CONFIRMED    => _x( 'Confirmed', 'optin_status', 'groundhogg' ),
			self::UNSUBSCRIBED => _x( 'Unsubscribed', 'optin_status', 'groundhogg' ),
			self::WEEKLY       => _x( 'Subscribed Weekly', 'optin_status', 'groundhogg' ),
			self::MONTHLY      => _x( 'Subscribed Monthly', 'optin_status', 'groundhogg' ),
			self::HARD_BOUNCE  => _x( 'Bounced', 'optin_status', 'groundhogg' ),
			self::SPAM         => _x( 'Spam', 'optin_status', 'groundhogg' ),
			self::COMPLAINED   => _x( 'Complained', 'optin_status', 'groundhogg' ),
		];
	}

	/**
	 * Map a string to an email preference
	 *
	 * @param string $string a string representation of a preference
	 *
	 * @return int
	 */
	public static function string_to_preference( $string ) {

		if ( ! is_string( $string ) ) {
			return self::UNCONFIRMED;
		}

		$string_map = array_merge( [
			'pending'      => self::UNCONFIRMED,
			'unconfirm'    => self::UNCONFIRMED,
			'unconfirmed'  => self::UNCONFIRMED,
			'confirm'      => self::CONFIRMED,
			'confirmed'    => self::CONFIRMED,
			'subscribe'    => self::CONFIRMED,
			'subscribed'   => self::CONFIRMED,
			'unsubscribe'  => self::UNSUBSCRIBED,
			'unsubscribed' => self::UNSUBSCRIBED,
			'weekly'       => self::WEEKLY,
			'monthly'      => self::MONTHLY,
			'hard_bounce'  => self::HARD_BOUNCE,
			'hard bounce'  => self::HARD_BOUNCE,
			'bounce'       => self::HARD_BOUNCE,
			'bounced'      => self::HARD_BOUNCE,
			'complain'     => self::COMPLAINED,
			'complaint'    => self::COMPLAINED,
			'complained'   => self::COMPLAINED,
			'spam'         => self::SPAM,
			'spammed'      => self::SPAM,
			'fake'         => self::SPAM,
		] );

		// Add translated names as well!
		$pretty_names = array_map( 'strtolower', self::get_preference_names() );
		$string_map   = array_merge( $string_map, array_flip( $pretty_names ) );

		return get_array_var( $string_map, strtolower( $string ), self::UNCONFIRMED );
	}

	/**
	 * Get a specifc preference name
	 *
	 * @param $preference int
	 *
	 * @return string
	 */
	public static function get_preference_pretty_name( $preference ) {
		return get_array_var( self::get_preference_names(), $preference, false );
	}

	/**
	 * ensure that the provided preference is valid
	 *
	 * @param       $preference
	 * @param false $old_preference
	 *
	 * @return false|int|mixed
	 */
	public static function sanitize( $preference, $old_preference = false ) {
		return self::is_valid( absint( $preference ) ) ? absint( $preference ) : ( $old_preference ?: self::UNCONFIRMED );
	}

	/**
	 * simple check to see if the provided preference is valid
	 *
	 * @param $preference
	 *
	 * @return bool
	 */
	public static function is_valid( $preference ) {
		return in_array( $preference, array_keys( self::get_preference_names() ) );
	}

	/**
	 * Return whether the contact is marketable or not.
	 *
	 * @return bool
	 */
	public function is_marketable( $id_or_email ) {
		$contact = get_contactdata( $id_or_email );

		if ( ! $contact ) {
			return _x( 'No Contact', 'notice', 'groundhogg' );
		}

		/* check for strict GDPR settings */
		if ( $this->is_gdpr_enabled() && $this->is_gdpr_strict() ) {

			if ( ! $contact->has_gdpr_consent() || ! $contact->has_gdpr_consent( 'marketing' ) ) {
				return false;
			}
		}

		switch ( $contact->get_optin_status() ) {
			default:
			case self::UNCONFIRMED:
				/* check for grace period if necessary */
				if ( $this->is_confirmation_strict() ) {
					if ( ! $this->is_in_grace_period( $contact->ID ) ) {
						return false;
					}
				}

				return true;
				break;
			case self::CONFIRMED:
				return true;
				break;
			case self::SPAM;
			case self::COMPLAINED;
			case self::HARD_BOUNCE;
			case self::UNSUBSCRIBED:
				return false;
				break;
			case self::WEEKLY:
				$last_sent = $contact->get_meta( 'last_sent' );

				return ( time() - absint( $last_sent ) ) > 7 * 24 * HOUR_IN_SECONDS;
				break;
			case self::MONTHLY:
				$last_sent = $contact->get_meta( 'last_sent' );

				return ( time() - absint( $last_sent ) ) > 30 * 24 * HOUR_IN_SECONDS;
				break;
		}
	}

	/**
	 * Check if GDPR is enabled throughout the plugin.
	 *
	 * @return bool whether it's enable or not.
	 */
	public function is_gdpr_enabled() {
		return is_option_enabled( 'gh_enable_gdpr' );
	}

	/**
	 * check if the GDPR strict option is enabled
	 *
	 * @return bool
	 */
	public function is_gdpr_strict() {
		return is_option_enabled( 'gh_enable_gdpr' ) && is_option_enabled( 'gh_strict_gdpr' );
	}

	/**
	 * Whether strict confirmation is enabled for CASL.
	 *
	 * @return bool
	 */
	public function is_confirmation_strict() {
		return Plugin::$instance->settings->is_option_enabled( 'strict_confirmation' );
	}

	/**
	 * Get the grace period for confirmation
	 *
	 * @return mixed
	 */
	public function get_grace_period( $as_date = false ) {
		return Plugin::$instance->settings->get_option( 'confirmation_grace_period', 14 );
	}

	/**
	 * Get the
	 *
	 * @throws \Exception
	 *
	 * @param $format
	 *
	 * @return \DateTime|string
	 */
	public function get_grace_period_cutoff_date( $format = false ) {
		$gracePeriod = new \DateTime( 'today', wp_timezone() );
		$gracePeriod->modify( sprintf( '%d days ago', $this->get_grace_period() ) );

		if ( $format ) {
			return $gracePeriod->format( $format );
		}

		return $gracePeriod;
	}

	/**
	 * Return whether the given contact is within the strict confirmation grace period
	 *
	 * @param $id_or_email
	 *
	 * @return bool
	 */
	public function is_in_grace_period( $id_or_email ) {

		$contact = get_contactdata( $id_or_email );

		if ( ! is_a_contact( $contact ) ) {
			return false;
		}

		$grace = absint( $this->get_grace_period() ) * 24 * HOUR_IN_SECONDS;

		$base = absint( $contact->last_optin );

		if ( ! $base ) {
			$base = strtotime( $contact->get_date_created() );
		}

		$time_passed = time() - $base;

		return $time_passed < $grace;
	}

	/**
	 * Same as bounce, but complained
	 *
	 * @param Contact $contact the contact that complained
	 * @param array   $info additional info to display in the activity timeline
	 *
	 * @return False|Activity
	 */
	public static function complained( Contact $contact, array $info = [] ) {
		$contact->change_marketing_preference( self::COMPLAINED );

		return track_activity( $contact, Activity::COMPLAINT, [], $info );
	}

	/**
	 * Track a bounce
	 *
	 * @param Contact $contact the contact that bounced
	 * @param array   $info additional info to display in the activity timeline
	 *
	 * @return Activity|False
	 */
	public static function bounced( Contact $contact, array $info = [] ) {
		$contact->change_marketing_preference( self::HARD_BOUNCE );

		return track_activity( $contact, Activity::BOUNCE, [], $info );
	}

	/**
	 * Track a soft bounce. If too many soft bounces, mark the contact as actually bounced.
	 *
	 * Get the last 4 soft bounces & opens
	 * if there are no opens in the list, all activities are soft bounces
	 * that means we should mark as perma bounce.
	 *
	 * @param Contact $contact the contact that soft bounced
	 * @param array   $info additional info to display in the activity timeline
	 *
	 * @return Activity|False
	 */
	public static function soft_bounced( Contact $contact, array $info = [] ) {

		$recent_activity = db()->activity->query( [
			'select'        => 'activity_type',
			'contact_id'    => $contact->ID,
			'activity_type' => [ Activity::EMAIL_OPENED, Activity::SOFT_BOUNCE ],
			'limit'         => 4,
			'orderby'       => 'timestamp',
			'order'         => 'DESC',
		] );

		$activities = wp_list_pluck( $recent_activity, 'activity_type' );

		// If there is no recent activity, or we don't have enough information, track a soft bounce
		// Or if we recently tracked an open
		if ( count( $recent_activity ) < 4 || in_array( Activity::EMAIL_OPENED, $activities ) ) {
			return track_activity( $contact, Activity::SOFT_BOUNCE, [], $info );
		}

		return self::bounced( $contact, $info );
	}

}
