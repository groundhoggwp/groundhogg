<?php

namespace Groundhogg\Classes;

use Groundhogg\License_Manager;
use WP_Error;
use function Groundhogg\get_hostname;
use function Groundhogg\white_labeled_name;

/**
 * The relay's API, that sets up the inbox for the site. The relay receives the mail that's sent to the site's
 * addresses, and delivers it to the endpoint of the site, see Messages_Api::receive().
 *
 * Setting the inbox up gives the relay the license, the url of the site, the url of the endpoint and a secret. It checks
 * the license, and then that it's the site that it's talking to, by sending a challenge to the endpoint signed with
 * the secret. Only when that's answered does it give the addresses.
 *
 * That check is a request to this site, that arrives while the request to the relay that asked for it is still
 * waiting, so the secret is kept as pending, and requests signed with it are accepted, until the relay says
 * that it has it. See Inbound_Signature.
 */
class Inbox_Client {

	const DEFAULT_URL = 'https://groundhogg.email';

	// the relay looks up the license, and then calls the site back, each of which can take ten seconds
	const TIMEOUT = 45;

	/**
	 * @return string the url of the relay, without a slash at the end
	 */
	public static function base_url() {

		$url = defined( 'GH_INBOX_API_URL' ) ? GH_INBOX_API_URL : self::DEFAULT_URL;

		/**
		 * The url of the relay, to use one that isn't the live one, like a local copy of it
		 *
		 * @param string $url
		 */
		return untrailingslashit( (string) apply_filters( 'groundhogg/inbox/api_url', $url ) );
	}

	/**
	 * The license key to set the inbox up with. The relay checks it for the url of the site.
	 *
	 * @return string empty if there isn't one
	 */
	public static function license_key() {

		$key = License_Manager::get_master_license();

		// The master license can be one that has expired, when there isn't a valid one that gets more than one item. The
		// relay doesn't take it, so it's the same as not having one, and the licenses that are valid are looked at.
		// A key defined in wp-config.php isn't one of the licenses, there's nothing to check it with.
		if ( $key && ! defined( 'GH_MASTER_LICENSE' ) && ! License_Manager::is_valid( (string) $key ) ) {
			$key = false;
		}

		if ( ! $key ) {
			foreach ( License_Manager::get_licenses() as $license_key => $license ) {
				if ( $license->is_valid() ) {
					$key = $license_key;
					break;
				}
			}
		}

		/**
		 * @param string|false $key
		 */
		return (string) apply_filters( 'groundhogg/inbox/license_key', $key ?: '' );
	}

	/**
	 * Set up the inbox, or set it up again. Setting it up again is how a site that lost its secret gets the inbox back,
	 * the relay gives the addresses that it already has, and the site has a new secret.
	 *
	 * @return true|WP_Error
	 */
	public static function enable() {

		$license = self::license_key();

		if ( ! $license ) {
			// the same wording as the settings UI shows before this is ever reached, for when it's reached some other way
			return new WP_Error( 'no_license', __( 'A Groundhogg license is needed to receive messages. Activate your license on the Licenses tab.', 'groundhogg' ) );
		}

		// The relay checks that the site has the secret before it gives the addresses. A secret that's defined in
		// wp-config.php is the only one the site can have, so that's the one the relay is given
		$secret = Inbound_Signature::is_constant() ? Inbound_Signature::secret() : Inbound_Signature::generate();
		Inbound_Signature::set_pending( $secret );

		$response = self::request( 'POST', '/v1/inboxes', [
			'license_key' => $license,
			'site_url'    => home_url(),
			'endpoint'    => Inbox::endpoint(),
			'secret'      => $secret,
		] );

		if ( is_wp_error( $response ) ) {
			Inbound_Signature::clear_pending();

			return $response;
		}

		$inbox = [
			'id'            => $response['inbox_id'] ?? '',
			'address'       => $response['inbox_address'] ?? '',
			'reply_address' => $response['reply_address'] ?? '',
		];

		if ( ! $inbox['id'] || ! is_email( $inbox['address'] ) || ! is_email( $inbox['reply_address'] ) ) {
			Inbound_Signature::clear_pending();

			return new WP_Error( 'invalid_response', __( 'The service did not give the addresses of the inbox.', 'groundhogg' ) );
		}

		Inbound_Signature::promote_pending();
		Inbox::save( $inbox );

		/**
		 * The inbox was set up
		 *
		 * @param array $inbox id, address, reply_address
		 */
		do_action( 'groundhogg/inbox/enabled', $inbox );

		return true;
	}

