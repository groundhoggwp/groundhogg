<?php

namespace Groundhogg;

class Preferences {

	// Optin Statuses
	const UNCONFIRMED = 'unconfirmed';
	const CONFIRMED = 'confirmed';
	const UNSUBSCRIBED = 'unsubscribed';
	const HARD_BOUNCE = 'bounced';
	const SPAM = 'spam';
	const COMPLAINED = 'complained';

	/**
	 * No longer in use
	 *
	 * @deprecated
	 */
	const WEEKLY = 'weekly';

	/**
	 * No longer in use
	 *
	 * @deprecated
	 */
	const MONTHLY = 'monthly';


	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );

		// Do last so precedence is given to Groundhogg
		add_filter( 'template_include', [ $this, 'template_include' ], 99 );
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
	 * @todo update for refactored statuses and consent
	 *
	 */
	public function get_optin_status_text( $id_or_email ) {

		if ( ! is_a_contact( $id_or_email ) ) {
			$contact = get_contactdata( $id_or_email );
		} else {
			$contact = $id_or_email;
		}

		if ( ! $contact ) {
			return false;
		}

		// Did not provide GDPR consent or has revoked it
		if ( $this->is_gdpr_enabled() && $this->is_gdpr_strict() && ( ! $contact->has_data_processing_consent() || ! $contact->has_marketing_consent() ) ) {
			$text = __( 'This contact has not agreed to receive email marketing from you.', 'groundhogg' );
		} // Is unconfirmed and outside the confirmation grace period
		else if ( $this->is_confirmation_strict() && $contact->get_optin_status() === Preferences::UNCONFIRMED && strtotime( $contact->date_last_optin ) < strtotime( self::get_min_grace_period_date() ) ) {
			$text = __( 'Unconfirmed. <b>This contact will not receive emails</b>, they are passed the email confirmation grace period.', 'groundhogg' );
		} else {
			switch ( $contact->get_optin_status() ) {
				default:
				case self::UNCONFIRMED:

					$text = _x( 'Unconfirmed. They will receive marketing.', 'optin_status', 'groundhogg' );
					break;
				case self::CONFIRMED:
					$text = _x( 'Confirmed. They will receive marketing.', 'optin_status', 'groundhogg' );
					break;
				case self::UNSUBSCRIBED:
					$text = _x( 'Unsubscribed. They will not receive marketing.', 'optin_status', 'groundhogg' );
					break;
				case self::HARD_BOUNCE:
					$text = _x( 'This email address bounced, they will not receive marketing.', 'optin_status', 'groundhogg' );
					break;
				case self::SPAM:
					$text = _x( 'This contact was marked as spam. They will not receive marketing.', 'optin_status', 'groundhogg' );
					break;
				case self::COMPLAINED:
					$text = _x( 'This contact complained about your emails. They will not receive marketing.', 'optin_status', 'groundhogg' );
					break;
			}
		}

		/**
		 * Filter the optin status description
		 *
		 * @param $text    string
		 * @param $contact Contact
		 */
		return apply_filters( 'groundhogg/preferences/optin_status_description', $text, $contact );
	}

	/**
	 * Return whether the contact is marketable or not.
	 *
	 * @return bool
	 *
	 * @deprecated 3.0 Moved to be part of the Contact class itself
	 *
	 */
	public function is_marketable( $contact ) {

		_deprecated_function( 'Preferences::is_marketable', '3.0', 'Contact::is_marketable' );

		if ( ! is_a_contact( $contact ) ){
			$contact = get_contactdata( $contact );
		}

		if ( ! $contact ) {
			return  false;
		}

		return $contact->is_marketable();
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
	 * @return string
	 */
	public static function string_to_preference( $string ) {

		if ( ! is_string( $string ) ) {
			return self::UNCONFIRMED;
		}

		$string_map = [
			'unconfirm'    => self::UNCONFIRMED,
			'unconfirmed'  => self::UNCONFIRMED,
			'confirm'      => self::CONFIRMED,
			'confirmed'    => self::CONFIRMED,
			'unsubscribe'  => self::UNSUBSCRIBED,
			'unsubscribed' => self::UNSUBSCRIBED,
			'weekly'       => self::CONFIRMED,
			'monthly'      => self::CONFIRMED,
			'hard_bounce'  => self::HARD_BOUNCE,
			'bounce'       => self::HARD_BOUNCE,
			'bounced'      => self::HARD_BOUNCE,
			'complain'     => self::COMPLAINED,
			'complaint'    => self::COMPLAINED,
			'complained'   => self::COMPLAINED,
			'spam'         => self::SPAM,
			'spammed'      => self::SPAM,
		];

		return get_array_var( $string_map, $string, self::UNCONFIRMED );
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
	 * @param int|string|array $preference
	 * @param bool             $old_preference
	 * @param mixed            $default
	 *
	 * @return false|string|array
	 */
	public static function sanitize( $preference, $old_preference = false, $default = self::UNCONFIRMED ) {

		// If an array is passed (which often happens) return an array of sanitized values.
		if ( is_array( $preference ) ) {
			return array_map( [ self::class, 'sanitize' ], $preference );
		}

		if ( is_numeric( $preference ) ) {
			$preference = self::int_to_string( $preference );
		}

		if ( is_numeric( $old_preference ) ) {
			$old_preference = self::int_to_string( $old_preference );
		}

		// monthly/weekly might have been passed, change it to confirmed.
		$preference = self::string_to_preference( $preference );

		return self::is_valid( $preference ) ? $preference : ( $old_preference ?: $default );
	}

	/**
	 * Since refactoring the preferences we want to make everything backwards compatible
	 * So we can use the function to map the preferences to the correct string if an int was provided.
	 *
	 * @param $int
	 *
	 * @return string
	 */
	public static function int_to_string( $int ) {
		$old_preferences_to_new = [
			1 => self::UNCONFIRMED,
			2 => self::CONFIRMED,
			3 => self::UNSUBSCRIBED,
			4 => self::CONFIRMED,
			5 => self::CONFIRMED,
			6 => self::HARD_BOUNCE,
			7 => self::SPAM,
			8 => self::COMPLAINED,
		];

		return get_array_var( $old_preferences_to_new, absint( $int ) );
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
	 * Check if GDPR is enabled throughout the plugin.
	 *
	 * @return bool, whether it's enable or not.
	 */
	public function is_gdpr_enabled() {
		return Plugin::$instance->settings->is_option_enabled( 'enable_gdpr' );
	}

	/**
	 * check if the GDPR strict option is enabled
	 *
	 * @return bool
	 */
	public function is_gdpr_strict() {
		return Plugin::$instance->settings->is_option_enabled( 'strict_gdpr' );
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
	public function get_grace_period() {
		return Plugin::$instance->settings->get_option( 'confirmation_grace_period', 14 );
	}

	protected static $min_grace_period_date;

	/**
	 * Gets the date for which unconfirmed contacts are no longer marketable.
	 *
	 * @return false|string
	 */
	public static function get_min_grace_period_date() {

		if ( self::$min_grace_period_date ) {
			return self::$min_grace_period_date;
		}

		self::$min_grace_period_date = Ymd_His( strtotime( sprintf( '%s days ago', get_option( 'gh_confirmation_grace_period', 14 ) ) ) );

		return self::$min_grace_period_date;
	}

	/**
	 * Return whether the given contact is within the strict confirmation grace period
	 *
	 * @param $id_or_email
	 *
	 * @return bool
	 *
	 * @deprecated 3.0
	 */
	public function is_in_grace_period( $id_or_email ) {

		_deprecated_function( 'is_in_grace_period', '3.0' );

		$contact = get_contactdata( $id_or_email );

		$grace = absint( $this->get_grace_period() ) * 24 * HOUR_IN_SECONDS;

		$base = absint( $contact->last_optin );

		if ( ! $base ) {
			$base = strtotime( $contact->get_date_created() );
		}

		$time_passed = time() - $base;

		return $time_passed < $grace;
	}

}