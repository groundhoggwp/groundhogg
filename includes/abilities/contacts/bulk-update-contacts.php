<?php

namespace Groundhogg\Abilities\Contacts;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Abilities\Traits\Has_Optin_Status;
use Groundhogg\Abilities\Traits\Has_Owner_Validation;
use Groundhogg\Background_Tasks;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
use Throwable;
use WP_Error;
use function Groundhogg\get_db;
use function Groundhogg\parse_tag_list;

/**
 * Gives every contact in a segment the same changes: tags added or removed, opt-in
 * status, owner, custom fields - the agent equivalent of the contacts table's bulk edit
 * (PATCH gh/v4/contacts with a query).
 *
 * - The audience is the shared Segment_Schema params, and must be named explicitly:
 *   without a narrowing param the call is refused unless all_contacts is set.
 * - Up to SYNC_LIMIT matching contacts are updated during the call. A larger segment
 *   is handed to the Update_Contacts_Last_Id background task, the same one the admin
 *   bulk edit uses, which works through it in batches.
 * - The changes go in their own `changes` object, because the segment params already
 *   use optin_status, owner and meta as filters.
 * - dry_run counts the matching contacts without changing anything.
 * - Fields that identify one contact (email, first and last name) aren't offered, see
 *   groundhogg/update-contacts for those.
 *
 * Every contact still needs edit_contact for the current user; ones that don't pass are
 * skipped, here and in the background task.
 *
 * DESTRUCTIVE is true: it can change thousands of contacts at once (unsubscribe a whole
 * list, strip a tag that flows depend on) and there is no undo. Running it again with the
 * same input gives the same result, so IDEMPOTENT is true.
 */
class Bulk_Update_Contacts extends Ability {

	use Has_Optin_Status;
	use Has_Owner_Validation;

	protected const NAME       = 'groundhogg/bulk-update-contacts';
	protected const CATEGORY   = 'groundhogg-contacts';
	protected const CAPABILITY = 'edit_contacts';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = true;

	const SYNC_LIMIT = 100;

