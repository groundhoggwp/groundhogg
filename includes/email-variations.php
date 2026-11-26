<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Table_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email variations: other versions of an email, like the email in another language, that are sent instead of it to
 * the contacts they're for.
 *
 * A variation is an email of its own whose parent_id is the email it's a variation of. Flows, broadcasts and
 * notifications only ever send the parent. When it sends, Email::send() picks the variation for the contact, sends
 * that instead, and changes the event's email_id to it, so its opens and clicks are counted for it. Reports and
 * segments about an email include its variations through get_family_ids().
 *
 * What a variation is for is in its meta:
 * - variation_audience: [ locales => string[], include_filters => Filters DSL, exclude_filters => Filters DSL ]. A
 *   contact gets the variation when they speak one of the locales (if any are given) and match the filters (if any).
 *   A variation with no audience is never sent.
 * - variation_priority: which variation a contact gets when they match more than one, lowest first
 * - variation_locale: the language it's written in, which it's built in while sending
 *
 * Only ready variations are sent, so a draft can be worked on without anyone getting it.
 */
class Email_Variations {

	const AUDIENCE_KEY = 'variation_audience';
	const PRIORITY_KEY = 'variation_priority';
	const LOCALE_KEY   = 'variation_locale';

	/**
	 * @var array parent ID => int[] variation IDs
	 */
	protected static $variation_ids = [];

	public function __construct() {
		add_action( 'groundhogg/email/pre_delete', [ $this, 'delete_variations' ], 10, 3 );
	}

	/**
	 * Clear the cache of variation IDs
	 *
	 * @return void
	 */
	public static function flush() {
		self::$variation_ids = [];
	}

	/**
	 * Whether emails can have variations yet, see Emails::supports_variations()
	 *
	 * @return bool
	 */
	public static function supported(): bool {
		return get_db( 'emails' )->supports_variations();
	}

	/**
	 * The IDs of an email's variations, in the order they're checked: by priority, then oldest first
	 *
	 * @param int $email_id
	 *
	 * @return int[]
	 */
	public static function get_variation_ids( int $email_id ): array {

		if ( ! $email_id || ! self::supported() ) {
			return [];
		}

		if ( isset( self::$variation_ids[ $email_id ] ) ) {
			return self::$variation_ids[ $email_id ];
		}

		$query = new Table_Query( 'emails' );
		$query->setSelect( 'ID' )->where()->equals( 'parent_id', $email_id );

		$ids = wp_parse_id_list( wp_list_pluck( $query->get_results(), 'ID' ) );

		$priorities = [];

		foreach ( $ids as $id ) {
			$priorities[ $id ] = intval( get_db( 'emailmeta' )->get_meta( $id, self::PRIORITY_KEY, true ) );
		}

		usort( $ids, function ( $a, $b ) use ( $priorities ) {
			return $priorities[ $a ] <=> $priorities[ $b ] ?: $a <=> $b;
		} );

		self::$variation_ids[ $email_id ] = $ids;

		return $ids;
	}

	/**
	 * An email's variations
	 *
	 * @param Email $email
	 * @param bool  $ready_only only the ones that can be sent
	 *
	 * @return Email[]
	 */
	public static function get_variations( Email $email, bool $ready_only = false ): array {

		if ( $email->is_variation() ) {
			return [];
		}

		$variations = array_map( function ( $id ) {
			return new Email( $id );
		}, self::get_variation_ids( $email->get_id() ) );

		return array_values( array_filter( $variations, function ( Email $variation ) use ( $ready_only ) {
			return $variation->exists() && ( ! $ready_only || $variation->is_ready() );
		} ) );
	}

