<?php

use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see Message::ingest() receiving a message from any transport: matching, threading, dedupe and
 *      the automatic reply filter.
 * @see Message::use_message_id() assigning our own Message-ID to outgoing email.
 * @see Message::extract_reply() cutting the quoted history out of a reply.
 * @see \Groundhogg\Main_Roles::map_meta_cap() the view_message/edit_message/delete_message caps.
 */
class Message_Ingest_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and Message-ID is unique
	 * to the run, otherwise a test would match what an earlier one left behind.
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
	}

	/** an email address that is unique to this run */
	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	/** a Message-ID that is unique to this run */
	protected function mid( $name ) {
		return '<' . $name . '-' . $this->run_id . '@example.com>';
	}

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/message/ingest/objects' );
		remove_all_filters( 'groundhogg/message/ingest/is_ours' );
		remove_all_filters( 'groundhogg/message/ingest/is_automated' );
		Message::release_message_id();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @return \Groundhogg\Contact
	 */
	protected function create_contact( $email, array $args = [] ) {
		return get_contactdata( self::factory()->contacts->create( array_merge( [ 'email' => $email ], $args ) ) );
	}

	/**
	 * A composed email we sent to a contact
	 *
	 * @return Message
	 */
	protected function create_sent( $contact, $message_id, $subject = 'Hello there' ) {
		return Message::record_composed_email( $contact, [
			'subject'      => $subject,
			'content'      => '<p>Hi!</p>',
			'from_address' => 'me@example.com',
			'user_id'      => 1,
			'message_id'   => $message_id,
		] );
	}

	/** @return int the number of messages stored against a contact */
	protected function message_count( $contact ) {
		return \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] );
	}

	protected function payload( array $args = [] ) {
		static $n = 0;

		return array_merge( [
			'from'       => $this->addr( 'jordan' ),
			'to'         => 'me@example.com',
			'subject'    => 'Re: Hello there',
			'text'       => 'Thanks!',
			'message_id' => '<reply-' . $this->run_id . '-' . ( ++ $n ) . '@mail.example.com>',
		], $args );
	}

	/* ---------------------------------------------------------------------
	 * matching
	 * ------------------------------------------------------------------- */

	public function test_it_matches_a_contact_by_the_sender_address() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [ 'from' => 'Jordan Rivera <' . ucfirst( $this->addr( 'jordan' ) ) . '>' ] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( 'contact', $message->object_type );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertSame( $this->addr( 'jordan' ), $message->from_address );
		$this->assertSame( 'received', $message->status );
		$this->assertSame( 'Thanks!', $message->content );
	}

	public function test_it_matches_by_in_reply_to_when_the_sender_is_a_different_address() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );
		$sent    = $this->create_sent( $contact, $this->mid( 'sent-1' ) );

		// replied from an alias that isn't on any contact
		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'in_reply_to' => $this->mid( 'sent-1' ),
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $this->mid( 'sent-1' ), $message->in_reply_to );
		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_it_matches_by_references_when_in_reply_to_is_unknown() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );
		$this->create_sent( $contact, $this->mid( 'sent-2' ) );

		$message = Message::ingest( $this->payload( [
			'from'        => 'someone.else@example.org',
			'in_reply_to' => '<a-later-reply-we-never-saw@example.org>',
			'references'  => $this->mid( 'sent-2' ) . ' <a-later-reply-we-never-saw@example.org>',
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	public function test_a_reply_goes_to_the_contact_that_wrote_it_when_the_email_went_to_several() {

		$a = $this->create_contact( $this->addr( 'a' ) );
		$b = $this->create_contact( $this->addr( 'b' ) );

		// one composed email to both is stored once per contact under the same Message-ID
		$this->create_sent( $a, $this->mid( 'shared' ) );
		$this->create_sent( $b, $this->mid( 'shared' ) );

		$message = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'b' ),
			'in_reply_to' => $this->mid( 'shared' ),
		] ) );

		$this->assertEquals( $b->get_id(), $message->object_id );
	}

	public function test_it_does_not_store_what_it_cannot_match() {

		$result = Message::ingest( $this->payload( [ 'from' => 'stranger@example.org' ] ) );

		$this->assertWPError( $result );
		$this->assertSame( 'no_match', $result->get_error_code() );
	}

	public function test_the_objects_can_be_provided_by_a_filter() {

		$contact = $this->create_contact( $this->addr( 'new' ) );

		add_filter( 'groundhogg/message/ingest/objects', fn( $objects ) => $objects ?: [ $contact ] );

		$message = Message::ingest( $this->payload( [ 'from' => 'stranger@example.org' ] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	/* ---------------------------------------------------------------------
	 * rejecting
	 * ------------------------------------------------------------------- */

	public function test_it_ignores_duplicates() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$payload = $this->payload();

		$this->assertInstanceOf( Message::class, Message::ingest( $payload ) );

		$again = Message::ingest( $payload );

		$this->assertWPError( $again );
		$this->assertSame( 'duplicate', $again->get_error_code() );
	}

	public function test_it_requires_a_sender_and_a_body() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$this->assertSame( 'invalid', Message::ingest( $this->payload( [ 'from' => 'not an address' ] ) )->get_error_code() );
		$this->assertSame( 'invalid', Message::ingest( $this->payload( [ 'text' => '   ' ] ) )->get_error_code() );
	}

	public function test_it_ignores_automatic_replies() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$cases = [
			'auto-submitted header' => [ 'headers' => [ 'Auto-Submitted' => 'auto-replied' ] ],
			'bulk precedence'       => [ 'headers' => [ 'Precedence' => 'bulk' ] ],
			'x-autoreply header'    => [ 'headers' => [ 'X-Autoreply' => 'yes' ] ],
			'out of office'         => [ 'subject' => 'Out of Office: Hello there' ],
			'automatic reply'       => [ 'subject' => 'Automatic reply: Hello there' ],
			'undeliverable'         => [ 'subject' => 'Undeliverable: Hello there' ],
		];

		foreach ( $cases as $label => $override ) {
			$result = Message::ingest( $this->payload( $override ) );
			$this->assertWPError( $result, $label );
			$this->assertSame( 'ignored', $result->get_error_code(), $label );
		}

		// Auto-Submitted: no is what a person's mail client sends
		$this->assertInstanceOf( Message::class, Message::ingest( $this->payload( [ 'headers' => [ 'Auto-Submitted' => 'no' ] ] ) ) );
	}

	public function test_it_ignores_mail_from_a_mailer_daemon() {

		$this->create_contact( $this->addr( 'mailer-daemon' ) );

		$this->assertSame( 'ignored', Message::ingest( $this->payload( [ 'from' => strtoupper( $this->addr( 'mailer-daemon' ) ) ] ) )->get_error_code() );
	}

	/* ---------------------------------------------------------------------
	 * threading
	 * ------------------------------------------------------------------- */

	public function test_a_reply_without_a_message_id_match_joins_the_thread_with_the_same_subject() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );
		$sent    = $this->create_sent( $contact, $this->mid( 'sent-3' ), 'Quarterly pricing' );

		$message = Message::ingest( $this->payload( [ 'subject' => 'RE: Re: quarterly pricing' ] ) );

		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_a_new_conversation_starts_its_own_thread() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [
			'subject'    => 'A question',
			'message_id' => $this->mid( 'fresh' ),
		] ) );

		$this->assertSame( $this->mid( 'fresh' ), $message->thread_id );
	}

	public function test_a_composed_email_starts_a_thread() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );
		$sent    = $this->create_sent( $contact, $this->mid( 'sent-4' ) );

		$this->assertSame( $this->mid( 'sent-4' ), $sent->thread_id );
	}

	/* ---------------------------------------------------------------------
	 * storing
	 * ------------------------------------------------------------------- */

	public function test_html_is_sanitized_and_preferred_over_text() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [
			'html' => '<p onclick="steal()">Hi</p><script>alert(1)</script>',
			'text' => 'plain version',
		] ) );

		$this->assertStringContainsString( '<p>Hi</p>', $message->content );
		$this->assertStringNotContainsString( 'script', $message->content );
		$this->assertStringNotContainsString( 'onclick', $message->content );
	}

	public function test_an_unreasonable_date_falls_back_to_now() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [ 'date' => '2099-01-01 00:00:00' ] ) );

		$this->assertLessThan( 5, abs( time() - strtotime( $message->date_created . ' UTC' ) ) );
	}

	public function test_a_given_date_is_kept() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [ 'date' => 'Tue, 01 Sep 2026 15:30:00 +0000' ] ) );

		$this->assertSame( '2026-09-01 15:30:00', $message->date_created );
	}

	public function test_it_fires_an_action_when_a_message_is_received() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );
		$fired   = [];

		add_action( 'groundhogg/message/received', function ( $message, $object ) use ( &$fired ) {
			$fired = [ $message, $object ];
		}, 10, 2 );

		$message = Message::ingest( $this->payload() );

		$this->assertSame( $message->get_id(), $fired[0]->get_id() );
		$this->assertEquals( $contact->get_id(), $fired[1]->get_id() );
	}

	/* ---------------------------------------------------------------------
	 * direction, a message from one of us is a copy of what was sent to a contact
	 * ------------------------------------------------------------------- */

	/**
	 * @return \WP_User someone on the site that works with contacts
	 */
	protected function create_rep( $local = 'adrian' ) {
		return get_user_by( 'id', self::factory()->user->create( [ 'role' => 'sales_rep', 'user_email' => $this->addr( $local ) ] ) );
	}

	public function test_a_copy_of_an_email_sent_by_one_of_us_is_stored_as_outbound_against_who_it_was_sent_to() {

		$rep     = $this->create_rep();
		$contact = $this->create_contact( $this->addr( 'jordan' ) );

		$received = [];
		$logged   = [];
		add_action( 'groundhogg/message/received', function ( $message ) use ( &$received ) {
			$received[] = $message;
		} );
		add_action( 'groundhogg/message/logged', function ( $message, $object ) use ( &$logged ) {
			$logged[] = [ $message, $object ];
		}, 10, 2 );

		$message = Message::ingest( $this->payload( [
			'from'    => 'Adrian <' . $rep->user_email . '>',
			'to'      => 'Jordan Rivera <' . $this->addr( 'jordan' ) . '>',
			'subject' => 'Following up',
			'text'    => 'Just checking in.',
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::OUTBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertEquals( $rep->ID, $message->user_id );
		$this->assertSame( $rep->user_email, $message->from_address );
		$this->assertSame( $this->addr( 'jordan' ), $message->to_address );
		$this->assertSame( 'sent', $message->status );

		// it is not a message that someone should be told about
		$this->assertSame( [], $received );
		$this->assertCount( 1, $logged );
		$this->assertEquals( $contact->get_id(), $logged[0][1]->get_id() );
	}

	public function test_it_finds_the_contacts_in_cc_too_and_stores_a_copy_for_each() {

		$rep = $this->create_rep();
		$a   = $this->create_contact( $this->addr( 'a' ) );
		$b   = $this->create_contact( $this->addr( 'b' ) );

		$payload = $this->payload( [
			'from' => $rep->user_email,
			'to'   => $this->addr( 'a' ) . ', someone.else@example.org',
			'cc'   => [ $this->addr( 'b' ) ],
		] );

		$first = Message::ingest( $payload );

		$this->assertSame( Message::OUTBOUND, $first->direction );
		$this->assertSame( 1, $this->message_count( $a ) );
		$this->assertSame( 1, $this->message_count( $b ) );

		// the same message again is a duplicate for both
		$this->assertSame( 'duplicate', Message::ingest( $payload )->get_error_code() );
	}

	public function test_a_copy_that_was_only_stored_for_some_of_the_contacts_is_stored_for_the_rest() {

		$rep = $this->create_rep();
		$a   = $this->create_contact( $this->addr( 'a' ) );
		$b   = $this->create_contact( $this->addr( 'b' ) );

		$payload = $this->payload( [ 'from' => $rep->user_email, 'to' => $this->addr( 'a' ) ] );
		Message::ingest( $payload );

		// resent, now with b as well
		$payload['cc'] = $this->addr( 'b' );
		$result        = Message::ingest( $payload );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertEquals( $b->get_id(), $result->object_id );
		$this->assertSame( 1, $this->message_count( $a ) );
		$this->assertSame( 1, $this->message_count( $b ) );
	}

	public function test_colleagues_that_were_copied_in_are_not_logged_as_contacts() {

		$rep       = $this->create_rep();
		$colleague = $this->create_rep( 'colleague' );
		$contact   = $this->create_contact( $this->addr( 'jordan' ) );

		// the colleague is a contact too, the way a lot of people on a site are
		$colleague_contact = $this->create_contact( $this->addr( 'colleague' ) );

		Message::ingest( $this->payload( [
			'from' => $rep->user_email,
			'to'   => $this->addr( 'jordan' ),
			'cc'   => $colleague->user_email,
		] ) );

		$this->assertSame( 1, $this->message_count( $contact ) );
		$this->assertSame( 0, $this->message_count( $colleague_contact ) );
	}

	public function test_an_address_the_site_sends_from_is_one_of_us_too() {

		$contact = $this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [
			'from' => strtoupper( \Groundhogg\get_default_from_email() ),
			'to'   => $this->addr( 'jordan' ),
		] ) );

		$this->assertSame( Message::OUTBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	public function test_who_is_one_of_us_can_be_changed_by_a_filter() {

		$this->create_contact( $this->addr( 'jordan' ) );
		$other = $this->addr( 'assistant' );

		add_filter( 'groundhogg/message/ingest/is_ours', fn( $ours, $address ) => $ours || $address === $other, 10, 2 );

		$message = Message::ingest( $this->payload( [ 'from' => $other, 'to' => $this->addr( 'jordan' ) ] ) );

		$this->assertSame( Message::OUTBOUND, $message->direction );
	}

	public function test_a_customer_with_a_user_account_is_not_one_of_us() {

		// a user, but not one that works with contacts, they are a contact
		self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => $this->addr( 'customer' ) ] );
		$contact = $this->create_contact( $this->addr( 'customer' ) );

		$message = Message::ingest( $this->payload( [ 'from' => $this->addr( 'customer' ), 'to' => 'me@example.com' ] ) );

		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( 0, (int) $message->user_id );
	}

	public function test_one_of_us_that_is_also_a_contact_is_never_recorded_as_writing_in_to_ourselves() {

		// users are synced to contacts, so the person that sends is a contact too
		$rep = $this->create_rep();
		$this->assertTrue( \Groundhogg\is_a_contact( get_contactdata( $rep->user_email ) ) );

		$result = Message::ingest( $this->payload( [ 'from' => $rep->user_email, 'to' => 'support@example.org' ] ) );

		$this->assertWPError( $result );
		$this->assertSame( 'no_match', $result->get_error_code() );
		$this->assertSame( 0, $this->message_count( get_contactdata( $rep->user_email ) ) );
	}

	public function test_mail_from_one_of_us_to_no_one_we_know_is_not_stored() {

		$rep = $this->create_rep();

		$result = Message::ingest( $this->payload( [ 'from' => $rep->user_email, 'to' => 'stranger@example.org' ] ) );

		$this->assertSame( 'no_match', $result->get_error_code() );
	}

	public function test_a_reply_from_one_of_us_to_an_address_that_is_not_on_file_goes_with_the_conversation() {

		$rep = $this->create_rep();
		$this->create_contact( $this->addr( 'jordan' ) );
		$contact = get_contactdata( $this->addr( 'jordan' ) );

		// they wrote to us, and we answer at their other address
		$received = Message::ingest( $this->payload( [ 'from' => $this->addr( 'jordan' ), 'message_id' => $this->mid( 'theirs' ) ] ) );

		$message = Message::ingest( $this->payload( [
			'from'        => $rep->user_email,
			'to'          => 'jordan.rivera@work.example.org',
			'in_reply_to' => $this->mid( 'theirs' ),
		] ) );

		$this->assertSame( Message::OUTBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $received->thread_id, $message->thread_id );
	}

	public function test_what_we_send_is_redacted_but_what_we_receive_is_not() {

		$rep = $this->create_rep();
		$this->create_contact( $this->addr( 'jordan' ) );

		$secret = 'hunter2-' . $this->run_id;
		\Groundhogg\redactor()->add( $secret, '[redacted]' );

		$sent     = Message::ingest( $this->payload( [ 'from' => $rep->user_email, 'to' => $this->addr( 'jordan' ), 'text' => "the password is $secret" ] ) );
		$received = Message::ingest( $this->payload( [ 'text' => "the password is $secret" ] ) );

		$this->assertSame( 'the password is [redacted]', $sent->content );
		$this->assertSame( "the password is $secret", $received->content );
	}

	public function test_it_reads_the_addresses_out_of_recipient_headers() {

		$this->assertSame( [ 'a@x.com', 'b@y.org' ], Message::parse_addresses( '"Doe, Jane" <A@x.com>, Bob <b@y.org>' ) );
		$this->assertSame( [ 'a@x.com', 'b@y.org' ], Message::parse_addresses( [ 'a@x.com', [ 'b@y.org', 'A@X.com' ] ] ) );
		$this->assertSame( [ 'a@x.com' ], Message::parse_addresses( [ 'email' => 'a@x.com', 'name' => 'A' ] ) );
		$this->assertSame( [], Message::parse_addresses( 'not an address' ) );
		$this->assertSame( [], Message::parse_addresses( null ) );
	}

	/* ---------------------------------------------------------------------
	 * message IDs
	 * ------------------------------------------------------------------- */

	public function test_it_assigns_a_message_id_to_phpmailer() {

		$id = Message::use_message_id();

		$this->assertMatchesRegularExpression( '/^<[a-z2-7]{16}@[^>]+>$/', $id );

		$phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
		do_action_ref_array( 'phpmailer_init', [ &$phpmailer ] );

		$this->assertSame( $id, $phpmailer->MessageID );

		Message::release_message_id();

		$phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
		do_action_ref_array( 'phpmailer_init', [ &$phpmailer ] );

		$this->assertSame( '', $phpmailer->MessageID );
	}

	public function test_it_reads_message_ids_from_header_values() {

		$this->assertSame( [ '<a@x.com>', '<b@y.com>' ], Message::parse_message_ids( "<a@x.com>\r\n <b@y.com>" ) );
		$this->assertSame( [ '<a@x.com>' ], Message::parse_message_ids( 'a@x.com' ) );
		$this->assertSame( [ '<a@x.com>', '<b@y.com>' ], Message::parse_message_ids( [ '<a@x.com>', '<b@y.com>' ] ) );
		$this->assertSame( [], Message::parse_message_ids( '' ) );
	}

	public function test_it_normalizes_subjects() {
		$this->assertSame( 'hello there', Message::normalize_subject( 'RE: Re: FWD: Hello There ' ) );
		$this->assertSame( 'reading list', Message::normalize_subject( 'Reading list' ) );
	}

	/* ---------------------------------------------------------------------
	 * the reply without its quoted history
	 * ------------------------------------------------------------------- */

	public function test_the_time_of_a_message_is_right_when_the_site_is_not_in_utc() {

		$this->create_contact( $this->addr( 'jordan' ) );
		$message = Message::ingest( $this->payload() );

		$this->assertInstanceOf( Message::class, $message );

		// dates are stored in UTC, a site that is behind or ahead of it must not see the message as being in the past or the future
		foreach ( [ 'America/Toronto', 'Asia/Tokyo' ] as $zone ) {

			update_option( 'timezone_string', $zone );

			try {
				$array = ( new Message( $message->get_id() ) )->get_as_array();

				$this->assertEqualsWithDelta( time(), $array['timestamp'], 10, $zone );
				$this->assertMatchesRegularExpression( '/^\d+ seconds?$/', $array['i18n']['time_diff'], $zone );
			} finally {
				update_option( 'timezone_string', '' );
			}
		}
	}

	public function test_it_cuts_the_quoted_history_out_of_a_reply() {

		$plain = "Sounds good, see you then.\n\nOn Tue, Sep 1, 2026 at 3:30 PM Adrian <me@example.com> wrote:\n> Are you free Thursday?\n> Let me know.";

		$this->assertSame( 'Sounds good, see you then.', Message::extract_reply( $plain ) );

		$this->assertSame( 'Thursday works.', Message::extract_reply( "Thursday works.\n\n> earlier\n> lines" ) );

		$this->assertSame( 'Yes!', Message::extract_reply( "Yes!\n\n-----Original Message-----\nFrom: Adrian\nSent: Tuesday" ) );

		$this->assertSame( 'Yes!', Message::extract_reply( "Yes!\n\n________________________________\nFrom: Adrian\nSent: Tuesday" ) );

		$gmail = '<div dir="ltr">Count me in.</div><br><div class="gmail_quote"><div class="gmail_attr">On Tue Adrian wrote:</div><blockquote>Are you coming?</blockquote></div>';

		$this->assertSame( 'Count me in.', Message::extract_reply( $gmail ) );
	}

	public function test_the_preview_of_a_reply_is_what_was_written() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [
			'text' => "Perfect, that worked.\n\nOn Tue, Sep 1, 2026 Adrian wrote:\n> Try importing the CSV again.",
		] ) );

		$this->assertSame( 'Perfect, that worked.', $message->get_preview() );
	}

	public function test_the_preview_falls_back_to_everything_when_the_reply_is_only_quoted_text() {

		$this->create_contact( $this->addr( 'jordan' ) );

		$message = Message::ingest( $this->payload( [ 'text' => "> just a quote" ] ) );

		$this->assertSame( '> just a quote', $message->get_preview() );
	}

	/* ---------------------------------------------------------------------
	 * message caps map to the contact permissions
	 * ------------------------------------------------------------------- */

	public function test_message_caps_follow_the_contact_permissions() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->create_contact( $this->addr( 'jordan' ), [ 'owner_id' => $owner_id ] );
		$message  = $this->create_sent( $contact, $this->mid( 'sent-5' ) );

		$this->assertTrue( user_can( $owner_id, 'view_message', $message ) );
		$this->assertFalse( user_can( $other_id, 'view_message', $message ) );

		// messages are only written by sending and receiving
		$this->assertFalse( user_can( $owner_id, 'edit_message', $message ) );
		$this->assertFalse( user_can( $owner_id, 'add_messages' ) );
		$this->assertFalse( user_can( $owner_id, 'edit_messages' ) );
	}
}
