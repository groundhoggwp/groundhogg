<?php

namespace Groundhogg;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The policy for personally identifiable information (PII) on contacts.
 *
 * A user without the `view_contact_pii` capability is "restricted": they keep working with contacts (IDs, tags,
 * activity, opt-in status, segments, broadcasts, flows) but never see, or search by, the contact's identity. Everything
 * that sends to a contact still resolves the real address server side, so redaction happens where data is *output*,
 * never in the Contact model.
 *
 * The capability is granted to every role that can view contacts, so nothing changes until it is taken away from a role
 * or a user. Taking it away applies to administrators too. It is also how an AI connection is limited: abilities run as
 * a WordPress user, so a connection that runs as a restricted user is redacted automatically.
 *
 * Restricted means a logged-in user without the capability. There is no user in cron, background tasks or WP-CLI, which
 * are system contexts and are never restricted.
 *
 * Filtering or searching by PII is refused rather than allowed, because a count or a match is an answer: "email equals
 * jane@example.com" returning 1 confirms she is on the list. See check_query() and check_filters().
 *
 * Add-ons that expose contact data in their own output should redact with is_restricted() and REDACTED, and can extend
 * what counts as PII with the filters below.
 */
class Pii {

	/**
	 * The capability that allows seeing and searching by contact PII
	 */
	const CAP = 'view_contact_pii';

	/**
	 * What a redacted value is replaced with
	 */
	const REDACTED = '[redacted]';

	/**
	 * The shortest value scrub_text() looks for on its own
	 */
	const MIN_SCRUB_LENGTH = 3;

	/**
	 * Whether a user is restricted from PII. Defaults to the current user.
	 *
	 * @param int|\WP_User $user
	 *
	 * @return bool
	 */
	public static function is_restricted( $user = 0 ): bool {

		$user_id = $user instanceof \WP_User ? $user->ID : absint( $user );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// No user is a system context (cron, background task, CLI), not a restricted person
		if ( ! $user_id ) {
			return false;
		}

		/**
		 * Filters whether a user is restricted from contact PII
		 *
		 * @param bool $restricted
		 * @param int  $user_id
		 */
		return (bool) apply_filters( 'groundhogg/pii/is_restricted', ! user_can( $user_id, self::CAP ), $user_id );
	}

	/**
	 * Columns on the contact itself that are PII
	 *
	 * @return string[]
	 */
	public static function contact_fields(): array {

		/**
		 * Filters the contact columns that are PII
		 *
		 * @param string[] $fields
		 */
		return apply_filters( 'groundhogg/pii/contact_fields', [ 'email', 'first_name', 'last_name' ] );
	}

	/**
	 * Built in contact meta keys that are PII. Custom fields are PII when their definition has `pii` set, see
	 * is_pii_meta_key().
	 *
	 * @return string[]
	 */
	public static function meta_keys(): array {

		/**
		 * Filters the contact meta keys that are PII
		 *
		 * @param string[] $keys
		 */
		return apply_filters( 'groundhogg/pii/meta_keys', [
			'alternate_emails',
			'alternate_phones',
			'birthday',
			'city',
			'company_phone',
			'ip_address',
			'mobile_phone',
			'postal_zip',
			'primary_phone',
			'primary_phone_extension',
			'street_address_1',
			'street_address_2',
		] );
	}

	/**
	 * Built in contact meta keys a restricted user may see and filter on. Anything else that is not a registered
	 * custom field is treated as unknown, and unknown meta is hidden from them.
	 *
	 * @return string[]
	 */
	public static function safe_meta_keys(): array {

		/**
		 * Filters the built in contact meta keys that are not PII
		 *
		 * @param string[] $keys
		 */
		return apply_filters( 'groundhogg/pii/safe_meta_keys', [
			'company_name',
			'country',
			'gdpr_consent',
			'job_title',
			'lead_source',
			'locale',
			'marketing_consent',
			'region',
			'source_page',
			'terms_agreement',
			'time_zone',
		] );
	}

