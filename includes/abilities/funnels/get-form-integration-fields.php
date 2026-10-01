<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Plugin;
use Groundhogg\Steps\Benchmarks\Form_Integration;
use WP_Error;
use function Groundhogg\get_mappable_fields;

/**
 * Lists the forms a form integration step type (Contact Form 7, Gravity Forms, WPForms...) can run for, and the
 * fields in one of them, so a caller can fill in that step's `form_id` and `field_map` in groundhogg/create-flow
 * and groundhogg/edit-flow. The forms and fields belong to the form plugin, they can't be guessed.
 *
 * It's the same data the flow editor's form integration settings ask the REST API for, see
 * Funnels_Api::form_integration().
 */
class Get_Form_Integration_Fields extends Ability {

	protected const NAME       = 'groundhogg/get-form-integration-fields';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'view_funnels';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Form Integration Fields', 'groundhogg' ),
			'description' => __( 'List the forms a form integration step type (like cf7_form_submit or gravity_form, see groundhogg/list-step-types in the "forms" sub group) can run for, and with form_id, the fields in that form. Use the ids as form_id and as the keys of field_map in the step\'s settings.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'step_type' ],
				'properties'           => [
					'step_type' => [
						'type'        => 'string',
						'description' => __( 'The form integration step type, like "cf7_form_submit".', 'groundhogg' ),
					],
					'form_id'   => [
						'type'        => 'integer',
						'description' => __( 'Also list the fields of this form.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'forms'          => [
						'type'        => 'array',
						'description' => __( 'Every form of the step type, to choose the form_id from.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'id'   => [ 'type' => 'string' ],
								'text' => [ 'type' => 'string', 'description' => __( 'The name of the form.', 'groundhogg' ) ],
							],
						],
					],
					'fields'         => [
						'type'        => 'array',
						'description' => __( 'Only with form_id. The fields in the form, whose ids are the keys of field_map.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'id'    => [ 'type' => 'string' ],
								'label' => [ 'type' => 'string' ],
							],
						],
					],
					'contact_fields' => [
						'type'        => 'object',
						'description' => __( 'Only with form_id. The contact fields a form field can be mapped to, which are the values of field_map, by group.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$type    = sanitize_key( $input['step_type'] );
		$element = Plugin::instance()->step_manager->get_element( $type );

		if ( ! $element instanceof Form_Integration ) {
			/* translators: %s: the step type */
			return new WP_Error( 'groundhogg_not_a_form_integration', sprintf( __( '"%s" is not a form integration step type.', 'groundhogg' ), $type ) );
		}

		$result = [
			'forms' => $element->get_forms_for_api(),
		];

		if ( ! empty( $input['form_id'] ) ) {
			$result['fields']         = $element->get_fields_for_api( absint( $input['form_id'] ) );
			$result['contact_fields'] = get_mappable_fields();
		}

		return $result;
	}
}
