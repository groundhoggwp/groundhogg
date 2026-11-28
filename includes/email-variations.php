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
 * A variation can instead be a test version, for A/B testing (variation_kind "test"). It has no audience: the contacts
 * no audience variation is for are split between the email and its test versions by variation_weight, a percentage
 * each. A contact always lands in the same one (see bucket()), so they don't get both. Once a winner is declared (meta
 * variation_winner on the email, its own ID or a test version's), all of them get the winner. report() compares them.
 *
 * Only ready variations are sent, so a draft can be worked on without anyone getting it.
 */
class Email_Variations {

	const AUDIENCE_KEY = 'variation_audience';
	const PRIORITY_KEY = 'variation_priority';
	const LOCALE_KEY   = 'variation_locale';
	const KIND_KEY     = 'variation_kind';
	const WEIGHT_KEY   = 'variation_weight';

	/**
	 * On the email: the version that won its test
	 */
	const WINNER_KEY = 'variation_winner';

	/**
	 * On the email: when its test started, which is when the report starts counting
	 */
	const TEST_STARTED_KEY = 'variation_test_started';

	/**
	 * On the email: a broadcast that tests on a sample first, see start_broadcast_test()
	 */
	const BROADCAST_TEST_KEY = 'variation_broadcast_test';

	const FINISH_BROADCAST_TEST_HOOK = 'groundhogg/email_variations/finish_broadcast_test';

	/**
	 * The email's meta about its own A/B test, which a copy or an export doesn't take with it
	 */
	const TEST_STATE_KEYS = [ self::WINNER_KEY, self::TEST_STARTED_KEY, self::BROADCAST_TEST_KEY ];

	/**
	 * How long past its first expected finish a broadcast test waits for the last of its sample, like a paused event,
	 * before sending the winner anyway
	 */
	const BROADCAST_TEST_MAX_DELAY = WEEK_IN_SECONDS;

	/**
	 * What a test is judged on
	 */
	const METRICS = [ 'clicks', 'opens' ];

	/**
	 * @var array parent ID => int[] variation IDs
	 */
	protected static $variation_ids = [];

	public function __construct() {
		add_action( 'groundhogg/email/pre_delete', [ $this, 'delete_variations' ], 10, 3 );
		add_action( self::FINISH_BROADCAST_TEST_HOOK, [ __CLASS__, 'finish_broadcast_test' ] );
	}

	/**
	 * Whether a variation is a test version rather than one for an audience
	 *
	 * @param Email $variation
	 *
	 * @return bool
	 */
	public static function is_test( Email $variation ): bool {
		return $variation->get_meta( self::KIND_KEY ) === 'test';
	}

	/**
	 * A test version's share of the contacts in the test, in percent
	 *
	 * @param Email $variation
	 *
	 * @return int
	 */
	public static function get_weight( Email $variation ): int {
		return max( 0, min( 100, intval( $variation->get_meta( self::WEIGHT_KEY ) ) ) );
	}

	/**
	 * The email's test versions
	 *
	 * @param Email $email
	 * @param bool  $ready_only
	 *
	 * @return Email[]
	 */
	public static function get_tests( Email $email, bool $ready_only = false ): array {
		return array_values( array_filter( self::get_variations( $email, $ready_only ), [ __CLASS__, 'is_test' ] ) );
	}

	/**
	 * The version that won the email's test: the email's own ID, a test version's, or 0 while it's still testing
	 *
	 * @param Email $email
	 *
	 * @return int
	 */
	public static function get_winner_id( Email $email ): int {
		return absint( $email->get_meta( self::WINNER_KEY ) );
	}

	/**
	 * Which of 100 buckets a contact is in for an email's test. The same contact is always in the same bucket for the
	 * same email, so a test doesn't send them a different version each time, and nothing has to be stored.
	 *
	 * @param Contact $contact
	 * @param Email   $email
	 *
	 * @return int 0 to 99
	 */
	public static function bucket( Contact $contact, Email $email ): int {
		return abs( crc32( $contact->get_id() . ':' . $email->get_id() ) ) % 100;
	}

