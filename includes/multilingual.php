<?php

namespace Groundhogg;

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
	}

	/**
	 * Clear the static caches, for tests
	 *
	 * @return void
	 */
	public static function flush() {
		self::$site_languages = null;
		self::$default_locale = null;
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
			$language['name'] = $language['name'] ? sanitize_text_field( $language['name'] ) : self::get_locale_name( $locale );
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

		foreach ( is_array( self::$site_languages ) ? self::$site_languages : [] as $language ) {
			if ( $language['locale'] === $locale && $language['name'] ) {
				return $language['name'];
			}
		}

		$name = '';

		if ( class_exists( '\Locale' ) ) {
			$name = \Locale::getDisplayName( $locale, $locale );
		}

		if ( ! $name || $name === $locale ) {
			$name = $locale === 'en_US' ? 'English (United States)' : $locale;
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
