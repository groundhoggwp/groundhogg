<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use function Groundhogg\replacements;

/**
 * Lists Groundhogg's replacement codes (merge tags), e.g. {first}, {email}, {date.Y-m-d|now} -
 * so a caller can discover what's available and how to use each one instead of guessing at
 * codes that may not exist, or missing ones (like {meta.attribute} or {date.format|time}) that
 * take dynamic arguments.
 */
class List_Replacement_Codes extends Ability {

	protected const NAME       = 'groundhogg/list-replacement-codes';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Replacement Codes', 'groundhogg' ),
			'description' => __( 'List the replacement codes (merge tags) available on this site, e.g. {first}, {email}, {date.Y-m-d|now}. Usable anywhere Groundhogg processes replacements - email subject/body, SMS, notifications - including groundhogg/create-email-template and groundhogg/update-email-template content. Some codes take a dynamic argument after a period (e.g. {meta.attribute}, {user.attribute}) - the returned `insert` shows the exact syntax including a default argument where one applies.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'group'  => [
						'type'        => 'string',
						'description' => __( 'Only codes in this group (e.g. "contact", "address", "crm"). See the response\'s `groups` map for the exact keys/labels available on this site - it\'s extensible by add-ons, so don\'t assume a fixed list.', 'groundhogg' ),
					],
					'search' => [
						'type'        => 'string',
						'description' => __( 'Free-text search against the code, name, and description.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'groups' => [
						'type'        => 'object',
						'description' => __( 'Map of group key to its display label, for every group referenced by `codes`.', 'groundhogg' ),
					],
					'codes'  => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'code'        => [
									'type'        => 'string',
									'description' => __( 'The bare code, e.g. "first" for {first}.', 'groundhogg' ),
								],
								'name'        => [ 'type' => 'string' ],
								'group'       => [ 'type' => 'string' ],
								'description' => [ 'type' => 'string' ],
								'insert'      => [
									'type'        => 'string',
									'description' => __( 'The exact syntax to use, e.g. "{first}" or "{meta.meta_key}" for codes that take a dynamic argument.', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$codes = array_map( function ( $code ) {
			// A handful of internal codes (e.g. this_email, this_flow) are registered
			// without a group, which resolves to a literal null rather than the 'other'
			// default add() otherwise applies - normalize those here.
			$code['group'] = $code['group'] ?: 'other';

			return $code;
		}, replacements()->get_codes_for_frontend() );

		if ( ! empty( $input['group'] ) ) {
			$group = sanitize_key( $input['group'] );
			$codes = array_filter( $codes, function ( $code ) use ( $group ) {
				return $code['group'] === $group;
			} );
		}

		if ( ! empty( $input['search'] ) ) {
			$search = strtolower( sanitize_text_field( $input['search'] ) );
			$codes  = array_filter( $codes, function ( $code ) use ( $search ) {
				return str_contains( strtolower( $code['code'] ), $search )
					|| str_contains( strtolower( $code['name'] ), $search )
					|| str_contains( strtolower( $code['description'] ), $search );
			} );
		}

		$groups_used = array_unique( array_map( function ( $code ) {
			return $code['group'];
		}, $codes ) );

		$codes = array_values( array_map( function ( $code ) {
			return [
				'code'        => $code['code'],
				'name'        => $code['name'],
				'group'       => $code['group'],
				'description' => $code['description'],
				'insert'      => $code['insert'],
			];
		}, $codes ) );

		$all_groups = replacements()->replacement_code_groups;

		return [
			'groups' => array_intersect_key( $all_groups, array_flip( $groups_used ) ),
			'codes'  => $codes,
		];
	}
}