	/**
	 * Which test version a contact gets, or false for the email itself
	 *
	 * @param Email   $email
	 * @param Contact $contact
	 *
	 * @return Email|false
	 */
	protected static function resolve_test( Email $email, Contact $contact ) {

		$tests = self::get_tests( $email, true );

		if ( empty( $tests ) ) {
			return false;
		}

		$winner = self::get_winner_id( $email );

		if ( $winner ) {
			foreach ( $tests as $test ) {
				if ( $test->get_id() === $winner ) {
					return $test;
				}
			}

			// the email won, or the winner isn't ready any more
			return false;
		}

		$bucket = self::bucket( $contact, $email );
		$upto   = 0;

		foreach ( $tests as $test ) {

			$upto += self::get_weight( $test );

			if ( $bucket < $upto ) {
				return $test;
			}
		}

		return false;
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

			// which version a contact gets doesn't depend on who's sending it, so not limited to the current user's
			// contacts like a root query is for a sales rep
			$query = new Contact_Query( [
				'filters'         => $audience['include_filters'],
				'exclude_filters' => $audience['exclude_filters'],
				'include'         => $contact->get_id(),
			], Contact_Query::IS_SUB_QUERY );

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

		// a variation for their audience, like their language, first
		foreach ( self::get_variations( $email, true ) as $variation ) {
			if ( ! self::is_test( $variation ) && self::matches( $variation, $contact ) ) {
				$chosen = $variation;
				break;
			}
		}

		// then everyone else is in the email's test, if it has one
		if ( ! $chosen ) {
			$chosen = self::resolve_test( $email, $contact );
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

		$is_test  = ( $args['kind'] ?? 'audience' ) === 'test';
		$audience = self::sanitize_audience( $is_test ? [] : ( $args['audience'] ?? [] ) );

		if ( ! $is_test && self::audience_is_empty( $audience ) ) {
			return new \WP_Error( 'no_audience', __( 'Say who gets the variation: languages, filters or both.', 'groundhogg' ) );
		}

		$locale = Multilingual::sanitize_locale( $args['locale'] ?? '' );

		// Written in the one language it's for
		if ( ! $locale && count( $audience['locales'] ) === 1 ) {
			$locale = $audience['locales'][0];
		}

		if ( $is_test ) {
			// B, C, D... after the email, which is A
			$title = $email->get_title() . ' (' . chr( 66 + min( 24, count( self::get_tests( $email ) ) ) ) . ')';
		} else {
			$title = $email->get_title() . ( $locale ? ' (' . $locale . ')' : '' );
		}

		$data = [
			'parent_id' => $email->get_id(),
			'title'     => sanitize_text_field( $args['title'] ?? '' ) ?: $title,
			'status'    => one_of( $args['status'] ?? 'draft', [ 'draft', 'ready' ] ),
		];

		// a given share has to fit beside the other test versions', like update() checks
		if ( $is_test && isset( $args['weight'] ) ) {

			$taken = array_sum( array_map( [ __CLASS__, 'get_weight' ], self::get_tests( $email ) ) );

			if ( $taken + max( 1, min( 99, intval( $args['weight'] ) ) ) > 100 ) {
				/* translators: %d: the most the weight can be */
				return new \WP_Error( 'weight_too_high', sprintf( __( 'The test versions\' weights add up to more than 100. This one can be at most %d.', 'groundhogg' ), max( 0, 100 - $taken ) ) );
			}
		}

		$meta = [
			self::KIND_KEY     => $is_test ? 'test' : 'audience',
			self::AUDIENCE_KEY => $audience,
			self::PRIORITY_KEY => intval( $args['priority'] ?? 0 ),
			self::LOCALE_KEY   => $locale,
			self::WEIGHT_KEY   => $is_test && isset( $args['weight'] ) ? max( 1, min( 99, intval( $args['weight'] ) ) ) : 0,
			// what's about the email's own test isn't copied to its variation
			self::WINNER_KEY         => '',
			self::TEST_STARTED_KEY   => '',
			self::BROADCAST_TEST_KEY => '',
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

		if ( $is_test ) {

			// an even split, unless it was given its share
			if ( ! isset( $args['weight'] ) ) {
				self::split_evenly( $email );
			}

			// a new version is a new test
			self::restart_test( $email );
		}

		// with what was just written to it
		return self::fresh( $variation );
	}

	/**
	 * Give the email and each of its test versions the same share of the test
	 *
	 * @param Email $email
	 *
	 * @return void
	 */
	public static function split_evenly( Email $email ) {

		$tests = self::get_tests( $email );
		$share = intdiv( 100, count( $tests ) + 1 );

		foreach ( $tests as $test ) {
			$test->update_meta( self::WEIGHT_KEY, $share );
		}
	}

	/**
	 * Start the email's test over: no winner, and the report counts from now
	 *
	 * @param Email $email
	 *
	 * @return void
	 */
	public static function restart_test( Email $email ) {
		$email = self::fresh( $email );
		$email->delete_meta( self::WINNER_KEY );
		$email->update_meta( self::TEST_STARTED_KEY, time() );
	}

	/**
	 * The email as it is now. Base_Object_With_Meta::update_meta() only writes when the stored value is still the one
	 * the object read, so meta written through another object of the same email would otherwise be silently kept.
	 *
	 * @param Email $email
	 *
	 * @return Email
	 */
	protected static function fresh( Email $email ): Email {
		return new Email( $email->get_id() );
	}

	/**
	 * End the email's test: from now on everyone in it gets the winner
	 *
	 * @param Email $email
	 * @param int   $winner_id the email's own ID, or one of its test versions'
	 *
	 * @return true|\WP_Error
	 */
	public static function declare_winner( Email $email, int $winner_id ) {

		if ( $email->is_variation() ) {
			return new \WP_Error( 'is_variation', __( 'Declare the winner on the email that has the test, not on one of its versions.', 'groundhogg' ) );
		}

		$candidates = array_merge( [ $email->get_id() ], wp_list_pluck( self::get_tests( $email, true ), 'ID' ) );

		if ( ! in_array( $winner_id, array_map( 'absint', $candidates ), true ) ) {
			return new \WP_Error( 'not_in_test', __( 'The winner has to be the email or one of its ready test versions.', 'groundhogg' ) );
		}

		self::fresh( $email )->update_meta( self::WINNER_KEY, $winner_id );

		return true;
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

		$variation = self::fresh( $variation );

		if ( self::is_test( $variation ) ) {

			if ( array_key_exists( 'audience', $args ) ) {
				return new \WP_Error( 'is_test', __( 'A test version has no audience, it gets a share of the contacts the email is for. Change its weight instead.', 'groundhogg' ) );
			}

			if ( array_key_exists( 'weight', $args ) ) {

				$weight = max( 0, min( 100, intval( $args['weight'] ) ) );
				$others = 0;

				foreach ( self::get_tests( $variation->get_parent() ) as $test ) {
					if ( $test->get_id() !== $variation->get_id() ) {
						$others += self::get_weight( $test );
					}
				}

				if ( $others + $weight > 100 ) {
					/* translators: %d: the most the weight can be */
					return new \WP_Error( 'weight_too_high', sprintf( __( 'The test versions\' weights add up to more than 100. This one can be at most %d.', 'groundhogg' ), 100 - $others ) );
				}

				$variation->update_meta( self::WEIGHT_KEY, $weight );
			}
		} else if ( array_key_exists( 'weight', $args ) ) {
			return new \WP_Error( 'not_a_test', __( 'Only a test version has a weight.', 'groundhogg' ) );
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
			// only the email itself gets more, from its own editor
			'languages'    => $can_have && ! $email->is_variation() ? self::languages_to_add( $variations ) : [],
			// the A/B test, when it has test versions
			'test'         => $can_have && ! empty( self::get_tests( $original ) ) ? self::report( $original ) : null,
		];
	}

	/**
	 * The site's languages a variation can be added for, with how many contacts speak each. Leaves out the default
	 * language and the ones there's a variation for already.
	 *
	 * @param array $variations from editor_data()
	 *
	 * @return array[] locale, name, contacts
	 */
	protected static function languages_to_add( array $variations ): array {

		$taken = [ Multilingual::get_default_locale() ];

		foreach ( $variations as $variation ) {
			array_push( $taken, ...$variation['audience']['locales'] );
		}

		$languages = array_filter( Multilingual::get_site_languages(), function ( $language ) use ( $taken ) {
			return ! in_array( $language['locale'], $taken, true );
		} );

		// counting is a query, so only when there's a language to count for
		$counts = empty( $languages ) ? [] : Multilingual::count_contacts_by_locale();

		return array_values( array_map( function ( $language ) use ( $counts ) {
			return [
				'locale'   => $language['locale'],
				'name'     => $language['name'],
				'contacts' => $counts[ $language['locale'] ] ?? 0,
			];
		}, $languages ) );
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
			'kind'     => self::is_test( $variation ) ? 'test' : 'audience',
			'audience' => self::get_audience( $variation ),
			'priority' => intval( $variation->get_meta( self::PRIORITY_KEY ) ),
			'locale'   => (string) $variation->get_meta( self::LOCALE_KEY ),
			'weight'   => self::is_test( $variation ) ? self::get_weight( $variation ) : 0,
		];
	}

	/**
	 * Broadcast an email with test versions to a random sample of the audience first, and the winner to everyone else
	 * once the test has run: the sample is sent now (or when scheduled), and finish_broadcast_test() runs after the
	 * wait to declare the version that did best and broadcast the email to the rest, who then all get the winner.
	 *
	 * @param Email $email
	 * @param array $query    the audience, Contact_Query vars like Broadcast::schedule() takes
	 * @param array $schedule the rest of Broadcast::schedule()'s args for the sample, like send_now or date and time
	 * @param array $test     sample_percent (5 to 50), wait_hours (1 to 168), metric (clicks or opens)
	 *
	 * @throws \Exception what Broadcast::schedule() throws
	 *
	 * @return Broadcast|\WP_Error the sample's broadcast
	 */
	public static function start_broadcast_test( Email $email, array $query, array $schedule, array $test ) {

		if ( empty( self::get_tests( $email, true ) ) ) {
			return new \WP_Error( 'no_test_versions', __( 'The email has no ready test versions to test. Add one first.', 'groundhogg' ) );
		}

		$current = self::fresh( $email )->get_meta( self::BROADCAST_TEST_KEY );

		if ( is_array( $current ) && ( $current['status'] ?? '' ) === 'testing' ) {
			return new \WP_Error( 'test_running', __( 'A broadcast is already testing this email. Wait for it to send the winner.', 'groundhogg' ) );
		}

		$percent = max( 5, min( 50, absint( $test['sample_percent'] ?? 20 ) ) );
		$hours   = max( 1, min( 168, absint( $test['wait_hours'] ?? 4 ) ) );
		$metric  = one_of( $test['metric'] ?? 'clicks', self::METRICS );

		// the contacts the broadcast would go to, in a random order
		$sample_query = $query;
		unset( $sample_query['limit'], $sample_query['number'], $sample_query['offset'] );

		if ( ! $email->is_transactional() ) {
			$sample_query['marketable'] = true;
		}

		// a broadcast goes to the whole audience whoever schedules it, so not limited to their own contacts
		$contact_query = new Contact_Query( [], Contact_Query::IS_SUB_QUERY );
		$contact_query->setSelect( 'ID' );

		// like Broadcast::schedule(), refuse rather than send to the wrong contacts when part of the audience, like an
		// exclusion from a deactivated add-on, can't be applied and would match nobody
		$errors = [];
		$track  = function ( $e ) use ( &$errors ) {
			$errors[] = $e->getMessage();
		};

		add_action( 'groundhogg/query/filter_error', $track );
		add_action( 'groundhogg/contact_query/error', $track );

		try {
			$ids = wp_parse_id_list( wp_list_pluck( (array) $contact_query->query( $sample_query ), 'ID' ) );
		} finally {
			remove_action( 'groundhogg/query/filter_error', $track );
			remove_action( 'groundhogg/contact_query/error', $track );
		}

		if ( ! empty( $errors ) ) {
			/* translators: %s: what went wrong with the filters */
			return new \WP_Error( 'query_errors', sprintf( __( 'Some of the filters could not be applied, so nothing was sent: %s', 'groundhogg' ), implode( '; ', array_unique( $errors ) ) ) );
		}

		if ( count( $ids ) < 2 ) {
			return new \WP_Error( 'too_few_contacts', __( 'A test needs an audience of at least two contacts.', 'groundhogg' ) );
		}

		shuffle( $ids );

		$size   = max( 1, min( count( $ids ) - 1, (int) round( count( $ids ) * $percent / 100 ) ) );
		$sample = array_slice( $ids, 0, $size );

		// a new test, counted from now. If the sample can't be scheduled, the test it replaced, and a winner flows are
		// sending, are put back.
		$before = self::fresh( $email );
		$winner = $before->get_meta( self::WINNER_KEY );
		$start  = $before->get_meta( self::TEST_STARTED_KEY );

		self::restart_test( $email );

		try {
			$broadcast = Broadcast::schedule( array_merge( $schedule, [
				'object_id'    => $email->get_id(),
				'object_type'  => 'email',
				'segment_type' => 'fixed',
				'query'        => [ 'include' => $sample ],
			] ) );
		} catch ( \Throwable $e ) {
			$after = self::fresh( $email );
			$after->update_meta( self::TEST_STARTED_KEY, $start );

			if ( $winner ) {
				$after->update_meta( self::WINNER_KEY, $winner );
			}

			throw $e;
		}

		// the wait starts once the last of the sample is sent, so a first guess at when that is: batches and local
		// time spread it out. finish_broadcast_test() checks, and waits longer if it isn't.
		$finish = max( time(), $broadcast->get_send_time() ) + self::sample_spread( $size, $schedule ) + $hours * HOUR_IN_SECONDS;

		// what the broadcast to the rest is scheduled with, everything but when
		$rest = array_diff_key( $schedule, array_flip( [ 'date', 'time', 'dates', 'send_now', 'is_recurring' ] ) );

		// in the contact's local time, the rest go out at the same time of day as the sample did, in the site's timezone
		if ( ! empty( $schedule['send_in_local_time'] ) && empty( $schedule['send_now'] ) ) {
			$rest['time_of_day'] = wp_date( 'H:i:s', $broadcast->get_send_time() );
		}

		self::fresh( $email )->update_meta( self::BROADCAST_TEST_KEY, [
			'status'       => 'testing',
			'broadcast_id' => $broadcast->get_id(),
			'query'        => $query,
			'schedule'     => $rest,
			'metric'       => $metric,
			'wait_hours'   => $hours,
			'finish_at'    => $finish,
			// the first guess, to stop waiting at some point, see BROADCAST_TEST_MAX_DELAY
			'expected_at'  => $finish,
			'sample'       => $size,
			'rest'         => count( $ids ) - $size,
		] );

		wp_schedule_single_event( $finish, self::FINISH_BROADCAST_TEST_HOOK, [ $email->get_id() ] );

		return $broadcast;
	}

	/**
	 * After a broadcast test's wait: declare the version that did best on the sample the winner, and broadcast the
	 * email to the rest of the audience, who all get the winner
	 *
	 * @param int $email_id
	 *
	 * @return void
	 */
	public static function finish_broadcast_test( $email_id ) {

		$email  = new Email( absint( $email_id ) );
		$record = $email->exists() ? $email->get_meta( self::BROADCAST_TEST_KEY ) : null;

		if ( ! is_array( $record ) || ( $record['status'] ?? '' ) !== 'testing' ) {
			return;
		}

		$sample = new Broadcast( absint( $record['broadcast_id'] ) );

		// the sample was stopped, so the rest isn't sent either
		if ( ! $sample->exists() || $sample->is_cancelled() ) {
			$record['status'] = 'cancelled';
			$email->update_meta( self::BROADCAST_TEST_KEY, $record );

			return;
		}

		// every contact in the sample gets the whole wait, however the sample is batched or timed. But not forever: some
		// of it may never go out, like a paused event, and the rest shouldn't wait on it for good.
		$next     = self::when_sample_is_done( $sample, absint( $record['wait_hours'] ?? 4 ) * HOUR_IN_SECONDS );
		$give_up  = absint( $record['expected_at'] ?? $record['finish_at'] ?? 0 ) + self::BROADCAST_TEST_MAX_DELAY;

		if ( $next > time() && time() < $give_up ) {
			$record['finish_at'] = $next;
			$email->update_meta( self::BROADCAST_TEST_KEY, $record );
			wp_schedule_single_event( $next, self::FINISH_BROADCAST_TEST_HOOK, [ $email->get_id() ] );

			return;
		}

		// so a second run can't send it twice
		$record['status'] = 'finishing';
		$email->update_meta( self::BROADCAST_TEST_KEY, $record );

		$report = self::report( $email, [
			'metric'       => $record['metric'] ?? 'clicks',
			'since'        => 0,
			'broadcast_id' => $record['broadcast_id'],
		] );

		// the leader, sure of it or not, is better than a coin toss for the rest
		$winner = $report['leader'] ?: $email->get_id();

		if ( is_wp_error( self::declare_winner( $email, $winner ) ) ) {
			$winner = $email->get_id();
			self::declare_winner( $email, $winner );
		}

		$query = (array) $record['query'];

		$query['exclude'] = array_values( array_unique( array_merge(
			wp_parse_id_list( $query['exclude'] ?? [] ),
			wp_parse_id_list( $sample->exists() ? ( $sample->get_query()['include'] ?? [] ) : [] )
		) ) );

		$schedule = (array) $record['schedule'];
		$when     = [ 'send_now' => true ];

		// in the contact's local time: the next time it's that time of day, each contact at their own
		if ( ! empty( $schedule['time_of_day'] ) ) {

			$at = new \DateTime( 'today ' . $schedule['time_of_day'], wp_timezone() );

			if ( $at->getTimestamp() <= time() ) {
				$at->modify( '+1 day' );
			}

			$when = [
				'send_now' => false,
				'date'     => $at->format( 'Y-m-d' ),
				'time'     => $at->format( 'H:i:s' ),
			];
		}

		unset( $schedule['time_of_day'] );

		try {
			$rest = Broadcast::schedule( array_merge( $schedule, $when, [
				'object_id'    => $email->get_id(),
				'object_type'  => 'email',
				'segment_type' => 'fixed',
				'query'        => $query,
			] ) );

			$record['status']            = 'done';
			$record['winner']            = $winner;
			$record['confidence']        = $report['confidence'];
			$record['rest_broadcast_id'] = $rest->get_id();
		} catch ( \Throwable $e ) {
			$record['status'] = 'failed';
			$record['error']  = $e->getMessage();
			$record['winner'] = $winner;
		}

		$email->update_meta( self::BROADCAST_TEST_KEY, $record );
	}

	/**
	 * About how long after it starts the last of a sample goes out: batches go out one interval apart, and in the
	 * contact's local time it can take up to a day for the send time to come round everywhere
	 *
	 * @param int   $size     how many contacts are in the sample
	 * @param array $schedule Broadcast::schedule() args
	 *
	 * @return int seconds
	 */
	protected static function sample_spread( int $size, array $schedule ): int {

		$spread = 0;

		if ( ! empty( $schedule['send_in_local_time'] ) && empty( $schedule['send_now'] ) ) {
			$spread += DAY_IN_SECONDS;
		}

		if ( ! empty( $schedule['batching'] ) ) {

			$units = [
				'minutes' => MINUTE_IN_SECONDS,
				'hours'   => HOUR_IN_SECONDS,
				'days'    => DAY_IN_SECONDS,
			];

			$batches  = (int) ceil( $size / max( 1, absint( $schedule['batch_amount'] ?? 100 ) ) );
			$interval = max( 1, absint( $schedule['batch_interval_length'] ?? 10 ) ) * ( $units[ $schedule['batch_interval'] ?? 'minutes' ] ?? MINUTE_IN_SECONDS );

			$spread += max( 0, $batches - 1 ) * $interval;
		}

		return $spread;
	}

	/**
	 * When a test's sample has had its wait: a wait after the last of it was sent. Until then, when to look again.
	 *
	 * @param Broadcast $sample
	 * @param int       $wait in seconds
	 *
	 * @return int a timestamp, in the past once the wait is over
	 */
	protected static function when_sample_is_done( Broadcast $sample, int $wait ): int {

		// still putting the sample in the queue, a big one is done a batch at a time in the background
		if ( $sample->is_schedulable() ) {
			return time() + 15 * MINUTE_IN_SECONDS;
		}

		$last_queued = new Table_Query( 'event_queue' );
		$last_queued->add_safe_column( 'MAX(time)' );
		$last_queued->setSelect( [ 'MAX(time)', 'last' ] )
		            ->where()
		            ->equals( 'step_id', $sample->get_id() )
		            ->equals( 'funnel_id', Broadcast::FUNNEL_ID )
		            ->equals( 'event_type', Event::BROADCAST )
		            // anything still to go out, whatever it's waiting on
		            ->notIn( 'status', [ Event::COMPLETE, Event::CANCELLED, Event::SKIPPED, Event::FAILED ] );

		$last_queued = absint( $last_queued->get_var() );

		// some of it hasn't gone out yet: a wait after the last of it is due, or after now if the queue is behind
		if ( $last_queued ) {
			return max( $last_queued, time() ) + $wait;
		}

		$last_sent = new Table_Query( 'events' );
		$last_sent->add_safe_column( 'MAX(time)' );
		$last_sent->setSelect( [ 'MAX(time)', 'last' ] )
		          ->where()
		          ->equals( 'step_id', $sample->get_id() )
		          ->equals( 'funnel_id', Broadcast::FUNNEL_ID )
		          ->equals( 'event_type', Event::BROADCAST );

		return absint( $last_sent->get_var() ) + $wait;
	}

	/**
	 * The standard normal distribution's cumulative probability, for the confidence in a test's result
	 *
	 * @param float $z
	 *
	 * @return float
	 */
	protected static function normal_cdf( float $z ): float {

		// Abramowitz and Stegun 7.1.26, good to about 1e-7
		$x = abs( $z ) / M_SQRT2;
		$t = 1 / ( 1 + 0.3275911 * $x );
		$y = 1 - ( ( ( ( ( 1.061405429 * $t - 1.453152027 ) * $t ) + 1.421413741 ) * $t - 0.284496736 ) * $t + 0.254829592 ) * $t * exp( - $x * $x );

		return $z < 0 ? ( 1 - $y ) / 2 : ( 1 + $y ) / 2;
	}

	/**
	 * How confident we can be that the rates of two versions really are different, from a two-proportion z-test
	 *
	 * @param int $hits_a
	 * @param int $sent_a
	 * @param int $hits_b
	 * @param int $sent_b
	 *
	 * @return float 0 to 1
	 */
	public static function confidence( int $hits_a, int $sent_a, int $hits_b, int $sent_b ): float {

		if ( $sent_a < 1 || $sent_b < 1 ) {
			return 0.0;
		}

		$pooled = ( $hits_a + $hits_b ) / ( $sent_a + $sent_b );
		$error  = sqrt( $pooled * ( 1 - $pooled ) * ( 1 / $sent_a + 1 / $sent_b ) );

		if ( $error <= 0 ) {
			return 0.0;
		}

		$z = ( $hits_a / $sent_a - $hits_b / $sent_b ) / $error;

		// two-sided: that they differ, either way
		return max( 0.0, min( 1.0, 1 - 2 * ( 1 - self::normal_cdf( abs( $z ) ) ) ) );
	}

	/**
	 * How the email and its versions did: sent, opened, clicked and unsubscribed for each, and for a test, which is
	 * ahead and how sure that is. Counts from when the test started, or everything for an email with no test.
	 *
	 * @param Email $email
	 * @param array $args metric (clicks or opens), since (a timestamp), broadcast_id (only what that broadcast sent)
	 *
	 * @return array metric, since, winner, leader, confidence, suggested_winner, versions => [ id, title, kind, status,
	 *               weight, sent, opened, clicked, unsubscribed, open_rate, click_rate ]
	 */
	public static function report( Email $email, array $args = [] ): array {

		$metric = one_of( $args['metric'] ?? 'clicks', self::METRICS );
		$since  = isset( $args['since'] ) ? absint( $args['since'] ) : absint( $email->get_meta( self::TEST_STARTED_KEY ) );

		$versions = array_merge( [ $email ], self::get_variations( $email ) );
		$ids      = wp_parse_id_list( wp_list_pluck( $versions, 'ID' ) );

		$sent = new Table_Query( 'events' );
		$sent->setSelect( 'email_id', [ 'COUNT(ID)', 'total' ] )->setGroupby( 'email_id' )
		     ->where()->in( 'email_id', $ids )->equals( 'status', Event::COMPLETE )->greaterThanEqualTo( 'time', $since );

		$activity = new Table_Query( 'activity' );
		$activity->add_safe_column( 'COUNT(DISTINCT(contact_id))' );
		$activity->setSelect( 'email_id', 'activity_type', [ 'COUNT(DISTINCT(contact_id))', 'total' ] )->setGroupby( 'email_id', 'activity_type' )
		         ->where()->in( 'email_id', $ids )
		         ->in( 'activity_type', [ Classes\Activity::EMAIL_OPENED, Classes\Activity::EMAIL_CLICKED, Classes\Activity::UNSUBSCRIBED ] )
		         ->greaterThanEqualTo( 'timestamp', $since );

		if ( ! empty( $args['broadcast_id'] ) ) {
			$sent->where()->equals( 'funnel_id', Broadcast::FUNNEL_ID )->equals( 'step_id', absint( $args['broadcast_id'] ) );
			$activity->where()->equals( 'funnel_id', Broadcast::FUNNEL_ID )->equals( 'step_id', absint( $args['broadcast_id'] ) );
		}

		$counts = [];

		foreach ( $sent->get_results() as $row ) {
			$counts[ absint( $row->email_id ) ]['sent'] = absint( $row->total );
		}

		$types = [
			Classes\Activity::EMAIL_OPENED  => 'opened',
			Classes\Activity::EMAIL_CLICKED => 'clicked',
			Classes\Activity::UNSUBSCRIBED  => 'unsubscribed',
		];

		foreach ( $activity->get_results() as $row ) {
			$counts[ absint( $row->email_id ) ][ $types[ $row->activity_type ] ?? $row->activity_type ] = absint( $row->total );
		}

		$rows = [];

		foreach ( $versions as $version ) {

			$is_email = $version->get_id() === $email->get_id();
			$count    = $counts[ $version->get_id() ] ?? [];
			$sent_to  = $count['sent'] ?? 0;
			$weight   = 0;

			if ( $is_email ) {
				// the split as it's set, which is how it goes once the test versions are ready
				$weight = 100 - array_sum( array_map( [ __CLASS__, 'get_weight' ], self::get_tests( $email ) ) );
			} else if ( self::is_test( $version ) ) {
				$weight = self::get_weight( $version );
			}

			$rows[] = [
				'id'           => $version->get_id(),
				'title'        => $version->get_title(),
				'kind'         => $is_email ? 'email' : ( self::is_test( $version ) ? 'test' : 'audience' ),
				'status'       => $version->get_status(),
				'weight'       => $weight,
				'sent'         => $sent_to,
				'opened'       => $count['opened'] ?? 0,
				'clicked'      => $count['clicked'] ?? 0,
				'unsubscribed' => $count['unsubscribed'] ?? 0,
				'open_rate'    => $sent_to ? round( ( $count['opened'] ?? 0 ) / $sent_to, 4 ) : 0,
				'click_rate'   => $sent_to ? round( ( $count['clicked'] ?? 0 ) / $sent_to, 4 ) : 0,
			];
		}

		// the test is the email and its test versions, the audience versions go to other contacts
		$arms = array_values( array_filter( $rows, function ( $row ) {
			return $row['kind'] !== 'audience';
		} ) );

		$hits = $metric === 'opens' ? 'opened' : 'clicked';
		$rate = $metric === 'opens' ? 'open_rate' : 'click_rate';

		usort( $arms, function ( $a, $b ) use ( $rate ) {
			return $b[ $rate ] <=> $a[ $rate ];
		} );

		$leader     = 0;
		$confidence = 0.0;

		if ( count( $arms ) > 1 && $arms[0]['sent'] > 0 ) {
			$leader     = $arms[0]['id'];
			$confidence = self::confidence( $arms[0][ $hits ], $arms[0]['sent'], $arms[1][ $hits ], $arms[1]['sent'] );
		}

		/**
		 * Filters how confident a test's result has to be to suggest the leader as the winner
		 *
		 * @param float $threshold 0 to 1
		 */
		$threshold = (float) apply_filters( 'groundhogg/email/variation/confidence_threshold', 0.95 );

		return [
			'metric'           => $metric,
			'since'            => $since,
			'winner'           => self::get_winner_id( $email ),
			'leader'           => $leader,
			'confidence'       => round( $confidence, 4 ),
			'suggested_winner' => $leader && $confidence >= $threshold ? $leader : 0,
			'versions'         => $rows,
		];
	}
}
