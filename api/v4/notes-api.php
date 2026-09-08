<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Classes\Note;
use function Groundhogg\create_object_from_type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notes_Api
 *
 * @package Groundhogg\Api\V4
 */
class Notes_Api extends Base_Object_Api {

	/**
	 * @inheritDoc
	 */
	public function get_db_table_name() {
		return 'notes';
	}
	
	protected function get_object_class() {
		return Note::class;
	}

	/**
	 * @inheritDoc
	 */
	public function read_permissions_callback() {
		return current_user_can( 'view_notes' );
	}

	/**
	 * @inheritDoc
	 */
	public function update_permissions_callback() {
		return current_user_can( 'edit_notes' );
	}

	/**
	 * @inheritDoc
	 *
	 * `add_notes` / `add_tasks` only gates the endpoint. On top of that, a caller
	 * has to be able to view whatever object the note/task is being attached to -
	 * without this, `add_notes` alone lets anyone create notes against contacts
	 * (or deals, companies) they can't otherwise see, and then read them straight
	 * back. Mirrors the Add_Contact_Note ability's per-contact check and the
	 * view_note / view_task association cascade in Main_Roles::map_meta_cap().
	 *
	 * @param \WP_REST_Request|null $request
	 *
	 * @return bool|\WP_Error
	 */
	public function create_permissions_callback( ?\WP_REST_Request $request = null ) {

		if ( ! current_user_can( sprintf( 'add_%ss', $this->get_object_type() ) ) ) {
			return false;
		}

		if ( ! $request ) {
			return true;
		}

		$items = $request->get_json_params();

		// Nothing to inspect - let the handler return its own 422.
		if ( empty( $items ) ) {
			return true;
		}

		// Normalise single-resource vs. bulk into a list of payloads.
		if ( ! array_is_list( $items ) ) {
			$items = [ $items ];
		}

		foreach ( $items as $item ) {

			$item = (array) $item;
			$data = isset( $item['data'] ) ? (array) $item['data'] : $item;

			if ( ! $this->current_user_can_create_for_associated_object( $data ) ) {
				return self::ERROR_403(
					'cannot_create',
					sprintf(
						'You do not have permission to add a %s to one or more of the target objects.',
						$this->get_db_table()->singular
					)
				);
			}
		}

		return true;
	}

	/**
	 * Whether the current user may attach a note/task to the object referenced by
	 * a single create payload's object_type / object_id.
	 *
	 * A payload with no association, or one pointing at a type we can't resolve
	 * (add-on inactive) or don't recognise, falls through to the endpoint cap
	 * only - never more restrictive for a type it can't reason about, matching
	 * the map_meta_cap cascade.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	protected function current_user_can_create_for_associated_object( array $data ) {

		$object_type = $data['object_type'] ?? '';
		$object_id   = absint( $data['object_id'] ?? 0 );

		if ( ! $object_type || ! $object_id ) {
			return true;
		}

		$associated = create_object_from_type( $object_id, $object_type );

		if ( ! is_object( $associated ) || ! $associated->exists() ) {
			return true;
		}

		$associated_type = $associated->_get_object_type();

		$cascade_types = apply_filters(
			'groundhogg/roles/note_association_cap_check_types',
			[ 'contact', 'deal', 'company' ],
			$associated,
			'view'
		);

		if ( ! in_array( $associated_type, $cascade_types, true ) ) {
			return true;
		}

		return current_user_can( 'view_' . $associated_type, $associated );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_permissions_callback() {
		return current_user_can( 'delete_notes' );
	}

	/**
	 * @param \WP_REST_Request $request
	 * @param                  $cap
	 *
	 * @return bool|\WP_Error
	 */
	public function single_cap_check( \WP_REST_Request $request, $cap ){
		$note = $this->get_object_from_request( $request );

		if ( ! $note->exists() ){
			return self::ERROR_404();
		}

		return current_user_can( $cap, $note );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function update_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'edit_note' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function read_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'view_note' );
	}

	/**
	 * protect delete endpoint
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_single_permissions_callback( \WP_REST_Request $request ) {
		return $this->single_cap_check( $request, 'delete_note' );
	}
}