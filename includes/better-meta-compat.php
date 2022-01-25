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
		$field = Contact_Properties::instance()->get_field( $field_id );
	}

	if ( ! $field || empty( $value ) ) {
		return '';
	}

	switch ( $field['type'] ):
		case 'text':
		case 'radio':
			return sanitize_text_field( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'number':
			return intval( $value );
		case 'date':
			return date( 'Y-m-d', strtotime( $value ) );
		case 'dropdown':
		case 'checkboxes':
			if ( is_array( $value ) ) {
				return map_deep( $value, 'sanitize_text_field' );
			} else {
				return sanitize_text_field( $value );
			}
	endswitch;

	return '';

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
	if ( is_array( $id_or_name ) && isset( $field_id['type'] ) ) {
		$field = $field_id;
	} else {
		$field = Contact_Properties::instance()->get_field( $id_or_name );
	}

	// Change from int to Contact
	if ( is_int( $contact ) ) {
		$contact = get_contactdata( $contact );
	}

	if ( ! $field || ! is_a_contact( $contact ) ) {
		return '';
	}

	$data = $contact->get_meta( $field['name'] );

	switch ( $field['type'] ):
		case 'text':
		case 'radio':
		case 'textarea':
		case 'number':
		case 'date':
			$data = esc_html( $data );
			break;
		case 'dropdown':
		case 'checkboxes':
			if ( is_array( $data ) ) {
				$data = implode( ', ', $data );
			} else {
				$data = esc_html( $data );
			}
			break;
	endswitch;

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
	$field = Contact_Properties::instance()->get_field( $field_id );

	if ( ! $field ) {
		return false;
	}

	return $field['name'];
}

/**
 * Add the custom fields to the mappable fields API
 *
 * @param array $fields
 *
 * @return array
 */
function add_custom_fields_to_mappable_fields( $fields = [] ) {

	$groups = Contact_Properties::instance()->get_groups();

	if ( empty( $groups ) ) {
		return $fields;
	}

	foreach ( $groups as $group ) {

		$_group = [];

		$tab = Contact_Properties::instance()->get_group_tab( $group['id'] );

	    $group_name = sprintf( '%s: %s', $tab['name'], $group['name'] );

		$custom_fields = Contact_Properties::instance()->get_fields( $group['id'] );

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

	$custom_fields = Contact_Properties::instance()->get_fields();

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

	$field = Contact_Properties::instance()->get_field( $field_id );

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

	$field = Contact_Properties::instance()->get_field( $id );

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

	$field = Contact_Properties::instance()->get_field( $field_id );

	// if we don't know about it forget about it
	if ( ! empty( $return ) || ! $field || ! $field['name'] ) {
		return $return;
	}

	return $contact->get_meta( $field['name'] );
}

add_filter( 'groundhogg/export_field', __NAMESPACE__ . '\export_custom_property', 10, 3 );


/**
 * @param \Groundhogg\Replacements $replacements
 */
function add_custom_property_replacements( $replacements ) {

	$groups = Contact_Properties::instance()->get_groups();

	if ( empty( $groups ) ) {
		return;
	}

	foreach ( $groups as $group ) {

		$tab = Contact_Properties::instance()->get_group_tab( $group['id'] );

		$replacements->add_group( $group['id'], sprintf( '%s: %s', $tab['name'], $group['name'] ) );

		$custom_fields = Contact_Properties::instance()->get_fields( $group['id'] );

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
				},
			);

			// Hide ugly replacement codes from the UI
			$replacements->make_hidden($custom_field['id']);
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

	$custom_fields = Contact_Properties::instance()->get_fields();

	foreach ( $custom_fields as $i => $custom_field ) {

		$callback = function ( $contact ) use ( $custom_field ) {
			display_custom_field( $custom_field, $contact );
		};

		$columns::register( $custom_field['id'], $custom_field['label'], $callback, false, 100 + $i );
	}
}

add_action( 'groundhogg/admin/contacts/register_table_columns', __NAMESPACE__ . '\register_contact_property_table_columns' );

