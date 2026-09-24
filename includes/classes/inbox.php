<?php

namespace Groundhogg\Classes;

/**
 * The site's inbox, the addresses that received messages are sent to.
 *
 * There are two, and they're trusted differently:
 *
 *  - The inbox address is secret. It's for the admin to BCC, or to forward a support address to, and it's never put
 *    in a header that a contact can see. Mail to it is trusted, because only someone that has the address can send
 *    to it, so the sender's address is enough to know who it's from.
 *
 *  - The reply address is public. It's what Reply-To is set to on the emails that the site sends, so everyone that
 *    gets one of them sees it. It has a token added, `<reply address>+<token>@`, and the token is what's trusted. It
 *    names one email, and it's signed with the site's secret, so it can't be made up. Mail to the reply address
 *    without a valid token is never trusted, whoever it says it's from.
 *
 * The addresses are given to the site when the inbox is set up, see Inbox_Client. Without a reply address, or on a
 * site that isn't the one the inbox was set up for, nothing adds a Reply-To.
 */
class Inbox {

	const ID_OPTION            = 'gh_inbox_id';
	const ADDRESS_OPTION       = 'gh_inbox_address';
	const REPLY_ADDRESS_OPTION = 'gh_inbox_reply_address';
	const SITE_URL_OPTION      = 'gh_inbox_site_url';
	const ENDPOINT_OPTION      = 'gh_inbox_endpoint';
	const TOKEN_KEY_OPTION     = 'gh_inbox_token_key';
	const LAST_RECEIVED_OPTION = 'gh_inbox_last_received';

	// 10 bytes of each, as 16 characters of base32
	const TOKEN_LENGTH = 32;

	/**
	 * The id of the inbox, that the relay knows it by
	 *
	 * @return string
	 */
	public static function id() {
		return (string) get_option( self::ID_OPTION, '' );
	}

	/**
	 * Whether the relay has set up the inbox for this site
	 *
	 * @return bool
	 */
	public static function is_provisioned() {
		return self::id() !== '' && self::address() !== '' && self::reply_address() !== '';
	}

	/**
	 * Save what the relay gave us when it set up the inbox
	 *
	 * @param array $inbox id, address, reply_address
	 */
	public static function save( array $inbox ) {
		update_option( self::ID_OPTION, (string) $inbox['id'], false );
		update_option( self::ADDRESS_OPTION, strtolower( (string) $inbox['address'] ), false );
		update_option( self::REPLY_ADDRESS_OPTION, strtolower( (string) $inbox['reply_address'] ), false );
		update_option( self::SITE_URL_OPTION, self::normalize_url( home_url() ), false );
		update_option( self::ENDPOINT_OPTION, self::endpoint(), false );
	}

	/**
	 * Forget the inbox. Not the key that tokens are signed with, the emails that went out with a reply address
	 * still have one, and they're good again if the inbox is set up again, since it has the same reply address.
	 */
	public static function clear() {

		foreach ( [ self::ID_OPTION, self::ADDRESS_OPTION, self::REPLY_ADDRESS_OPTION, self::SITE_URL_OPTION, self::ENDPOINT_OPTION ] as $option ) {
			delete_option( $option );
		}

		delete_option( Inbound_Signature::OPTION );
		Inbound_Signature::clear_pending();
	}

	/**
	 * The url of the endpoint that the relay delivers to, exactly as it's given to the relay.
	 *
	 * @return string
	 */
	public static function endpoint() {
		return (string) apply_filters( 'groundhogg/inbox/endpoint', rest_url( 'gh/v4/messages/inbound' ) );
	}

