<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Properties;

/**
 * Lists the custom field definitions configured for contacts (Settings > Custom Fields),
 * so a caller can map the raw meta keys returned by groundhogg/get-contact and
 * groundhogg/search-contacts (when "meta" is included) to human-readable labels/types.
 * Meta not covered here is either a core Groundhogg field or a plain, unstructured
 * arbitrary value (e.g. set by a third-party integration).
 */
class List_Custom_Fields extends Ability {

	protected const string NAME       = 'groundhogg/list-custom-fields';
	protected const string CATEGORY   = 'groundhogg-contacts';
	protected const string CAPABILITY = 'edit_contacts';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

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
			'label'       => __( 'List Custom Fields', 'groundhogg' ),
			'description' => __( 'List the custom field definitions configured for contacts, including their group/tab, so raw contact meta keys (from groundhogg/get-contact or groundhogg/search-contacts with meta included) can be mapped to human-readable labels and types.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'fields' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
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
									'description' => __( 'Available choices, for fields with a fixed set of options (e.g. dropdown).', 'groundhogg' ),
								],
							],
							'required' => [ 'name', 'label', 'type' ],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$properties = Properties::instance();
		$fields     = $properties->get_fields();

		$fields = array_values( array_map( function ( $field ) use ( $properties ) {

			$group = $properties->get_group( $field['group'] ?? '' );
			$tab   = $group ? $properties->get_tab( $group['tab'] ?? '' ) : false;

			return [
				'name'    => $field['name'] ?? '',
				'label'   => $field['label'] ?? '',
				'type'    => $field['type'] ?? '',
				'group'   => [
					'id'   => $group['id'] ?? ( $field['group'] ?? '' ),
					'name' => $group['name'] ?? '',
				],
				'tab'     => [
					'id'   => $tab['id'] ?? '',
					'name' => $tab['name'] ?? '',
				],
				'options' => $field['options'] ?? [],
			];
		}, $fields ) );

		return [
			'fields' => $fields,
		];
	}
}
