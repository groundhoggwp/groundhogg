<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Property_Field_Schema;
use Groundhogg\Abilities\Traits\Has_Custom_Field_Validation;
use Groundhogg\Properties;
use WP_Error;

/**
 * Creates a new contact custom field definition (Settings > Custom Fields, see
 * \Groundhogg\Properties) - the same thing properties.js's "Add field" modal creates
 * from the contact editor's "More" tabs. `group` accepts an existing group's id or
 * name (see groundhogg/list-custom-fields); if it doesn't match one, a new group is
 * created with that name, under `tab` (also id-or-name, creating a new tab the same
 * way if needed - defaults to the built-in General tab).
 *
 * This creates the field *definition* - it does not set a value for any contact. See
 * groundhogg/update-contact's `meta` param for that, once the field exists.
 */
class Add_Custom_Field extends Ability {

	use Has_Custom_Field_Validation;

	protected const NAME       = 'groundhogg/add-custom-field';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Add Custom Field', 'groundhogg' ),
			'description' => __( 'Create a new contact custom field definition. See groundhogg/list-custom-fields to review existing fields/groups first, and groundhogg/update-custom-field to edit one afterward.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'label', 'type', 'group' ],
				'properties'           => [
					'label' => [
						'type'        => 'string',
						'description' => __( 'The field label shown to users.', 'groundhogg' ),
					],
					'name' => [
						'type'        => 'string',
						'description' => __( 'The internal meta key. Auto-generated from the label if omitted. Must be unique among existing custom fields.', 'groundhogg' ),
					],
					'type' => [
						'type'        => 'string',
						'enum'        => self::field_type_enum(),
						'description' => __( 'The field type. checkboxes, radio, and dropdown require `options`.', 'groundhogg' ),
					],
					'group' => [
						'type'        => 'string',
						'description' => __( 'An existing group\'s id or name (see groundhogg/list-custom-fields). If nothing matches, a new group is created with this name.', 'groundhogg' ),
					],
					'tab' => [
						'type'        => 'string',
						'description' => __( 'An existing tab\'s id or name, used only when `group` creates a new group. Defaults to the built-in General tab. If nothing matches an existing tab, a new one is created with this name.', 'groundhogg' ),
					],
					'options' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Available choices. Required (and non-empty) for checkboxes, radio, and dropdown fields.', 'groundhogg' ),
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
						'type'        => 'integer',
						'default'     => 10,
						'description' => __( 'Sort order relative to other fields in the same group - lower shows first.', 'groundhogg' ),
					],
					'width' => [
						'type'        => 'integer',
						'enum'        => [ 1, 2 ],
						'default'     => 2,
						'description' => __( '1 = full width, 2 = half width.', 'groundhogg' ),
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

		$type = sanitize_key( $input['type'] );

		if ( ! in_array( $type, self::field_type_enum(), true ) ) {
			return new WP_Error( 'groundhogg_invalid_field_type', __( 'Unrecognized field type.', 'groundhogg' ) );
		}

		if ( in_array( $type, self::types_requiring_options(), true ) && empty( $input['options'] ) ) {
			return new WP_Error(
				'groundhogg_options_required',
				sprintf(
					/* translators: %s: a field type, e.g. "dropdown" */
					__( 'The "%s" field type requires a non-empty "options" list.', 'groundhogg' ),
					$type
				)
			);
		}

		$label = sanitize_text_field( $input['label'] );

		$name = self::sanitize_field_name( isset( $input['name'] ) ? (string) $input['name'] : '', $label );

		if ( $name === '' ) {
			return new WP_Error( 'groundhogg_invalid_field_name', __( 'Could not derive a valid internal name from the label - provide one explicitly via `name`.', 'groundhogg' ) );
		}

		$all = Properties::instance()->get_all();

		if ( self::is_field_name_in_use( $name, $all['fields'] ) ) {
			return new WP_Error(
				'groundhogg_field_name_in_use',
				sprintf(
					/* translators: %s: an internal field name */
					__( 'The internal name "%s" is already in use by another field.', 'groundhogg' ),
					$name
				)
			);
		}

		$group = self::resolve_group(
			sanitize_text_field( $input['group'] ),
			isset( $input['tab'] ) ? sanitize_text_field( $input['tab'] ) : '',
			$all
		);

		$field = [
			'id'    => wp_generate_uuid4(),
			'group' => $group['id'],
			'label' => $label,
			'name'  => $name,
			'type'  => $type,
			'order' => isset( $input['order'] ) ? absint( $input['order'] ) : 10,
			'width' => ( isset( $input['width'] ) && absint( $input['width'] ) === 1 ) ? 1 : 2,
		];

		if ( in_array( $type, self::types_requiring_options(), true ) ) {
			$field['options'] = array_values( array_map( 'sanitize_text_field', (array) $input['options'] ) );
		}

		if ( $type === 'dropdown' && ! empty( $input['multiple'] ) ) {
			$field['multiple'] = true;
		}

		if ( $type === 'number' && isset( $input['decimals'] ) ) {
			$field['decimals'] = absint( $input['decimals'] );
		}

		$all['fields'][] = $field;

		self::save_properties( $all );

		return [
			'field' => Property_Field_Schema::transform( $field ),
		];
	}
}