	/**
	 * Filter types, see Contact_Query, whose condition is on PII
	 *
	 * @return string[]
	 */
	public static function filter_types(): array {

		/**
		 * Filters the segment filter types that are on PII
		 *
		 * @param string[] $types
		 */
		return apply_filters( 'groundhogg/pii/filter_types', [
			'age',
			'anniversary',
			'birthday',
			'city',
			'email',
			'first_name',
			'last_name',
			'phone',
			'street_address_1',
			'street_address_2',
			'user_meta',
			'zip_code',
		] );
	}

	/**
	 * Whether a meta key is PII
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function is_pii_meta_key( string $key ): bool {

		if ( in_array( $key, self::meta_keys(), true ) ) {
			return true;
		}

		$field = Properties::instance()->get_field( $key );

		return is_array( $field ) && ! empty( $field['pii'] );
	}

	/**
	 * Whether a restricted user may see or filter on a meta key. PII is never exposed, and neither is a key that is
	 * neither a registered custom field nor a known safe one, since nothing says it isn't PII.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function can_expose_meta_key( string $key ): bool {

		if ( $key === '' || self::is_pii_meta_key( $key ) ) {
			return false;
		}

		return in_array( $key, self::safe_meta_keys(), true ) || is_array( Properties::instance()->get_field( $key ) );
	}

	/**
	 * Remove the meta a restricted user can't see
	 *
	 * @param array $meta key => value
	 *
	 * @return array
	 */
	public static function filter_meta( array $meta ): array {

		if ( ! self::is_restricted() ) {
			return $meta;
		}

		return array_filter( $meta, [ self::class, 'can_expose_meta_key' ], ARRAY_FILTER_USE_KEY );
	}

