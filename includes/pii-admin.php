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

	public function __construct() {

		// the picture is a hash of the email address, which would confirm a guess
		add_filter( 'groundhogg/contact/profile_picture', [ $this, 'profile_picture' ] );
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