	/**
	 * Whether the relay has the endpoint that the site has now, it won't after the permalinks are changed
	 *
	 * @return bool
	 */
	public static function endpoint_is_current() {
		return get_option( self::ENDPOINT_OPTION, '' ) === self::endpoint();
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function normalize_url( $url ) {
		return untrailingslashit( strtolower( trim( (string) $url ) ) );
	}

	/**
	 * Whether this is the site that the inbox was set up for. It isn't when the site has been copied, like to a
	 * staging site, that has the database of the site and so what the inbox is, but is not the site that the inbox
	 * belongs to. The relay is careful about that, and so is the site.
	 *
	 * @return bool true if it is, or if there's nothing to say that it isn't
	 */
	public static function site_matches() {

		$site_url = (string) get_option( self::SITE_URL_OPTION, '' );

		return $site_url === '' || $site_url === self::normalize_url( home_url() );
	}

	/**
	 * Whether the inbox can be used, there's one, and it's for this site
	 *
	 * @return bool
	 */
	public static function is_active() {
		return self::reply_address() !== '' && self::site_matches();
	}

	/**
	 * When the last message arrived through the inbox, silence is not proof that it works
	 *
	 * @return int a unix timestamp, 0 if never
	 */
	public static function last_received() {
		return (int) get_option( self::LAST_RECEIVED_OPTION, 0 );
	}

	public static function touch_last_received() {
		update_option( self::LAST_RECEIVED_OPTION, time(), false );
	}

	/**
	 * The secret address to BCC or forward to
	 *
	 * @return string
	 */
	public static function address() {
		return self::get_address( self::ADDRESS_OPTION, 'groundhogg/inbox/address' );
	}

	/**
	 * The public address that replies go to, without a token
	 *
	 * @return string
	 */
	public static function reply_address() {
		return self::get_address( self::REPLY_ADDRESS_OPTION, 'groundhogg/inbox/reply_address' );
	}

	protected static function get_address( $option, $filter ) {

		$address = strtolower( trim( (string) get_option( $option, '' ) ) );

		/**
		 * @param string $address
		 */
		$address = (string) apply_filters( $filter, $address );

		return is_email( $address ) ? $address : '';
	}

	/**
	 * An address that identifies one email, that a reply to it can be sent to.
	 *
	 * @param string $message_id the Message-ID of the email, as given by Message::generate_message_id()
	 *
	 * @return string empty if replies aren't being sent to the inbox
	 */
	public static function reply_to( string $message_id ) {

		/**
		 * Whether the emails the site sends should have a Reply-To that sends replies to the inbox. On by default,
		 * when there's a reply address.
		 *
		 * @param bool   $enabled
		 * @param string $message_id
		 */
		if ( ! apply_filters( 'groundhogg/message/reply_to_enabled', true, $message_id ) ) {
			return '';
		}

		$reply_address = self::is_active() ? self::reply_address() : '';

		// only a Message-ID that we made has an id that can be signed
		if ( ! $reply_address || ! preg_match( '/^<([a-z2-7]{16})@/', $message_id, $matches ) ) {
			return '';
		}

		[ $local, $domain ] = explode( '@', $reply_address, 2 );

		$local .= '+' . $matches[1] . self::mac( $matches[1] );

		// the local part can't be longer than 64 characters
		if ( strlen( $local ) > 64 ) {
			return '';
		}

		return $local . '@' . $domain;
	}

	/**
	 * The email that a reply is to, given the addresses it was sent to
	 *
	 * @param string[] $addresses the envelope recipients
	 *
	 * @return string the id of the email, empty if there isn't an address with a valid token
	 */
	public static function verify_reply( array $addresses ) {

		foreach ( $addresses as $address ) {

			[ $route, $token ] = self::split( $address );

			if ( ! $route || ! self::is_reply_route( $route ) || strlen( $token ) !== self::TOKEN_LENGTH ) {
				continue;
			}

			$id  = substr( $token, 0, 16 );
			$mac = substr( $token, 16 );

			if ( hash_equals( self::mac( $id ), $mac ) ) {
				return $id;
			}
		}

		return '';
	}

	/**
	 * Which of the addresses of the inbox a message was sent to
	 *
	 * @param string[] $addresses the envelope recipients
	 *
	 * @return string reply, inbox, or unknown if it wasn't sent to either, like to an address that was replaced.
	 *                It's inbox when there are no addresses to go on, for a transport that doesn't say.
	 */
	public static function route( array $addresses ) {

		if ( empty( $addresses ) ) {
			return 'inbox';
		}

		$route = 'unknown';

		foreach ( $addresses as $address ) {
			[ $base ] = self::split( $address );

			if ( $base && self::is_reply_route( $base ) ) {
				return 'reply';
			}

			if ( $base && $base === self::address() ) {
				$route = 'inbox';
			}
		}

		return $route;
	}

	/**
	 * @param string $route the address without the token, lowercase
	 *
	 * @return bool
	 */
	protected static function is_reply_route( string $route ) {
		return $route === self::reply_address();
	}

	/**
	 * Split an address into [ the address without its token, the token ]
	 *
	 * @param string $address
	 *
	 * @return string[]
	 */
	protected static function split( $address ) {

		$address = strtolower( trim( (string) $address ) );

		if ( ! str_contains( $address, '@' ) ) {
			return [ '', '' ];
		}

		[ $local, $domain ] = explode( '@', $address, 2 );
		[ $local, $token ] = array_pad( explode( '+', $local, 2 ), 2, '' );

		return [ $local . '@' . $domain, $token ];
	}

	/**
	 * A random id for an email. It's what the Message-ID that we make starts with.
	 *
	 * @return string 16 characters of base32
	 */
	public static function new_id() {
		return self::base32( random_bytes( 10 ) );
	}

	/**
	 * The key that tokens are signed with. It's the site's own, and it's never given to the relay, and it's not
	 * the secret that the relay's requests are signed with, so that the secret can be replaced without every reply
	 * address that's already been sent out stopping working.
	 *
	 * @return string
	 */
	protected static function token_key() {

		$key = get_option( self::TOKEN_KEY_OPTION );

		if ( ! $key ) {
			$key = self::regenerate_token_key();
		}

		return $key;
	}

	/**
	 * Make a new key. Every reply address that has been sent out stops working.
	 *
	 * @return string
	 */
	public static function regenerate_token_key() {
		$key = bin2hex( random_bytes( 32 ) );
		update_option( self::TOKEN_KEY_OPTION, $key, false );

		return $key;
	}

	/**
	 * The signature of an id
	 *
	 * @param string $id
	 *
	 * @return string 16 characters of base32
	 */
	protected static function mac( string $id ) {
		return self::base32( substr( hash_hmac( 'sha256', $id, self::token_key(), true ), 0, 10 ) );
	}

	/**
	 * Lowercase base32, without padding, so that it can go in an email address
	 *
	 * @param string $bytes
	 *
	 * @return string
	 */
	protected static function base32( string $bytes ) {

		$alphabet = 'abcdefghijklmnopqrstuvwxyz234567';
		$bits     = '';

		foreach ( str_split( $bytes ) as $char ) {
			$bits .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		$encoded = '';

		foreach ( str_split( $bits, 5 ) as $chunk ) {
			$encoded .= $alphabet[ bindec( str_pad( $chunk, 5, '0' ) ) ];
		}

		return $encoded;
	}
}
