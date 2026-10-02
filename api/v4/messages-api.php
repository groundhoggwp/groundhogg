<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Broadcast;
use Groundhogg\DB\Query\Table_Query;
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
use function Groundhogg\get_team_ids;
use function Groundhogg\isset_not_empty;

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

		// what has been received and not read yet, a conversation at a time
		register_rest_route( self::NAME_SPACE, '/messages/unread', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_unread' ],
				'permission_callback' => [ $this, 'read_permissions_callback' ],
			],
		] );

		register_rest_route( self::NAME_SPACE, '/messages/read', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'mark_read' ],
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
	 * The columns that a list of messages can be filtered by, exactly. Nothing else in the request is a filter, the
	 * generic read takes any column, any function and any where, and counts what the user can't see.
	 */
	const FILTERABLE_COLUMNS = [
		'ID',
		'object_type',
		'object_id',
		'user_id',
		'direction',
		'status',
		'is_read',
		'message_id',
		'in_reply_to',
		'thread_id',
	];

	/**
	 * Messages, of the contacts that the user can see. The counts are of those too, so that the total, and `count`,
	 * can't be used to find out about messages on contacts that they can't: whether a contact has replied, or
	 * with `search`, what is in what they wrote.
	 *
	 * Query: the columns in FILTERABLE_COLUMNS, `include` (ids), `search`, `before` and `after` (dates in UTC),
	 * `orderby` (ID or date_created), `order`, `limit` (100 at the most), `offset`, `count` (only the total), and
	 * `found_rows` (on by default).
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$params = $request->get_params();

		$query = new Table_Query( 'messages' );

		foreach ( self::FILTERABLE_COLUMNS as $column ) {

			if ( ! isset( $params[ $column ] ) || ! is_scalar( $params[ $column ] ) || $params[ $column ] === '' ) {
				continue;
			}

			$query->where()->equals( $column, $params[ $column ] );
		}

		if ( ! empty( $params['include'] ) ) {
			$query->where()->in( 'ID', array_map( 'absint', wp_parse_list( $params['include'] ) ) );
		}

		if ( isset( $params['search'] ) && is_scalar( $params['search'] ) && $params['search'] !== '' ) {
			$query->search( (string) $params['search'], get_db( 'messages' )->get_searchable_columns() );
		}

		foreach ( [ 'before' => 'lessThanEqualTo', 'after' => 'greaterThanEqualTo' ] as $key => $compare ) {

			$time = isset( $params[ $key ] ) && is_scalar( $params[ $key ] ) ? strtotime( (string) $params[ $key ] . ' UTC' ) : false;

			if ( $time ) {
				$query->where()->$compare( 'date_created', gmdate( 'Y-m-d H:i:s', $time ) );
			}
		}

		if ( ! $this->scope_to_visible_contacts( $query ) ) {
			return self::SUCCESS_RESPONSE( isset_not_empty( $params, 'count' ) ? [ 'total_items' => 0 ] : [ 'total_items' => 0, 'items' => [] ] );
		}

		if ( isset_not_empty( $params, 'count' ) ) {
			return self::SUCCESS_RESPONSE( [ 'total_items' => $query->count() ] );
		}

		$orderby = ( $params['orderby'] ?? '' ) === 'date_created' ? 'date_created' : 'ID';
		$order   = strtoupper( (string) ( $params['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';

		$query->setOrderby( [ $orderby, $order ] );
		$query->setLimit( min( 100, max( 1, absint( $params['limit'] ?? 25 ) ) ) );
		$query->setOffset( absint( $params['offset'] ?? 0 ) );

		$found_rows = ! array_key_exists( 'found_rows', $params ) || filter_var( $params['found_rows'], FILTER_VALIDATE_BOOLEAN );
		$query->setFoundRows( $found_rows );

		$rows  = $query->get_results();
		$total = $found_rows ? $query->get_found_rows() : count( $rows );

		// what is counted is what the user can see, this is for what the scope doesn't say, like the object it's on
		$items = array_values( array_filter(
			array_map( [ $this, 'map_raw_object_to_class' ], $rows ),
			[ $this, 'current_user_can_read' ]
		) );

		return self::SUCCESS_RESPONSE( [
			'total_items' => $total,
			'items'       => $items,
		] );
	}

	/**
	 * The owners of the contacts that the user can see, the same rules as view_contact in Main_Roles::map_meta_cap():
	 * their own, and someone else's when they can view others' contacts and the owner is in their team, if they have one.
	 *
	 * @return int[]|null|false the IDs of the owners, null when they can see every contact, false when there's no user
	 */
	protected function visible_owner_ids() {

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$owners = [ $user_id ];

		if ( current_user_can( 'view_others_contacts' ) ) {

			$team = get_team_ids( $user_id );

			// with no team they can see every contact
			if ( empty( $team ) ) {
				return null;
			}

			$owners = array_unique( array_merge( $owners, array_map( 'absint', $team ) ) );
		}

		return array_values( $owners );
	}

	/**
	 * Limit a query of messages to those on contacts that the user can see, by who owns them, see visible_owner_ids()
	 *
	 * @param Table_Query $query
	 *
	 * @return bool false if there's nothing that they can see
	 */
	protected function scope_to_visible_contacts( Table_Query $query ) {

		$owners = $this->visible_owner_ids();

		if ( $owners === false ) {
			return false;
		}

		// every contact
		if ( $owners === null ) {
			return true;
		}

		$contacts = new Table_Query( 'contacts' );
		$contacts->setSelect( 'ID' );
		$contacts->where()->in( 'owner_id', $owners );

		$query->where()->equals( 'object_type', 'contact' );
		$query->where()->in( 'object_id', $contacts );

		return true;
	}

	/**
	 * The contacts that have received messages that have not been read, the one that was received last first.
	 *
	 * Each is a contact with how many messages there are to read and the last of them, without its body. What a user
	 * can see is what they can see of the contact, and by default it's the contacts that they own, `scope=all` is
	 * everything that they have access to.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function read_unread( WP_REST_Request $request ) {

		global $wpdb;

		$scope = $request->get_param( 'scope' ) === 'all' ? 'all' : 'mine';
		$limit = min( 50, max( 1, absint( $request->get_param( 'limit' ) ?: 20 ) ) );

		$messages = get_db( 'messages' )->get_table_name();
		$contacts = get_db( 'contacts' )->get_table_name();

		// Whose contacts, before the limit, so that it's what the user can see that is counted. The contact has to exist,
		// what's left of a deleted one isn't a reply that anyone can read.
		$owners = $scope === 'mine' ? [ get_current_user_id() ] : $this->visible_owner_ids();

		if ( $owners === false || $owners === [ 0 ] ) {
			return self::SUCCESS_RESPONSE( [
				'items'    => [],
				'has_more' => false,
				'scope'    => $scope,
			] );
		}

		$args = [];
		$join = "INNER JOIN $contacts c ON c.ID = m.object_id";

		// null is every contact
		if ( $owners !== null ) {
			$join .= ' AND c.owner_id IN (' . implode( ',', array_fill( 0, count( $owners ), '%d' ) ) . ')';
			$args  = array_map( 'absint', $owners );
		}

		$items    = [];
		$has_more = false;
		$offset   = 0;
		$batch    = $limit + 1; // one more than is wanted says that there's more

		// What's in the query is what the user can see of the contact, but the message has its own say, so it's paged
		// until there's enough that can be seen, or there's no more, and not a set number of them and then what's seen of those
		for ( $page = 0; $page < 20 && ! $has_more; $page ++ ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT m.object_id, COUNT(*) AS unread, MAX(m.ID) AS latest_id
				FROM $messages m $join
				WHERE m.object_type = 'contact' AND m.direction = 'inbound' AND m.is_read = 0
				GROUP BY m.object_id
				ORDER BY latest_id DESC
				LIMIT %d OFFSET %d",
				array_merge( $args, [ $batch, $offset ] )
			) );
			// phpcs:enable

			foreach ( $rows as $row ) {

				$latest = new Message( (int) $row->latest_id );

				if ( ! $latest->exists() || ! current_user_can( 'view_message', $latest ) ) {
					continue;
				}

				if ( count( $items ) >= $limit ) {
					$has_more = true;
					break;
				}

				$contact = $latest->get_associated_object();

				$array = $latest->get_as_array();
				unset( $array['data']['content'] ); // it's for the preview, and what it says is loaded when it's opened

				$items[] = [
					'object_type' => 'contact',
					'object_id'   => (int) $row->object_id,
					'unread'      => (int) $row->unread,
					'contact'     => [
						'ID'     => (int) $row->object_id,
						'name'   => $contact->get_full_name() ?: $contact->get_email(),
						'email'  => $contact->get_email(),
						'avatar' => $contact->get_profile_picture( 96 ),
					],
					'latest'      => $array,
				];
			}

			// that was all of them
			if ( count( $rows ) < $batch ) {
				break;
			}

			$offset += $batch;
		}

		return self::SUCCESS_RESPONSE( [
			'items'    => $items,
			'has_more' => $has_more,
			'scope'    => $scope,
		] );
	}

	/**
	 * Mark what has been received as read, or as not read. It's one message when there's a message_id. Or it's what has
	 * been received from a contact: everything is read at once, and not read is the last message, so that there's
	 * something to see in the list and that it's the newest that's looked at.
	 *
	 * @param WP_REST_Request $request message_id, or object_type (only a contact) and object_id, and read (default true)
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function mark_read( WP_REST_Request $request ) {

		$read = $request->has_param( 'read' ) ? filter_var( $request->get_param( 'read' ), FILTER_VALIDATE_BOOLEAN ) : true;

		if ( $request->get_param( 'message_id' ) ) {
			return $this->mark_message_read( absint( $request->get_param( 'message_id' ) ), $read );
		}

		$object_type = sanitize_key( $request->get_param( 'object_type' ) ?: 'contact' );
		$object_id   = absint( $request->get_param( 'object_id' ) );

		if ( ! $object_id || $object_type !== 'contact' ) {
			return self::ERROR_400( 'invalid_object', 'A contact is needed.' );
		}

		$db = get_db( 'messages' );

		$where = [
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'direction'   => 'inbound',
		];

		$received = $db->query( array_merge( $where, [ 'orderby' => 'ID', 'order' => 'DESC', 'limit' => 1 ] ) );

		// nothing to be read is not a failure
		if ( empty( $received ) ) {
			return self::SUCCESS_RESPONSE( [ 'updated' => 0, 'unread' => 0 ] );
		}

		$latest = new Message( $received[0] );

		if ( ! current_user_can( 'view_message', $latest ) ) {
			return self::ERROR_403( 'cannot_view', 'You do not have permission to view the messages of this contact.' );
		}

		global $wpdb;

		$table = $db->get_table_name();

		// not the query of the table, a 0 there is a filter that's not applied
		if ( $read ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
			$updated = (int) $wpdb->query( $wpdb->prepare( "UPDATE $table SET is_read = 1 WHERE object_type = %s AND object_id = %d AND direction = 'inbound' AND is_read = 0", $object_type, $object_id ) );
		} else {
			$updated = (int) $wpdb->update( $table, [ 'is_read' => 0 ], [ 'ID' => $latest->get_id(), 'is_read' => 1 ], [ '%d' ], [ '%d', '%d' ] );
		}

		$db->cache_set_last_changed();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
		$unread = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE object_type = %s AND object_id = %d AND direction = 'inbound' AND is_read = 0", $object_type, $object_id ) );

		return self::SUCCESS_RESPONSE( [
			'updated' => $updated,
			'unread'  => $unread,
		] );
	}

	/**
	 * One message that was received, read or not
	 *
	 * @param int  $message_id
	 * @param bool $read
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	protected function mark_message_read( int $message_id, bool $read ) {

		global $wpdb;

		$message = new Message( $message_id );

		if ( ! $message->exists() ) {
			return self::ERROR_400( 'invalid_message', 'Not a message.' );
		}

		if ( ! $message->is_inbound() ) {
			return self::ERROR_400( 'not_received', 'Only a message that was received can be read or not read.' );
		}

		if ( ! current_user_can( 'view_message', $message ) ) {
			return self::ERROR_403( 'cannot_view', 'You do not have permission to view this message.' );
		}

		$db    = get_db( 'messages' );
		$table = $db->get_table_name();

		$updated = (int) $wpdb->update( $table, [ 'is_read' => $read ? 1 : 0 ], [ 'ID' => $message->get_id() ], [ '%d' ], [ '%d' ] );

		$db->cache_set_last_changed();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
		$unread = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE object_type = %s AND object_id = %d AND direction = 'inbound' AND is_read = 0", $message->object_type, $message->object_id ) );

		return self::SUCCESS_RESPONSE( [
			'updated' => $updated,
			'is_read' => (int) $read,
			'unread'  => $unread,
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
	 * the events table. Pages are cursor based, newest first by time, then messages before automated emails,
	 * then the highest ID. The cursor is the last item of the page: `before` is its unix timestamp,
	 * `before_kind` is its kind (message or event) and `before_id` is its ID, and the next page is what's
	 * older than it, so a page that's all the same second still moves on. With only `before`, it's what's at
	 * that time or older. The response gives the cursor of the following page in `next`, that way the two
	 * sources page together correctly.
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
		$cursor    = [
			'kind' => in_array( $request->get_param( 'before_kind' ), [ 'message', 'event' ], true ) ? $request->get_param( 'before_kind' ) : '',
			'id'   => absint( $request->get_param( 'before_id' ) ),
		];
		$direction = in_array( $request->get_param( 'direction' ), [ 'inbound', 'outbound' ], true ) ? $request->get_param( 'direction' ) : '';
		$search    = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

		// automated emails only exist for contacts, are always "sent", and can't be searched (the content isn't stored)
		$automated = rest_sanitize_boolean( $request->get_param( 'include_automated' ) )
		             && $object_type === 'contact'
		             && $direction !== 'inbound'
		             && ! $search
		             && current_user_can( 'view_contact', $object );

		$messages = $this->query_messages( $object_type, $object_id, $before, $limit, $direction, $search, $cursor );
		$events   = $automated ? $this->query_automated_emails( $object, $before, $limit, $cursor ) : [];

		$fetched_full_page = count( $messages ) >= $limit || count( $events ) >= $limit;

		$items = array_merge( $messages, $events );

		// the order the pages are in, a message is before an automated email at the same time, IDs of the two aren't comparable
		usort( $items, fn( $a, $b ) => [ $b['timestamp'], (int) ( $b['kind'] === 'message' ), $b['ID'] ] <=> [ $a['timestamp'], (int) ( $a['kind'] === 'message' ), $a['ID'] ] );

		$has_more = count( $items ) > $limit || $fetched_full_page;
		$items    = array_slice( $items, 0, $limit );
		$last     = $items ? end( $items ) : null;

		return self::SUCCESS_RESPONSE( [
			'items'       => $items,
			'has_more'    => $has_more,
			'next_before' => $last ? $last['timestamp'] : null,
			'next'        => $last ? [
				'before'      => $last['timestamp'],
				'before_kind' => $last['kind'],
				'before_id'   => $last['ID'],
			] : null,
		] );
	}

	/**
	 * @return array[]
	 */
	protected function query_messages( $object_type, $object_id, $before, $limit, $direction, $search, array $cursor = [] ) {

		global $wpdb;

		$table = get_db( 'messages' )->get_table_name();

		$time  = gmdate( 'Y-m-d H:i:s', $before );
		$where = [ 'object_type = %s', 'object_id = %d' ];
		$args  = [ $object_type, $object_id ];

		if ( ( $cursor['kind'] ?? '' ) === 'message' && ! empty( $cursor['id'] ) ) {
			// what's after that message, at the same time the ones with a lower ID
			$where[] = '( date_created < %s OR ( date_created = %s AND ID < %d ) )';
			array_push( $args, $time, $time, $cursor['id'] );
		} else if ( ( $cursor['kind'] ?? '' ) === 'event' && ! empty( $cursor['id'] ) ) {
			// messages are before the automated emails of the same time, so the ones at that time were already in a page
			$where[] = 'date_created < %s';
			$args[]  = $time;
		} else {
			$where[] = 'date_created <= %s';
			$args[]  = $time;
		}

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
	protected function query_automated_emails( $contact, $before, $limit, array $cursor = [] ) {

		global $wpdb;

		// older than the item the page starts after: after an automated email, the ones at the same time with a lower ID,
		// after a message, the ones at the same time, they come after messages
		if ( ( $cursor['kind'] ?? '' ) === 'event' && ! empty( $cursor['id'] ) ) {
			$older = $wpdb->prepare( '( e.time < %d OR ( e.time = %d AND e.ID < %d ) )', $before, $before, $cursor['id'] );
		} else {
			$older = $wpdb->prepare( 'e.time <= %d', $before );
		}

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
			WHERE e.contact_id = %d AND e.status = %s AND $older
			AND ( ( e.event_type = %d AND s.step_type = %s ) OR ( e.event_type = %d AND b.object_type = %s ) )
			ORDER BY e.time DESC, e.ID DESC LIMIT %d",
			Event::FUNNEL, Event::BROADCAST,
			$contact->get_id(), Event::COMPLETE,
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
