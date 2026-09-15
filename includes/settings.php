<?php

namespace Groundhogg;

use Groundhogg\Form\Form_Fields;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Settings {

	/**
	 * Settings registered via add_setting(), keyed by the fully-prefixed option id.
	 *
	 * This is the preferred way to register a Groundhogg setting going forward: unlike
	 * Settings_Page (admin/settings/settings-page.php), which is only concerned with
	 * rendering settings fields across tabs/sections in wp-admin, this registry exists so a
	 * setting's type/enum/description are known in one place and can be reused to build
	 * ability input/output schemas (see get_setting_schema()/get_settings_schema()), and so
	 * a sanitize_callback is always applied - whether the option is saved from wp-admin, the
	 * REST API, an ability, or straight update_option()/add_option() calls - since it hooks
	 * WP's own `sanitize_option_{$option}` filter.
	 *
	 * @var array<string, array>
	 */
	protected array $registered_settings = [];

	/**
	 * Groups registered via add_group(), keyed by group id. Purely organizational (e.g. to
	 * fetch related settings together with get_settings()) - not tied to Settings_Page's tabs.
	 *
	 * @var array<string, array>
	 */
	protected array $registered_groups = [];

	public function __construct() {
		$this->register_settings();
	}

	/**
	 * Register the settings that have been migrated to the new registry so far.
	 *
	 * Only the business_info settings are registered here for now - this is meant to
	 * establish the shape of the registry, not to migrate every setting at once. Settings
	 * not registered here are unaffected and continue to work exactly as before.
	 *
	 * @return void
	 */
	protected function register_settings() {

		$this->add_group( 'business_info', [
			'label' => __( 'Business Info', 'groundhogg' ),
		] );

		$this->add_setting( 'business_name', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'The business name as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'street_address_1', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business street address, line 1, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'street_address_2', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business street address, line 2 (optional), as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'city', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business city, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'zip_or_postal', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business zip/postal code, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'region', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business state/province/region, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'country', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business country, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_setting( 'phone', [
			'group'       => 'business_info',
			'type'        => 'string',
			'description' => __( 'Business phone number, as it appears in the email footer.', 'groundhogg' ),
		] );

		$this->add_group( 'policies', [
			'label' => __( 'Policies', 'groundhogg' ),
		] );

		$this->add_setting( 'privacy_policy', [
			'group'       => 'policies',
			'type'        => 'string',
			'description' => __( 'Link to the site\'s privacy policy, used in emails and preference/consent forms.', 'groundhogg' ),
		] );

		$this->add_setting( 'terms', [
			'group'       => 'policies',
			'type'        => 'string',
			'description' => __( 'Link to the site\'s terms & conditions, used in emails and preference/consent forms.', 'groundhogg' ),
		] );

		$this->add_group( 'double_optin', [
			'label' => __( 'Double Opt-in', 'groundhogg' ),
		] );

		$this->add_setting( 'strict_confirmation', [
			'group'       => 'double_optin',
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Only send email to contacts with a confirmed email address, outside of the grace period.', 'groundhogg' ),
		] );

		$this->add_setting( 'confirmation_grace_period', [
			'group'       => 'double_optin',
			'type'        => 'integer',
			'default'     => 14,
			'schema'      => [ 'minimum' => 0, 'maximum' => 365 ],
			'description' => __( 'Number of days a newly-created, unconfirmed contact can still be emailed before strict_confirmation cuts them off.', 'groundhogg' ),
		] );

		$this->add_group( 'gdpr', [
			'label' => __( 'GDPR', 'groundhogg' ),
		] );

		$this->add_setting( 'enable_gdpr', [
			'group'       => 'gdpr',
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Add a consent checkbox to forms and a "Delete Everything" button to the preferences page.', 'groundhogg' ),
		] );

		$this->add_setting( 'strict_gdpr', [
			'group'       => 'gdpr',
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Never email a contact without explicit consent. Only takes effect when enable_gdpr is also on.', 'groundhogg' ),
		] );

		$this->add_group( 'cookies', [
			'label' => __( 'Cookies', 'groundhogg' ),
		] );

		$this->add_setting( 'disable_unnecessary_cookies', [
			'group'       => 'cookies',
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Prevent the lead-source, page-visits, and form-impressions cookies from being set.', 'groundhogg' ),
		] );

		$this->add_group( 'overrides', [
			'label' => __( 'Sender Profiles', 'groundhogg' ),
		] );

		$this->add_setting( 'override_from_name', [
			'group'       => 'overrides',
			'type'        => 'string',
			'description' => __( 'Fallback "From Name" used when an email has none set. Falls back to the site name when empty.', 'groundhogg' ),
		] );

		$this->add_setting( 'override_from_email', [
			'group'             => 'overrides',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'description'       => __( 'Fallback "From Email" used when an email has none set. Falls back to the site admin email when empty.', 'groundhogg' ),
		] );

		$this->add_setting( 'support_license', [
			'type'        => 'string',
			'sensitive'   => true,
			'description' => __( 'License key used to open support tickets from the Help page.', 'groundhogg' ),
		] );

		$this->add_setting( 'guided_setup_finished', [
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Whether the guided setup wizard has been completed.', 'groundhogg' ),
		] );

		$this->add_setting( 'is_send_time_optimization_enabled', [
			'type'        => 'boolean',
			'default'     => false,
			'description' => __( 'Whether broadcasts are, by default, sent using send-time optimization rather than immediately/scheduled as-is.', 'groundhogg' ),
		] );

		/**
		 * gh_master_license is intentionally NOT registered here - get_option('gh_master_license')
		 * is intercepted by a `pre_option_gh_master_license` filter (License_Manager) that computes
		 * it live from the gh_licenses option, so it isn't really a plain, independently-writable
		 * setting. See License_Manager::get_master_license().
		 */

		$this->add_setting( 'custom_reports', [
			'type'              => 'array',
			'sanitize_callback' => 'Groundhogg\sanitize_payload',
			'description'       => __( 'Custom reports shown on the Reports page. Each item is an object with at least id, name, and type; other properties vary by report type.', 'groundhogg' ),
		] );

		$this->add_group( 'email_editor', [
			'label' => __( 'Email Editor', 'groundhogg' ),
		] );

		$this->add_setting( 'email_editor_color_palette', [
			'group'             => 'email_editor',
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_deep_text_setting' ],
			'description'       => __( 'The color swatches offered in the email block editor. A flat array of hex color strings.', 'groundhogg' ),
		] );

		$this->add_setting( 'email_editor_global_fonts', [
			'group'             => 'email_editor',
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_deep_text_setting' ],
			'description'       => __( 'Named font presets available in the email block editor. Each item is an object with name, id, and a style object (lineHeight, fontFamily, fontWeight, fontSize, fontStyle, textTransform).', 'groundhogg' ),
		] );

		$this->add_setting( 'email_editor_global_social_accounts', [
			'group'             => 'email_editor',
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_deep_text_setting' ],
			'description'       => __( 'Social accounts available to the email block editor\'s social links block. Each item is a 2-element [platform, url] tuple, not an object.', 'groundhogg' ),
		] );

		$this->add_setting( 'email_editor_block_defaults', [
			'group'             => 'email_editor',
			'type'              => 'object',
			'sanitize_callback' => 'Groundhogg\sanitize_payload',
			'description'       => __( 'Default attributes applied to new blocks in the email block editor, keyed by block type. Always includes a version key.', 'groundhogg' ),
		] );

		$this->add_group( 'custom_fields', [
			'label' => __( 'Custom Fields', 'groundhogg' ),
		] );

		$this->add_setting( 'custom_profile_fields', [
			'group'             => 'custom_fields',
			'type'              => 'array',
			'sanitize_callback' => [ Form_Fields::class, 'sanitize_form_and_map' ],
			'description'       => __( 'Custom fields shown on the contact profile edit screen. A 2-element [form, map] tuple: form is a list of field definitions ({id, name, label, description, required, type, mapFrom, mapTo}); map relates field ids to contact meta keys.', 'groundhogg' ),
		] );

		$this->add_setting( 'custom_preference_fields', [
			'group'             => 'custom_fields',
			'type'              => 'array',
			'sanitize_callback' => [ Form_Fields::class, 'sanitize_form_and_map' ],
			'description'       => __( 'Custom fields shown on the contact-facing preferences page. Same 2-element [form, map] tuple shape as custom_profile_fields.', 'groundhogg' ),
		] );
	}

	/**
	 * Shared sanitize_callback for settings whose value is an arbitrarily-nested
	 * array/object of strings - deep-sanitizes every scalar leaf with
	 * sanitize_text_field(), same as the REST options API's own handling of these
	 * settings pre-registry (see filter_option_sanitize_callback() in includes/filters.php).
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function sanitize_deep_text_setting( $value ) {
		return map_deep( $value, 'sanitize_text_field' );
	}

	/**
	 * Register a group settings can be organized under via add_setting()'s 'group' arg.
	 * This is purely organizational - unlike Settings_Page's tabs/sections, it has no
	 * bearing on wp-admin rendering.
	 *
	 * @param string $id   the group id
	 * @param array  $args ['label' => string, 'description' => string]
	 *
	 * @return void
	 */
	public function add_group( string $id, array $args = [] ) {
		$this->registered_groups[ $id ] = wp_parse_args( $args, [
			'label'       => $id,
			'description' => '',
		] );
	}

	/**
	 * Get the groups registered via add_group(), keyed by group id.
	 *
	 * @return array<string, array>
	 */
	public function get_groups(): array {
		return $this->registered_groups;
	}

	/**
	 * Register a setting so it's known outside of wherever it happens to be read/written -
	 * in particular so it can be exposed as part of an ability's input/output schema (see
	 * get_setting_schema()) - and so a sanitize_callback always runs on it via WP's
	 * `sanitize_option_{$option}` filter, which core applies from both add_option() and
	 * update_option().
	 *
	 * @param string $id   the option id, with or without the gh_ prefix - it will be added
	 *                     automatically, matching prefix()/get_option()/update_option()
	 * @param array  $args {
	 *
	 * @type string $type json schema type: string|integer|number|boolean|array|object
	 * @type array|callable $schema extra JSON-schema properties (e.g. enum, minimum,
	 *                     maximum, format, items, pattern) merged on top of {type,
	 *                     description} by get_setting_schema(). May be a callable
	 *                     returning that array instead, for schemas that depend on
	 *                     dynamic data (e.g. an enum built from other settings) - the
	 *                     whole array is expected to be dynamic in that case, not just
	 *                     the 'enum' key within it. enum/minimum/maximum/pattern/maxLength
	 *                     here are also enforced when the setting is sanitized (see
	 *                     enforce_schema_constraints()) - enum/pattern reject to 'default',
	 *                     minimum/maximum clamp, maxLength truncates. Anything else (items,
	 *                     format, etc.) is currently descriptive only.
	 * @type string $description human-readable description, used for ability schemas
	 * @type mixed $default default value; also used as the fallback when an incoming
	 *                     value fails its schema's enum/pattern check
	 * @type callable|string $sanitize_callback called on the value via
	 *                     `sanitize_option_{$id}`; guessed from 'type' when omitted
	 * @type string $group optional id of a group registered via add_group()
	 * @type bool $sensitive mark as a secret (license key, API key, token, etc.) - its
	 *                     value is redacted by get_redacted_value() (used by the
	 *                     list-settings/update-settings abilities) unless WP_DEBUG is on
	 *                     or the `groundhogg/settings/expose_sensitive_values` filter
	 *                     returns true. get_setting_schema() notes this in the
	 *                     description whenever the value is currently being redacted.
	 *                     Does NOT affect get_option() - internal code always gets the
	 *                     real value.
	 *                     }
	 *
	 * @return void
	 */
	public function add_setting( string $id, array $args = [] ) {

		$id = $this->prefix( $id );

		$args = wp_parse_args( $args, [
			'type'              => 'string',
			'schema'            => [],
			'description'       => '',
			'default'           => null,
			'sanitize_callback' => null,
			'group'             => null,
			'sensitive'         => false,
		] );

		if ( ! $args['sanitize_callback'] ) {
			$args['sanitize_callback'] = $this->guess_sanitize_callback( $args['type'] );
		}

		$this->registered_settings[ $id ] = $args;

		add_filter( "sanitize_option_{$id}", [ $this, 'sanitize_registered_setting' ], 10, 2 );
	}

	/**
	 * Best-guess sanitize_callback for a setting based on its declared type, used by
	 * add_setting() when one isn't explicitly provided.
	 *
	 * @param string $type
	 *
	 * @return callable
	 */
	protected function guess_sanitize_callback( string $type ) {

		switch ( $type ) {
			case 'integer':
			case 'int':
				return 'absint';
			case 'number':
			case 'float':
			case 'double':
				return 'floatval';
			case 'boolean':
			case 'bool':
				return [ $this, 'sanitize_boolean_setting' ];
			case 'array':
				return function ( $value ) {
					return is_array( $value ) ? array_values( $value ) : (array) $value;
				};
			case 'object':
				return function ( $value ) {
					return is_object( $value ) ? $value : (object) $value;
				};
			case 'string':
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Default boolean sanitize_callback (see guess_sanitize_callback()). A handful of
	 * checkbox settings pre-date this registry and still submit `name="option[]"` from the
	 * classic wp-admin settings form for backwards compat (e.g. gh_strict_confirmation), so
	 * the raw incoming value may be `['on']`/`[]` as well as the plain `'on'`/`''` a REST or
	 * ability caller would send - both are normalized to a real PHP bool here, matching what
	 * is_option_enabled() already tolerates when reading.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function sanitize_boolean_setting( $value ): bool {

		if ( is_array( $value ) ) {
			return in_array( 'on', $value, true ) || in_array( true, $value, true );
		}

		return rest_sanitize_boolean( $value );
	}

	/**
	 * Resolve a setting's 'schema' arg to a plain array - it may itself be a callable
	 * returning one, for schemas that depend on dynamic data.
	 *
	 * @param array|callable $schema
	 *
	 * @return array
	 */
	protected function resolve_schema( $schema ) {

		if ( is_callable( $schema ) ) {
			$schema = call_user_func( $schema );
		}

		return is_array( $schema ) ? $schema : [];
	}

	/**
	 * The `sanitize_option_{$option}` callback for every setting registered via
	 * add_setting(). Runs the setting's sanitize_callback, then enforces whatever
	 * constraints its resolved schema declares (see enforce_schema_constraints()).
	 *
	 * @param mixed  $value
	 * @param string $option
	 *
	 * @return mixed
	 */
	public function sanitize_registered_setting( $value, string $option ) {

		if ( ! isset( $this->registered_settings[ $option ] ) ) {
			return $value;
		}

		$args = $this->registered_settings[ $option ];

		if ( $args['sanitize_callback'] ) {
			$value = call_user_func( $args['sanitize_callback'], $value );
		}

		$schema = $this->resolve_schema( $args['schema'] );

		return $this->enforce_schema_constraints( $value, $schema, $args['default'] );
	}

	/**
	 * Enforce the JSON-schema constraints a setting's resolved schema declares, against
	 * an already-sanitize_callback'd value:
	 *
	 * - enum: value must be one of the allowed choices (arrays are filtered down to the
	 *   allowed subset); otherwise falls back to $default
	 * - minimum/maximum: numeric values are clamped into range, not rejected
	 * - pattern: string must match (as a PCRE pattern, no delimiters); otherwise falls
	 *   back to $default
	 * - maxLength: strings longer than this are truncated
	 *
	 * Anything else a schema declares (items, format, minLength, etc.) is currently
	 * descriptive only - add a case here if a setting needs it actually enforced.
	 *
	 * @param mixed $value
	 * @param array $schema
	 * @param mixed $default fallback used when a check rejects the value outright
	 *                        (enum/pattern) rather than clamping/truncating it
	 *
	 * @return mixed
	 */
	protected function enforce_schema_constraints( $value, array $schema, $default ) {

		if ( ! empty( $schema['enum'] ) ) {
			if ( is_array( $value ) ) {
				$value = array_values( array_intersect( $value, $schema['enum'] ) );
			} else if ( ! in_array( $value, $schema['enum'], true ) ) {
				return $default;
			}
		}

		if ( is_numeric( $value ) ) {
			if ( isset( $schema['minimum'] ) && $value < $schema['minimum'] ) {
				$value = $schema['minimum'];
			}
			if ( isset( $schema['maximum'] ) && $value > $schema['maximum'] ) {
				$value = $schema['maximum'];
			}
		}

		if ( is_string( $value ) ) {
			if ( isset( $schema['pattern'] ) && ! preg_match( '#' . $schema['pattern'] . '#', $value ) ) {
				return $default;
			}
			if ( isset( $schema['maxLength'] ) && mb_strlen( $value ) > $schema['maxLength'] ) {
				$value = mb_substr( $value, 0, $schema['maxLength'] );
			}
		}

		return $value;
	}

	/**
	 * Whether a setting was registered with 'sensitive' => true (a license key, API key,
	 * token, or other secret).
	 *
	 * @param string $id with or without the gh_ prefix
	 *
	 * @return bool
	 */
	public function is_setting_sensitive( string $id ): bool {
		$setting = $this->get_setting( $id );

		return $setting ? (bool) $setting['sensitive'] : false;
	}

	/**
	 * Whether sensitive setting values should currently be exposed in full - true when
	 * WP_DEBUG is on, or when the `groundhogg/settings/expose_sensitive_values` filter is
	 * used to opt back in (e.g. for a trusted internal tool). False means
	 * get_redacted_value() masks sensitive values.
	 *
	 * @param string $id with or without the gh_ prefix - passed to the filter so it can
	 *                    choose to expose only specific settings
	 *
	 * @return bool
	 */
	public function should_expose_sensitive_value( string $id ): bool {

		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		/**
		 * Filter whether a sensitive setting's real value should be exposed (e.g. by
		 * groundhogg/list-settings) instead of the redacted placeholder.
		 *
		 * @param bool   $expose defaults to whether WP_DEBUG is on
		 * @param string $id     the fully-prefixed option id
		 */
		return (bool) apply_filters( 'groundhogg/settings/expose_sensitive_values', $debug, $this->prefix( $id ) );
	}

	/**
	 * Redact a sensitive setting's value for display/export (e.g. the list-settings and
	 * update-settings abilities). Non-sensitive settings, and empty values (nothing to
	 * hide), always pass through unchanged. A sensitive value is only ever returned in
	 * full when BOTH:
	 *
	 * - $requested is true - the caller explicitly asked for this specific id (e.g. named
	 *   it in an ability's `reveal` input) - naming a setting is a deliberate, auditable
	 *   act, unlike a blanket "don't redact" flag that could reveal every secret at once
	 *   without anyone having asked for each one by name; and
	 * - should_expose_sensitive_value() allows it - the site-level gate (WP_DEBUG or the
	 *   `groundhogg/settings/expose_sensitive_values` filter), which decides whether
	 *   exposing sensitive values is even possible on this site at all.
	 *
	 * Neither alone is enough - an id in $requested is still redacted if the site-level
	 * gate is closed, and the gate being open does NOT reveal anything that wasn't
	 * explicitly requested. This never affects get_option() itself - internal code always
	 * sees the real value.
	 *
	 * @param string $id        with or without the gh_ prefix
	 * @param mixed  $value     the value to (maybe) redact - usually get_option( $id )
	 * @param bool   $requested whether the caller explicitly asked to see this id
	 *
	 * @return mixed
	 */
	public function get_redacted_value( string $id, $value, bool $requested = false ) {

		if ( empty( $value ) || ! $this->is_setting_sensitive( $id ) ) {
			return $value;
		}

		if ( $requested && $this->should_expose_sensitive_value( $id ) ) {
			return $value;
		}

		return is_string( $value ) ? str_repeat( '•', 8 ) : true;
	}

	/**
	 * Whether a setting has been registered via add_setting().
	 *
	 * @param string $id with or without the gh_ prefix
	 *
	 * @return bool
	 */
	public function is_setting_registered( string $id ): bool {
		return isset( $this->registered_settings[ $this->prefix( $id ) ] );
	}

	/**
	 * Get the args a setting was registered with via add_setting().
	 *
	 * @param string $id with or without the gh_ prefix
	 *
	 * @return array|null
	 */
	public function get_setting( string $id ) {
		return $this->registered_settings[ $this->prefix( $id ) ] ?? null;
	}

	/**
	 * Get the registered settings, optionally limited to one group.
	 *
	 * @param string|null $group a group id registered via add_group()
	 *
	 * @return array<string, array>
	 */
	public function get_settings( ?string $group = null ): array {

		if ( ! $group ) {
			return $this->registered_settings;
		}

		return array_filter( $this->registered_settings, function ( $args ) use ( $group ) {
			return $args['group'] === $group;
		} );
	}

	/**
	 * Get the JSON-schema-style attributes for a registered setting - type, description,
	 * and default, plus whatever the setting's 'schema' arg contributes (enum, minimum,
	 * maximum, format, items, pattern, etc.) - suitable for use as a property within an
	 * ability's input/output schema.
	 *
	 * @param string $id with or without the gh_ prefix
	 *
	 * @return array|null
	 */
	public function get_setting_schema( string $id ) {

		$id = $this->prefix( $id );

		if ( ! isset( $this->registered_settings[ $id ] ) ) {
			return null;
		}

		$args = $this->registered_settings[ $id ];

		$schema = array_merge( [
			'type'        => $args['type'],
			'description' => $args['description'],
		], $this->resolve_schema( $args['schema'] ) );

		if ( $args['default'] !== null && ! array_key_exists( 'default', $schema ) ) {
			$schema['default'] = $args['default'];
		}

		if ( $args['sensitive'] && ! $this->should_expose_sensitive_value( $id ) ) {
			$schema['description'] = trim( $schema['description'] . ' ' . __( '(Sensitive: the value is redacted by default. Explicitly name this id in `reveal` to expose it - only takes effect if WP_DEBUG is on or the `groundhogg/settings/expose_sensitive_values` filter allows it.)', 'groundhogg' ) );
		}

		return $schema;
	}

	/**
	 * Get JSON-schema-style properties for several registered settings at once, keyed by
	 * option id - e.g. to build the `properties` of an ability's input/output schema.
	 *
	 * @param string[]|null $ids limit to these ids (with or without the gh_ prefix);
	 *                            defaults to every registered setting
	 *
	 * @return array<string, array>
	 */
	public function get_settings_schema( ?array $ids = null ): array {

		$ids = $ids ? array_map( [ $this, 'prefix' ], $ids ) : array_keys( $this->registered_settings );

		$schema = [];

		foreach ( $ids as $id ) {
			$setting_schema = $this->get_setting_schema( $id );

			if ( $setting_schema ) {
				$schema[ $id ] = $setting_schema;
			}
		}

		return $schema;
	}

	/**
	 * Check if the site is global multisite enabled
	 *
	 * @return false
	 * @deprecated
	 */
	public function is_global_multisite() {
		if ( ! is_multisite() ) {
			return false;
		}

		if ( is_multisite() && ! get_site_option( 'gh_global_db_enabled' ) ) {
			return false;
		}

//        return true;
		return false;
	}

	/**
	 * Prefix the option name.
	 *
	 * @param $option
	 *
	 * @return string
	 */
	protected function prefix( $option ) {
		if ( ! preg_match( '/^gh_.*/', $option ) || preg_match( '/^wpgh_.*/', $option ) ) {
			return 'gh_' . $option;
		}

		return $option;
	}

	/**
	 * Generic function for checking checkboxes from the Groundhogg settings.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function is_option_enabled( $key = '' ) {
		$option = $this->get_option( $key, [] );

		if ( ! is_array( $option ) && $option ) {
			return true;
		}

		return is_array( $option ) && in_array( 'on', $option );
	}

	/**
	 * Swicth between the main site options if on a multisite network.
	 *
	 * @param $key
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public function get_option( $key, $default = false ) {
		$key = $this->prefix( $key );

		if ( $this->is_global_multisite() ) {
			$value = get_blog_option( get_network()->site_id, $key, $default );
		} else {
			$value = get_option( $key, $default );
		}

		// Coerce to the registered type/enum, same as sanitize_option_{$key} does on write -
		// so a value stored before the setting was registered (or written some other way)
		// still comes back matching its declared schema, not whatever shape happens to be
		// in the DB. See sanitize_registered_setting().
		if ( isset( $this->registered_settings[ $key ] ) ) {
			$value = $this->sanitize_registered_setting( $value, $key );
		}

		return $value;
	}

	/**
	 * update option wrapper
	 *
	 * @return mixed
	 */
	public function update_option( $key, $value ) {
		$key = $this->prefix( $key );
		if ( $this->is_global_multisite() ) {
			return update_blog_option( get_network()->site_id, $key, $value );
		} else {
			return update_option( $key, $value );
		}
	}

	/**
	 * delete option wrapper
	 *
	 * @return mixed
	 */
	public function delete_option( $key ) {
		$key = $this->prefix( $key );
		if ( $this->is_global_multisite() ) {
			return delete_blog_option( get_network()->site_id, $key );
		} else {
			return delete_option( $key );
		}
	}

	/**
	 * get_transient wrapper
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get_transient( $key ) {
		$key = $this->prefix( $key );
		if ( $this->is_global_multisite() ) {
			return get_site_transient( $key );
		} else {
			return get_transient( $key );
		}
	}

	/**
	 * delete_transient wrapper
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function delete_transient( $key ) {
		$key = $this->prefix( $key );
		if ( $this->is_global_multisite() ) {
			return delete_site_transient( $key );
		} else {
			return delete_transient( $key );
		}
	}

	/**
	 * Set transient wrapper
	 *
	 * @param $key
	 * @param $value
	 * @param $exp
	 *
	 * @return bool
	 */
	public function set_transient( $key, $value, $exp ) {
		$key = $this->prefix( $key );
		if ( $this->is_global_multisite() ) {
			return set_site_transient( $key, $value, $exp );
		} else {
			return set_transient( $key, $value, $exp );
		}
	}

}
