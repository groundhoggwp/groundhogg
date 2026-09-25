<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;
use function Groundhogg\create_object_from_type;
use function Groundhogg\email_kses;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_sender_profiles;
use function Groundhogg\is_a_contact;
use function Groundhogg\redact;

/**
 * A single sent or received message associated with an object (i.e. a contact)
 */
class Message extends Base_Object {

	const OUTBOUND = 'outbound';
	const INBOUND  = 'inbound';

	const MAX_CONTENT_LENGTH = 1000000;

	/**
	 * The Message-ID that will be given to the next email sent, see use_message_id()
	 *
	 * @var string
	 */
	protected static $next_message_id = '';

	protected function post_setup() {
		// nothing to set up yet
	}

	protected function get_db() {
		return get_db( 'messages' );
	}

	protected function sanitize_columns( $data = [] ) {
		foreach ( $data as $col => &$val ) {
			switch ( $col ) {
				case 'subject':
					$val = sanitize_text_field( $val );
					break;
				case 'direction':
					$val = $val === self::INBOUND ? self::INBOUND : self::OUTBOUND;
					break;
			}
		}

		return $data;
	}

	/**
	 * Generate a Message-ID for an outgoing email. It starts with a random id, which is what a reply address for the
	 * email is signed for, see Inbox::reply_to().
	 *
	 * @return string like <abcdefghijklmnop@example.com>
	 */
	public static function generate_message_id() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'localhost';

