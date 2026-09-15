<?php

namespace Groundhogg\Abilities\Schemas;

use Groundhogg\Properties;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared schema for a Groundhogg contact custom field definition (Settings > Custom
 * Fields, see \Groundhogg\Properties), as returned by groundhogg/list-custom-fields,
 * groundhogg/add-custom-field, and groundhogg/update-custom-field.
 *
 * This describes the *definition* of a field (its label, type, group, etc.) - not a
 * contact's stored value for it. See groundhogg/get-contact and
 * groundhogg/search-contacts (with `meta` expanded) for raw values, and
 * groundhogg/update-contact's `meta` param for setting them.
 */
class Property_Field_Schema extends Schema {

	/**
	 * The built-in tab every contact's custom fields live under unless a caller has
	 * created additional tabs - never itself stored in Properties::get_tabs(), see
	 * managePrimaryTabs() in assets/js/admin/contacts/contact-editor.js.
	 */
	const GENERAL_TAB_ID = 'general';

	public static function get_schema(): array {

		$group_schema = [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type' => 'string',
				],
				'name' => [
					'type' => 'string',
				],
			],
		];

		return [
			'type'       => 'object',
			'properties' => [
				'id' => [
					'type'        => 'string',
					'description' => __( 'Internal id. Pass this to groundhogg/update-custom-field to identify the field.', 'groundhogg' ),
				],
				'name' => [
					'type'        => 'string',
					'description' => __( 'The meta key this field is stored under on the contact.', 'groundhogg' ),
				],
				'label' => [
					'type' => 'string',
				],
				'type' => [
					'type'        => 'string',
					'description' => __( 'The field type, e.g. text, dropdown, checkbox, textarea.', 'groundhogg' ),
				],
				'group' => $group_schema,
				'tab'   => $group_schema,
				'options' => [
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
					],
					'description' => __( 'Available choices, for fields with a fixed set of options (checkboxes, radio, dropdown).', 'groundhogg' ),
				],
				'multiple' => [
					'type'        => 'boolean',
					'description' => __( 'Whether multiple options can be selected. Only meaningful for dropdown fields.', 'groundhogg' ),
				],
				'decimals' => [
					'type'        => 'integer',
					'description' => __( 'Number of decimal places allowed. Only meaningful for number fields.', 'groundhogg' ),
				],
				'order' => [
					'type'        => 'integer',
					'description' => __( 'Sort order relative to other fields in the same group - lower shows first.', 'groundhogg' ),
				],
				'width' => [
					'type'        => 'integer',
					'enum'        => [ 1, 2 ],
					'description' => __( '1 = full width, 2 = half width.', 'groundhogg' ),
				],
			],
			'required' => [ 'id', 'name', 'label', 'type' ],
		];
	}

	/**
	 * @param array $field A raw field row from Properties::get_fields().
	 * @param array $include Unused - nothing optional to offer.
	 *
	 * @return array
	 */
	public static function transform( $field, array $include = [] ): array {

		$properties = Properties::instance();

		$group = $properties->get_group( $field['group'] ?? '' );
		$tab   = $group ? $properties->get_tab( $group['tab'] ?? '' ) : false;

		$tab_id = $tab['id'] ?? ( $group['tab'] ?? '' );

		return [
			'id'       => $field['id'] ?? '',
			'name'     => $field['name'] ?? '',
			'label'    => $field['label'] ?? '',
			'type'     => $field['type'] ?? '',
			'group'    => [
				'id'   => $group['id'] ?? ( $field['group'] ?? '' ),
				'name' => $group['name'] ?? '',
			],
			'tab'      => [
				'id'   => $tab_id,
				'name' => $tab['name'] ?? ( $tab_id === self::GENERAL_TAB_ID ? __( 'General', 'groundhogg' ) : '' ),
			],
			'options'  => $field['options'] ?? [],
			'multiple' => ! empty( $field['multiple'] ),
			'decimals' => isset( $field['decimals'] ) ? (int) $field['decimals'] : 0,
			'order'    => isset( $field['order'] ) ? (int) $field['order'] : 10,
			'width'    => isset( $field['width'] ) && (int) $field['width'] === 1 ? 1 : 2,
		];
	}
}
