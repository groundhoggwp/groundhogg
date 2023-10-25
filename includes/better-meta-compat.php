<?php

namespace Groundhogg;

use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;

/**
 * Sanitize user input based on a field configuration
 *
 * @param $value    mixed
 * @param $field_id string
 *
 * @return mixed sanitized...
 */
function sanitize_custom_field( $value, $field_id ) {

	// Field object was passed...
	if ( is_array( $field_id ) && isset( $field_id['type'] ) ) {
		$field = $field_id;
	} else {
		$field = Properties::instance()->get_field( $field_id );
	}

	if ( ! $field || empty( $value ) ) {
		return '';
	}

	switch ( $field['type'] ):
		default:
		case 'text':
		case 'url':
		case 'radio':
		case 'tel':
			return sanitize_text_field( $value );
		case 'email':
		case 'custom_email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'number':
			return intval( $value );
		case 'time':
			return date( 'H:i:s', strtotime( $value ) );
		case 'date':
			return date( 'Y-m-d', strtotime( $value ) );
		case 'datetime':
			return date( 'Y-m-d H:i:s', strtotime( $value ) );
		case 'dropdown':
		case 'checkboxes':
			if ( is_array( $value ) ) {
				return map_deep( $value, 'sanitize_text_field' );
			} else {
				return sanitize_text_field( $value );
			}
		case 'html':
			return wp_kses_post( $value );
	endswitch;
}

/**
 * Display a field
 *
 * @param      $field_id string|array
 * @param      $contact  Contact|int
 * @param bool $echo
 *
 * @return array|false|int|mixed|string
 */
function display_custom_field( $id_or_name, $contact, $echo = true ) {

	// Field object was passed...
	if ( is_array( $id_or_name ) && isset( $id_or_name['type'] ) ) {
		$field = $id_or_name;
	} else {
		$field = Properties::instance()->get_field( $id_or_name );
	}

	// Change from int to Contact
	if ( is_int( $contact ) ) {
		$contact = get_contactdata( $contact );
	}

	if ( ! $field || ! is_a_contact( $contact ) ) {
		return '';
	}

	$data = $contact->get_meta( $field['name'] );

	if ( ! empty( $data ) ){
		switch ( $field['type'] ):
			default:
			case 'text':
			case 'custom_email':
			case 'email':
			case 'url':
			case 'tel':
			case 'radio':
			case 'textarea':
				$data = esc_html( $data );
				break;
			case 'datetime':
				$data = date_i18n( get_date_time_format(), strtotime( $data ) );
				break;
			case 'time':
				$data = date_i18n( get_time_format(), strtotime( $data ) );
				break;
			case 'date':
				$data = date_i18n( get_option( 'date_format' ), strtotime( $data ) );
				break;
			case 'number':
				$data = floatval( $data );
				$data = number_format_i18n( $data, floor( $data ) != $data ? 2 : 0 );
				break;
			case 'dropdown':
			case 'checkboxes':
				if ( is_array( $data ) ) {
					$data = esc_html( implode( ', ', $data ) );
				} else {
					$data = esc_html( $data );
				}
				break;
			case 'html':
				// output with no change as already HTML
				break;
		endswitch;
	}

	/**
	 * Filter the display value of a custom field
	 *
	 * @param $data mixed the custom field display value
	 * @param $contact Contact
	 */
	$data = apply_filters( 'groundhogg/display_custom_field', $data, $contact );

	if ( $echo ) {
		echo $data;
	}

	return $data;
}

/**
 * Get the associated meta key with a field
 *
 * @param $field_id
 *
 * @return bool|mixed
 */
function get_field_meta_key( $field_id ) {
	$field = Properties::instance()->get_field( $field_id );

	if ( ! $field ) {
		return false;
	}

	return $field['name'];
}

/**
 * Add the custom fields to the mappable fields API
 *
 * @return array
 */
function get_custom_fields_dropdown_options() {

	$fields = [];

	$groups = Properties::instance()->get_groups();

	foreach ( $groups as $group ) {

		$_group = [];

		$tab = Properties::instance()->get_group_tab( $group['id'] );

		$group_name = sprintf( '%s: %s', $tab['name'], $group['name'] );

		$custom_fields = Properties::instance()->get_fields( $group['id'] );

		foreach ( $custom_fields as $custom_field ) {
			$_group[ $custom_field['name'] ] = $custom_field['label'];
		}

		$fields[ $group_name ] = $_group;
	}

	return $fields;

}

