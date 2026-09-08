<?php

namespace Groundhogg\Abilities\Traits;

use Groundhogg\Preferences;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared int-or-string opt-in status vocabulary and resolution, for any ability that
 * accepts or filters on Preferences::get_preference_names() values. Used by
 * Search_Contacts (filtering - a list of accepted values) and Create_Contact
 * (setting - a single value); pulled out here once a second ability needed the exact
 * same enum/resolver logic Search_Contacts already had.
 */
trait Has_Optin_Status {

	/**
	 * The subset of Preferences::string_to_preference()'s own accepted string keys
	 * that abilities expose as a schema's `enum` of string labels for optin_status -
	 * not the full synonym list that method accepts (e.g. "subscribe", "pending",
	 * translated names), so a caller sees exactly these labels in the schema rather
	 * than guessing which synonyms happen to also work.
	 *
	 * @return string[]
	 */
	protected static function optin_status_labels(): array {
		return [
			'unconfirmed',
			'confirmed',
			'unsubscribed',
			'weekly',
			'monthly',
			'hard_bounce',
			'spam',
			'complained',
			'blocked',
		];
	}

	/**
	 * Both accepted forms merged, for use as a schema property's `enum`: the raw
	 * Preferences status ints, plus the canonical string labels above.
	 *
	 * @return array
	 */
	protected static function optin_status_enum(): array {
		return array_merge(
			array_keys( Preferences::get_preference_names() ),
			self::optin_status_labels()
		);
	}

	/**
	 * Resolve a single int-or-string optin_status value to its status int, via
	 * Preferences::string_to_preference() for strings.
	 *
	 * Both Preferences::string_to_preference() and the DB layer's own
	 * Preferences::sanitize() silently fall back to Preferences::UNCONFIRMED for any
	 * value they don't recognize - the kind of silent-wrong-value failure that cost
	 * us earlier with the old `filters` system's date_created shape. A schema `enum`
	 * built from optin_status_enum() is what actually guards against that in normal
	 * operation, rejecting an unrecognized value before this ever runs - the
	 * in_array() check here just means nothing ever falls through to either method's
	 * unsafe default even if this is somehow reached another way.
	 *
	 * Returns null (not a silent default) when the value isn't recognized, rather
	 * than picking a fallback here, so each call site decides what "unresolved"
	 * should mean for its own case: default it to something (a single value being
	 * set, like Create_Contact) or drop it (a list being filtered on, like
	 * Search_Contacts - silently defaulting an unrecognized entry to UNCONFIRMED
	 * there would add a filter condition nobody asked for).
	 *
	 * @param int|string $value
	 *
	 * @return int|null
	 */
	protected static function resolve_optin_status( $value ): ?int {

		if ( is_numeric( $value ) ) {
			return absint( $value );
		}

		if ( is_string( $value ) && in_array( strtolower( trim( $value ) ), self::optin_status_labels(), true ) ) {
			return Preferences::string_to_preference( $value );
		}

		return null;
	}
}
