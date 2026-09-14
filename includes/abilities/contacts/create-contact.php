<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Contact_Schema;
use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Abilities\Traits\Has_Owner_Validation;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use Groundhogg\Submission;
use WP_Error;
use function Groundhogg\is_email_address_in_use;
use function Groundhogg\sanitize_object_meta;
use function Groundhogg\sanitize_payload;

/**
 * Creates a new Groundhogg contact, or updates an existing one if the given email
 * already belongs to a contact (upsert-by-email) - the same behavior as the /v4
 * contacts REST API's create_single() and \Groundhogg\generate_contact_with_map(),
 * which this mirrors: \Groundhogg\Contact::__construct() with an array containing
 * 'email' looks up-or-creates by that email internally.
 *
 * The upsert isn't silent, though: the response always reports whether a contact was
 * actually created or an existing one was updated (see the `created` output field),
 * and updating an existing contact is only allowed if the current user can
 * `edit_contact` that specific contact (matches the REST API's own permission check -
 * relevant for sales reps who can add_contacts but can't necessarily edit every
 * existing contact).
 */
class Create_Contact extends Ability {

	use Has_Optin_Status;
	use Has_Owner_Validation;

	protected const NAME       = 'groundhogg/create-contact';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'add_contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Contact', 'groundhogg' ),
			'description' => __( 'Create a new contact. If a contact with the given email already exists, it is updated instead of creating a duplicate - the response\'s "created" field tells you which happened.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email' ],
				'properties'           => [
					'email' => [
						'type'        => 'string',
						'format'      => 'email',
						'description' => __( 'Required. If a contact with this email already exists, it is updated instead of creating a duplicate.', 'groundhogg' ),
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
						'description' => __( 'Int or canonical string label. Defaults to unconfirmed if omitted (Groundhogg\'s own default for new contacts).', 'groundhogg' ),
					],
					'owner' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'A WordPress user ID to assign as the contact owner - see groundhogg/list-owners for valid IDs. Defaults to the current user if they can add contacts, otherwise the site\'s primary owner.', 'groundhogg' ),
					],
					'tags' => [
						'type'        => 'array',
						'items'       => [ 'type' => [ 'string', 'integer' ] ],
						'description' => __( 'Tag names or IDs to apply. Unlike groundhogg/search-contacts\' tags_include/tags_exclude, a name that does not match an existing tag creates a new one here - that is the intended, expected behavior when tagging a contact on creation.', 'groundhogg' ),
					],
					'meta' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Arbitrary custom field key => value pairs. See groundhogg/list-custom-fields for defined fields. Each value is sanitized the same way the admin UI would sanitize it for that field.', 'groundhogg' ),
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
					'created' => [
						'type'        => 'boolean',
						'description' => __( 'true if a new contact was created; false if an existing contact (matched by email) was updated instead.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = sanitize_email( $input['email'] );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'groundhogg_invalid_email', __( 'The provided email address is not valid.', 'groundhogg' ) );
		}

		// Check for an existing contact, and gate the update path on edit_contact
		// specifically, BEFORE ever constructing a Contact with the incoming data -
		// Contact::__construct() upserts silently (create-if-missing, else update), so
		// this check has to happen first or a caller without edit rights on someone
		// else's contact could overwrite it just by re-submitting their email.
		$existing = is_email_address_in_use( $email );

		if ( $existing ) {
			$contact = new Contact( $email );

			if ( ! current_user_can( 'edit_contact', $contact ) ) {
				return new WP_Error(
					'groundhogg_cannot_edit_contact',
					__( 'A contact with this email already exists and you do not have permission to edit it.', 'groundhogg' )
				);
			}
		}

		$data = [ 'email' => $email ];

		if ( ! empty( $input['first_name'] ) ) {
			$data['first_name'] = sanitize_text_field( $input['first_name'] );
		}

		if ( ! empty( $input['last_name'] ) ) {
			$data['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		if ( isset( $input['optin_status'] ) ) {
			// resolve_optin_status() (Has_Optin_Status trait) returns null for an
			// unrecognized value rather than a silent default - unreachable in normal
			// operation since the schema's enum already rejects it, but a single field
			// being set here still needs a concrete int, so default explicitly.
			$data['optin_status'] = self::resolve_optin_status( $input['optin_status'] ) ?? Preferences::UNCONFIRMED;
		}

		if ( ! empty( $input['owner'] ) ) {

			// resolve_owner() (Has_Owner_Validation trait) - view_contacts, not
			// edit_contacts, matches \Groundhogg\get_owner_roles() (what
			// groundhogg/list-owners is built on). See the trait's docblock.
			$owner_id = self::resolve_owner( absint( $input['owner'] ) );

			if ( is_wp_error( $owner_id ) ) {
				return $owner_id;
			}

			$data['owner_id'] = $owner_id;
		}

		// Upserts by email: creates if $email doesn't exist yet, updates if it does -
		// see \Groundhogg\Contact::__construct().
		$contact = new Contact( $data );

		if ( ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_created', __( 'Unable to create the contact record.', 'groundhogg' ) );
		}

		if ( ! empty( $input['tags'] ) ) {
			// apply_tag() resolves names/IDs via \Groundhogg\parse_tag_list() with its
			// default $create = true - creating a tag by name here is intentional.
			$contact->apply_tag( $input['tags'] );
		}

		if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {

			$meta = [];

			foreach ( $input['meta'] as $key => $value ) {
				$key          = sanitize_key( $key );
				$meta[ $key ] = sanitize_object_meta( $value, $key, 'contact' );
			}

			$contact->update_meta( $meta );
		}

		// Record a submission, same as \Groundhogg\generate_contact_with_map() does for
		// every contact it touches (create or update alike - not just creates) - gives
		// the contact an audit-trail entry in the admin's Submissions log showing what was
		// sent and through what channel, the same way a real form submission would.
		// Best-effort: mirrors generate_contact_with_map() in not treating a logging
		// failure as fatal to the contact create/update itself.
		$submission = new Submission();

		$submission->create( [
			'type'       => 'api',
			'name'       => __( 'Create Contact ability', 'groundhogg' ),
			'contact_id' => $contact->get_id(),
		] );

		$posted_data = $input;
		unset( $posted_data['expand'] );

		$submission->add_posted_data( sanitize_payload( $posted_data ) );

		$expand = $input['expand'] ?? [ 'tags' ];

		return [
			'contact' => Contact_Schema::transform( $contact, $expand ),
			'created' => ! $existing,
		];
	}
}