		return sprintf( '<%s@%s>', Inbox::new_id(), $host );
	}

	/**
	 * Give the next email that is sent a Message-ID of our choosing, and get it back to store on the message.
	 *
	 * Sending integrations do not all report the ID of the message they sent, so instead we assign the ID up
	 * front. Anything sending through PHPMailer will use it as is, a service that rewrites the ID will
	 * not, in which case a reply is matched by the sender's address instead of by In-Reply-To.
	 *
	 * Call release_message_id() once the email has been sent.
	 *
	 * @return string
	 */
	public static function use_message_id() {
		self::$next_message_id = self::generate_message_id();
		add_action( 'phpmailer_init', [ self::class, 'apply_message_id' ] );

		return self::$next_message_id;
	}

	public static function release_message_id() {
		self::$next_message_id = '';
		remove_action( 'phpmailer_init', [ self::class, 'apply_message_id' ] );
	}

	/**
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
	 */
	public static function apply_message_id( $phpmailer ) {
		if ( self::$next_message_id ) {
			$phpmailer->MessageID = self::$next_message_id;
		}
	}

	/**
	 * Record a composed (one-off, sent by a person) email against a contact.
	 * The subject and content are redacted before they're stored.
	 *
	 * Only composed emails and received replies are stored as messages. Flow and broadcast
	 * emails already have an event record, so they are deliberately not duplicated here.
	 *
	 * @param \Groundhogg\Contact $contact
	 * @param array               $args subject, content, from_address, user_id, email_log_id, message_id...
	 *
	 * @return Message|false
	 */
	public static function record_composed_email( $contact, array $args = [] ) {

		if ( ! $contact || ! $contact->exists() ) {
			return false;
		}

		$args = wp_parse_args( $args, [
			'subject'      => '',
			'content'      => '',
			'from_address' => '',
			'user_id'      => get_current_user_id(),
			'email_log_id' => 0,
			'message_id'   => '',
		] );

		$message = new self();

		$created = $message->create( array_merge( $args, [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'direction'   => self::OUTBOUND,
			'to_address'  => $contact->get_email(),
			'subject'     => redact( (string) $args['subject'] ),
			'content'     => redact( (string) $args['content'] ),
			'thread_id'   => $args['message_id'], // this starts a thread
			'status'      => 'sent',
		] ) );

		return $created ? $message : false;
	}

	/**
	 * Ingest a message, from any transport (webhook, IMAP, ...).
	 *
	 * Transports only have to produce the normalized payload, everything else happens here:
	 * filtering out automatic replies, working out which way the message went, finding what it belongs to,
	 * ignoring duplicates, threading and storing it.
	 *
	 * Payload keys:
	 *  - from        string|array  "Name <a@b.com>", "a@b.com" or [ 'email' =>, 'name' => ]
	 *  - to, cc      string|array  the recipients, an address, a header value like "A <a@b.com>, c@d.com", or a list of them
	 *  - envelope_to string|array  the addresses it was actually delivered to, which is not always the same as to and cc.
	 *                              It's how what was sent to the reply address is known, see Inbox.
	 *  - subject     string
	 *  - html, text  string        the body, either or both
	 *  - message_id  string        the Message-ID of the message
	 *  - in_reply_to string        In-Reply-To
	 *  - references  string|array  References
	 *  - date        int|string    when it was sent
	 *  - headers     array         other headers by name, used to spot automatic replies
	 *
	 * Which way it went depends on who sent it. A message from one of us (see is_ours()) is a copy of something
	 * sent to a contact from outside of the site, like an email BCC'd to an inbox, and is stored as outbound against
	 * the contacts among the recipients, and if there aren't any it's not stored. A message from anyone else is a
	 * received message, and is stored as inbound against the contact that wrote it.
	 *
	 * The object a message belongs to is found from In-Reply-To/References first, when it's a reply to a message we
	 * have, and otherwise from the addresses. Anything not matched is not stored.
	 *
	 * A copy that was sent to several contacts is stored once for each of them. The first is returned.
	 *
	 * Attachments are not handled yet.
	 *
	 * @param array $payload
	 *
	 * @return Message|WP_Error error codes: invalid, ignored, invalid_token, duplicate, no_match, failed
	 */
	public static function ingest( array $payload ) {

		$payload = wp_parse_args( $payload, [
			'from'        => '',
			'to'          => '',
			'cc'          => '',
			'envelope_to' => '',
			'subject'     => '',
			'html'        => '',
			'text'        => '',
			'message_id'  => '',
			'in_reply_to' => '',
			'references'  => '',
			'date'        => '',
			'headers'     => [],
		] );

		[ $from ] = self::parse_address( $payload['from'] );

		if ( ! is_email( $from ) ) {
			return new WP_Error( 'invalid', 'A valid sender is required.' );
		}

		$html = trim( (string) $payload['html'] );
		$text = trim( (string) $payload['text'] );

		if ( $html === '' && $text === '' ) {
			return new WP_Error( 'invalid', 'The message has no content.' );
		}

		$subject = sanitize_text_field( (string) $payload['subject'] );

		/**
		 * Whether the message is an automatic reply (out of office, bounce, ...). Those are not conversation.
		 *
		 * @param bool  $automated
		 * @param array $payload
		 */
		if ( apply_filters( 'groundhogg/message/ingest/is_automated', self::is_automated( $from, $subject, (array) $payload['headers'] ), $payload ) ) {
			return new WP_Error( 'ignored', 'The message is an automatic reply.' );
		}

		$recipients = self::parse_addresses( [ $payload['to'], $payload['cc'] ] );
		$envelope   = self::parse_addresses( $payload['envelope_to'] );

		// A message to the reply address is only good if the address has a token that we signed. That address is
		// in the Reply-To of everything the site sends, so anyone that's had an email has seen it, and can say
		// they're anyone. Without the token there's nothing to say that it's a reply to anything.
		$reply_id = Inbox::verify_reply( $envelope );
		$route    = Inbox::route( $envelope );

		// an address that was replaced, or that isn't this site's
		if ( $route === 'unknown' ) {
			return new WP_Error( 'no_match', 'The message was not sent to an address of this inbox.' );
		}

		if ( $route === 'reply' && ! $reply_id ) {
			return new WP_Error( 'invalid_token', 'The reply address does not have a valid token.' );
		}

		$objects = [];
		$parent  = null;

		// From us, so a copy of what was sent to someone. Never a message received, even if we're a contact too, which
		// we are when the users of the site are synced to contacts. The copy is of what we sent to someone else.
		$direction = self::is_ours( $from ) ? self::OUTBOUND : self::INBOUND;

		if ( $direction === self::OUTBOUND ) {

			// the ones it was sent to, not us and not colleagues that were copied in
			$to = array_values( array_filter( $recipients, fn( $address ) => $address !== $from && ! self::is_ours( $address ) ) );

			foreach ( $to as $address ) {
				$contact = get_contactdata( $address );
				if ( is_a_contact( $contact ) ) {
					$objects[ $contact->get_id() ] = $contact;
				}
			}

			// the reply address says what it's a reply to even when the headers don't, a colleague that answers from their
			// own mailbox writes to it, not to the contact
			$parent = self::find_parent( $payload['in_reply_to'], $payload['references'], $to )
			          ?? ( $reply_id ? self::find_parent_by_reply_id( $reply_id, $to ) : null );

			// sent to an address that isn't theirs, but in reply to something that is
			if ( empty( $objects ) && $parent && self::is_object( $parent->get_associated_object() ) ) {
				$object                       = $parent->get_associated_object();
				$objects[ $object->get_id() ] = $object;
			}
		}

		// From someone else, a message received
		if ( $direction === self::INBOUND ) {

			$parent = ( $reply_id ? self::find_parent_by_reply_id( $reply_id, [ $from ] ) : null )
			          ?? self::find_parent( $payload['in_reply_to'], $payload['references'], [ $from ] );

			if ( $parent ) {
				$object = $parent->get_associated_object();
			} else {
				$contact = get_contactdata( $from );
				$object  = is_a_contact( $contact ) ? $contact : null;
			}

			$objects = $object ? [ $object ] : [];
		}

		/**
		 * Filter the objects the message will be associated with. This is the place to, for example,
		 * create a contact for a sender that isn't one yet, or to match on something other than the address.
		 *
		 * @param \Groundhogg\DB_Object[] $objects   empty if nothing was matched
		 * @param array                   $payload
		 * @param Message|null            $parent    the message this is a reply to, if found
		 * @param string                  $direction inbound|outbound
		 */
		$objects = apply_filters( 'groundhogg/message/ingest/objects', array_values( $objects ), $payload, $parent, $direction );
		$objects = array_values( array_filter( (array) $objects, [ self::class, 'is_object' ] ) );

		if ( empty( $objects ) ) {
			return new WP_Error( 'no_match', 'The message could not be matched to anything.' );
		}

		$message_id  = self::parse_message_ids( $payload['message_id'] )[0] ?? '';
		$in_reply_to = self::parse_message_ids( $payload['in_reply_to'] )[0] ?? '';

		// Transports can deliver the same message more than once
		if ( $message_id ) {
			$objects = array_values( array_filter( $objects, fn( $object ) => ! get_db( 'messages' )->exists( [
				'message_id'  => $message_id,
				'direction'   => $direction,
				'object_type' => $object->_get_object_type(),
				'object_id'   => $object->get_id(),
			] ) ) );

			if ( empty( $objects ) ) {
				return new WP_Error( 'duplicate', 'This message was already received.' );
			}
		}

		$content = $html !== '' ? email_kses( $html ) : sanitize_textarea_field( $text );
		$content = mb_substr( $content, 0, self::MAX_CONTENT_LENGTH );

		$outbound = $direction === self::OUTBOUND;

		// anything we sent is redacted, the same as a composed email
		$stored_subject = $outbound ? redact( $subject ) : $subject;
		$stored_content = $outbound ? redact( $content ) : $content;

		$sender = get_user_by( 'email', $from );
		$date   = self::parse_date( $payload['date'] );

		$messages = [];

		foreach ( $objects as $object ) {

			// group it with the conversation it belongs to
			$same_object = $parent
			               && $parent->object_type === $object->_get_object_type()
			               && (int) $parent->object_id === (int) $object->get_id();

			if ( $same_object ) {
				$thread_id = $parent->thread_id ?: $parent->message_id;
			} else {
				$thread_id = self::find_thread_by_subject( $object, $subject );
			}

			$message = new self();

			$created = $message->create( [
				'object_type'  => $object->_get_object_type(),
				'object_id'    => $object->get_id(),
				'direction'    => $direction,
				'user_id'      => $outbound && $sender ? $sender->ID : 0,
				'from_address' => $from,
				'to_address'   => $outbound && method_exists( $object, 'get_email' ) ? $object->get_email() : ( $recipients[0] ?? '' ),
				'subject'      => $stored_subject,
				'content'      => $stored_content,
				'message_id'   => $message_id,
				'in_reply_to'  => $in_reply_to,
				'thread_id'    => $thread_id ?: $message_id,
				'status'       => $outbound ? 'sent' : 'received',
				'date_created' => $date,
			] );

			if ( $created ) {
				$messages[] = [ $message, $object ];
			}
		}

		if ( empty( $messages ) ) {
			return new WP_Error( 'failed', 'The message could not be saved.' );
		}

		foreach ( $messages as [ $message, $object ] ) {

			if ( $outbound ) {

				/**
				 * A copy of a message that was sent from outside of the site was logged
				 *
				 * @param Message                $message
				 * @param \Groundhogg\DB_Object  $object  the contact (or other object) it was sent to
				 * @param array                  $payload the payload as it was given
				 */
				do_action( 'groundhogg/message/logged', $message, $object, $payload );

				continue;
			}

			/**
			 * A message was received
			 *
			 * @param Message               $message
			 * @param \Groundhogg\DB_Object $object  the contact (or other object) it was matched to
			 * @param array                 $payload the payload as it was given
			 */
			do_action( 'groundhogg/message/received', $message, $object, $payload );
		}

		return $messages[0][0];
	}

	/**
	 * Whether an address is one of ours, so that mail from it is something we sent, and not something received.
	 *
	 * That's anyone on the site that can work with contacts, or an address the site sends from. A customer that also
	 * has a user account is not one of us, only people that can view contacts are.
	 *
	 * @param string $address
	 *
	 * @return bool
	 */
	public static function is_ours( $address ) {

		$address = strtolower( trim( (string) $address ) );

		if ( ! is_email( $address ) ) {
			return false;
		}

		$user = get_user_by( 'email', $address );

		if ( $user && user_can( $user, 'view_contacts' ) ) {
			return true;
		}

		$senders = [ strtolower( get_default_from_email() ) ];

		foreach ( get_sender_profiles() as $profile ) {
			$senders[] = strtolower( (string) ( $profile['from_email'] ?? '' ) ); // one with a merge tag isn't an address, it's skipped by is_email
		}

		/**
		 * Whether an address is one of ours
		 *
		 * @param bool   $is_ours
		 * @param string $address
		 */
		return (bool) apply_filters( 'groundhogg/message/ingest/is_ours', in_array( $address, array_filter( $senders, 'is_email' ), true ), $address );
	}

	/**
	 * @param mixed $object
	 *
	 * @return bool whether it's a real object that a message can be associated with
	 */
	protected static function is_object( $object ) {
		return is_object( $object ) && method_exists( $object, 'exists' ) && $object->exists();
	}

	/**
	 * Every email address in a recipient header, or an address, or a list of either.
	 * "A <a@b.com>, c@d.com" is [ a@b.com, c@d.com ].
	 *
	 * @param string|array $value
	 *
	 * @return string[] lowercase, without duplicates
	 */
	public static function parse_addresses( $value ) {

		$found = [];

		if ( is_array( $value ) ) {

			// [ 'email' => , 'name' => ]
			if ( isset( $value['email'] ) && is_string( $value['email'] ) ) {
				$value = [ $value['email'] ];
			}

			foreach ( $value as $item ) {
				$found = array_merge( $found, self::parse_addresses( $item ) );
			}

			return array_values( array_unique( $found ) );
		}

		if ( ! is_string( $value ) || $value === '' ) {
			return [];
		}

		if ( preg_match_all( '/[A-Za-z0-9._%+\-\']+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', $value, $matches ) ) {
			$found = array_map( 'strtolower', $matches[0] );
		}

		return array_values( array_unique( array_filter( $found, 'is_email' ) ) );
	}

	/**
	 * Split an address into [ email, name ]
	 *
	 * @param string|array $address
	 *
	 * @return string[]
	 */
	public static function parse_address( $address ) {

		if ( is_array( $address ) ) {
			return [
				sanitize_email( strtolower( trim( $address['email'] ?? '' ) ) ),
				sanitize_text_field( $address['name'] ?? '' ),
			];
		}

		$address = trim( (string) $address );
		$name    = '';

		if ( preg_match( '/^"?([^"<]*?)"?\s*<([^>]+)>$/', $address, $matches ) ) {
			$name    = $matches[1];
			$address = $matches[2];
		}

		return [ sanitize_email( strtolower( trim( $address ) ) ), sanitize_text_field( $name ) ];
	}

	/**
	 * Pull the message IDs out of a Message-ID, In-Reply-To or References header value, in order.
	 * Always in the <id> form that we store.
	 *
	 * @param string|array $value
	 *
	 * @return string[]
	 */
	public static function parse_message_ids( $value ) {

		$value = trim( is_array( $value ) ? implode( ' ', $value ) : (string) $value );

		if ( $value === '' ) {
			return [];
		}

		if ( preg_match_all( '/<[^<>\s]+>/', $value, $matches ) ) {
			return $matches[0];
		}

		// a bare id
		return [ '<' . trim( $value, '<> ' ) . '>' ];
	}

	/**
	 * @param string $value
	 *
	 * @return string GMT mysql datetime, now if the date is missing, invalid or in the future
	 */
	protected static function parse_date( $value ) {

		$time = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );

		if ( ! $time || $time > time() + DAY_IN_SECONDS ) {
			$time = time();
		}

		return gmdate( 'Y-m-d H:i:s', $time );
	}

	/**
	 * Whether this looks like an automatic reply or a bounce, rather than a person writing back
	 *
	 * @param string $from
	 * @param string $subject
	 * @param array  $headers
	 *
	 * @return bool
	 */
	protected static function is_automated( $from, $subject, array $headers ) {

		$headers = array_change_key_case( array_map( fn( $v ) => is_array( $v ) ? implode( ',', $v ) : (string) $v, $headers ) );

		$auto_submitted = strtolower( trim( $headers['auto-submitted'] ?? '' ) );

		if ( $auto_submitted !== '' && $auto_submitted !== 'no' ) {
			return true;
		}

		if ( in_array( strtolower( trim( $headers['precedence'] ?? '' ) ), [ 'bulk', 'junk', 'list', 'auto_reply' ], true ) ) {
			return true;
		}

		if ( isset( $headers['x-autoreply'] ) || isset( $headers['x-autorespond'] ) ) {
			return true;
		}

		$local = strstr( $from, '@', true );

		if ( in_array( $local, [ 'mailer-daemon', 'postmaster' ], true ) ) {
			return true;
		}

		return (bool) preg_match( '/^\s*(automatic reply|auto(matic)?[\s-]*reply|autoreply|out of (the )?office|undeliverable|delivery status notification|mail delivery (failed|failure))/i', $subject );
	}

	/**
	 * Find the message we have that this is a reply to, by In-Reply-To and References. That's a message we sent, or
	 * one we received, since the person that answers can just as well be answering what someone else wrote.
	 *
	 * More than one message can have the same Message-ID. A composed email to several contacts is stored once per
	 * contact. Of those, the one with the person that's mentioned is preferred.
	 *
	 * @param string|array $in_reply_to
	 * @param string|array $references
	 * @param string[]     $prefer the addresses that the message is likely to be to or from, when there's a choice
	 *
	 * @return Message|null
	 */
	protected static function find_parent( $in_reply_to, $references, array $prefer = [] ) {

		// the most recent ancestor first
		$candidates = array_unique( array_merge(
			self::parse_message_ids( $in_reply_to ),
			array_reverse( self::parse_message_ids( $references ) )
		) );

		foreach ( array_slice( $candidates, 0, 10 ) as $id ) {

			$rows = get_db( 'messages' )->query( [ 'message_id' => $id ] );

			if ( empty( $rows ) ) {
				continue;
			}

			return self::pick_parent( $rows, $prefer );
		}

		return null;
	}

	/**
	 * Find the email that a reply address was made for. The address is made for the id that the Message-ID starts with,
	 * which is all that's left to go on when the service that sent the email changed the rest of it.
	 *
	 * @param string   $id     the id from the token, that has already been verified
	 * @param string[] $prefer the addresses that the message is likely to be to or from, when there's a choice
	 *
	 * @return Message|null
	 */
	protected static function find_parent_by_reply_id( string $id, array $prefer = [] ) {

		global $wpdb;

		$table = get_db( 'messages' )->get_table_name();
		$like  = $wpdb->esc_like( '<' . $id . '@' ) . '%';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE message_id LIKE %s AND direction = %s LIMIT 50", $like, self::OUTBOUND ) );

		return $rows ? self::pick_parent( $rows, $prefer ) : null;
	}

	/**
	 * Of the messages that have the same Message-ID, the one to use. A composed email to several contacts is stored
	 * once per contact, and the one with the person that's mentioned is preferred.
	 *
	 * @param object[] $rows   at least one
	 * @param string[] $prefer the addresses that the message is likely to be to or from
	 *
	 * @return Message
	 */
	protected static function pick_parent( array $rows, array $prefer ) {

		$messages = array_map( fn( $row ) => new self( $row ), $rows );

		foreach ( $messages as $message ) {

			// the one on the other end of the message
			$other = strtolower( $message->is_inbound() ? $message->from_address : $message->to_address );

			if ( in_array( $other, $prefer, true ) ) {
				return $message;
			}
		}

		// someone else, an alias or a forward
		return $messages[0];
	}

	/**
	 * When there's no In-Reply-To to go on, a reply with the same subject as something we
	 * recently sent to them belongs to the same conversation.
	 *
	 * @param \Groundhogg\DB_Object $object
	 * @param string                $subject
	 *
	 * @return string the thread ID, or empty if there isn't a match
	 */
	protected static function find_thread_by_subject( $object, $subject ) {

		$subject = self::normalize_subject( $subject );

		if ( $subject === '' ) {
			return '';
		}

		$rows = get_db( 'messages' )->query( [
			'object_type' => $object->_get_object_type(),
			'object_id'   => $object->get_id(),
			'direction'   => self::OUTBOUND,
			'limit'       => 10,
			'orderby'     => 'date_created',
			'order'       => 'DESC',
		] );

		foreach ( $rows as $row ) {
			if ( self::normalize_subject( $row->subject ) === $subject ) {
				return $row->thread_id ?: $row->message_id;
			}
		}

		return '';
	}

	/**
	 * Take the Re: and Fwd: off a subject
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function normalize_subject( $subject ) {
		return strtolower( trim( preg_replace( '/^(\s*(re|fwd?|aw|sv)\s*:\s*)+/i', '', (string) $subject ) ) );
	}

	/**
	 * Cut the quoted history out of a reply, so only what the person actually wrote is left
	 *
	 * @param string $content plain text or HTML
	 *
	 * @return string plain text
	 */
	public static function extract_reply( $content ) {

		$content = (string) $content;

		// where the quoted part of an HTML email starts, as written by the common clients
		$html_markers = [ '<blockquote', '<div class="gmail_quote', '<div class="gmail_attr', '<div class="yahoo_quoted', 'id="divRplyFwdMsg"', 'id="appendonsend"', '<div class="moz-cite-prefix' ];

		foreach ( $html_markers as $marker ) {
			$position = stripos( $content, $marker );
			if ( $position !== false ) {
				$content = substr( $content, 0, $position );
			}
		}

		// keep the line breaks that tags imply, so that the text markers below can be found
		$text = preg_replace( '#<br\s*/?>|</(p|div|tr|li|h[1-6])>#i', "\n", $content );
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
		$text = str_replace( [ "\r\n", "\r" ], "\n", $text );

		$text_markers = [
			'/^\s*On\s.{1,300}?wrote:.*$/ims',                  // On Tue, 3 Sep 2026, Jordan wrote:
			'/^\s*-{2,}\s*Original Message\s*-{2,}.*$/ims',    // -----Original Message-----
			'/^\s*_{10,}\s*\n\s*From:.*$/ims',                  // Outlook
			'/^\s*From:\s.+\n\s*(Sent|Date):.*$/ims',           // Outlook without the rule
			'/^>.*$/ims',                                       // quoted lines
		];

		foreach ( $text_markers as $pattern ) {
			$text = preg_replace( $pattern, '', $text );
		}

		return trim( $text );
	}

	public function is_inbound() {
		return $this->direction === self::INBOUND;
	}

	/**
	 * Gets the related object
	 *
	 * @return \Groundhogg\DB_Object|\Groundhogg\DB_Object_With_Meta|null
	 */
	public function get_associated_object() {
		return create_object_from_type( $this->object_id, $this->object_type );
	}

	/**
	 * A short plain text preview of the message body, for the message bubble.
	 * For a reply that's what was written, not what was quoted.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function get_preview( $length = 200 ) {

		$text = $this->is_inbound() ? self::extract_reply( $this->content ) : '';

		// not a reply, or it was nothing but quoted text
		if ( $text === '' ) {
			// wp_strip_all_tags() drops <style> and <script> along with their contents
			$text = html_entity_decode( wp_strip_all_tags( $this->content ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}

		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

		return wp_html_excerpt( $text, $length, '…' );
	}

	public function get_as_array() {

		// stored in UTC, DateTimeHelper reads a date as being in the time zone of the site
		$timestamp = ( new \DateTime( $this->date_created, new \DateTimeZone( 'UTC' ) ) )->getTimestamp();

		return array_merge( parent::get_as_array(), [
			'timestamp' => $timestamp,
			'preview'   => $this->get_preview(),
			'i18n'      => [
				'time_diff' => human_time_diff( $timestamp, time() ),
			],
		] );
	}

	/**
	 * The message for the contact's timeline. That's a line of text, so it has who it was from and when, and not what
	 * it said, which can be a lot. That's read when the message is opened.
	 *
	 * @param \Groundhogg\Contact|null $contact whose timeline it is, to say it was them when it was
	 *
	 * @return array
	 */
	public function get_timeline_array( $contact = null ) {

		$array = $this->get_as_array();

		unset( $array['data']['content'], $array['preview'] );

		$date = new DateTimeHelper( (int) $array['timestamp'] );

		$sent_by = $this->is_inbound() ? false : get_userdata( (int) $this->user_id );
		$sender  = $this->from_address;

		// what the person that got it would call them
		if ( $this->is_inbound() && $contact && strtolower( (string) $contact->get_email() ) === strtolower( $this->from_address ) ) {
			$sender = $contact->get_full_name() ?: $this->from_address;
		}

		$array['i18n'] = array_merge( $array['i18n'], [
			'diff_time' => $date->wi18n(),
			'ymdhis'    => $date->ymdhis(),
			// who sent what we sent, and who wrote what we got
			'sent_by'   => $sent_by ? $sent_by->display_name : $this->from_address,
			'sender'    => $sender,
		] );

		return $array;
	}
}
