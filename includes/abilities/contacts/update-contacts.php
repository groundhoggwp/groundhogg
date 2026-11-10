<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use WP_Error;
use function Groundhogg\get_contactdata;

/**
 * Updates a list of contacts by ID, each with its own changes - groundhogg/update-contact
 * for up to MAX_ITEMS contacts in one call. Each item takes the same fields as
 * update-contact and is applied by Update_Contact::apply_changes(), so the rules
 * (permission per contact, email uniqueness, tags_remove never creating a tag, the
 * submission recorded) are the same.
 *
 * Items are independent: one that fails (missing contact, email in use, no permission)
 * is reported in `results` and the rest still go through. It is not a transaction.
 *
 * To give every contact in a segment the same changes, use groundhogg/bulk-update-contacts.
 */
class Update_Contacts extends Ability {

	protected const NAME       = 'groundhogg/update-contacts';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'edit_contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	const MAX_ITEMS = 100;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Contacts', 'groundhogg' ),
			/* translators: %d: the maximum number of contacts per call */
			'description' => sprintf( __( 'Update up to %d contacts by ID in one call, each with its own changes (the same fields as groundhogg/update-contact). Each contact succeeds or fails on its own; the results say which. To give every contact in a segment the same changes, use groundhogg/bulk-update-contacts instead.', 'groundhogg' ), self::MAX_ITEMS ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'contacts' ],
				'properties'           => [
					'contacts' => [
						'type'        => 'array',
						'minItems'    => 1,
						'maxItems'    => self::MAX_ITEMS,
						'description' => __( 'The contacts to update. Each needs an id plus at least one field to change; omitted fields are left as they are.', 'groundhogg' ),
						'items'       => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'required'             => [ 'id' ],
							'properties'           => array_merge( [
								'id' => [
									'type'        => 'integer',
									'minimum'     => 1,
									'description' => __( 'The Groundhogg contact ID to update - see groundhogg/search-contacts.', 'groundhogg' ),
								],
							], Update_Contact::change_properties() ),
						],
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'updated' => [
						'type'        => 'integer',
						'description' => __( 'How many contacts were updated.', 'groundhogg' ),
					],
					'failed'  => [
						'type'        => 'integer',
						'description' => __( 'How many contacts were not updated. See results for why.', 'groundhogg' ),
					],
					'results' => [
						'type'        => 'array',
						'description' => __( 'One entry per item, in the order given.', 'groundhogg' ),
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'id'      => [ 'type' => 'integer' ],
								'success' => [ 'type' => 'boolean' ],
								'error'   => [
									'type'        => 'object',
									'description' => __( 'Only when success is false.', 'groundhogg' ),
									'properties'  => [
										'code'    => [ 'type' => 'string' ],
										'message' => [ 'type' => 'string' ],
									],
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$results = [];
		$updated = 0;

		foreach ( $input['contacts'] as $item ) {

			$id      = absint( $item['id'] );
			$contact = get_contactdata( $id );

			if ( ! $contact || ! $contact->exists() ) {
				$applied = new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
			} else {
				$applied = Update_Contact::apply_changes( $contact, $item, __( 'Update Contacts ability', 'groundhogg' ) );
			}

			if ( is_wp_error( $applied ) ) {
				$results[] = [
					'id'      => $id,
					'success' => false,
					'error'   => [
						'code'    => $applied->get_error_code(),
						'message' => $applied->get_error_message(),
					],
				];

				continue;
			}

			$updated ++;

			$results[] = [
				'id'      => $id,
				'success' => true,
			];
		}

		return [
			'updated' => $updated,
			'failed'  => count( $results ) - $updated,
			'results' => $results,
		];
	}
}
