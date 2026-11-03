<?php

namespace Groundhogg\Notifications;

use function Groundhogg\base64url_decode;
use function Groundhogg\base64url_encode;
use function Groundhogg\check_signature;
use function Groundhogg\compute_signature;
use function Groundhogg\managed_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A link in a notification email that does something for a user without them signing in, like unsubscribing or
 * snoozing a task.
 *
 * It is the same shape as the open tracking links of an email, `base64url( payload ).base64url( signature )`, signed
 * with the site's secret by compute_signature(). The payload says who it is for, what it does, what it does it to and
 * when it stops working, so a link can't be changed, or used for anything else, without breaking the signature.
 *
 * A link is not a login, and it doesn't do anything when it's opened. Mail scanners and link previews open every link in
 * an email, so the page behind it asks first and only a POST does the thing, see Actions.
 */
class Signed_Link {

	/**
	 * Keeps a signature made for something else from being accepted as one of these
	 */
	const PREFIX = 'n';

	/**
	 * The path under the managed page
	 */
	const ROUTE = 'notification';

	/**
	 * The signature is cut to this many bytes, the same as the links that track an email
	 */
	const SIGNATURE_LENGTH = 16;

	/**
	 * The link's token
	 *
	 * @param string $action  what the link does, e.g. unsub, snooze or complete
	 * @param int    $user_id who it is for
	 * @param string $object  what it does it to, the ID of a task or the ID of a type of notification
	 * @param int    $ttl     seconds from now until it stops working
	 * @param int    $now     to make a link at a different time, mostly for tests
	 *
	 * @return string
	 */
	public static function token( string $action, int $user_id, string $object = '', int $ttl = WEEK_IN_SECONDS, int $now = 0 ): string {

		$payload = implode( '|', [
			self::PREFIX,
			sanitize_key( $action ),
			dechex( $user_id ),
			sanitize_key( $object ),
			dechex( ( $now ?: time() ) + $ttl ),
		] );

		return base64url_encode( $payload ) . '.' . base64url_encode( compute_signature( $payload, self::SIGNATURE_LENGTH ) );
	}

	/**
	 * The URL of a link
	 *
	 * @param string $action
	 * @param int    $user_id
	 * @param string $object
	 * @param int    $ttl
	 *
	 * @return string
	 */
	public static function url( string $action, int $user_id, string $object = '', int $ttl = WEEK_IN_SECONDS ): string {
		return managed_page_url( self::ROUTE . '/' . self::token( $action, $user_id, $object, $ttl ) );
	}

	/**
	 * What a token says, if it's one of ours and hasn't been changed
	 *
	 * @param string $token
	 * @param int    $now to check at a different time, mostly for tests
	 *
	 * @return array|false false when it isn't genuine, otherwise action, user_id, object, expires and expired. A link that
	 *                     has expired is still returned, so the page can tell the user that, and not just that it's wrong.
	 */
	public static function parse( string $token, int $now = 0 ) {

		$parts = explode( '.', $token );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$payload   = base64url_decode( $parts[0] );
		$signature = base64url_decode( $parts[1] );

		if ( $payload === false || $signature === false || ! check_signature( $payload, $signature, self::SIGNATURE_LENGTH ) ) {
			return false;
		}

		$fields = explode( '|', $payload );

		if ( count( $fields ) !== 5 || $fields[0] !== self::PREFIX ) {
			return false;
		}

		$expires = (int) hexdec( $fields[4] );

		return [
			'action'  => $fields[1],
			'user_id' => (int) hexdec( $fields[2] ),
			'object'  => $fields[3],
			'expires' => $expires,
			'expired' => $expires < ( $now ?: time() ),
		];
	}
}
