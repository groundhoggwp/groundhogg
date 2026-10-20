<?php

namespace Groundhogg\Mobile;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What a person used last, per kind of thing, so the mobile app can put it at the top of the list next time:
 * the email template they sent, the form they submitted, the tag they applied.
 *
 * It lives in user meta, so it follows the person across devices and survives clearing a browser or reinstalling the
 * home screen app. Each entry keeps the name (and a second line) it was used under, so the app shows recents
 * straight from the manifest with no extra request. A name can go stale if the thing is renamed, and a recent
 * that has been deleted is removed when the app finds out.
 *
 * Nothing here grants anything: an entry is a label and an id for the person's own convenience. Using one still goes
 * through the endpoint for the real thing, with its own permission checks.
 *
 * `POST gh/v4/mobile/recent/{kind}` with { id, name, sub } records a use, and
 * `DELETE gh/v4/mobile/recent/{kind}/{id}` forgets one.
 */
class Recent {

	const META_KEY = 'gh_mobile_recent';

	/**
	 * How many of each kind are kept.
	 */
	const MAX = 8;

	/**
	 * The most characters kept of a name or its second line.
	 */
	const MAX_TEXT = 120;

	const KINDS = [ 'email_template', 'form', 'tag' ];

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * The kinds of thing that can be remembered. Add-ons add their own (a pipeline, say).
	 *
	 * @return string[]
	 */
	public static function kinds(): array {

		/**
		 * @param string[] $kinds slugs of lowercase letters and underscores
		 */
		$kinds = (array) apply_filters( 'groundhogg/mobile/recent_kinds', self::KINDS );

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $kinds ) ) ) );
	}

	/**
	 * @param array $item
	 *
	 * @return array|null the item cleaned up, or null when it isn't one
	 */
	protected static function clean( $item ) {

		if ( ! is_array( $item ) || empty( $item['id'] ) || ! is_numeric( $item['id'] ) ) {
			return null;
		}

		return [
			'id'   => absint( $item['id'] ),
			'name' => mb_substr( sanitize_text_field( (string) ( $item['name'] ?? '' ) ), 0, self::MAX_TEXT ),
			'sub'  => mb_substr( sanitize_text_field( (string) ( $item['sub'] ?? '' ) ), 0, self::MAX_TEXT ),
		];
	}

	/**
	 * Everything a person used recently, newest first, for every kind (an empty list when there is none).
	 *
	 * @param int $user_id defaults to the current user
	 *
	 * @return array<string, array[]>
	 */
	public static function get( int $user_id = 0 ): array {

		$user_id = $user_id ?: get_current_user_id();
		$stored  = $user_id ? get_user_meta( $user_id, self::META_KEY, true ) : [];
		$stored  = is_array( $stored ) ? $stored : [];
		$out     = [];

		foreach ( self::kinds() as $kind ) {

			$items = [];

			foreach ( (array) ( $stored[ $kind ] ?? [] ) as $item ) {

				$item = self::clean( $item );

				if ( $item ) {
					$items[ $item['id'] ] = $item;
				}
			}

			$out[ $kind ] = array_slice( array_values( $items ), 0, self::MAX );
		}

		return $out;
	}

	/**
	 * Note that something was used. It moves to the front if it was there already.
	 *
	 * @param int    $user_id
	 * @param string $kind
	 * @param array  $item { id, name, sub }
	 *
	 * @return bool whether it was recorded
	 */
	public static function record( int $user_id, string $kind, array $item ): bool {

		$kind = sanitize_key( $kind );
		$item = self::clean( $item );

		if ( ! $user_id || ! $item || ! in_array( $kind, self::kinds(), true ) ) {
			return false;
		}

		$all          = self::get( $user_id );
		$rest         = array_filter( $all[ $kind ], function ( $existing ) use ( $item ) {
			return $existing['id'] !== $item['id'];
		} );
		$all[ $kind ] = array_slice( array_merge( [ $item ], array_values( $rest ) ), 0, self::MAX );

		// update_user_meta() says false when nothing changed, which is still a success for us
		update_user_meta( $user_id, self::META_KEY, $all );

		return true;
	}

	/**
	 * Remove one, for when the thing it points to is gone.
	 */
	public static function forget( int $user_id, string $kind, int $id ): void {

		$kind = sanitize_key( $kind );
		$all  = self::get( $user_id );

		if ( ! $user_id || ! isset( $all[ $kind ] ) ) {
			return;
		}

		$all[ $kind ] = array_values( array_filter( $all[ $kind ], function ( $existing ) use ( $id ) {
			return $existing['id'] !== $id;
		} ) );

		update_user_meta( $user_id, self::META_KEY, $all );
	}

	public function register_routes() {

		register_rest_route( 'gh/v4', '/mobile/recent/(?P<kind>[a-z0-9_]+)', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'record_use' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
			],
		] );

		register_rest_route( 'gh/v4', '/mobile/recent/(?P<kind>[a-z0-9_]+)/(?P<id>\d+)', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'forget_use' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
			],
		] );
	}

	/**
	 * Whoever can use the app can keep their own list of recents. It is only ever their own.
	 *
	 * @return bool
	 */
	public function permissions_callback() {
		return is_user_logged_in() && current_user_can( 'view_contacts' );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function record_use( WP_REST_Request $request ) {

		$kind = sanitize_key( $request->get_param( 'kind' ) );

		if ( ! in_array( $kind, self::kinds(), true ) ) {
			return new WP_Error( 'unknown_kind', 'That is not something that can be remembered.', [ 'status' => 404 ] );
		}

		$item = [
			'id'   => $request->get_param( 'id' ),
			'name' => $request->get_param( 'name' ),
			'sub'  => $request->get_param( 'sub' ),
		];

		if ( ! self::clean( $item ) ) {
			return new WP_Error( 'invalid_item', 'An id is needed.', [ 'status' => 400 ] );
		}

		self::record( get_current_user_id(), $kind, $item );

		return new WP_REST_Response( [ 'items' => self::get()[ $kind ] ] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function forget_use( WP_REST_Request $request ) {

		$kind = sanitize_key( $request->get_param( 'kind' ) );

		self::forget( get_current_user_id(), $kind, absint( $request->get_param( 'id' ) ) );

		return new WP_REST_Response( [ 'items' => self::get()[ $kind ] ?? [] ] );
	}
}
