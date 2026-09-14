<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use function Groundhogg\get_owners;

/**
 * Lists the WordPress users who can own a contact (any role with the `view_contacts`
 * capability - see \Groundhogg\get_owner_roles()), so a caller can discover valid IDs to
 * pass as groundhogg/search-contacts' owner/users_include/users_exclude params without
 * guessing WP user IDs.
 */
class List_Owners extends Ability {

	protected const NAME       = 'groundhogg/list-owners';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'view_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Owners', 'groundhogg' ),
			'description' => __( 'List the WordPress users who can be assigned as a contact owner. Use the returned id with groundhogg/search-contacts\' owner param.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'owners' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id' => [
									'type'        => 'integer',
									'description' => __( 'Pass this as groundhogg/search-contacts\' owner param.', 'groundhogg' ),
								],
								'name' => [
									'type' => 'string',
								],
								'email' => [
									'type' => 'string',
								],
							],
							'required' => [ 'id', 'name', 'email' ],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$owners = get_owners();

		$results = array_values( array_map( function ( $user ) {

			return [
				'id'    => $user->ID,
				'name'  => $user->display_name,
				'email' => $user->user_email,
			];
		}, $owners ) );

		return [
			'owners' => $results,
		];
	}
}
