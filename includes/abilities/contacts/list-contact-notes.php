<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Note_Schema;
use WP_Error;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

/**
 * Lists the notes on a contact.
 *
 * Two layers of permission: base CAPABILITY = 'view_notes' (matches
 * Notes_Api::read_permissions_callback()) gates the ability generally - this matters
 * more than it might look, since \Groundhogg\DB\Notes::query() only scopes results to
 * the current user's own notes when `current_user_can( 'view_notes' )` is already true
 * and they lack `view_others_notes`; a caller with no note-related capabilities at all
 * would skip that condition entirely and get back *everyone's* notes, unscoped, if
 * CAPABILITY weren't set here. On top of that, a per-contact `view_contact` check - a
 * caller shouldn't be able to list notes on a contact they can't otherwise see, even
 * if they hold `view_notes` in general. Main_Roles::map_meta_cap() now cascades
 * view_note into the associated object's view check as well, but this ability keeps
 * the explicit contact check so it doesn't depend on that.
 */
class List_Contact_Notes extends Ability {

	protected const string NAME       = 'groundhogg/list-contact-notes';
	protected const string CATEGORY   = 'groundhogg-contacts';
	protected const string CAPABILITY = 'view_notes';

	protected const bool READONLY   = true;
	protected const bool IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Contact Notes', 'groundhogg' ),
			'description' => __( 'List the notes on a contact, most recent first. If the current user can view_notes but not view_others_notes, only their own notes on this contact are returned.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'contact_id' ],
				'properties'           => [
					'contact_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The Groundhogg contact ID - see groundhogg/search-contacts or groundhogg/get-contact to find it.', 'groundhogg' ),
					],
					'limit' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'maximum'     => 200,
						'default'     => 100,
						'description' => __( 'Maximum number of notes to return.', 'groundhogg' ),
					],
					'offset' => [
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'total_items' => [
						'type'        => 'integer',
						'description' => __( 'Total notes on this contact visible to the current user, ignoring limit/offset.', 'groundhogg' ),
					],
					'notes' => [
						'type'  => 'array',
						'items' => Note_Schema::get_schema(),
					],
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
				__( 'You do not have permission to view notes on this contact.', 'groundhogg' )
			);
		}

		$limit  = ! empty( $input['limit'] ) ? min( absint( $input['limit'] ), 200 ) : 100;
		$offset = ! empty( $input['offset'] ) ? absint( $input['offset'] ) : 0;

		$query_vars = [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'limit'       => $limit,
			'offset'      => $offset,
			'found_rows'  => true,
			'orderby'     => 'date_created',
			'order'       => 'DESC',
		];

		$db      = get_db( 'notes' );
		$results = $db->query( $query_vars );

		// Same found_rows() ordering caveat as list-tags: capture it immediately after
		// the query, before anything else that could run its own query first.
		$total_items = $db->found_rows();

		$notes = array_map( [ Note_Schema::class, 'transform' ], $results );

		return [
			'total_items' => $total_items,
			'notes'       => $notes,
		];
	}
}
