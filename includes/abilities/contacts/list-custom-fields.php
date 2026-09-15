<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Property_Field_Schema;
use Groundhogg\Properties;

/**
 * Lists the custom field definitions configured for contacts (Settings > Custom Fields),
 * so a caller can map the raw meta keys returned by groundhogg/get-contact and
 * groundhogg/search-contacts (when "meta" is included) to human-readable labels/types.
 * Meta not covered here is either a core Groundhogg field or a plain, unstructured
 * arbitrary value (e.g. set by a third-party integration).
 */
class List_Custom_Fields extends Ability {

	protected const NAME       = 'groundhogg/list-custom-fields';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'edit_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Custom Fields', 'groundhogg' ),
			'description' => __( 'List the custom field definitions configured for contacts, including their group/tab, so raw contact meta keys (from groundhogg/get-contact or groundhogg/search-contacts with meta included) can be mapped to human-readable labels and types. Use groundhogg/add-custom-field and groundhogg/update-custom-field to manage them.', 'groundhogg' ),

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
						'items' => Property_Field_Schema::get_schema(),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$fields = Properties::instance()->get_fields();

		return [
			'fields' => array_values( array_map( [ Property_Field_Schema::class, 'transform' ], $fields ) ),
		];
	}
}
