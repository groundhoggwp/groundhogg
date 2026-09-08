<?php

namespace Groundhogg\Abilities\Traits;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared `owner` param validation, for any ability that lets a caller assign a
 * contact owner by WP user ID. Used by Create_Contact and Update_Contact.
 *
 * Pulled out as soon as a second ability needed it - same reasoning as
 * Has_Optin_Status. First written directly inside Create_Contact after comparing it
 * against \Groundhogg\generate_contact_with_map()'s own owner_id handling: that
 * function validates via `user_can( $user, 'edit_contacts' )` (silently dropping the
 * field if it fails), but the correct capability to check here is `view_contacts` -
 * that's what \Groundhogg\get_owner_roles() (what groundhogg/list-owners is built on)
 * actually checks. Validating against edit_contacts instead would risk a real
 * inconsistency: list-owners could hand a caller an id this validation then refuses,
 * for any role that has view_contacts but not edit_contacts.
 */
trait Has_Owner_Validation {

	/**
	 * @param int $owner_id
	 *
	 * @return int|WP_Error The validated owner id, or a WP_Error if it doesn't belong
	 *                       to a real WordPress user who can own contacts.
	 */
	protected static function resolve_owner( int $owner_id ) {

		$owner = get_userdata( $owner_id );

		if ( ! $owner || ! user_can( $owner, 'view_contacts' ) ) {
			return new WP_Error(
				'groundhogg_invalid_owner',
				__( 'The given owner is not a valid WordPress user who can own contacts. See groundhogg/list-owners for valid IDs.', 'groundhogg' )
			);
		}

		return $owner_id;
	}
}
