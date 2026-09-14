<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Note_Schema;
use Groundhogg\Classes\Note;
use WP_Error;
use function Groundhogg\get_contactdata;

/**
 * Adds a note to a contact. Gated on the base `add_notes` capability, plus a
 * per-contact `view_contact` check - a caller has to be able to see the specific
 * contact before attaching a note to it, not just hold `add_notes` in the abstract.
 * Notes_Api::create_permissions_callback() now makes the same per-associated-object
 * check, but this ability keeps its own so it stands on its own regardless of the
 * REST layer - leaving a caller able to attach notes to a contact they can't
 * otherwise see is real information leakage (see the "Notes" section of the project doc).
 */
class Add_Contact_Note extends Ability {

	protected const NAME       = 'groundhogg/add-contact-note';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'add_notes';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Add Contact Note', 'groundhogg' ),
			'description' => __( 'Add a note to a contact. Each call creates a new note - it does not update or replace any existing one.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'contact_id', 'content' ],
				'properties'           => [
					'contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The Groundhogg contact ID to add the note to - see groundhogg/search-contacts or groundhogg/get-contact to find it.', 'groundhogg' ),
					],
					'content' => [
						'type'        => 'string',
						'description' => __( 'The note text. Supports the same merge-field replacements as the admin UI (e.g. {first_name}), resolved against this contact.', 'groundhogg' ),
					],
					'summary' => [
						'type'        => 'string',
						'description' => __( 'Optional short summary/title for the note. Most notes leave this blank.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'note' => Note_Schema::get_schema(),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$contact = get_contactdata( $input['contact_id'] );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'groundhogg_contact_not_found', __( 'Contact not found.', 'groundhogg' ) );
		}

		if ( ! current_user_can( 'view_contact', $contact ) ) {
			return new WP_Error(
				'groundhogg_cannot_view_contact',
				__( 'You do not have permission to add a note to this contact.', 'groundhogg' )
			);
		}

		$data = [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'content'     => $input['content'],
		];

		if ( ! empty( $input['summary'] ) ) {
			$data['summary'] = sanitize_text_field( $input['summary'] );
		}

		// Note::create() runs merge-field replacements on content itself (do_replacements()
		// against the contact) and wp_kses_post()'s it via sanitize_columns() - no need to
		// sanitize content here first.
		$note = new Note();
		$note->create( $data );

		if ( ! $note->exists() ) {
			return new WP_Error( 'groundhogg_note_not_created', __( 'Unable to create the note.', 'groundhogg' ) );
		}

		return [
			'note' => Note_Schema::transform( $note ),
		];
	}
}
