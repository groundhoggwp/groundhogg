<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Saved_Searches;

/**
 * Lists the saved searches (Contacts > Saved Searches in the admin) stored in the
 * gh_saved_searches option, so a caller can discover valid IDs to pass as
 * groundhogg/search-contacts' saved_search param without guessing.
 */
class List_Saved_Searches extends Ability {

	protected const NAME       = 'groundhogg/list-saved-searches';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'view_contacts';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Saved Searches', 'groundhogg' ),
			'description' => __( 'List the saved contact searches (Contacts > Saved Searches in the admin). Use the returned id with groundhogg/search-contacts\' saved_search param.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'query' ],
						],
						'default'     => [],
						'description' => __( 'Optional extra sections to expand on each saved search. Available: query (the raw Contact_Query query vars this saved search applies - informational only, not a documented/stable shape, since it mirrors Contact_Query internals directly).', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'saved_searches' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id' => [
									'type'        => 'string',
									'description' => __( 'Pass this as groundhogg/search-contacts\' saved_search param.', 'groundhogg' ),
								],
								'name' => [
									'type' => 'string',
								],
								'query' => [
									'type'                 => 'object',
									'additionalProperties' => true,
									'description'          => __( 'Only present when "query" is passed in expand.', 'groundhogg' ),
								],
							],
							'required' => [ 'id', 'name' ],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$expand = $input['expand'] ?? [];

		$searches = Saved_Searches::instance()->get_all();

		$results = array_values( array_map( function ( $search ) use ( $expand ) {

			$data = [
				'id'   => (string) ( $search['id'] ?? '' ),
				'name' => $search['name'] ?? '',
			];

			if ( in_array( 'query', $expand, true ) ) {
				$data['query'] = $search['query'] ?? [];
			}

			return $data;
		}, $searches ) );

		return [
			'saved_searches' => $results,
		];
	}
}
