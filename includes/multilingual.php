<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Form\Form_v2;
use Groundhogg\Form\Submission_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which languages the site is in and which one a contact speaks.
 *
 * Multilingual plugins (WPML, Polylang, TranslatePress) only tell Groundhogg the
 * site's languages and the language a visitor is browsing in. A contact's
 * language is their `locale` meta (see Contact::get_locale()), always a WordPress
 * locale like fr_FR, never a plugin's own language code.
 */
class Multilingual {

	/**
	 * The hidden form input that carries the language of the page a form was shown on
	 */
	const FORM_FIELD = 'gh_locale';

	/**
	 * @var array|null
	 */
	protected static $site_languages = null;

	/**
	 * @var string|null
	 */
	protected static $default_locale = null;

	public function __construct() {
		add_action( 'groundhogg/form/v2/submit', [ $this, 'update_locale_from_form' ], 10, 3 );
		add_action( 'groundhogg/form/submission_handler/after', [ $this, 'update_locale_from_legacy_form' ], 10, 3 );

		// Before Rewrites (9) and Tracking (10), so everything they show is in the contact's language
		add_action( 'template_redirect', [ $this, 'switch_for_managed_page' ], 8 );
	}

	/**
	 * The language the current page was switched to with switch_page_to(), if it was
	 *
	 * @var string
	 */
	protected static $page_locale = '';

	/**
	 * Clear the static caches, for tests
	 *
	 * @return void
	 */
	public static function flush() {
		self::$site_languages = null;
		self::$default_locale = null;

		if ( self::$page_locale ) {
			self::$page_locale = '';
			restore_previous_locale();
		}
	}

	/**
	 * Show the managed page (preferences, unsubscribe, browser view, archive) in the contact's language
	 *
	 * The managed page is its own template, without the theme's header and footer, so everything on it is this
	 * plugin's text and switching the locale translates all of it, with any multilingual plugin or none. The links in
	 * emails stay as they are.
	 *
	 * @return void
	 */
	public function switch_for_managed_page() {

		if ( ! is_managed_page() ) {
			return;
		}

		self::switch_page_to( self::get_managed_page_locale() );
	}

	/**
	 * The language to show the current managed page in: the one of the email being looked at when it's the version of
	 * an email for a language, otherwise the contact's
	 *
	 * @return string a locale, or '' to leave the page as it is
	 */
	public static function get_managed_page_locale(): string {

		$contact = get_contactdata();
		$locale  = '';

		if ( is_a_contact( $contact ) ) {

			$email = null;

			switch ( get_query_var( 'subpage' ) ) {
				case 'browser_view':
					$event = new Event( absint( get_query_var( 'event_id' ) ), 'events', get_query_var( 'use_queued' ) ? 'queued_id' : 'ID' );

					// someone else's email is refused by the page, and says nothing about this contact's language
					if ( $event->exists() && absint( $event->get_contact_id() ) === $contact->get_id() && $event->get_email_id() ) {
						$email = new Email( $event->get_email_id() );
					}
					break;
				case 'emails':
					$email = new Email( absint( get_query_var( 'email_id' ) ) );
					break;
			}

			// the email as it was sent, which may not be the language they have now
			if ( $email && $email->exists() && $email->is_variation() ) {
				$locale = self::sanitize_locale( $email->get_meta( Email_Variations::LOCALE_KEY ) );
			}

			if ( ! $locale ) {
				$locale = self::sanitize_locale( $contact->get_locale() );
			}
		}

		/**
		 * Filters the language a managed page is shown in
		 *
		 * @param string        $locale  '' leaves the page in the language it would be in anyway
		 * @param Contact|false $contact the contact the page is for, if it's known
		 */
		return self::sanitize_locale( apply_filters( 'groundhogg/multilingual/managed_page_locale', $locale, $contact ) );
	}

	/**
	 * Switch the rest of the page to a language. Safe to call again, like after the contact picks another language on
	 * the preferences page: the last call wins.
	 *
	 * Nothing changes, and the page shows as it would have, when the site isn't in that language, when it's the page's
	 * language already, or when WordPress doesn't have the language installed.
	 *
	 * @param string $locale
	 *
	 * @return bool whether the page is in that language now
	 */
	public static function switch_page_to( $locale ): bool {

		$locale = self::sanitize_locale( $locale );

		if ( ! $locale || ! self::is_site_locale( $locale ) ) {
			return false;
		}

		// what translations are loaded for, which is the switched locale, and the site's or the multilingual plugin's
		// before any switch
		if ( $locale === determine_locale() ) {
			return true;
		}

		// only one switch of ours at a time, so the locales switched to don't pile up
		if ( self::$page_locale ) {
			self::$page_locale = '';
			restore_previous_locale();
		}

		// back to the language the page started in
		if ( $locale === determine_locale() ) {
			return true;
		}

		// false when WordPress doesn't have the language
		if ( ! switch_to_locale( $locale ) ) {
			return false;
		}

		self::$page_locale = $locale;

		// Polylang adds its locale filter after WordPress' locale switcher added its own, so get_locale() would still
		// be the language of the URL. Added once, and it does nothing when the page isn't switched.
		if ( ! has_filter( 'locale', [ __CLASS__, 'filter_page_locale' ] ) ) {
			add_filter( 'locale', [ __CLASS__, 'filter_page_locale' ], PHP_INT_MAX );
		}

		return true;
	}