/**
 * Add the custom fields to the mappable fields API
 *
 * @param array $fields
 *
 * @return array
 */
function add_custom_fields_to_mappable_fields( $fields = [] ) {

	$groups = Properties::instance()->get_groups();

	if ( empty( $groups ) ) {
		return $fields;
	}

	foreach ( $groups as $group ) {

		$_group = [];

		$tab = Properties::instance()->get_group_tab( $group['id'] );

		// Tab is missing, deleted?
		if ( ! $tab ){
			continue;
		}

		$group_name = sprintf( '%s: %s', $tab['name'], $group['name'] );

		$custom_fields = Properties::instance()->get_fields( $group['id'] );

		foreach ( $custom_fields as $custom_field ) {
			$_group[ $custom_field['id'] ] = $custom_field['label'];
		}

		$fields[ $group_name ] = $_group;
	}

	return $fields;

}

add_filter( 'groundhogg/mappable_fields', __NAMESPACE__ . '\add_custom_fields_to_mappable_fields' );

/**
 * Add the custom fields to the meta key picker for easier access
 *
 * @param array[] $response
 * @param string  $search
 *
 * @return array
 */
function add_custom_fields_to_meta_key_picker( $response = [], $search = '' ) {

	$custom_fields = Properties::instance()->get_fields();

	if ( empty( $custom_fields ) ) {
		return $response;
	}

	foreach ( $custom_fields as $custom_field ) {
		if ( preg_match( "/" . $search . "/i", $custom_field['label'] ) || preg_match( "/" . $search . "/", $custom_field['name'] ) ) {
			array_unshift( $response, [
				'id'    => $custom_field['id'],
				'label' => $custom_field['label'],
				'value' => $custom_field['name']
			] );
		}
	}

	return $response;

}

add_filter( 'groundhogg/handle_ajax_meta_picker', __NAMESPACE__ . '\add_custom_fields_to_meta_key_picker', 10, 2 );

/**
 * Uknown args from the field mapping API get passed here
 *
 * @param $field string the Field ID
 * @param $value mixed the content value
 * @param $args  array the basic contact args
 * @param $meta  array the metadata
 * @param $tags  int[] the tags to add
 * @param $notes string[] any notes to add
 * @param $files array any files to add
 */
function map_custom_fields_to_meta( $field_id, $value, &$args, &$meta, &$tags, &$notes, &$files ) {

	$field = Properties::instance()->get_field( $field_id );

	// if we don't know about it forget about it
	if ( ! $field || ! $field['name'] ) {
		return;
	}

	// Sanitize and add to the meta data...
	$meta[ $field['name'] ] = sanitize_custom_field( $value, $field_id );

}

add_action( 'groundhogg/generate_contact_with_map/default', __NAMESPACE__ . '\map_custom_fields_to_meta', 10, 7 );
add_action( 'groundhogg/update_contact_with_map/default', __NAMESPACE__ . '\map_custom_fields_to_meta', 10, 7 );

/**
 * Filter the column name for the custom property
 *
 * @param $header string
 * @param $id     string
 * @param $type   string
 */
function export_custom_property_header( $header, $id, $type ) {

	$field = Properties::instance()->get_field( $id );

	if ( ! $field ) {
		return $header;
	}

	if ( $type === 'basic' ) {
		return $field['name'];
	}

	return $field['label'];
}

add_filter( 'groundhogg/export_header_name', __NAMESPACE__ . '\export_custom_property_header', 10, 3 );

/**
 * Filter an exported args and ensure the field is properly exported.
 *
 * @param $return   mixed
 * @param $contact  Contact
 * @param $field_id string
 */
function export_custom_property( $return, $contact, $field_id ) {

	$field = Properties::instance()->get_field( $field_id );

	// if we don't know about it forget about it
	if ( ! empty( $return ) || ! $field || ! $field['name'] ) {
		return $return;
	}

	return display_custom_field( $field, $contact, false );
}

add_filter( 'groundhogg/export_field', __NAMESPACE__ . '\export_custom_property', 10, 3 );


/**
 * @param \Groundhogg\Replacements $replacements
 */
