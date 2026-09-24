<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Inbound_Signature;
use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Message;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Send_Email;
use WP_REST_Request;
use WP_REST_Server;
use function Groundhogg\create_object_from_type;
use function Groundhogg\do_replacements;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The message history of an object, i.e. a contact.
 *
 * Permissions are the defaults (view_messages, view_message, etc.), which are mapped to the
 * contact permissions in Main_Roles::map_meta_cap().
 *
 * @package Groundhogg\Api\V4
 */
class Messages_Api extends Base_Object_Api {

	public function get_db_table_name() {
		return 'messages';
	}

	protected function get_object_class() {
		return Message::class;
	}

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, '/messages/feed', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_feed' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		// Not for logged in users. What delivers received messages signs the request, see Inbound_Signature
		register_rest_route( self::NAME_SPACE, '/messages/inbound', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'receive' ],
				'permission_callback' => [ $this, 'receive_permissions_callback' ],
			],
		] );
	}

	/**
	 * The largest request that's accepted, bigger than the message body that's kept (1MB) to allow for the headers
	 * and the two versions of the body.
	 */
	const MAX_INBOUND_BYTES = 5 * MB_IN_BYTES;

	/**
	 * The request has to be signed with the site's inbound secret
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return true|\WP_Error
	 */
	public function receive_permissions_callback( WP_REST_Request $request ) {

		$body = $request->get_body();

		if ( strlen( $body ) > self::MAX_INBOUND_BYTES ) {
			return new \WP_Error( 'payload_too_large', 'The message is too large.', [ 'status' => 413 ] );
		}

		return Inbound_Signature::verify( $request->get_header( Inbound_Signature::HEADER ), $body );
	}

	/**
	 * Receive a message from something that delivers them, a hosted inbox, a mail provider's inbound webhook...
	 *
	 * The body is the JSON payload that Message::ingest() takes, plus optionally `authentication` with the
	 * results of the sender checks, like { "spf": "pass", "dkim": "pass" }.
	 *
	 * The response tells the sender whether to try again. A message that was stored is a 201. A message that is
	 * not going to be stored no matter how many times it's sent (a duplicate, an automatic reply, from no one we know)
	 * is a 200 with the reason, so it isn't retried. Only a failure on this end is an error worth retrying, a 500.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function receive( WP_REST_Request $request ) {

		// the bytes that were signed, whatever content type the sender used
		$payload = json_decode( $request->get_body(), true );

		if ( ! is_array( $payload ) || array_is_list( $payload ) ) {
			return self::ERROR_422( 'invalid_payload', 'The body must be a JSON object.' );
		}

		// The relay checks that the site has the secret, and is the site, before it will set up the inbox. It sends
		// a challenge, signed like anything else, and expects it back.
		if ( ( $payload['type'] ?? '' ) === 'verify' ) {

			$challenge = $payload['challenge'] ?? '';

			if ( ! is_string( $challenge ) || $challenge === '' || strlen( $challenge ) > 128 ) {
				return self::ERROR_422( 'invalid_challenge', 'A challenge is required.' );
			}

			return self::SUCCESS_RESPONSE( [ 'status' => 'verified', 'challenge' => $challenge ] );
		}

		// A site that was copied has the inbox of the site that it was copied from, and is not that site.
		if ( ! Inbox::site_matches() ) {
			return self::SUCCESS_RESPONSE( [ 'reason' => 'ignored' ], 'This site is not the one that the inbox was set up for.', 'ignored' );
		}

		// only what ingest takes, in the shape it takes it
		$scalars = [ 'subject', 'html', 'text', 'message_id', 'in_reply_to', 'date' ];
		$either  = [ 'from', 'to', 'cc', 'envelope_to', 'references' ]; // a string, or an array

		$message = [];

		foreach ( $scalars as $key ) {
			$message[ $key ] = is_scalar( $payload[ $key ] ?? null ) ? (string) $payload[ $key ] : '';
		}

		foreach ( $either as $key ) {
			$value           = $payload[ $key ] ?? '';
			$message[ $key ] = is_array( $value ) || is_scalar( $value ) ? $value : '';
		}

		$message['headers'] = array_filter( (array) ( $payload['headers'] ?? [] ), 'is_scalar' );

		// What delivered the message can say whether the sender is who they claim to be. Matching a message to a
		// contact by their address is only as good as that, so if they say it's not, it's not stored.
		$authentication = is_array( $payload['authentication'] ?? null ) ? array_map( 'strtolower', array_filter( $payload['authentication'], 'is_string' ) ) : [];

		// A reply to an email that we sent, to an address that we signed for it, doesn't depend on who it says it's from
		$is_reply = Inbox::verify_reply( Message::parse_addresses( $message['envelope_to'] ) ) !== '';

		/**
		 * Whether the sender of a received message passed authentication
		 *
		 * @param bool  $authenticated true unless authentication results were given and neither SPF nor DKIM passed,
		 *                             or it's a reply to the reply address with a valid token
		 * @param array $authentication the results
		 * @param array $message
		 */
		$authenticated = apply_filters(
			'groundhogg/message/inbound/authenticated',
			$is_reply || empty( $authentication ) || ( $authentication['spf'] ?? '' ) === 'pass' || ( $authentication['dkim'] ?? '' ) === 'pass',
			$authentication,
			$message
		);

		if ( ! $authenticated ) {
			return self::SUCCESS_RESPONSE( [ 'reason' => 'unauthenticated' ], 'The sender could not be authenticated.', 'ignored' );
		}

		$result = Message::ingest( $message );

		if ( is_wp_error( $result ) ) {

			switch ( $result->get_error_code() ) {
				case 'invalid':
					return self::ERROR_422( 'invalid_message', $result->get_error_message() );
				case 'failed':
					return self::ERROR_CODE( 'failed', $result->get_error_message(), [], 500 );
				default: // duplicate, ignored, no_match
					return self::SUCCESS_RESPONSE( [ 'reason' => $result->get_error_code() ], $result->get_error_message(), 'ignored' );
			}
		}

		Inbox::touch_last_received();

		// received, or logged when it's a copy of something that we sent
		$response = self::SUCCESS_RESPONSE( [ 'ID' => $result->get_id(), 'direction' => $result->direction ], '', $result->is_inbound() ? 'received' : 'logged' );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * The conversation for an object, newest first, in pages.
	 *
	 * Messages (composed emails and replies) are always included. Completed broadcast and flow emails
	 * can be merged in with include_automated. Those are not stored as messages, they are read from
	 * the events table. Pages are cursor based on time, `before` is a unix timestamp (inclusive) and
	 * the response gives the `next_before` to use for the following page, that way the two sources
	 * page together correctly.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read_feed( WP_REST_Request $request ) {

		$object_type = sanitize_key( $request->get_param( 'object_type' ) );
		$object_id   = absint( $request->get_param( 'object_id' ) );

		if ( ! $object_type || ! $object_id ) {
			return self::ERROR_422( 'invalid_object', 'An object_type and object_id are required.' );
		}

		$object = create_object_from_type( $object_id, $object_type );

		if ( ! $object || ! $object->exists() ) {
			return self::ERROR_404();
		}

		$limit     = min( max( absint( $request->get_param( 'limit' ) ?: 25 ), 1 ), 100 );
		$before    = absint( $request->get_param( 'before' ) ) ?: time() + DAY_IN_SECONDS;
		$direction = in_array( $request->get_param( 'direction' ), [ 'inbound', 'outbound' ], true ) ? $request->get_param( 'direction' ) : '';
		$search    = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

		// automated emails only exist for contacts, are always "sent", and can't be searched (the content isn't stored)
		$automated = rest_sanitize_boolean( $request->get_param( 'include_automated' ) )
		             && $object_type === 'contact'
		             && $direction !== 'inbound'
		             && ! $search
		             && current_user_can( 'view_contact', $object );

		$messages = $this->query_messages( $object_type, $object_id, $before, $limit, $direction, $search );
		$events   = $automated ? $this->query_automated_emails( $object, $before, $limit ) : [];

		$fetched_full_page = count( $messages ) >= $limit || count( $events ) >= $limit;

		$items = array_merge( $messages, $events );

		usort( $items, fn( $a, $b ) => [ $b['timestamp'], $b['ID'] ] <=> [ $a['timestamp'], $a['ID'] ] );

		$has_more = count( $items ) > $limit || $fetched_full_page;
		$items    = array_slice( $items, 0, $limit );

		return self::SUCCESS_RESPONSE( [
			'items'       => $items,
			'has_more'    => $has_more,
			'next_before' => $items ? end( $items )['timestamp'] : null,
		] );
	}

	/**
	 * @return array[]
	 */
	protected function query_messages( $object_type, $object_id, $before, $limit, $direction, $search ) {

		global $wpdb;

		$table = get_db( 'messages' )->get_table_name();

		$where = [ 'object_type = %s', 'object_id = %d', 'date_created <= %s' ];
		$args  = [ $object_type, $object_id, gmdate( 'Y-m-d H:i:s', $before ) ];

		if ( $direction ) {
			$where[] = 'direction = %s';
			$args[]  = $direction;
		}

		if ( $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '( subject LIKE %s OR content LIKE %s OR from_address LIKE %s OR to_address LIKE %s )';
			array_push( $args, $like, $like, $like, $like );
		}

		$args[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and fixed where clauses
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY date_created DESC, ID DESC LIMIT %d", $args ) );

		$items = [];

		foreach ( $rows as $row ) {

			$message = new Message( $row );

			if ( ! current_user_can( 'view_message', $message ) ) {
				continue;
			}

			$items[] = array_merge( $message->get_as_array(), [
				'kind'      => 'message',
				'key'       => 'message-' . $message->get_id(),
				'ID'        => $message->get_id(),
				'timestamp' => (int) strtotime( $message->date_created . ' UTC' ),
			] );
		}

		return $items;
	}

	/**
	 * Completed broadcast and flow emails for a contact, as pointers to the event. Nothing is duplicated
	 * here, the body of the email (when it was logged) is loaded by the UI from the email log on demand.
	 *
	 * @param \Groundhogg\Contact $contact
	 * @param int                 $before
	 * @param int                 $limit
	 *
	 * @return array[]
	 */
	protected function query_automated_emails( $contact, $before, $limit ) {

		global $wpdb;

		$events_table     = get_db( 'events' )->get_table_name();
		$steps_table      = get_db( 'steps' )->get_table_name();
		$broadcasts_table = get_db( 'broadcasts' )->get_table_name();

		// Broadcast events store the broadcast ID in step_id, flow events store the step ID.
		// Only email broadcasts and send email steps, no SMS, no other kinds of step.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names
		$sql = $wpdb->prepare( "SELECT e.ID, e.time, e.event_type, e.funnel_id, e.step_id, e.email_id, e.queued_id, b.object_id AS broadcast_object_id
			FROM $events_table e
			LEFT JOIN $steps_table s ON e.event_type = %d AND e.step_id = s.ID
			LEFT JOIN $broadcasts_table b ON e.event_type = %d AND e.step_id = b.ID
			WHERE e.contact_id = %d AND e.status = %s AND e.time <= %d
			AND ( ( e.event_type = %d AND s.step_type = %s ) OR ( e.event_type = %d AND b.object_type = %s ) )
			ORDER BY e.time DESC, e.ID DESC LIMIT %d",
			Event::FUNNEL, Event::BROADCAST,
			$contact->get_id(), Event::COMPLETE, $before,
			Event::FUNNEL, Send_Email::TYPE, Event::BROADCAST, 'email',
			$limit
		);

		$rows = $wpdb->get_results( $sql );

		if ( empty( $rows ) ) {
			return [];
		}

		// If the email was logged, that's the subject that was actually sent
		$logs       = [];
		$queued_ids = array_filter( array_map( 'absint', wp_list_pluck( $rows, 'queued_id' ) ) );

		if ( $queued_ids ) {
			$logs_table = get_db( 'email_log' )->get_table_name();
			$in         = implode( ',', $queued_ids );
			$sensitive  = is_super_admin() ? '' : 'AND is_sensitive = 0';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and integers only
			$log_rows = $wpdb->get_results( "SELECT ID, subject, queued_event_id FROM $logs_table WHERE queued_event_id IN ($in) $sensitive" );

			foreach ( $log_rows as $log ) {
				$logs[ (int) $log->queued_event_id ] = $log;
			}
		}

		$items = [];

		foreach ( $rows as $row ) {

			$is_broadcast = (int) $row->event_type === Event::BROADCAST;
			$email_id     = absint( $row->email_id );

			if ( ! $email_id ) {
				$email_id = $is_broadcast ? absint( $row->broadcast_object_id ) : absint( ( new Step( $row->step_id ) )->get_meta( 'email_id' ) );
			}

			$log   = $logs[ (int) $row->queued_id ] ?? null;
			$email = $email_id ? new Email( $email_id ) : null;

			if ( $log ) {
				$subject = $log->subject;
			} else if ( $email && $email->exists() ) {
				$subject = do_replacements( $email->get_subject_line(), $contact );
			} else {
				$subject = '';
			}

			if ( $is_broadcast ) {
				$source_title = ( new Broadcast( $row->step_id ) )->get_title();
			} else {
				$source_title = ( new Funnel( $row->funnel_id ) )->get_title();
			}

			$items[] = [
				'kind'         => 'event',
				'key'          => 'event-' . $row->ID,
				'ID'           => (int) $row->ID,
				'timestamp'    => (int) $row->time,
				'direction'    => Message::OUTBOUND,
				'source'       => $is_broadcast ? 'broadcast' : 'flow',
				'source_id'    => $is_broadcast ? (int) $row->step_id : (int) $row->funnel_id,
				'source_title' => $source_title,
				'subject'      => $subject,
				'email_id'     => $email_id,
				'queued_id'    => (int) $row->queued_id,
				'has_log'      => (bool) $log,
				'date_created' => gmdate( 'Y-m-d H:i:s', (int) $row->time ),
				'i18n'         => [
					'time_diff' => human_time_diff( (int) $row->time, time() ),
				],
			];
		}

		return $items;
	}
}