	protected function get_args(): array {

		return [
			'label'       => __( 'Bulk Update Contacts', 'groundhogg' ),
			/* translators: %d: how many contacts are updated during the call before it moves to the background */
			'description' => sprintf( __( 'Give every contact in a segment the same changes: add or remove tags, set the opt-in status, the owner, or custom fields, all given in "changes". Name the audience with the segment params (tags_include, saved_search, include, ...) or pass all_contacts; the top-level optin_status, owner and meta are filters on who is updated, not changes. Up to %d contacts are updated straight away, a larger segment is updated in the background. Use dry_run first to see how many contacts match. For different changes per contact, use groundhogg/update-contacts.', 'groundhogg' ), self::SYNC_LIMIT ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'changes' ],
				'properties'           => array_merge( Segment_Schema::properties(), [
					'all_contacts' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Explicitly update every contact. Required when no narrowing segment param (tags_include, saved_search, include, ...) is given.', 'groundhogg' ),
					],
					'dry_run' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Only count the contacts that would be updated. Nothing is changed and no tags are created.', 'groundhogg' ),
					],
					// Its own object, because the segment params already use optin_status, owner
					// and meta as filters
					'changes' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'description'          => __( 'Required. What to change on every matching contact. Give at least one field; omitted fields are left as they are.', 'groundhogg' ),
						'properties'           => [
							'tags_add' => [
								'type'        => 'array',
								'items'       => [ 'type' => [ 'string', 'integer' ] ],
								'description' => __( 'Tag names or IDs to add. A name that does not match an existing tag creates a new one.', 'groundhogg' ),
							],
							'tags_remove' => [
								'type'        => 'array',
								'items'       => [ 'type' => [ 'string', 'integer' ] ],
								'description' => __( 'Tag names or IDs to remove. A name that does not match an existing tag is ignored, never created.', 'groundhogg' ),
							],
							'optin_status' => [
								'type'        => [ 'integer', 'string' ],
								'enum'        => self::optin_status_enum(),
								'description' => __( 'Set every matching contact to this opt-in status. Int or canonical string label.', 'groundhogg' ),
							],
							'owner' => [
								'type'        => 'integer',
								'minimum'     => 1,
								'description' => __( 'A WordPress user ID to assign as the owner - see groundhogg/list-owners for valid IDs.', 'groundhogg' ),
							],
							'meta' => [
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => __( 'Custom field key => value pairs to set on every matching contact. See groundhogg/list-custom-fields for defined fields.', 'groundhogg' ),
							],
						],
					],
				] ),
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'mode' => [
						'type'        => 'string',
						'enum'        => [ 'dry_run', 'immediate', 'background' ],
						'description' => __( '"dry_run" when nothing was changed, "immediate" when the contacts were updated during the call, "background" when a background task will update them.', 'groundhogg' ),
					],
					'matched' => [
						'type'        => 'integer',
						'description' => __( 'How many contacts match the audience.', 'groundhogg' ),
					],
					'updated' => [
						'type'        => 'integer',
						'description' => __( 'For mode "immediate": how many contacts were updated.', 'groundhogg' ),
					],
					'skipped' => [
						'type'        => 'integer',
						'description' => __( 'For mode "immediate": matching contacts left alone because you are not allowed to edit them.', 'groundhogg' ),
					],
					'scheduled' => [
						'type'        => 'boolean',
						'description' => __( 'For mode "background": true once the background task is queued.', 'groundhogg' ),
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$changes = is_array( $input['changes'] ?? null ) ? $input['changes'] : [];

		$has_changes = ! empty( $changes['tags_add'] )
			|| ! empty( $changes['tags_remove'] )
			|| isset( $changes['optin_status'] )
			|| ! empty( $changes['owner'] )
			|| ! empty( $changes['meta'] );

		if ( ! $has_changes ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'No fields to update were given. Pass tags_add, tags_remove, optin_status, owner or meta in changes.', 'groundhogg' ) );
		}

		if ( ! Segment_Schema::has_audience( $input ) && empty( $input['all_contacts'] ) ) {
			return new WP_Error(
				'groundhogg_no_audience',
				__( 'Name an audience with a segment param (tags_include, saved_search, include, ...), or pass all_contacts to update every contact.', 'groundhogg' )
			);
		}

		$data = [];

		if ( isset( $changes['optin_status'] ) ) {

			$optin_status = self::resolve_optin_status( $changes['optin_status'] );

			// Unlike update-contact there's no falling back to unconfirmed, for a whole segment
			if ( ! $optin_status ) {
				return new WP_Error( 'groundhogg_invalid_optin_status', __( 'Not a valid opt-in status.', 'groundhogg' ) );
			}

			$data['optin_status'] = $optin_status;
		}

		if ( ! empty( $changes['owner'] ) ) {

			$owner_id = self::resolve_owner( absint( $changes['owner'] ) );

			if ( is_wp_error( $owner_id ) ) {
				return $owner_id;
			}

			$data['owner_id'] = $owner_id;
		}

		// The plain array form, not to_contact_query()'s live query object, because it may
		// be stored for the background task and re-run in batches
		$query = Segment_Schema::to_query( $input );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		try {
			$matched = get_db( 'contacts' )->count( $query );
		} catch ( Throwable $e ) {
			return new WP_Error( 'groundhogg_invalid_segment', $e->getMessage() );
		}

		if ( $matched === 0 ) {
			return new WP_Error( 'groundhogg_no_contacts', __( 'No contacts match the given audience.', 'groundhogg' ) );
		}

		if ( ! empty( $input['dry_run'] ) ) {
			return [
				'mode'    => 'dry_run',
				'matched' => $matched,
			];
		}

		// Resolved once to IDs here, so the background task never resolves names. tags_add
		// mints unknown names once, tags_remove never does (see update-contact).
		$add_tags    = empty( $changes['tags_add'] ) ? [] : parse_tag_list( $changes['tags_add'], 'ID', true );
		$remove_tags = empty( $changes['tags_remove'] ) ? [] : parse_tag_list( $changes['tags_remove'], 'ID', false );
		$meta        = empty( $changes['meta'] ) ? [] : Update_Contact::sanitize_meta( $changes['meta'] );

		if ( empty( $data ) && empty( $meta ) && empty( $add_tags ) && empty( $remove_tags ) ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'None of the tags in tags_remove match an existing tag, so there is nothing to change.', 'groundhogg' ) );
		}

		if ( $matched > self::SYNC_LIMIT ) {

			$scheduled = Background_Tasks::update_contacts( $query, [
				'data'        => $data,
				'meta'        => $meta,
				'add_tags'    => $add_tags,
				'remove_tags' => $remove_tags,
			] );

			if ( is_wp_error( $scheduled ) ) {
				return $scheduled;
			}

			return [
				'mode'      => 'background',
				'matched'   => $matched,
				'scheduled' => (bool) $scheduled,
			];
		}

		try {
			$contacts = ( new Contact_Query() )->query( $query, true );
		} catch ( Throwable $e ) {
			return new WP_Error( 'groundhogg_invalid_segment', $e->getMessage() );
		}

		$updated = 0;
		$skipped = 0;

		/**
		 * @var $contact Contact
		 */
		foreach ( $contacts as $contact ) {

			if ( ! current_user_can( 'edit_contact', $contact ) ) {
				$skipped ++;
				continue;
			}

			if ( ! empty( $data ) ) {
				$contact->update( $data );
			}

			if ( ! empty( $meta ) ) {
				$contact->update_meta( $meta );
			}

			if ( ! empty( $add_tags ) ) {
				$contact->apply_tag( $add_tags );
			}

			if ( ! empty( $remove_tags ) ) {
				$contact->remove_tag( $remove_tags );
			}

			$updated ++;
		}

		return [
			'mode'    => 'immediate',
			'matched' => $matched,
			'updated' => $updated,
			'skipped' => $skipped,
		];
	}
}
