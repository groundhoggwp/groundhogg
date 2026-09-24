<?php

namespace Groundhogg\Classes;

use WP_Error;

/**
 * Signing of the requests that deliver received messages to this site (POST gh/v4/messages/inbound),
 * so that only whatever holds the secret can put messages into a conversation.
 *
 * The signature is sent in the X-Groundhogg-Signature header as `t=<unix time>,v1=<hex>`, where v1 is the
 * HMAC-SHA256 of `<t>.<raw request body>`. The time is signed too, and requests too far from now are
 * refused, so a captured request can't be replayed later. More than one v1 can be sent, that's how the
 * secret is rotated without dropping mail.
 *
 * The secret is separate from the site's master secret key, it is only good for this.
 */
class Inbound_Signature {

	const HEADER         = 'X-Groundhogg-Signature';
	const OPTION         = 'gh_inbound_secret';
	const PENDING_OPTION = 'gh_inbound_secret_pending';
	const TOLERANCE = 300; // seconds

	/**
	 * The secret, which is generated the first time it's needed.
	 * Define GH_INBOUND_SECRET in wp-config.php to set it there instead.
	 *
	 * @return string
	 */
	public static function secret() {

		$secret = get_option( self::OPTION );

		if ( ! $secret ) {
			$secret = self::regenerate();
		}

		/**
		 * Filter the secret that inbound messages are signed with
		 *
		 * @param string $secret
		 */
		return (string) apply_filters( 'groundhogg/message/inbound/secret', $secret );
	}

	/**
	 * A secret that the relay is being given, that it hasn't confirmed yet. The relay checks that the site has the
	 * secret by sending a signed request while the request that gave it the secret is still in flight, so
	 * until that request finishes, requests signed with either are accepted, see promote_pending().
	 *
	 * @return string empty if there isn't one
	 */
	public static function pending() {
		return (string) get_option( self::PENDING_OPTION, '' );
	}

	/**
	 * @return string a new random secret, 64 hexadecimal characters, that isn't stored
	 */
	public static function generate() {
		return bin2hex( random_bytes( 32 ) );
	}

	/**
	 * @param string $secret
	 */
	public static function set_pending( string $secret ) {
		update_option( self::PENDING_OPTION, $secret, false );
	}

	public static function clear_pending() {
		delete_option( self::PENDING_OPTION );
	}

	/**
	 * The relay has the pending secret, it's the secret now
	 *
	 * @return bool whether there was one
	 */
	public static function promote_pending() {

		$pending = self::pending();

		if ( ! $pending ) {
			return false;
		}

		update_option( self::OPTION, $pending, false );
		self::clear_pending();

		return true;
	}

	/**
	 * Replace the secret with a new one. Anything sending messages to the site has to be given it, so this is
	 * for a secret that hasn't been given to anything yet, the relay is given a new one with Inbox_Client.
	 *
	 * @return string the new secret
	 */
	public static function regenerate() {

		$secret = self::generate();
		update_option( self::OPTION, $secret, false );

		return $secret;
	}

	/**
	 * The value of the signature header for a request body
	 *
	 * @param string   $body      the raw body
	 * @param int|null $timestamp defaults to now
	 * @param string   $secret    defaults to the site's
	 *
	 * @return string
	 */
	public static function sign( string $body, ?int $timestamp = null, string $secret = '' ) {

		$timestamp = $timestamp ?? time();
		$secret    = $secret ?: self::secret();

		return sprintf( 't=%d,v1=%s', $timestamp, hash_hmac( 'sha256', $timestamp . '.' . $body, $secret ) );
	}

	/**
	 * Check the signature header of a request
	 *
	 * @param string $header the X-Groundhogg-Signature header
	 * @param string $body   the raw body
	 *
	 * @return true|WP_Error codes: missing_signature, malformed_signature, expired_signature, invalid_signature
	 */
	public static function verify( $header, string $body ) {

		$header = trim( (string) $header );

		if ( $header === '' ) {
			return new WP_Error( 'missing_signature', 'The request is not signed.', [ 'status' => 401 ] );
		}

		$timestamp  = 0;
		$signatures = [];

		foreach ( explode( ',', $header ) as $part ) {

			[ $key, $value ] = array_pad( explode( '=', trim( $part ), 2 ), 2, '' );

			if ( $key === 't' ) {
				$timestamp = (int) $value;
			} else if ( $key === 'v1' && $value !== '' ) {
				$signatures[] = $value;
			}
		}

		if ( ! $timestamp || empty( $signatures ) ) {
			return new WP_Error( 'malformed_signature', 'The signature could not be read.', [ 'status' => 401 ] );
		}

		if ( abs( time() - $timestamp ) > self::TOLERANCE ) {
			return new WP_Error( 'expired_signature', 'The signature is too old, or the clocks are out of sync.', [ 'status' => 401 ] );
		}

		// the secret, and the one that's being given to the relay
		foreach ( array_filter( [ self::secret(), self::pending() ] ) as $secret ) {

			$expected = hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );

			foreach ( $signatures as $signature ) {
				if ( hash_equals( $expected, $signature ) ) {
					return true;
				}
			}
		}

		return new WP_Error( 'invalid_signature', 'The signature does not match.', [ 'status' => 401 ] );
	}
}