	/**
	 * Tell the relay what changed, the endpoint if it has, and optionally give it a new secret.
	 * The endpoint changes with the permalinks, and it's the url as it was given that's used to deliver.
	 *
	 * @param bool $rotate_secret give the relay a new secret
	 *
	 * @return true|WP_Error
	 */
	public static function update( bool $rotate_secret = false ) {

		$check = self::can_manage();

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		if ( $rotate_secret && Inbound_Signature::is_constant() ) {
			return new WP_Error( 'secret_defined', __( 'The secret is defined in wp-config.php, so it can\'t be replaced from here. Change GH_INBOUND_SECRET there and set up the inbox again.', 'groundhogg' ) );
		}

		$changes = [ 'endpoint' => Inbox::endpoint() ];

		if ( $rotate_secret ) {
			$secret            = Inbound_Signature::generate();
			$changes['secret'] = $secret;
			Inbound_Signature::set_pending( $secret );
		}

		// signed with the secret that the relay has now
		$response = self::request( 'PATCH', '/v1/inboxes/' . rawurlencode( Inbox::id() ), $changes, Inbound_Signature::secret() );

		if ( is_wp_error( $response ) ) {
			Inbound_Signature::clear_pending();

			return $response;
		}

		Inbound_Signature::promote_pending();
		update_option( Inbox::ENDPOINT_OPTION, $changes['endpoint'], false );

		do_action( 'groundhogg/inbox/updated', $rotate_secret );

		return true;
	}

	/**
	 * Turn the inbox off. The relay stops accepting mail for the addresses, and the site forgets them.
	 *
	 * @return true|WP_Error
	 */
	public static function disable() {

		$check = self::can_manage();

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$response = self::request( 'DELETE', '/v1/inboxes/' . rawurlencode( Inbox::id() ), null, Inbound_Signature::secret() );

		// the relay doesn't have it, it's already off
		if ( is_wp_error( $response ) && $response->get_error_data()['status'] !== 404 ) {
			return $response;
		}

		self::forget();

		return true;
	}

	/**
	 * Forget the inbox here, without asking the relay. For when it can't be turned off, because the secret is lost.
	 * The relay keeps the addresses until it's set up again.
	 */
	public static function forget() {
		Inbox::clear();

		do_action( 'groundhogg/inbox/disabled' );
	}

	/**
	 * Whether it's all right to change the inbox at the relay from here
	 *
	 * @return true|WP_Error
	 */
	protected static function can_manage() {

		if ( ! Inbox::is_provisioned() ) {
			return new WP_Error( 'not_provisioned', __( 'The inbox has not been set up.', 'groundhogg' ) );
		}

		// A site that was copied has the secret and the id of the site that it was copied from, and could
		// change or turn off the inbox of that site. It has to set up an inbox of its own.
		if ( ! Inbox::site_matches() ) {
			return new WP_Error( 'site_mismatch', __( 'The inbox was set up for a different site. This site is a copy of it. Set up the inbox again for this site.', 'groundhogg' ) );
		}

		return true;
	}

	/**
	 * Make a request to the relay
	 *
	 * @param string      $method
	 * @param string      $path
	 * @param array|null  $data   what to send as JSON
	 * @param string|null $secret sign the request with it, the ones that change an inbox are signed
	 *
	 * @return array|WP_Error what the relay answered as JSON, or an error with the status, reason and code in its data
	 */
	protected static function request( string $method, string $path, ?array $data = null, ?string $secret = null ) {

		$body = $data === null ? '' : wp_json_encode( $data );

		$headers = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		];

		if ( $secret ) {
			$headers[ Inbound_Signature::HEADER ] = Inbound_Signature::sign( $body, null, $secret );
		}

		$args = [
			'method'      => $method,
			'timeout'     => self::TIMEOUT,
			'redirection' => 0,
			'headers'     => $headers,
		];

