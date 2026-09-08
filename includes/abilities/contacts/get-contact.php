<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use WP_Error;
use function Groundhogg\get_contactdata;

class Get_Contact extends Ability {

	protected const string NAME       = 'groundhogg/get-contact';
	protected const string CATEGORY   = 'groundhogg-contacts';
	protected const string CAPABILITY = 'view_contacts';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Get Contact', 'groundhogg' ),
			'description' => __( 'Retrieve a Groundhogg contact by ID.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The Groundhogg contact ID.', 'groundhogg' ),
					],
					'email' => [
						'type'        => 'string',
						'format'      => 'email',
						'description' => __( 'The contact email address.', 'groundhogg' ),
					],
					'user_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The associated WordPress user ID.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => [ 'tags', 'meta' ],
						],
						'default'     => [ 'tags' ],
						'description' => __( 'Optional extra sections to expand beyond the standard contact fields. Available: tags, meta (raw custom field/meta values - see groundhogg/list-custom-fields to interpret them).', 'groundhogg' ),
					],
				],
				'anyOf' => [
					[
						'required' => [ 'id' ],
					],
					[
						'required' => [ 'email' ],
					],
					[
						'required' => [ 'user_id' ],
					],
				],
			],

			'output_schema' => Contact_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		if ( ! empty( $input['id'] ) ) {
			$contact = get_contactdata( $input['id'] );
		} else if ( ! empty( $input['email'] ) ) {
			$contact = get_contactdata( $input['email'] );
		} else if ( ! empty( $input['user_id'] ) ) {
			$contact = get_contactdata( $input['user_id'], true );
		} else {
			$contact = false;
		}

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error(
				'groundhogg_contact_not_found',
				__( 'Contact not found.', 'groundhogg' )
			);
		}

		// Base CAPABILITY ('view_contacts') only gates whether this ability can run at
		// all; this per-object check is the same one the REST API's own single-contact
		// read route uses (Contacts_Api::read_single_permissions_callback() ->
		// current_user_can( 'view_contact', $contact )) - team/owner scoping, not just a
		// blanket "can view contacts in general" check.
		if ( ! current_user_can( 'view_contact', $contact ) ) {
			return new WP_Error(
				'groundhogg_cannot_view_contact',
				__( 'You do not have permission to view this contact.', 'groundhogg' )
			);
		}

		$expand = $input['expand'] ?? [ 'tags' ];

		return Contact_Schema::transform( $contact, $expand );
	}
}
