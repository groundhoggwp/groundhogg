<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Property_Field_Schema;
use Groundhogg\Abilities\Traits\Has_Custom_Field_Validation;
use Groundhogg\Properties;
use WP_Error;

/**
 * Updates an existing contact custom field definition by id - see
 * groundhogg/list-custom-fields to find it. Only the fields you pass are changed;
 * omitted fields are left as they are. `group`/`tab` resolve the same way
 * groundhogg/add-custom-field's do (existing id-or-name match, else create new).
 *
 * This edits the field *definition*, not any contact's stored value for it - see
 * groundhogg/update-contact's `meta` param for that.
 */
class Update_Custom_Field extends Ability {

	use Has_Custom_Field_Validation;

	protected const NAME       = 'groundhogg/update-custom-field';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Custom Field', 'groundhogg' ),
			'description' => __( 'Update an existing contact custom field definition by id. Only the fields you pass are changed. See groundhogg/list-custom-fields for valid ids.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id' ],
				'properties'           => [
					'id' => [
						'type'        => 'string',
						'description' => __( 'Required. The custom field id to update - see groundhogg/list-custom-fields.', 'groundhogg' ),
					],
					'label' => [
						'type' => 'string',
					],
					'name' => [
						'type'        => 'string',
						'description' => __( 'Rename the internal meta key. Must remain unique among existing custom fields. Changing this does not rename the key on contacts that already have a value stored under the old one.', 'groundhogg' ),
					],
					'type' => [
						'type'        => 'string',
						'enum'        => self::field_type_enum(),
						'description' => __( 'Change the field type. checkboxes, radio, and dropdown require `options` (either passed here or already set on the field).', 'groundhogg' ),
					],
					'group' => [
						'type'        => 'string',
						'description' => __( 'Move the field to a different group - an existing group\'s id or name (see groundhogg/list-custom-fields). If nothing matches, a new group is created with this name.', 'groundhogg' ),
					],
					'tab' => [
						'type'        => 'string',
						'description' => __( 'An existing tab\'s id or name, used only when `group` creates a new group. Defaults to the built-in General tab. If nothing matches an existing tab, a new one is created with this name.', 'groundhogg' ),
					],
					'options' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Replaces the full list of available choices. Only meaningful for checkboxes, radio, and dropdown fields.', 'groundhogg' ),
					],
					'multiple' => [
						'type'        => 'boolean',
						'description' => __( 'Allow multiple selections. Only meaningful for dropdown fields.', 'groundhogg' ),
					],
					'decimals' => [
						'type'        => 'integer',
						'minimum'     => 0,
						'maximum'     => 100,
						'description' => __( 'Decimal places allowed. Only meaningful for number fields.', 'groundhogg' ),
					],
					'order' => [
						'type' => 'integer',
					],
					'width' => [
						'type' => 'integer',
						'enum' => [ 1, 2 ],
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'field' => Property_Field_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$has_changes = array_key_exists( 'label', $input )
			|| array_key_exists( 'name', $input )
			|| array_key_exists( 'type', $input )
			|| array_key_exists( 'group', $input )
			|| array_key_exists( 'options', $input )
			|| array_key_exists( 'multiple', $input )
			|| array_key_exists( 'decimals', $input )
			|| array_key_exists( 'order', $input )
			|| array_key_exists( 'width', $input );

		if ( ! $has_changes ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'No fields to update were given.', 'groundhogg' ) );
		}

		$all = Properties::instance()->get_all();

		$index = null;

		foreach ( $all['fields'] as $i => $field ) {
			if ( ( $field['id'] ?? '' ) === $input['id'] ) {
				$index = $i;
				break;
			}
		}

		if ( $index === null ) {
			return new WP_Error( 'groundhogg_field_not_found', __( 'No custom field matches the given id. See groundhogg/list-custom-fields.', 'groundhogg' ) );
		}

		$field = $all['fields'][ $index ];

		if ( array_key_exists( 'label', $input ) ) {
			$field['label'] = sanitize_text_field( $input['label'] );
		}

		if ( array_key_exists( 'name', $input ) ) {

			$name = self::sanitize_field_name( (string) $input['name'], $field['label'] );

			if ( $name === '' ) {
				return new WP_Error( 'groundhogg_invalid_field_name', __( 'Could not derive a valid internal name.', 'groundhogg' ) );
			}

			if ( self::is_field_name_in_use( $name, $all['fields'], $field['id'] ) ) {
				return new WP_Error(
					'groundhogg_field_name_in_use',
					sprintf(
						/* translators: %s: an internal field name */
						__( 'The internal name "%s" is already in use by another field.', 'groundhogg' ),
						$name
					)
				);
			}

			$field['name'] = $name;
		}

		if ( array_key_exists( 'type', $input ) ) {

			$type = sanitize_key( $input['type'] );

			if ( ! in_array( $type, self::field_type_enum(), true ) ) {
				return new WP_Error( 'groundhogg_invalid_field_type', __( 'Unrecognized field type.', 'groundhogg' ) );
			}

			$field['type'] = $type;

			// No longer relevant to the new type - drop rather than leave stale.
			if ( ! in_array( $type, self::types_requiring_options(), true ) ) {
				unset( $field['options'], $field['multiple'] );
			}

			if ( $type !== 'number' ) {
				unset( $field['decimals'] );
			}
		}

		if ( array_key_exists( 'options', $input ) ) {
			$field['options'] = array_values( array_map( 'sanitize_text_field', (array) $input['options'] ) );
		}

		if ( in_array( $field['type'], self::types_requiring_options(), true ) && empty( $field['options'] ) ) {
			return new WP_Error(
				'groundhogg_options_required',
				sprintf(
					/* translators: %s: a field type, e.g. "dropdown" */
					__( 'The "%s" field type requires a non-empty "options" list.', 'groundhogg' ),
					$field['type']
				)
			);
		}

		if ( array_key_exists( 'multiple', $input ) && $field['type'] === 'dropdown' ) {
			$field['multiple'] = (bool) $input['multiple'];
		}

		if ( array_key_exists( 'decimals', $input ) && $field['type'] === 'number' ) {
			$field['decimals'] = absint( $input['decimals'] );
		}

		if ( array_key_exists( 'order', $input ) ) {
			$field['order'] = absint( $input['order'] );
		}

		if ( array_key_exists( 'width', $input ) ) {
			$field['width'] = absint( $input['width'] ) === 1 ? 1 : 2;
		}

		if ( array_key_exists( 'group', $input ) ) {

			$group = self::resolve_group(
				sanitize_text_field( $input['group'] ),
				isset( $input['tab'] ) ? sanitize_text_field( $input['tab'] ) : '',
				$all
			);

			$field['group'] = $group['id'];
		}

		$all['fields'][ $index ] = $field;

		self::save_properties( $all );

		return [
			'field' => Property_Field_Schema::transform( $field ),
		];
	}
}
