<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Applies the PII policy, see Pii, to what the admin renders on the server: tables, forms and cards.
 *
 * What comes from the REST API, which is most of the React-ish admin, is redacted by Pii_Rest. This is the rest. The
 * templates call Pii::display() and Pii::contact_label() where they print a contact's details; this class holds the
 * hooks that cover everything at once.
 */
class Pii_Admin {

	/**
	 * Capabilities that are only any good to a user who can view contact PII. What they let you do is take PII out as a
	 * file (exports, a contact's files), or find out what is on the list by putting it in (imports tell you which
	 * addresses they updated). A restricted user doesn't have them, whatever their role says.
	 */
	const CAPS = [
		'export_contacts',
		'view_previous_exports',
		'import_contacts',
		'download_contact_files',
	];

	public function __construct() {

		add_filter( 'user_has_cap', [ $this, 'user_has_cap' ], 99, 4 );

		// the picture is a hash of the email address, which would confirm a guess
		add_filter( 'groundhogg/contact/profile_picture', [ $this, 'profile_picture' ] );
	}

	/**
	 * Take the capabilities that need PII off a user who can't view it
	 *
	 * @param bool[]   $allcaps capability => has it
	 * @param string[] $caps
	 * @param array    $args
	 * @param \WP_User $user
	 *
	 * @return bool[]
	 */
	public function user_has_cap( $allcaps, $caps, $args, $user ) {

		if ( ! $user instanceof \WP_User || ! $user->ID ) {
			return $allcaps;
		}

		// not Pii::is_restricted(), that asks whether the user has the capability and this is where that's answered
		if ( ! apply_filters( 'groundhogg/pii/is_restricted', empty( $allcaps[ Pii::CAP ] ), $user->ID ) ) {
			return $allcaps;
		}

		foreach ( self::CAPS as $cap ) {
			if ( isset( $allcaps[ $cap ] ) ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}

	/**
	 * @param string $picture
	 *
	 * @return string
	 */
	public function profile_picture( $picture ) {
		return Pii::is_restricted() ? Pii::blank_picture() : $picture;
	}
}