	/**
	 * The error for something a restricted user can't do
	 *
	 * @param string $message
	 *
	 * @return WP_Error
	 */
	public static function restricted_error( string $message = '' ): WP_Error {

		return new WP_Error(
			'groundhogg_pii_restricted',
			$message ?: __( 'Your account is not allowed to view or search by personal information (email address, name, phone, address, birthday). Use tags, activity, dates, opt-in status or non-personal custom fields instead.', 'groundhogg' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Whether Filters DSL conditions, at any depth, are on PII
	 *
	 * @param array $filters
	 *
	 * @return bool
	 */
	public static function filters_touch_pii( array $filters ): bool {

		foreach ( $filters as $value ) {

			if ( ! is_array( $value ) ) {
				continue;
			}

			if ( isset( $value['type'] ) && is_string( $value['type'] ) ) {

				if ( in_array( $value['type'], self::filter_types(), true ) ) {
					return true;
				}

				if ( $value['type'] === 'meta' && ! self::can_expose_meta_key( (string) ( $value['meta'] ?? '' ) ) ) {
					return true;
				}
			}

			// OR groups, AND groups and sub queries are all nested arrays
			if ( self::filters_touch_pii( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether Contact_Query vars search or filter by PII
	 *
	 * @param array $query
	 *
	 * @return bool
	 */
	public static function query_touches_pii( array $query ): bool {

		$pii_vars = [ 'search', 'email', 'email_like', 'first_name_like', 'last_name_like', 'full_name_like', 'user_meta_key' ];

		foreach ( $pii_vars as $var ) {
			if ( ! empty( $query[ $var ] ) ) {
				return true;
			}
		}

		if ( ! empty( $query['orderby'] ) && in_array( $query['orderby'], self::contact_fields(), true ) ) {
			return true;
		}

		// sorting by a PII meta column, like cm.birthday, is as good as reading it
		if ( ! empty( $query['orderby'] ) && is_string( $query['orderby'] ) && str_starts_with( $query['orderby'], 'cm.' ) && ! self::can_expose_meta_key( substr( $query['orderby'], 3 ) ) ) {
			return true;
		}

		if ( ! empty( $query['meta_key'] ) && ! self::can_expose_meta_key( (string) $query['meta_key'] ) ) {
			return true;
		}

		if ( ! empty( $query['meta_query'] ) && is_array( $query['meta_query'] ) && self::meta_query_touches_pii( $query['meta_query'] ) ) {
			return true;
		}

		foreach ( [ 'filters', 'include_filters', 'exclude_filters' ] as $var ) {
			if ( ! empty( $query[ $var ] ) && is_array( $query[ $var ] ) && self::filters_touch_pii( $query[ $var ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array $meta_query
	 *
	 * @return bool
	 */
	protected static function meta_query_touches_pii( array $meta_query ): bool {

		foreach ( $meta_query as $clause ) {

			if ( ! is_array( $clause ) ) {
				continue;
			}

			if ( isset( $clause['key'] ) && ! self::can_expose_meta_key( (string) $clause['key'] ) ) {
				return true;
			}

			if ( self::meta_query_touches_pii( $clause ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The columns that are PII, by the name of the table they are on, which is what the REST routes are called too.
	 * Tables that aren't here have none.
	 *
	 * @return array<string,string[]>
	 */
	public static function table_columns(): array {

		/**
		 * Filters the PII columns of the tables that have them
		 *
		 * @param array<string,string[]> $tables table name => columns
		 */
		return apply_filters( 'groundhogg/pii/table_columns', [
			'email_log'   => [ 'recipients', 'from_address', 'subject', 'content', 'altbody', 'headers', 'error_message', 'msg_id' ],
			'messages'    => [ 'from_address', 'to_address', 'subject', 'content', 'summary', 'message_id', 'in_reply_to', 'thread_id' ],
			'page_visits' => [ 'ip_address', 'query', 'fragment' ],
			'activity'    => [ 'ip_address' ],
			'notes'       => [ 'content', 'summary' ],
			'tasks'       => [ 'content', 'summary' ],
			// what was typed into a form is meta, so filtering by meta is searching the answers
			'submissions' => [ 'meta_key', 'meta_value', 'meta_query', 'meta_compare', 'meta' ],
		] );
	}

	/**
	 * The tables whose free text search looks in PII columns
	 *
	 * @return string[]
	 */
	public static function search_tables(): array {

		/**
		 * Filters the tables whose free text search looks in PII columns
		 *
		 * @param string[] $tables
		 */
		return apply_filters( 'groundhogg/pii/search_tables', [ 'email_log', 'messages', 'notes', 'tasks', 'submissions' ] );
	}

	/**
	 * Whether the query vars of a table search, filter or sort by its PII columns
	 *
	 * @param string $table the name of the table, like email_log
	 * @param array  $query
	 *
	 * @return bool
	 */
	public static function table_query_touches_pii( string $table, array $query ): bool {

		$columns = self::table_columns()[ $table ] ?? [];

		if ( empty( $columns ) ) {
			return false;
		}

		if ( in_array( $table, self::search_tables(), true ) && ( ! empty( $query['search'] ) || ! empty( $query['query']['search'] ) ) ) {
			return true;
		}

		return self::query_mentions_columns( $query, $columns );
	}

	/**
	 * Refuse a query on a table that searches, filters or sorts by its PII columns, for a restricted user
	 *
	 * @param string $table
	 * @param array  $query
	 *
	 * @return true|WP_Error
	 */
	public static function check_table_query( string $table, array $query ) {

		if ( self::is_restricted() && self::table_query_touches_pii( $table, $query ) ) {
			return self::restricted_error();
		}

		return true;
	}

	/**
	 * Refuse Contact_Query vars that search or filter by PII, for a restricted user
	 *
	 * @param array $query
	 *
	 * @return true|WP_Error
	 */
	public static function check_query( array $query ) {

		if ( self::is_restricted() && self::query_touches_pii( $query ) ) {
			return self::restricted_error();
		}

		return true;
	}

	/**
	 * Refuse Filters DSL conditions on PII, for a restricted user
	 *
	 * @param array $filters
	 *
	 * @return true|WP_Error
	 */
	public static function check_filters( array $filters ) {

		if ( self::is_restricted() && self::filters_touch_pii( $filters ) ) {
			return self::restricted_error();
		}

		return true;
	}

	/**
	 * What a restricted user sees in place of a contact's name or email address
	 *
	 * @param int $contact_id
	 *
	 * @return string
	 */
	public static function contact_label( int $contact_id ): string {
		/* translators: %d: a contact ID */
		return sprintf( __( 'Contact #%d', 'groundhogg' ), $contact_id );
	}

	/**
	 * What to call a contact in a title or heading: their name, or the address they signed up with when there isn't one.
	 * A restricted user gets contact_label().
	 *
	 * @param Contact $contact
	 *
	 * @return string
	 */
	public static function contact_name( Contact $contact ): string {

		if ( self::is_restricted() ) {
			return self::contact_label( $contact->get_id() );
		}

		return trim( $contact->get_full_name() ) ?: guess_name_from_email( $contact->get_email() );
	}

	/**
	 * What a restricted user sees in place of a contact's picture, which is a hash of their email address
	 *
	 * @return string
	 */
	public static function blank_picture(): string {
		return includes_url( 'images/blank.gif' );
	}

	/**
	 * A value for display: the value, or the redacted marker for a restricted user. Empty values stay empty.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function display( $value ) {
		return ( $value !== '' && $value !== null && $value !== false && self::is_restricted() ) ? self::REDACTED : $value;
	}

	/**
	 * Whether a value is the redacted marker, which a screen that shows redacted data can hand back when it's saved
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function is_marker( $value ): bool {
		return is_string( $value ) && trim( $value ) === self::REDACTED;
	}

	/**
	 * Drop the given fields of an array that are the redacted marker. What was never shown is not a value to store: a
	 * form or a client that hands back what it was given must not overwrite the real thing with the marker. This is for
	 * everyone, not only restricted users, nothing should be stored as the marker.
	 *
	 * @param array    $data
	 * @param string[] $fields
	 *
	 * @return array
	 */
	public static function without_marker( array $data, array $fields ): array {

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $data ) && self::is_marker( $data[ $field ] ) ) {
				unset( $data[ $field ] );
			}
		}

		return $data;
	}

	/**
	 * Replace the given fields of a flat array with the redacted marker, leaving fields that aren't there alone
	 *
	 * @param array    $data
	 * @param string[] $fields
	 *
	 * @return array
	 */
	public static function redact_fields( array $data, array $fields ): array {

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$data[ $field ] = self::REDACTED;
			}
		}

		return $data;
	}

	/**
	 * The contact that something with an object type and ID belongs to, for scrubbing the free text on it
	 *
	 * @param string $object_type
	 * @param int    $object_id
	 *
	 * @return Contact|null
	 */
	public static function contact_for( string $object_type, int $object_id ) {

		if ( $object_type !== 'contact' || ! $object_id ) {
			return null;
		}

		$contact = get_contactdata( $object_id );

		return is_a_contact( $contact ) ? $contact : null;
	}

	/**
	 * Every value that says who a contact is, as search => replacement, for scrub_text(). A value shorter than
	 * MIN_SCRUB_LENGTH is left out, since it would redact ordinary words.
	 *
	 * @param Contact|int|null $contact
	 *
	 * @return string[]
	 */
	public static function scrub_pairs( $contact = null ): array {

		$contact = is_a_contact( $contact ) ? $contact : ( $contact ? get_contactdata( $contact ) : null );
		$pairs   = [];

		if ( is_a_contact( $contact ) ) {

			$values = [ $contact->get_full_name() ];

			foreach ( self::contact_fields() as $field ) {
				$values[] = $contact->$field;
			}

			$meta_keys = self::meta_keys();

			foreach ( Properties::instance()->get_fields() as $field ) {
				if ( ! empty( $field['pii'] ) && ! empty( $field['name'] ) ) {
					$meta_keys[] = $field['name'];
				}
			}

			foreach ( array_unique( $meta_keys ) as $key ) {
				$values[] = $contact->get_meta( $key );
			}

			foreach ( self::flatten_values( $values ) as $value ) {
				if ( mb_strlen( $value ) >= self::MIN_SCRUB_LENGTH ) {
					$pairs[ $value ] = self::REDACTED;
				}
			}
		}

		/**
		 * Filters what scrub_text() redacts. Add what is personal in your own data, like an add-on's field on the contact,
		 * the name of the company the contact works for, or the address of a deal.
		 *
		 * Keys are the text to find, matched whole and without regard to case, and values are what replaces it. An empty
		 * value is replaced with the redacted marker. Phone numbers are found however they are punctuated.
		 *
		 * @param string[]     $pairs   search => replacement
		 * @param Contact|null $contact the contact the text is about, if any
		 */
		$pairs = apply_filters( 'groundhogg/pii/scrub_text_pairs', $pairs, $contact );

		return is_array( $pairs ) ? $pairs : [];
	}

	/**
	 * Flatten values, which can be arrays or lists of addresses or numbers, to the strings in them
	 *
	 * @param mixed $values
	 *
	 * @return string[]
	 */
	protected static function flatten_values( $values ): array {

		$flat = [];

		foreach ( (array) $values as $value ) {

			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, self::flatten_values( $value ) );
			} else if ( is_scalar( $value ) ) {
				$flat = array_merge( $flat, preg_split( '/[\r\n,;]+/', (string) $value ) ?: [] );
			}
		}

		return array_values( array_unique( array_filter( array_map( 'trim', $flat ) ) ) );
	}

	/**
	 * The pattern that finds a value in free text
	 *
	 * @param string $search
	 *
	 * @return string
	 */
	protected static function scrub_pattern( string $search ): string {

		$digits = preg_replace( '/\D/', '', $search );

		// a phone number is found however it is punctuated
		if ( strlen( $digits ) >= 7 && preg_match( '/^[\d\s().+\-]+(x\d+)?$/i', $search ) ) {
			return '/(?<!\d)\+?' . implode( '[\s().\-]*', str_split( $digits ) ) . '(?!\d)/u';
		}

		return '/(?<![\p{L}\p{N}])' . preg_quote( $search, '/' ) . '(?![\p{L}\p{N}])/iu';
	}

	/**
	 * Redact what says who a contact is from free text, like a note or a task, for a restricted user.
	 *
	 * This is best effort. It finds what is on the contact's own record, as written, and what a filter adds, see
	 * `groundhogg/pii/scrub_text_pairs`. It can't find a nickname, or someone else's details written in the text.
	 *
	 * @param string           $text
	 * @param Contact|int|null $contact the contact the text is about
	 *
	 * @return string
	 */
	public static function scrub_text( string $text, $contact = null ): string {

		if ( $text === '' || ! self::is_restricted() ) {
			return $text;
		}

		$pairs = self::scrub_pairs( $contact );

		// longest first, so an address is redacted whole before the name inside it is
		uksort( $pairs, fn( $a, $b ) => strlen( (string) $b ) <=> strlen( (string) $a ) );

		foreach ( $pairs as $search => $replace ) {

			$search = trim( (string) $search );

			if ( $search === '' ) {
				continue;
			}

			$replace = (string) $replace === '' ? self::REDACTED : (string) $replace;
			$scrubbed = preg_replace_callback( self::scrub_pattern( $search ), fn() => $replace, $text );

			// null is invalid UTF-8, keep what there was
			$text = $scrubbed ?? $text;
		}

		return $text;
	}

	/**
	 * Whether a query, at any depth, names one of the columns, as a key or as a value. Used for tables that are not
	 * Contact_Query, where a where, select or orderby can name a column in any of those places.
	 *
	 * @param array    $query
	 * @param string[] $columns
	 *
	 * @return bool
	 */
	public static function query_mentions_columns( array $query, array $columns ): bool {

		foreach ( $query as $key => $value ) {

			if ( is_string( $key ) && in_array( $key, $columns, true ) ) {
				return true;
			}

			if ( is_string( $value ) && in_array( $value, $columns, true ) ) {
				return true;
			}

			if ( is_array( $value ) && self::query_mentions_columns( $value, $columns ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * A stored query for display. A restricted user can run a saved search that filters on PII, but can't read what it
	 * filters on, since the value is often the PII itself.
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public static function scrub_query( array $query ): array {

		if ( self::is_restricted() && self::query_touches_pii( $query ) ) {
			return [ 'redacted' => true ];
		}

		return $query;
	}
}