	/**
	 * The IDs of an email and its variations, for finding everything that was sent of an email. A variation's family is
	 * just itself.
	 *
	 * @param int|int[] $email_ids
	 *
	 * @return int[]
	 */
	public static function get_family_ids( $email_ids ): array {

		$ids = wp_parse_id_list( $email_ids );

		foreach ( $ids as $id ) {
			array_push( $ids, ...self::get_variation_ids( $id ) );
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Report rows grouped by email_id, with the rows of variations added into their email's, so a report shows one
	 * row for an email and everything sent of it
	 *
	 * @param object[] $rows        each with an email_id
	 * @param string[] $sum_columns the counts to add up, every other column is what the rows are grouped by
	 *
	 * @return object[]
	 */
	public static function merge_into_parents( array $rows, array $sum_columns ): array {

		if ( empty( $rows ) || ! self::supported() ) {
			return $rows;
		}

		$ids = wp_parse_id_list( wp_list_pluck( $rows, 'email_id' ) );

		$parents = [];

		if ( ! empty( $ids ) ) {
			$query = new Table_Query( 'emails' );
			$query->setSelect( 'ID', 'parent_id' )->where()->in( 'ID', $ids )->greaterThan( 'parent_id', 0 );

			foreach ( $query->get_results() as $result ) {
				$parents[ absint( $result->ID ) ] = absint( $result->parent_id );
			}
		}

		if ( empty( $parents ) ) {
			return $rows;
		}

		$merged = [];

		foreach ( $rows as $row ) {

			$row           = clone (object) $row;
			$email_id      = absint( $row->email_id );
			$row->email_id = $parents[ $email_id ] ?? $email_id;

			$group = wp_json_encode( array_diff_key( (array) $row, array_flip( $sum_columns ) ) );

			if ( ! isset( $merged[ $group ] ) ) {
				$merged[ $group ] = $row;
				continue;
			}

			foreach ( $sum_columns as $column ) {
				$merged[ $group ]->$column = absint( $merged[ $group ]->$column ) + absint( $row->$column ?? 0 );
			}
		}

		return array_values( $merged );
	}

	/**
	 * The variation's audience, with every key
	 *
	 * @param Email $variation
	 *
	 * @return array
	 */
	public static function get_audience( Email $variation ): array {
		return self::sanitize_audience( $variation->get_meta( self::AUDIENCE_KEY ) );
	}

	/**
	 * @param mixed $audience
	 *
	 * @return array locales, include_filters and exclude_filters
	 */
	public static function sanitize_audience( $audience ): array {

		$audience = is_array( $audience ) ? $audience : [];

		$locales = array_values( array_unique( array_filter( array_map( [ Multilingual::class, 'sanitize_locale' ], (array) ( $audience['locales'] ?? [] ) ) ) ) );

		$include = $audience['include_filters'] ?? [];
		$exclude = $audience['exclude_filters'] ?? [];

		return [
			'locales'         => $locales,
			'include_filters' => Filters::sanitize( is_array( $include ) ? $include : [] ),
			'exclude_filters' => Filters::sanitize( is_array( $exclude ) ? $exclude : [] ),
		];
	}

	/**
	 * Whether a variation's audience names anyone at all
	 *
	 * @param array $audience
	 *
	 * @return bool
	 */
	public static function audience_is_empty( array $audience ): bool {
		return empty( $audience['locales'] ) && empty( $audience['include_filters'] ) && empty( $audience['exclude_filters'] );
	}

	/**
	 * Whether the contact is in the variation's audience
	 *
	 * @param Email   $variation
	 * @param Contact $contact
	 *
	 * @return bool
	 */
	public static function matches( Email $variation, Contact $contact ): bool {

		$audience = self::get_audience( $variation );
		$matches  = ! self::audience_is_empty( $audience );

		// The language is checked first, it needs no query
		if ( $matches && ! empty( $audience['locales'] ) ) {
			$matches = in_array( $contact->get_locale(), $audience['locales'], true );
		}

		if ( $matches && ( ! empty( $audience['include_filters'] ) || ! empty( $audience['exclude_filters'] ) ) ) {

			$query = new Contact_Query( [
				'filters'         => $audience['include_filters'],
				'exclude_filters' => $audience['exclude_filters'],
				'include'         => $contact->get_id(),
			] );

			try {
				$matches = $query->count() > 0;
			} catch ( \Throwable $e ) {
				// A broken filter shouldn't stop the email from going out, the contact gets the email itself
				$matches = false;
			}
		}

		/**
		 * Filters whether the contact gets this variation
		 *
		 * @param bool    $matches
		 * @param Email   $variation
		 * @param Contact $contact
		 */
		return (bool) apply_filters( 'groundhogg/email/variation/matches', $matches, $variation, $contact );
	}

	/**
	 * Which variation of the email the contact gets, or false for the email itself
	 *
	 * @param Email         $email
	 * @param Contact|false $contact
	 *
	 * @return Email|false
	 */
	public static function resolve( Email $email, $contact ) {

		if ( ! is_a_contact( $contact ) || $email->is_variation() || ! self::supported() ) {
			return false;
		}

		$chosen = false;

		foreach ( self::get_variations( $email, true ) as $variation ) {
			if ( self::matches( $variation, $contact ) ) {
				$chosen = $variation;
				break;
			}
		}

		/**
		 * Filters which variation of an email a contact gets
		 *
		 * @param Email|false $chosen the variation, or false for the email itself
		 * @param Email       $email
		 * @param Contact     $contact
		 */
		$chosen = apply_filters( 'groundhogg/email/variation/resolve', $chosen, $email, $contact );

		if ( ! is_a( $chosen, Email::class ) || ! $chosen->exists() || $chosen->get_parent_id() !== $email->get_id() ) {
			return false;
		}

		return $chosen;
	}

	/**
	 * Count what's sent for the variation instead of the email it's a variation of
	 *
	 * @param Event|int|null $event
	 * @param Email          $variation
	 *
	 * @return void
	 */
	public static function attribute_event( $event, Email $variation ) {

		if ( is_numeric( $event ) ) {
			$event = get_queued_event_by_id( absint( $event ) );
		}

		if ( ! is_a( $event, Event::class ) || ! $event->exists() || $event->get_email_id() === $variation->get_id() ) {
			return;
		}

		$event->update( [ 'email_id' => $variation->get_id() ] );
	}

	/**
	 * Create a variation of an email
	 *
	 * @param Email $email the email it's a variation of
	 * @param array $args  title, audience, priority, locale, status, and copy: true to start from a copy of the email
	 *
	 * @return Email|\WP_Error
	 */
	public static function create( Email $email, array $args ) {

		if ( ! self::supported() ) {
			return new \WP_Error( 'variations_not_supported', __( 'Run the pending database updates before adding variations.', 'groundhogg' ) );
		}

		if ( ! $email->exists() ) {
			return new \WP_Error( 'email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( $email->is_variation() ) {
			return new \WP_Error( 'is_variation', __( 'A variation can\'t have variations of its own. Add it to the email it\'s a variation of.', 'groundhogg' ) );
		}

		if ( $email->is_template() || $email->get_message_type() === 'global_block' ) {
			return new \WP_Error( 'not_sendable', __( 'Templates and global blocks can\'t have variations.', 'groundhogg' ) );
		}

		$audience = self::sanitize_audience( $args['audience'] ?? [] );

		if ( self::audience_is_empty( $audience ) ) {
			return new \WP_Error( 'no_audience', __( 'Say who gets the variation: languages, filters or both.', 'groundhogg' ) );
		}

		$locale = Multilingual::sanitize_locale( $args['locale'] ?? '' );

		// Written in the one language it's for
		if ( ! $locale && count( $audience['locales'] ) === 1 ) {
			$locale = $audience['locales'][0];
		}

		$data = [
			'parent_id' => $email->get_id(),
			'title'     => sanitize_text_field( $args['title'] ?? '' ) ?: $email->get_title() . ( $locale ? ' (' . $locale . ')' : '' ),
			'status'    => one_of( $args['status'] ?? 'draft', [ 'draft', 'ready' ] ),
		];

		$meta = [
			self::AUDIENCE_KEY => $audience,
			self::PRIORITY_KEY => intval( $args['priority'] ?? 0 ),
			self::LOCALE_KEY   => $locale,
		];

		if ( ! empty( $args['copy'] ) ) {
			// Copies the content, settings and campaigns, keeping block ids so the variation lines up with the email
			$variation = $email->duplicate( $data, $meta, false );
		} else {
			$variation = new Email();
			$variation->create( array_merge( [
				'subject'      => $email->get_subject_line(),
				'message_type' => $email->get_message_type(),
				'from_user'    => $email->get_from_user_id(),
				'from_profile' => $email->from_profile,
			], $data ) );
			$variation->update_meta( $meta );
		}

		if ( ! $variation->exists() ) {
			return new \WP_Error( 'not_created', __( 'The variation could not be created.', 'groundhogg' ) );
		}

		self::flush();

		return $variation;
	}

	/**
	 * Change what a variation is for
	 *
	 * @param Email $variation
	 * @param array $args audience, priority, locale
	 *
	 * @return true|\WP_Error
	 */
	public static function update( Email $variation, array $args ) {

		if ( ! $variation->is_variation() ) {
			return new \WP_Error( 'not_a_variation', __( 'This email is not a variation.', 'groundhogg' ) );
		}

		if ( array_key_exists( 'audience', $args ) ) {

			$audience = self::sanitize_audience( $args['audience'] );

			if ( self::audience_is_empty( $audience ) ) {
				return new \WP_Error( 'no_audience', __( 'Say who gets the variation: languages, filters or both.', 'groundhogg' ) );
			}

			$variation->update_meta( self::AUDIENCE_KEY, $audience );
		}

		if ( array_key_exists( 'priority', $args ) ) {
			$variation->update_meta( self::PRIORITY_KEY, intval( $args['priority'] ) );
		}

		if ( array_key_exists( 'locale', $args ) ) {
			$variation->update_meta( self::LOCALE_KEY, Multilingual::sanitize_locale( $args['locale'] ) );
		}

		self::flush();

		return true;
	}

	/**
	 * Copy an email's variations to a copy of it
	 *
	 * @param Email $email the original
	 * @param Email $copy
	 *
	 * @return void
	 */
	public static function copy_variations( Email $email, Email $copy ) {
		foreach ( self::get_variations( $email ) as $variation ) {
			$variation->duplicate( [
				'parent_id' => $copy->get_id(),
				'title'     => $variation->get_title(),
				'status'    => $variation->get_status(),
			], [], false );
		}

		self::flush();
	}

	/**
	 * The variations of an email, for exporting with it
	 *
	 * @param Email $email
	 *
	 * @return array[]
	 */
	public static function export( Email $email ): array {
		return array_map( function ( Email $variation ) {
			return $variation->export();
		}, self::get_variations( $email ) );
	}

	/**
	 * Add the variations from an export to an imported email
	 *
	 * @param Email $email
	 * @param array $variations what export() gave
	 *
	 * @return void
	 */
	public static function import( Email $email, array $variations ) {

		if ( ! self::supported() ) {
			return;
		}

		foreach ( $variations as $raw ) {

			$raw  = json_decode( wp_json_encode( $raw ), true );
			$data = wp_array_slice_assoc( $raw['data'] ?? [], [ 'title', 'subject', 'pre_header', 'content', 'status' ] );
			$meta = $raw['meta'] ?? [];

			$variation = new Email();
			$variation->create( array_merge( $data, [
				'parent_id'    => $email->get_id(),
				'message_type' => $email->get_message_type(),
				'status'       => one_of( $data['status'] ?? 'draft', [ 'draft', 'ready' ] ),
			] ) );

			$variation->update_meta( is_array( $meta ) ? $meta : [] );
		}

		self::flush();
	}

	/**
	 * Deleting an email deletes its variations
	 *
	 * @param int   $id
	 * @param array $data
	 * @param Email $email
	 *
	 * @return void
	 */
	public function delete_variations( $id, $data = [], $email = null ) {

		foreach ( self::get_variation_ids( absint( $id ) ) as $variation_id ) {
			( new Email( $variation_id ) )->delete();
		}

		self::flush();
	}

	/**
	 * What the email editor shows about an email's variations: the email they're variations of, each one with how much
	 * of it is left to translate, and the site's languages there's no variation for yet
	 *
	 * @param Email $email the email, or one of its variations
	 *
	 * @return array
	 */
	public static function editor_data( Email $email ): array {

		$original = $email->is_variation() ? $email->get_parent() : $email;

		if ( ! $original || ! $original->exists() ) {
			$original = $email;
		}

		$can_have = self::supported() && ! $original->is_template() && $original->get_message_type() !== 'global_block';

		$variations = array_map( function ( Email $variation ) {
			$describe = self::describe( $variation );

			return array_merge( $describe, [
				'languages'    => array_map( [ Multilingual::class, 'get_locale_name' ], $describe['audience']['locales'] ),
				'subject'      => $variation->get_subject_line(),
				'edit_url'     => $variation->admin_link(),
				'to_translate' => Email_Strings::supports( $variation ) ? Email_Strings::count_to_translate( $variation ) : null,
			] );
		}, $can_have ? self::get_variations( $original ) : [] );

		$taken = [ Multilingual::get_default_locale() ];

		foreach ( $variations as $variation ) {
			array_push( $taken, ...$variation['audience']['locales'] );
		}

		$languages = array_values( array_filter( Multilingual::get_site_languages(), function ( $language ) use ( $taken ) {
			return ! in_array( $language['locale'], $taken, true );
		} ) );

		return [
			'supported'    => $can_have,
			'email_id'     => $email->get_id(),
			'is_variation' => $email->is_variation(),
			'original'     => [
				'id'       => $original->get_id(),
				'title'    => $original->get_title(),
				'edit_url' => $original->admin_link(),
			],
			'variations'   => $variations,
			'languages'    => array_map( function ( $language ) {
				return [ 'locale' => $language['locale'], 'name' => $language['name'] ];
			}, $languages ),
		];
	}

	/**
	 * A variation, for the abilities and the API
	 *
	 * @param Email $variation
	 *
	 * @return array
	 */
	public static function describe( Email $variation ): array {
		return [
			'id'       => $variation->get_id(),
			'title'    => $variation->get_title(),
			'status'   => $variation->get_status(),
			'audience' => self::get_audience( $variation ),
			'priority' => intval( $variation->get_meta( self::PRIORITY_KEY ) ),
			'locale'   => (string) $variation->get_meta( self::LOCALE_KEY ),
		];
	}
}
