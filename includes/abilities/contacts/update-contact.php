<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Abilities\Traits\Has_Owner_Validation;
use Groundhogg\Preferences;
use Groundhogg\Submission;
use WP_Error;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\parse_tag_list;
use function Groundhogg\sanitize_object_meta;
use function Groundhogg\sanitize_payload;

/**
 * Updates an existing contact by ID. Unlike groundhogg/create-contact (which upserts
 * by email - create if missing, update if not), this one is update-only: it requires
 * an existing contact and errors if `id` doesn't match one, matching the /v4 contacts
 * REST API's update_single() rather than its create_single(). `id` identifies the
 * contact being updated - it is not itself an updatable field; to change a contact's
 * email, pass the new value as `email`.
 */
class Update_Contact extends Ability {

	use Has_Optin_Status;
	use Has_Owner_Validation;

	protected const NAME       = 'groundhogg/update-contact';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'edit_contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Contact', 'groundhogg' ),
			'description' => __( 'Update an existing contact by ID. Only the fields you pass are changed - omitted fields are left as they are. Errors if no contact matches the ID, unlike groundhogg/create-contact.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'id' ],
				'properties'           => [
					'id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'Required. The Groundhogg contact ID to update - see groundhogg/search-contacts or groundhogg/get-contact to find it.', 'groundhogg' ),
					],
					'email' => [
						'type'        => 'string',
						'format'      => 'email',
						'description' => __( 'Change the contact\'s email address. Rejected if another contact already has it.', 'groundhogg' ),
					],
					'first_name' => [
						'type' => 'string',
					],
					'last_name' => [
						'type' => 'string',
					],
					'optin_status' => [
						'type'        => [ 'integer', 'string' ],
						'enum'        => self::optin_status_enum(),
						'description' => __( 'Int or canonical string label.', 'groundhogg' ),
					],
					'owner' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'A WordPress user ID to reassign as the contact owner - see groundhogg/list-owners for valid IDs.', 'groundhogg' ),
					],
					'tags_add' => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Tag names or IDs to add. A name that does not match an existing tag creates a new one, same as groundhogg/create-contact\'s tags param.', 'groundhogg' ),
					],
					'tags_remove' => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Tag names or IDs to remove. Unlike tags_add, a name that does not match an existing tag is simply ignored - never creates one.', 'groundhogg' ),
					],
					'meta' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Arbitrary custom field key => value pairs to set. See groundhogg/list-custom-fields for defined fields. Each value is sanitized the same way the admin UI would sanitize it for that field.', 'groundhogg' ),
					],
					'expand' => [
						'type'        => 'array',
						'items'       => [
							'type' => 'string',
							'enum' => Contact_Schema::expand_options(),
						],
						'default'     => [ 'tags' ],
						'description' => __( 'Optional extra sections to expand on the returned contact. Available: tags, meta, plus any sections an installed add-on has registered.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'contact' => Contact_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$contact = get_contactdata( $input['id'] );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
		}

		// Base CAPABILITY ('edit_contacts') already gated whether this ability can run
		// at all; this is the same per-object check create-contact's upsert path and
		// the REST API's update_single() both use - a sales rep with edit_contacts
		// isn't necessarily allowed to edit every contact (team scoping).
		if ( ! current_user_can( 'edit_contact', $contact ) ) {
			return new WP_Error(
				'groundhogg_cannot_edit_contact',
				__( 'You do not have permission to edit this contact.', 'groundhogg' )
			);
		}

		$has_changes = array_key_exists( 'email', $input )
			|| array_key_exists( 'first_name', $input )
			|| array_key_exists( 'last_name', $input )
			|| array_key_exists( 'optin_status', $input )
			|| ! empty( $input['owner'] )
			|| ! empty( $input['tags_add'] )
			|| ! empty( $input['tags_remove'] )
			|| ! empty( $input['meta'] );

		if ( ! $has_changes ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'No fields to update were given.', 'groundhogg' ) );
		}

		$data = [];

		if ( ! empty( $input['email'] ) ) {

			$email = sanitize_email( $input['email'] );

			if ( ! is_email( $email ) ) {
				return new WP_Error( 'groundhogg_invalid_email', __( 'The provided email address is not valid.', 'groundhogg' ) );
			}

			// $contact as the second arg excludes the contact's own current email from
			// the check, so "updating" it to the value it already has isn't rejected.
			if ( is_email_address_in_use( $email, $contact ) ) {
				return new WP_Error( 'groundhogg_email_in_use', __( 'Another contact already uses this email address.', 'groundhogg' ) );
			}

			$data['email'] = $email;
		}

		if ( ! empty( $input['first_name'] ) ) {
			$data['first_name'] = sanitize_text_field( $input['first_name'] );
		}

		if ( ! empty( $input['last_name'] ) ) {
			$data['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		if ( isset( $input['optin_status'] ) ) {
			$data['optin_status'] = self::resolve_optin_status( $input['optin_status'] ) ?? Preferences::UNCONFIRMED;
		}

		if ( ! empty( $input['owner'] ) ) {

			$owner_id = self::resolve_owner( absint( $input['owner'] ) );

			if ( is_wp_error( $owner_id ) ) {
				return $owner_id;
			}

			$data['owner_id'] = $owner_id;
		}

		if ( ! empty( $data ) ) {
			$contact->update( $data );
		}

		if ( ! empty( $input['tags_add'] ) ) {
			// apply_tag() resolves via parse_tag_list() with its default $create = true
			// - creating a tag by name here is intentional, same as create-contact.
			$contact->apply_tag( $input['tags_add'] );
		}

		if ( ! empty( $input['tags_remove'] ) ) {
			// Resolved here first, with $create = false, rather than passed straight to
			// remove_tag() - that method also calls parse_tag_list() with the default
			// $create = true, which would CREATE a brand new (empty, unwanted) tag for
			// any misspelled/nonexistent name before immediately no-op'ing the removal.
			// Pre-resolving to IDs sidesteps that entirely: nothing to create, nothing
			// unresolved ever reaches remove_tag().
			$resolved_tags = parse_tag_list( $input['tags_remove'], 'ID', false );

			if ( ! empty( $resolved_tags ) ) {
				$contact->remove_tag( $resolved_tags );
			}
		}

		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {

			$meta = [];

			foreach ( $input['meta'] as $key => $value ) {
				$key          = sanitize_key( $key );
				$meta[ $key ] = sanitize_object_meta( $value, $key, 'contact' );
			}

			$contact->update_meta( $meta );
		}

		// Record a submission, same as groundhogg/create-contact and
		// \Groundhogg\generate_contact_with_map() - an audit-trail entry in the admin's
		// Submissions log showing what was sent and through what channel. Best-effort:
		// a logging hiccup doesn't fail the update itself.
		$submission = new Submission();

		$submission->create( [
			'type'       => 'api',
			'name'       => __( 'Update Contact ability', 'groundhogg' ),
			'contact_id' => $contact->get_id(),
		] );

		$posted_data = $input;
		unset( $posted_data['expand'] );

		$submission->add_posted_data( sanitize_payload( $posted_data ) );

		$expand = $input['expand'] ?? [ 'tags' ];

		return [
			'contact' => Contact_Schema::transform( $contact, $expand ),
		];
	}
}