function add_custom_property_replacements( $replacements ) {

	$groups = Properties::instance()->get_groups();

	if ( empty( $groups ) ) {
		return;
	}

	foreach ( $groups as $group ) {

		$tab = Properties::instance()->get_group_tab( $group['id'] );

		if ( ! $tab ){
			continue;
		}

		$replacements->add_group( $group['id'], sprintf( '%s: %s', $tab['name'], $group['name'] ) );

		$custom_fields = Properties::instance()->get_fields( $group['id'] );

		foreach ( $custom_fields as $custom_field ) {
			$replacements->add(
				$custom_field['name'],
				function ( $contact_id, $name ) {
					return display_custom_field( $name, $contact_id, false );
				},
				'',
				$custom_field['label'],
				$group['id']
			);

			// For backwards compatibility with ugly replacement codes.
			$replacements->add(
				$custom_field['id'],
				function ( $contact_id, $name ) {
					return display_custom_field( $name, $contact_id, false );
				}
			);

			// Hide ugly replacement codes from the UI
			$replacements->make_hidden( $custom_field['id'] );
		}
	}
}

add_action( 'groundhogg/replacements/init', __NAMESPACE__ . '\add_custom_property_replacements' );

/**
 * Register Custom Columns for Custom Fields
 *
 * @param Contact_Table_Columns $columns
 */
function register_contact_property_table_columns( $columns ) {

	$custom_fields = Properties::instance()->get_fields();

	foreach ( $custom_fields as $i => $custom_field ) {
		$columns::register( $custom_field['id'], $custom_field['label'], __NAMESPACE__ . '\display_custom_field_column_callback', "cm.{$custom_field['name']}", 100 + absint( get_array_var( $custom_field, 'order', $i ) ) );
	}
}

add_action( 'groundhogg/admin/contacts/register_table_columns', __NAMESPACE__ . '\register_contact_property_table_columns' );

/**
 * Display the custom field columns
 *
 * @param $contact   Contact
 * @param $column_id string
 *
 * @return void
 */
function display_custom_field_column_callback( $contact, $column_id ) {
	display_custom_field( $column_id, $contact );
}

/**
 * Migrate existing custom fields and tabs to the new format
 */
function migrate_custom_fields_groundhogg_2_6() {

	$new_tab_state = [
		'tabs'   => [],
		'groups' => [],
		'fields' => [],
	];

	// Get the fields & tabs from the Custom Field Management Extension
	$tabs     = get_option( 'gh_custom_tabs', [] ) ?: [];
	$sections = get_option( 'gh_custom_tab_sections', [] ) ?: [];
	$fields   = get_option( 'gh_custom_tab_section_fields', [] ) ?: [];

	// No tabs? Nevermind
	if ( empty( $tabs ) ) {
		return;
	}

//	// Sort sections into correct order
//	uasort( $sections, function ( $a, $b ) {
//		return $a['order'] - $b['order'];
//	} );
//
//	// Sort Fields into correct order
//	uasort( $fields, function ( $a, $b ) {
//		return $a['order'] - $b['order'];
//	} );

	foreach ( $tabs as $tab ) {
		$new_tab_state['tabs'][] = [
			'id'   => $tab['id'],
			'name' => $tab['name'],
		];
	}

	foreach ( $sections as $section ) {
		$new_tab_state['groups'][] = [
			'id'   => $section['id'],
			'name' => $section['name'],
			'tab'  => $section['tab'],
		];
	}

	foreach ( $fields as $field ) {
		$new_field = [
			'id'    => $field['id'],
			'group' => $field['section'],
			'name'  => $field['meta'],
			'label' => $field['name'],
			'type'  => $field['type'],
			'order' => absint( get_array_var( $field, 'order', 10 ) ),
		];

		switch ( $field['type'] ):
			case 'text':
			case 'textarea':
			case 'number':
			case 'date':
				break;
			case 'dropdown':
				$new_field['multiple'] = boolval( $field['settings']['multiple'] );
				$new_field['options']  = array_map( 'trim', explode( PHP_EOL, $field['settings']['options'] ) );
				break;
			case 'checkboxes':
			case 'radio':
				$new_field['options'] = array_map( 'trim', explode( PHP_EOL, $field['settings']['options'] ) );
				break;
		endswitch;

		$new_tab_state['fields'][] = $new_field;
	}

	update_option( 'gh_contact_custom_properties', $new_tab_state );
}