	/**
	 * Keeps get_locale() on the language the page was switched to, whatever was filtered in before
	 *
	 * @param string $locale
	 *
	 * @return string
	 */
	public static function filter_page_locale( $locale ) {

		global $wp_locale_switcher;

		if ( ! self::$page_locale || ! is_locale_switched() || ! $wp_locale_switcher instanceof \WP_Locale_Switcher ) {
			return $locale;
		}

		// A later switch, like sending an email in another contact's language while the page loads, has its own locale.
		// Asked of the switcher, not determine_locale(), which calls get_locale() and would end up back here.
		return $wp_locale_switcher->get_switched_locale() === self::$page_locale ? self::$page_locale : $locale;
	}

	/**
	 * The multilingual plugin in use, if any
	 *
	 * @return string wpml|polylang|translatepress, or an empty string
	 */
	public static function get_plugin(): string {

		$plugin = '';

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$plugin = 'wpml';
		} else if ( function_exists( 'pll_languages_list' ) ) {
			$plugin = 'polylang';
		} else if ( class_exists( 'TRP_Translate_Press' ) ) {
			$plugin = 'translatepress';
		}

		/**
		 * Filters which multilingual plugin Groundhogg reads languages from
		 *
		 * Return any non-empty value and use the groundhogg/multilingual/site_languages and
		 * groundhogg/multilingual/current_locale filters to support another plugin.
		 *
		 * @param string $plugin wpml|polylang|translatepress or ''
		 */
		return (string) apply_filters( 'groundhogg/multilingual/plugin', $plugin );
	}

	/**
	 * Whether the site is multilingual
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return self::get_plugin() !== '';
	}

	/**
	 * Turn anything locale-like (fr_FR, fr-FR, fr-fr, de_DE_formal) into a WordPress locale, or an empty
	 * string if it isn't one. Safe to put in SQL.
	 *
	 * @param mixed $locale
	 *
	 * @return string
	 */
	public static function sanitize_locale( $locale ): string {

		if ( ! is_string( $locale ) ) {
			return '';
		}

		$locale = str_replace( '-', '_', trim( $locale ) );

		if ( ! preg_match( '/^[a-zA-Z]{2,3}(_[a-zA-Z0-9]{2,12}){0,2}$/', $locale ) ) {
			return '';
		}

		$parts    = explode( '_', $locale );
		$parts[0] = strtolower( $parts[0] );

		// The region is upper case, like fr_FR, but a variant like _formal is not
		if ( isset( $parts[1] ) && preg_match( '/^[a-zA-Z]{2}$/', $parts[1] ) ) {
			$parts[1] = strtoupper( $parts[1] );
		}

		return implode( '_', $parts );
	}

	/**
	 * The site's own locale, ignoring the language the current visitor is browsing in and any locale switch
	 *
	 * @return string
	 */
	protected static function get_site_locale(): string {

		if ( ! is_locale_switched() ) {
			return get_locale();
		}

		// get_locale() is the switched locale right now
		$locale = defined( 'WPLANG' ) && WPLANG ? WPLANG : ( is_multisite() ? get_site_option( 'WPLANG' ) : get_option( 'WPLANG' ) );

		return self::sanitize_locale( $locale ) ?: 'en_US';
	}

	/**
	 * The site's default language, which is what a contact speaks unless we know otherwise
	 *
	 * @return string
	 */
	public static function get_default_locale(): string {

		if ( self::$default_locale ) {
			return self::$default_locale;
		}

		$locale = '';

		switch ( self::get_plugin() ) {
			case 'wpml':
				$languages = self::wpml_languages();
				$code      = apply_filters( 'wpml_default_language', null );
				$locale    = $languages[ $code ]['default_locale'] ?? '';
				break;
			case 'polylang':
				$locale = function_exists( 'pll_default_language' ) ? pll_default_language( 'locale' ) : '';
				break;
			case 'translatepress':
				$settings = get_option( 'trp_settings', [] );
				$locale   = is_array( $settings ) ? ( $settings['default-language'] ?? '' ) : '';
				break;
		}

		$locale = self::sanitize_locale( $locale ) ?: self::sanitize_locale( self::get_site_locale() ) ?: 'en_US';

		/**
		 * Filters the site's default language
		 *
		 * @param string $locale
		 */
		$locale = self::sanitize_locale( apply_filters( 'groundhogg/multilingual/default_locale', $locale ) ) ?: 'en_US';

		// Only cache it once WordPress knows the language, plugins set it up late
		if ( did_action( 'init' ) ) {
			self::$default_locale = $locale;
		}

		return $locale;
	}

	/**
	 * WPML's active languages, keyed by WPML's language code
	 *
	 * @return array
	 */
	protected static function wpml_languages(): array {
		$languages = apply_filters( 'wpml_active_languages', null, [ 'skip_missing' => 0 ] );

		return is_array( $languages ) ? $languages : [];
	}

	/**
	 * The languages the site is in, default first
	 *
	 * @return array[] each with locale, name and default
	 */
	public static function get_site_languages(): array {

		if ( is_array( self::$site_languages ) ) {
			return self::$site_languages;
		}

		$names = [];

		switch ( self::get_plugin() ) {
			case 'wpml':
				foreach ( self::wpml_languages() as $language ) {
					$names[ $language['default_locale'] ?? '' ] = $language['native_name'] ?? '';
				}
				break;
			case 'polylang':
				$languages = pll_languages_list( [ 'fields' => '' ] );
				foreach ( is_array( $languages ) ? $languages : [] as $language ) {
					if ( is_object( $language ) ) {
						$names[ $language->locale ?? '' ] = $language->name ?? '';
					}
				}
				break;
			case 'translatepress':
				$settings = get_option( 'trp_settings', [] );
				foreach ( is_array( $settings ) ? (array) ( $settings['translation-languages'] ?? [] ) : [] as $locale ) {
					$names[ $locale ] = '';
				}
				break;
		}

		$default = self::get_default_locale();

		$languages = [
			$default => [
				'locale'  => $default,
				'name'    => $names[ $default ] ?? '',
				'default' => true,
			]
		];

		foreach ( $names as $locale => $name ) {

			$locale = self::sanitize_locale( $locale );

			if ( ! $locale || isset( $languages[ $locale ] ) ) {
				continue;
			}

			$languages[ $locale ] = [
				'locale'  => $locale,
				'name'    => $name,
				'default' => false,
			];
		}

		foreach ( $languages as $locale => &$language ) {
			$language['name'] = $language['name'] ? sanitize_text_field( $language['name'] ) : self::native_name( $locale );
		}

		/**
		 * Filters the site's languages
		 *
		 * @param array[] $languages each with locale, name and default, keyed by locale
		 */
		$languages = apply_filters( 'groundhogg/multilingual/site_languages', $languages );

		$languages = array_values( array_filter( (array) $languages, function ( $language ) {
			return is_array( $language ) && self::sanitize_locale( $language['locale'] ?? '' );
		} ) );

		if ( did_action( 'init' ) ) {
			self::$site_languages = $languages;
		}

		return $languages;
	}

	/**
	 * How many contacts get emails in each language. Contacts without a locale speak the default language.
	 *
	 * @return int[] locale => contacts, most first
	 */
	public static function count_contacts_by_locale(): array {

		$default = self::get_default_locale();

		$query = new Table_Query( 'contacts' );
		$alias = $query->joinMeta( 'locale' );

		// sanitize_locale() only lets through letters, numbers and underscores, so $default is safe in SQL
		$col = "COALESCE(NULLIF($alias.meta_value,''),'$default')";
		$query->add_safe_column( $col );
		$query->setSelect( [ $col, 'locale' ], [ 'COUNT(*)', 'contacts' ] )->setGroupby( 'locale' );

		$counts = [];

		foreach ( $query->get_results() as $row ) {
			$locale = self::sanitize_locale( $row->locale );

			if ( $locale ) {
				$counts[ $locale ] = ( $counts[ $locale ] ?? 0 ) + absint( $row->contacts );
			}
		}

		arsort( $counts );

		return $counts;
	}

	/**
	 * The locales of the site's languages, default first
	 *
	 * @return string[]
	 */
	public static function get_site_locales(): array {
		return wp_list_pluck( self::get_site_languages(), 'locale' );
	}

	/**
	 * Whether the site is in this language
	 *
	 * @param string $locale
	 *
	 * @return bool
	 */
	public static function is_site_locale( $locale ): bool {
		$locale = self::sanitize_locale( $locale );

		return $locale && in_array( $locale, self::get_site_locales(), true );
	}

	/**
	 * A language's name in that language, like "Français (France)"
	 *
	 * @param string $locale
	 *
	 * @return string
	 */
	public static function get_locale_name( $locale ): string {

		$locale = self::sanitize_locale( $locale );

		if ( ! $locale ) {
			return '';
		}

		// the multilingual plugin's name for it
		foreach ( self::get_site_languages() as $language ) {
			if ( $language['locale'] === $locale && $language['name'] ) {
				return $language['name'];
			}
		}

		return self::native_name( $locale );
	}

	/**
	 * A language's name in that language from PHP's intl extension, or the locale when it isn't installed
	 *
	 * @param string $locale
	 *
	 * @return string
	 */
	protected static function native_name( string $locale ): string {

		$name = class_exists( '\Locale' ) ? \Locale::getDisplayName( $locale, $locale ) : '';

		if ( ! $name || $name === $locale ) {
			return $locale === 'en_US' ? 'English (United States)' : $locale;
		}

		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) . mb_substr( $name, 1 ) : ucfirst( $name );
	}

	/**
	 * The language the current visitor is browsing the site in
	 *
	 * @return string
	 */
	public static function get_current_locale(): string {

		$locale = '';

		switch ( self::get_plugin() ) {
			case 'wpml':
				$languages = self::wpml_languages();
				$code      = apply_filters( 'wpml_current_language', null );
				$locale    = $languages[ $code ]['default_locale'] ?? '';
				break;
			case 'polylang':
				$locale = function_exists( 'pll_current_language' ) ? pll_current_language( 'locale' ) : '';
				break;
			case 'translatepress':
				global $TRP_LANGUAGE;
				$locale = is_string( $TRP_LANGUAGE ) ? $TRP_LANGUAGE : '';
				break;
		}

		$locale = self::sanitize_locale( $locale ) ?: self::sanitize_locale( get_locale() );

		/**
		 * Filters the language the current visitor is browsing the site in
		 *
		 * @param string $locale
		 */
		return self::sanitize_locale( apply_filters( 'groundhogg/multilingual/current_locale', $locale ) );
	}

	/**
	 * The hidden input that tells a form submission which language the form was shown in.
	 * Form submissions go through the REST API, where the multilingual plugin can't tell which language the page was in.
	 *
	 * @return string
	 */
	public static function get_form_input(): string {

		if ( ! self::is_active() ) {
			return '';
		}

		$locale = self::get_current_locale();

		if ( ! $locale ) {
			return '';
		}

		return html()->input( [
			'type'  => 'hidden',
			'name'  => self::FORM_FIELD,
			'value' => $locale,
			'class' => '',
		] );
	}

	/**
	 * Set the contact's language to the one they were browsing in when they submitted a form
	 *
	 * @param Contact $contact
	 * @param string  $locale
	 *
	 * @return bool whether the locale was changed
	 */
	protected function maybe_update_contact_locale( $contact, string $locale ): bool {

		if ( ! is_a_contact( $contact ) || ! self::is_active() || ! self::is_site_locale( $locale ) ) {
			return false;
		}

		$locale = self::sanitize_locale( $locale );

		/**
		 * Filters whether a form submission changes the contact's language to the one the form was shown in
		 *
		 * @param bool    $update
		 * @param Contact $contact
		 * @param string  $locale
		 */
		if ( ! apply_filters( 'groundhogg/multilingual/update_locale_from_form', true, $contact, $locale ) ) {
			return false;
		}

		if ( $contact->get_meta( 'locale' ) === $locale ) {
			return false;
		}

		$contact->set_locale( $locale );

		return true;
	}

	/**
	 * When a form is submitted
	 *
	 * @param Submission $submission
	 * @param Form_v2    $form
	 * @param Contact    $contact
	 *
	 * @return void
	 */
	public function update_locale_from_form( $submission, $form, $contact ) {
		$locale = get_post_var( self::FORM_FIELD );

		if ( ! is_string( $locale ) || ! $locale ) {
			return;
		}

		$this->maybe_update_contact_locale( $contact, $locale );
	}

	/**
	 * When a legacy form is submitted, which posts to the page it's on
	 *
	 * @param Submission         $submission
	 * @param Contact            $contact
	 * @param Submission_Handler $handler
	 *
	 * @return void
	 */
	public function update_locale_from_legacy_form( $submission, $contact, $handler = null ) {

		$locale = get_post_var( self::FORM_FIELD );

		if ( ! is_string( $locale ) || ! $locale ) {

			// In admin-ajax, and when an admin submits for a contact, the language isn't the page's
			if ( is_admin() ) {
				return;
			}

			$locale = self::get_current_locale();
		}

		$this->maybe_update_contact_locale( $contact, $locale );
	}
}