		if ( $body !== '' ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( self::base_url() . $path, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'unreachable', sprintf(
			/* translators: %1$s: Groundhogg, or the white labeled name. %2$s: the error */
				__( '%1$s could not be reached: %2$s', 'groundhogg' ),
				white_labeled_name(),
				$response->get_error_message()
			), [ 'status' => 0 ] );
		}

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		$decoded = is_array( $decoded ) ? $decoded : [];

		if ( $status >= 200 && $status < 300 ) {
			return $decoded;
		}

		return self::error( $status, $decoded, (int) wp_remote_retrieve_header( $response, 'retry-after' ) );
	}

	/**
	 * What the relay's errors mean to whoever is setting up the inbox
	 *
	 * @param int   $status
	 * @param array $body        the JSON that came with it
	 * @param int   $retry_after seconds
	 *
	 * @return WP_Error
	 */
	protected static function error( int $status, array $body, int $retry_after = 0 ) {

		$code     = (string) ( $body['code'] ?? 'error' );
		$reason   = (string) ( $body['reason'] ?? '' );
		$name     = white_labeled_name();
		$hostname = get_hostname() ?: __( 'this site', 'groundhogg' ); // a hostname isn't always resolvable, i.e. in tests

		switch ( $code ) {
			case 'invalid_license':
				$message = sprintf(
				/* translators: %s: the site's hostname */
					__( 'Your license is not valid for %s. Check that it is active for that site on the Licenses tab.', 'groundhogg' ),
					$hostname
				);
				break;
			case 'site_url_in_use':
				$message = sprintf(
				/* translators: %s: the site's hostname */
					__( 'A different license already has an inbox for %s.', 'groundhogg' ),
					$hostname
				);
				break;
			case 'invalid_endpoint':
				$message = sprintf(
				/* translators: 1: the reason, 2: the site's hostname */
					__( 'The address that messages would be delivered to is not accepted: %1$s. Messages can only be delivered to a public address on %2$s, over HTTPS.', 'groundhogg' ),
					self::reason_label( $reason ),
					$hostname
				);
				break;
			case 'handshake_failed':
				$message = self::handshake_failed_message( $reason );
				break;
			case 'rate_limited':
			case 'license_unavailable':
				$message = sprintf(
				/* translators: %s: Groundhogg, or the white labeled name */
					__( '%s is busy, or could not check your license right now. Try again in a minute.', 'groundhogg' ),
					$name
				);
				break;
			case 'missing_signature':
			case 'malformed_signature':
			case 'invalid_signature':
				$message = sprintf(
				/* translators: 1: Groundhogg, or the white labeled name. 2: the site's hostname */
					__( '%1$s did not accept the secret that %2$s has. Set up the inbox again to get a new one.', 'groundhogg' ),
					$name,
					$hostname
				);
				break;
			case 'expired_signature':
				$message = sprintf(
				/* translators: 1: Groundhogg, or the white labeled name. 2: the site's hostname */
					__( '%1$s and %2$s do not agree about what time it is. Check the time on %2$s\'s server.', 'groundhogg' ),
					$name,
					$hostname
				);
				break;
			case 'not_found':
				$message = sprintf(
				/* translators: %s: Groundhogg, or the white labeled name */
					__( '%s does not have the inbox. Set it up again.', 'groundhogg' ),
					$name
				);
				break;
			default:
				$message = sprintf(
				/* translators: 1: Groundhogg, or the white labeled name. 2: the http status */
					__( '%1$s answered with an error (%2$d).', 'groundhogg' ),
					$name,
					$status
				);
		}

		return new WP_Error( $code, $message, [
			'status'      => $status,
			'reason'      => $reason,
			'retry_after' => $retry_after,
		] );
	}

	/**
	 * @param string $reason a reason the relay gives for rejecting the endpoint
	 *
	 * @return string
	 */
	protected static function reason_label( string $reason ) {

		$hostname = get_hostname() ?: __( 'this site', 'groundhogg' );
		$name     = white_labeled_name();

		$labels = [
			'not_https'         => __( 'it is not HTTPS', 'groundhogg' ),
			'has_credentials'   => __( 'it has a username or password in it', 'groundhogg' ),
			'non_standard_port' => __( 'it uses a port other than 443', 'groundhogg' ),
			/* translators: 1: Groundhogg, or the white labeled name */
			'ip_literal'        => sprintf( __( 'it is an IP address rather than a domain - if this is a local development site, it needs to be reachable at a real, public domain before %s can deliver messages to it', 'groundhogg' ), $name ),
			/* translators: 1: the site's hostname. 2: Groundhogg, or the white labeled name */
			'internal_host'     => sprintf( __( '%1$s is not a public domain - a local development site (one ending in .local, .test, localhost, or only reachable on your own network) has to be made public first, for example with a temporary tunnel, before %2$s can deliver messages to it', 'groundhogg' ), $hostname, $name ),
			/* translators: %s: the site's hostname */
			'host_mismatch'     => sprintf( __( 'it is not on the same domain as %s', 'groundhogg' ), $hostname ),
			'has_fragment'      => __( 'it has a fragment in it', 'groundhogg' ),
			'too_long'          => __( 'it is too long', 'groundhogg' ),
		];

		return $labels[ $reason ] ?? ( $reason ?: __( 'unknown reason', 'groundhogg' ) );
	}

	/**
	 * A plain explanation of why the relay could not confirm this site controls the address it was given, with the
	 * likely, actionable cause for the specific reason the relay reported.
	 *
	 * @param string $reason
	 *
	 * @return string
	 */
	protected static function handshake_failed_message( string $reason ) {

		$hostname = get_hostname() ?: __( 'this site', 'groundhogg' );
		$name     = white_labeled_name();

		/* translators: 1: Groundhogg, or the white labeled name. 2: the site's hostname. Followed by one of the sentences below, explaining the likely cause */
		$intro = sprintf( __( '%1$s tried to confirm that %2$s controls the address it was given, and could not.', 'groundhogg' ), $name, $hostname );

		$causes = [
			/* translators: 1: the site's hostname. 2: Groundhogg, or the white labeled name */
			'network'            => sprintf( __( '%1$s could not be reached at all. If this is a local development site (for example one that only works on your own computer or network), it has to be made public first, for example with a temporary tunnel, before %2$s can receive messages from it.', 'groundhogg' ), $hostname, $name ),
			/* translators: %s: the site's hostname */
			'timeout'            => sprintf( __( '%s took too long to answer. A local development site that is not reachable from the internet would cause this. Otherwise, its server may just be slow or overloaded right now - it is worth trying again in a moment.', 'groundhogg' ), $hostname ),
			/* translators: 1: the site's hostname. 2: Groundhogg, or the white labeled name */
			'bad_status'         => sprintf( __( '%1$s did not answer normally. A common cause is password protection added at the server or hosting level (sometimes called "site-wide" login protection, common on staging sites) standing in front of it, before WordPress ever sees the request. A firewall or security plugin blocking unfamiliar requests can do the same. Check for those, and allow requests from %2$s, or turn them off while setting this up.', 'groundhogg' ), $hostname, $name ),
			/* translators: %s: the site's hostname */
			'redirect'           => sprintf( __( '%s redirected the request instead of answering it directly - to a login page, a "coming soon" page, or from http to https, for example. The address given has to answer directly, without redirecting.', 'groundhogg' ), $hostname ),
			/* translators: %s: the site's hostname */
			'challenge_mismatch' => sprintf( __( '%s answered, but not with what was expected. A caching or security plugin may be altering the response before it goes out.', 'groundhogg' ), $hostname ),
			/* translators: %s: the site's hostname */
			'invalid_response'   => sprintf( __( '%s answered, but not in the way that was expected. A caching or security plugin may be altering the response, or the address given may not be pointing at the right place.', 'groundhogg' ), $hostname ),
			/* translators: %s: the site's hostname */
			'response_too_large' => sprintf( __( '%s answered with far more than expected, which usually means an error page (for example, from a security plugin) was returned instead of the expected response.', 'groundhogg' ), $hostname ),
		];

		/* translators: %s: the site's hostname */
		$cause = $causes[ $reason ] ?? sprintf( __( '%s did not answer the way that was expected.', 'groundhogg' ), $hostname );

		return $intro . ' ' . $cause;
	}
}
