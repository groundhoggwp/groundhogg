<?php

namespace Groundhogg\Abilities\Traits;

use Groundhogg\Abilities\Schemas\Property_Field_Schema;
use Groundhogg\Properties;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared field-type vocabulary, name/group/tab resolution, and persistence for any
 * ability that creates or edits contact custom field definitions (\Groundhogg\Properties)
 * - used by Add_Custom_Field and Update_Custom_Field.
 *
 * The field type list mirrors the `fieldTypes` registry in
 * assets/js/admin/components/properties.js - that's the actual source of truth for
 * what a type does (its view/edit UI), but it's JS-only, so this is kept in step with
 * it by hand as the PHP-side vocabulary abilities validate against.
 */
trait Has_Custom_Field_Validation {

	/**
	 * @return array<string, string> type slug => human label
	 */
	protected static function field_type_labels(): array {
		return [
			'text'         => __( 'Text', 'groundhogg' ),
			'textarea'     => __( 'Textarea', 'groundhogg' ),
			'number'       => __( 'Number', 'groundhogg' ),
			'url'          => __( 'URL', 'groundhogg' ),
			'tel'          => __( 'Phone Number', 'groundhogg' ),
			'custom_email' => __( 'Email Address', 'groundhogg' ),
			'date'         => __( 'Date', 'groundhogg' ),
			'time'         => __( 'Time', 'groundhogg' ),
			'datetime'     => __( 'Date & Time', 'groundhogg' ),
			'checkboxes'   => __( 'Checkboxes', 'groundhogg' ),
			'radio'        => __( 'Radio Buttons', 'groundhogg' ),
			'dropdown'     => __( 'Dropdown', 'groundhogg' ),
			'html'         => __( 'HTML', 'groundhogg' ),
		];
	}

	/**
	 * @return string[]
	 */
	protected static function field_type_enum(): array {
		return array_keys( self::field_type_labels() );
	}

	/**
	 * Types whose properties.js `edit` UI requires a non-empty `options` list.
	 *
	 * @return string[]
	 */
	protected static function types_requiring_options(): array {
		return [ 'checkboxes', 'radio', 'dropdown' ];
	}

	/**
	 * Sanitize a caller-given internal name, or derive one from the label the same
	 * way properties.js's own sanitizeKey() does when a field is created without
	 * `name` explicitly given - lowercase, runs of non a-z0-9 collapsed to `_`.
	 *
	 * @param string $name  Caller-given name, may be empty.
	 * @param string $label Fallback source when $name is empty.
	 *
	 * @return string
	 */
	protected static function sanitize_field_name( string $name, string $label ): string {

		$source = $name !== '' ? $name : $label;
		$key    = strtolower( $source );
		$key    = preg_replace( '/[^a-z0-9]+/', '_', $key );
		$key    = trim( $key, '_' );

		return sanitize_key( $key );
	}

	/**
	 * @param string $name         The internal name to check.
	 * @param array  $fields       Properties::get_all()['fields'].
	 * @param string $excluding_id Skip the field with this id (for renaming in place).
	 *
	 * @return bool True if another field already uses this internal name.
	 */
	protected static function is_field_name_in_use( string $name, array $fields, string $excluding_id = '' ): bool {

		foreach ( $fields as $field ) {
			if ( ( $field['name'] ?? '' ) === $name && ( $field['id'] ?? '' ) !== $excluding_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve a `tab` input (an existing tab's id or name) to that tab, or create a
	 * new one and append it to $all['tabs']. The built-in General tab
	 * (Property_Field_Schema::GENERAL_TAB_ID) is always valid even though it's never
	 * actually stored in $all['tabs'] - see that constant's docblock.
	 *
	 * @param string $tab_input
	 * @param array  $all Properties::get_all() shape, mutated in place when a new tab
	 *                     is created.
	 *
	 * @return array{id: string, name: string}
	 */
	protected static function resolve_tab( string $tab_input, array &$all ): array {

		if ( $tab_input === '' || strcasecmp( $tab_input, Property_Field_Schema::GENERAL_TAB_ID ) === 0 ) {
			return [
				'id'   => Property_Field_Schema::GENERAL_TAB_ID,
				'name' => __( 'General', 'groundhogg' ),
			];
		}

		foreach ( $all['tabs'] as $tab ) {
			if ( $tab['id'] === $tab_input || strcasecmp( $tab['name'], $tab_input ) === 0 ) {
				return $tab;
			}
		}

		$tab = [
			'id'   => wp_generate_uuid4(),
			'name' => sanitize_text_field( $tab_input ),
		];

		$all['tabs'][] = $tab;

		return $tab;
	}

	/**
	 * Resolve a `group` input (an existing group's id or name) to that group, or
	 * create a new one - under the tab resolved via resolve_tab() - and append it to
	 * $all['groups'].
	 *
	 * @param string $group_input
	 * @param string $tab_input   Only used if $group_input doesn't match an existing group.
	 * @param array  $all Properties::get_all() shape, mutated in place when a new
	 *                     group (and possibly tab) is created.
	 *
	 * @return array{id: string, name: string, tab: string}
	 */
	protected static function resolve_group( string $group_input, string $tab_input, array &$all ): array {

		foreach ( $all['groups'] as $group ) {
			if ( $group['id'] === $group_input || strcasecmp( $group['name'], $group_input ) === 0 ) {
				return $group;
			}
		}

		$tab = self::resolve_tab( $tab_input, $all );

		$group = [
			'id'   => wp_generate_uuid4(),
			'name' => sanitize_text_field( $group_input ),
			'tab'  => $tab['id'],
		];

		$all['groups'][] = $group;

		return $group;
	}

	/**
	 * Sanitize and persist the full fields/groups/tabs structure the same way the
	 * admin UI's save path does (Properties::sanitize(), then the
	 * gh_contact_custom_properties option - see includes/filters.php's
	 * groundhogg/api/v4/options_sanitize_callback handling of that option), then
	 * reset the Properties singleton so this request's own subsequent reads
	 * (including the response's Property_Field_Schema::transform() call) see the
	 * change immediately rather than the stale copy it cached at construction.
	 *
	 * @param array $all Properties::get_all() shape.
	 *
	 * @return void
	 */
	protected static function save_properties( array $all ) {

		update_option( 'gh_contact_custom_properties', Properties::sanitize( $all ) );

		Properties::$instance = null;
	}
}
