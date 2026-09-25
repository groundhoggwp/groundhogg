<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\Classes\Inbound_Signature;
use Groundhogg\Classes\Inbox;
use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see Inbox the two addresses of the inbox, and the token that makes the reply address safe to publish
 * @see Message::ingest() what happens to a message that was sent to each of them
 * @see Emails_Api::send_email() the Reply-To that the emails we send get
 */
class Inbox_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and Message-ID is unique
	 * to the run, otherwise a test would match what an earlier one left behind.
	 *
	 * @var string
	 */
	protected $run_id;

	/** the public address that replies go to, tokens are added to it */
	protected $reply_address;

	/** the secret address, to BCC or forward to */
	protected $inbox_address;

	public function setUp(): void {
		parent::setUp();

		$this->run_id        = uniqid();
		$this->reply_address = 'r' . $this->run_id . '@inbox.example.com';
		$this->inbox_address = 's' . $this->run_id . '@inbox.example.com';

		update_option( Inbox::REPLY_ADDRESS_OPTION, $this->reply_address );
		update_option( Inbox::ADDRESS_OPTION, $this->inbox_address );
	}

	public function tearDown(): void {
		delete_option( Inbox::REPLY_ADDRESS_OPTION );
		delete_option( Inbox::ADDRESS_OPTION );
		remove_all_filters( 'groundhogg/message/reply_to_enabled' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local ) {
		return get_contactdata( self::factory()->contacts->create( [ 'email' => $this->addr( $local ) ] ) );
	}

	/**
	 * A composed email that we sent, with a Message-ID that we made
	 *
	 * @return Message
	 */
	protected function create_sent( $contact, $message_id = null ) {
		return Message::record_composed_email( $contact, [
			'subject'      => 'Hello there',
			'content'      => '<p>Hi!</p>',
			'from_address' => 'me@example.com',
			'user_id'      => 1,
			'message_id'   => $message_id ?: Message::generate_message_id(),
		] );
	}

	/** @return string the address that a reply to the email is sent to */
	protected function reply_to( Message $message ) {
		return Inbox::reply_to( $message->message_id );
	}

	protected function payload( array $args = [] ) {
		static $n = 0;

		return array_merge( [
			'from'       => $this->addr( 'jordan' ),
			'to'         => 'me@example.com',
			'subject'    => 'Re: Hello there',
			'text'       => 'Thanks!',
			'message_id' => '<tok-' . $this->run_id . '-' . ( ++ $n ) . '@mail.example.com>',
		], $args );
	}

	/* ---------------------------------------------------------------------
	 * the reply address
	 * ------------------------------------------------------------------- */

	public function test_the_message_ids_that_we_make_start_with_an_id_that_can_be_signed() {

		$this->assertMatchesRegularExpression( '/^<[a-z2-7]{16}@[^>]+>$/', Message::generate_message_id() );
		$this->assertNotSame( Message::generate_message_id(), Message::generate_message_id() );
	}

	public function test_the_reply_address_has_a_token_for_the_email() {

		$message_id = Message::generate_message_id();
		$address    = Inbox::reply_to( $message_id );

		$this->assertMatchesRegularExpression( '/^r' . $this->run_id . '\+[a-z2-7]{32}@inbox\.example\.com$/', $address );

		// the first half of the token is the id that the Message-ID starts with
		preg_match( '/^<([a-z2-7]{16})@/', $message_id, $matches );
		$this->assertStringContainsString( '+' . $matches[1], $address );
	}

	public function test_there_is_no_reply_to_without_a_reply_address() {

		delete_option( Inbox::REPLY_ADDRESS_OPTION );

		$this->assertSame( '', Inbox::reply_to( Message::generate_message_id() ) );
	}

	public function test_the_reply_to_is_on_by_default_and_a_filter_turns_it_off() {

		$message_id = Message::generate_message_id();

		$this->assertNotSame( '', Inbox::reply_to( $message_id ) );

		add_filter( 'groundhogg/message/reply_to_enabled', '__return_false' );

		$this->assertSame( '', Inbox::reply_to( $message_id ) );
	}

	public function test_there_is_no_reply_address_for_a_message_id_that_we_did_not_make() {

		$this->assertSame( '', Inbox::reply_to( '<9f2c1e0a-1b2c-4d3e-8f90-a1b2c3d4e5f6@mail.example.com>' ) );
		$this->assertSame( '', Inbox::reply_to( '' ) );
	}

	public function test_there_is_no_reply_address_that_is_too_long_to_be_one() {

		// the part before the @ is 64 characters at the most
		update_option( Inbox::REPLY_ADDRESS_OPTION, str_repeat( 'a', 40 ) . '@inbox.example.com' );

		$this->assertSame( '', Inbox::reply_to( Message::generate_message_id() ) );
	}

	/* ---------------------------------------------------------------------
	 * the token
	 * ------------------------------------------------------------------- */

	public function test_a_valid_token_gives_the_id_of_the_email() {

		$message_id = Message::generate_message_id();
		preg_match( '/^<([a-z2-7]{16})@/', $message_id, $matches );

		$this->assertSame( $matches[1], Inbox::verify_reply( [ Inbox::reply_to( $message_id ) ] ) );

		// it's found among other addresses, and addresses are not case sensitive
		$this->assertSame( $matches[1], Inbox::verify_reply( [ 'someone@example.org', strtoupper( Inbox::reply_to( $message_id ) ) ] ) );
	}

	public function test_a_token_that_was_changed_is_not_valid() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		[ $local, $domain ] = explode( '@', $address );
		$flip = fn( $char ) => $char === 'a' ? 'b' : 'a';

		// the id, and the signature
		$in_id  = substr( $local, 0, -31 ) . $flip( $local[ strlen( $local ) - 31 ] ) . substr( $local, -30 );
		$in_mac = substr( $local, 0, -1 ) . $flip( $local[ strlen( $local ) - 1 ] );

		$this->assertSame( '', Inbox::verify_reply( [ $in_id . '@' . $domain ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $in_mac . '@' . $domain ] ) );

		// and the one that wasn't changed is
		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_is_still_valid_after_the_secret_that_the_relay_signs_with_is_replaced() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );

		// the tokens are signed with a key of their own, otherwise replacing the secret would stop every reply
		// address that was already sent out from working
		Inbound_Signature::regenerate();

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_is_not_valid_after_the_key_it_was_signed_with_is_replaced() {

		$address = Inbox::reply_to( Message::generate_message_id() );

		$this->assertNotSame( '', Inbox::verify_reply( [ $address ] ) );

		Inbox::regenerate_token_key();

		$this->assertSame( '', Inbox::verify_reply( [ $address ] ) );
	}

	public function test_a_token_only_counts_on_the_reply_address() {

		$address = Inbox::reply_to( Message::generate_message_id() );
		$token   = explode( '+', explode( '@', $address )[0] )[1];

		$this->assertSame( '', Inbox::verify_reply( [ 'other+' . $token . '@inbox.example.com' ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $this->inbox_address ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $this->reply_address ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ 'not an address', '' ] ) );
	}

	public function test_which_address_of_the_inbox_it_was_sent_to() {

		$token = explode( '+', explode( '@', Inbox::reply_to( Message::generate_message_id() ) )[0] )[1];

		$this->assertSame( 'reply', Inbox::route( [ $this->reply_address ] ) );
		$this->assertSame( 'reply', Inbox::route( [ 'r' . $this->run_id . '+' . $token . '@inbox.example.com' ] ) );
		$this->assertSame( 'inbox', Inbox::route( [ $this->inbox_address ] ) );
		$this->assertSame( 'inbox', Inbox::route( [] ) );
	}

	/* ---------------------------------------------------------------------
	 * a message that's sent to the reply address
	 * ------------------------------------------------------------------- */

	public function test_a_reply_is_matched_by_its_token_when_the_message_id_was_rewritten_and_the_sender_is_someone_else() {

		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );

		// what a service that sends the email does to the Message-ID, so In-Reply-To doesn't match, and the reply
		// is from an address that isn't on any contact
		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'envelope_to' => $this->reply_to( $sent ),
			'in_reply_to' => '<0198a7b2-rewritten@smtp.provider.example>',
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_a_reply_by_token_goes_to_the_contact_that_wrote_it_when_the_email_went_to_several() {

		$a = $this->create_contact( 'a' );
		$b = $this->create_contact( 'b' );

		// one email to both is stored once for each of them, and they see the same Reply-To
		$message_id = Message::generate_message_id();
		$this->create_sent( $a, $message_id );
		$sent_b = $this->create_sent( $b, $message_id );

		$message = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'b' ),
			'envelope_to' => $this->reply_to( $sent_b ),
		] ) );

		$this->assertEquals( $b->get_id(), $message->object_id );
	}

	public function test_a_message_from_one_of_us_to_the_reply_address_goes_to_the_contact_the_token_is_for() {

		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );
		$rep     = get_user_by( 'id', self::factory()->user->create( [ 'role' => 'sales_rep', 'user_email' => $this->addr( 'adrian' ) ] ) );

		// a colleague answering from their own mailbox writes to the reply address, not to the contact, and the
		// headers don't say what it's in reply to
		$message = Message::ingest( $this->payload( [
			'from'        => $rep->user_email,
			'to'          => $this->reply_to( $sent ),
			'envelope_to' => $this->reply_to( $sent ),
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::OUTBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_a_message_from_one_of_us_to_a_reply_address_with_a_made_up_token_still_does_not_match() {

		$this->create_contact( 'jordan' );
		$rep = get_user_by( 'id', self::factory()->user->create( [ 'role' => 'sales_rep', 'user_email' => $this->addr( 'adrian' ) ] ) );

		$forged = preg_replace( '/\+([a-z2-7]{16})[a-z2-7]+@/', '+$1' . str_repeat( 'a', 24 ) . '@', Inbox::reply_to( Message::generate_message_id() ) );

		$result = Message::ingest( $this->payload( [
			'from'        => $rep->user_email,
			'to'          => $forged,
			'envelope_to' => $forged,
		] ) );

		$this->assertWPError( $result );
	}

	/* ---------------------------------------------------------------------
	 * a name in front of the address
	 * ------------------------------------------------------------------- */

	protected function create_user( array $args = [] ) {
		return get_user_by( 'id', self::factory()->user->create( array_merge( [ 'role' => 'sales_rep' ], $args ) ) );
	}

	public function test_the_secret_address_gets_the_name_of_the_user_in_front_of_it() {

		$user = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );
		wp_set_current_user( $user->ID );

		$this->assertSame( 'adrian.tobey-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address() );
		$this->assertSame( 'adrian.tobey-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $user ) );
	}

	public function test_the_name_falls_back_on_the_display_name_then_the_login_then_the_email() {

		$display = $this->create_user( [ 'display_name' => 'Jordan  O\'Rivera-Smith' ] );
		$this->assertSame( 'jordan.o.rivera.smith-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $display ) );

		$login = $this->create_user( [ 'user_login' => 'sam_lee', 'display_name' => '' ] );
		$this->assertSame( 'sam.lee-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $login ) );

		$email = $this->create_user( [ 'user_login' => '__', 'display_name' => '###', 'user_email' => 'pat.kim@example.org' ] );
		$this->assertSame( 'pat.kim-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $email ) );
	}

	public function test_accents_are_dropped_from_the_name() {

		$user = $this->create_user( [ 'first_name' => 'José', 'last_name' => 'Müller' ] );

		$this->assertSame( 'jose.muller-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $user ) );
	}

	public function test_a_name_that_is_too_long_is_cut_at_a_word_and_the_address_stays_within_64_characters() {

		// the ids that the relay gives are 26 characters, so there are 37 for the name
		update_option( Inbox::ADDRESS_OPTION, str_repeat( 'a', 26 ) . '@inbox.example.com' );

		$user = $this->create_user( [ 'first_name' => 'Bartholomew Maximilian', 'last_name' => 'Featherstonehaugh' ] );

		$address = Inbox::pretty_address( $user );
		[ $local ] = explode( '@', $address );

		$this->assertSame( 'bartholomew.maximilian-' . str_repeat( 'a', 26 ) . '@inbox.example.com', $address );
		$this->assertLessThanOrEqual( 64, strlen( $local ) );

		// one word that doesn't fit is cut short instead
		$single = $this->create_user( [ 'first_name' => str_repeat( 'x', 60 ), 'last_name' => '' ] );
		[ $local ] = explode( '@', Inbox::pretty_address( $single ) );

		$this->assertSame( 64, strlen( $local ) );
		$this->assertSame( str_repeat( 'x', 37 ) . '-' . str_repeat( 'a', 26 ), $local );
	}

	public function test_there_is_no_name_when_there_is_no_user_or_nothing_that_can_be_one() {

		$this->assertSame( $this->inbox_address, Inbox::pretty_address() );

		$nothing = $this->create_user( [ 'user_login' => '___', 'display_name' => '###', 'user_email' => '###@example.org' ] );
		$this->assertSame( $this->inbox_address, Inbox::pretty_address( $nothing ) );

		delete_option( Inbox::ADDRESS_OPTION );
		$this->assertSame( '', Inbox::pretty_address( $nothing ) );
	}

	public function test_the_name_can_be_changed_with_a_filter_and_only_what_can_be_in_an_address_is_kept() {

		$user = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );

		add_filter( 'groundhogg/inbox/address_prefix', fn() => ' Support Team! ' );
		$this->assertSame( 'supportteam-s' . $this->run_id . '@inbox.example.com', Inbox::pretty_address( $user ) );

		remove_all_filters( 'groundhogg/inbox/address_prefix' );
		add_filter( 'groundhogg/inbox/address_prefix', fn() => str_repeat( 'x', 60 ) );
		$this->assertSame( $this->inbox_address, Inbox::pretty_address( $user ), 'one that doesn\'t fit is not used' );

		remove_all_filters( 'groundhogg/inbox/address_prefix' );
	}

	public function test_an_address_with_a_name_in_front_of_it_is_still_the_inbox() {

		$user = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );

		$this->assertSame( 'inbox', Inbox::route( [ Inbox::pretty_address( $user ) ] ) );
		$this->assertSame( 'inbox', Inbox::route( [ 'anything.at.all-' . strtoupper( 's' . $this->run_id ) . '@inbox.example.com' ] ) );

		// with a name, an id that isn't ours is still not
		$this->assertSame( 'unknown', Inbox::route( [ 'adrian.tobey-sabcdefabcdef@inbox.example.com' ] ) );
	}

	public function test_a_reply_address_with_a_name_in_front_of_it_is_still_a_reply_with_a_valid_token() {

		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );

		$plain  = $this->reply_to( $sent );
		$named  = 'support.team-' . $plain;
		$forged = preg_replace( '/(\+[a-z2-7]{16})[a-z2-7]{16}@/', '$1' . str_repeat( 'a', 16 ) . '@', $named );

		$this->assertSame( 'reply', Inbox::route( [ $named ] ) );
		$this->assertNotSame( '', Inbox::verify_reply( [ $named ] ) );
		$this->assertSame( Inbox::verify_reply( [ $plain ] ), Inbox::verify_reply( [ $named ] ) );
		$this->assertSame( '', Inbox::verify_reply( [ $forged ] ), 'a name doesn\'t make a token valid' );
	}

	public function test_the_reply_address_gets_the_name_of_who_sent_the_email() {

		$sender  = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );
		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );

		$plain = Inbox::reply_to( $sent->message_id );
		$named = Inbox::reply_to( $sent->message_id, $sender );

		$this->assertSame( 'adrian.tobey-' . $plain, $named );

		// the current user is who it's for when nobody's said
		wp_set_current_user( $sender->ID );
		$this->assertSame( $named, Inbox::reply_to( $sent->message_id ) );
	}

	public function test_a_reply_to_the_address_with_a_name_is_received_by_its_token() {

		$sender  = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );
		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );
		$named   = Inbox::reply_to( $sent->message_id, $sender );

		$this->assertSame( 'reply', Inbox::route( [ $named ] ) );
		$this->assertNotSame( '', Inbox::verify_reply( [ $named ] ) );

		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'envelope_to' => $named,
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent->thread_id, $message->thread_id );
	}

	public function test_the_name_on_the_reply_address_is_shortened_or_left_out_to_stay_within_64_characters() {

		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );
		[ $plain_local ] = explode( '@', Inbox::reply_to( $sent->message_id ) );

		// the plain one is 14 + 1 + 32 characters here, so 17 are left for a name and the "-"
		$this->assertSame( 47, strlen( $plain_local ) );

		// a name that's too long for it is cut to the words that fit
		$long = $this->create_user( [ 'first_name' => 'Bartholomew', 'last_name' => 'Featherstonehaugh' ] );
		[ $local ] = explode( '@', Inbox::reply_to( $sent->message_id, $long ) );
		$this->assertSame( 'bartholomew-' . $plain_local, $local );

		// and one that has no word that fits isn't used, the address isn't a bad one
		$none = $this->create_user( [ 'first_name' => str_repeat( 'x', 30 ), 'last_name' => '' ] );
		[ $local ] = explode( '@', Inbox::reply_to( $sent->message_id, $none ) );
		$this->assertSame( str_repeat( 'x', 16 ) . '-' . $plain_local, $local );
		$this->assertSame( 64, strlen( $local ) );
	}

	public function test_the_filter_says_which_address_a_name_is_for() {

		$sender  = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );
		$contact = $this->create_contact( 'jordan' );
		$sent    = $this->create_sent( $contact );

		add_filter( 'groundhogg/inbox/address_prefix', fn( $prefix, $user, $route ) => $route === 'reply' ? 'support' : $prefix, 10, 3 );

		$this->assertStringStartsWith( 'support-', Inbox::reply_to( $sent->message_id, $sender ) );
		$this->assertStringStartsWith( 'adrian.tobey-', Inbox::pretty_address( $sender ) );

		remove_all_filters( 'groundhogg/inbox/address_prefix' );
	}

	public function test_a_message_to_the_inbox_address_with_a_name_is_received() {

		$contact = $this->create_contact( 'jordan' );
		$user    = $this->create_user( [ 'first_name' => 'Adrian', 'last_name' => 'Tobey' ] );

		$message = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'jordan' ),
			'to'          => Inbox::pretty_address( $user ),
			'envelope_to' => Inbox::pretty_address( $user ),
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( Message::INBOUND, $message->direction );
	}

	public function test_the_reply_address_without_a_token_is_not_trusted_whoever_it_says_it_is_from() {

		// the address is in the header of every email that was sent, this is what someone that's had one would do
		$contact = $this->create_contact( 'jordan' );

		$result = Message::ingest( $this->payload( [
			'from'        => $this->addr( 'jordan' ),
			'envelope_to' => $this->reply_address,
		] ) );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_token', $result->get_error_code() );
		$this->assertSame( 0, \Groundhogg\get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] ) );
	}

	public function test_the_reply_address_with_a_token_that_was_made_up_is_not_trusted() {

		$this->create_contact( 'jordan' );

		$address = Inbox::reply_to( Message::generate_message_id() );
		$at      = strpos( $address, '@' );

		// the last character of the signature, made into a different one
		$forged = substr( $address, 0, $at - 1 ) . ( $address[ $at - 1 ] === 'a' ? 'b' : 'a' ) . substr( $address, $at );

		$result = Message::ingest( $this->payload( [ 'envelope_to' => $forged ] ) );

		$this->assertSame( 'invalid_token', $result->get_error_code() );
	}

	public function test_a_token_for_an_email_we_do_not_have_falls_back_on_who_it_is_from() {

		$contact = $this->create_contact( 'jordan' );

		// a good token, for an email that was sent and since deleted, or that another site sent
		$message = Message::ingest( $this->payload( [ 'envelope_to' => Inbox::reply_to( Message::generate_message_id() ) ] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
	}

	/* ---------------------------------------------------------------------
	 * a message that's sent to the inbox address
	 * ------------------------------------------------------------------- */

	public function test_the_inbox_address_is_trusted_and_the_sender_says_who_it_is() {

		$contact = $this->create_contact( 'jordan' );

		$message = Message::ingest( $this->payload( [
			'to'          => 'help@theirdomain.example',  // what it says, the address was forwarded to the inbox
			'envelope_to' => $this->inbox_address,
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertSame( Message::INBOUND, $message->direction );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( 'help@theirdomain.example', $message->to_address );
	}

	/* ---------------------------------------------------------------------
	 * the emails we send
	 * ------------------------------------------------------------------- */

	/** @var callable|null what captures the email that is about to be sent */
	protected $capture;

	/**
	 * Send a composed email, and get what it was sent with
	 *
	 * @return array [ [ reply_to => the addresses, message_id => the Message-ID PHPMailer used ], the response ]
	 */
	protected function send_composed() {

		$captured = null;

		// the person sending, who has to be able to see who they're sending to
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// after the Message-ID has been set
		$this->capture = function ( $phpmailer ) use ( &$captured ) {
			$captured = [
				// each is [ address, name ]
				'reply_to'   => array_values( array_map( fn( $reply_to ) => $reply_to[0], $phpmailer->getReplyToAddresses() ) ),
				'message_id' => $phpmailer->MessageID,
			];
		};

		add_action( 'phpmailer_init', $this->capture, 999 );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/send' );
		$request->set_param( 'to', [ $this->addr( 'jordan' ) ] );
		$request->set_param( 'from_email', 'me@example.com' );
		$request->set_param( 'from_name', 'Me' );
		$request->set_param( 'subject', 'Hello there' );
		$request->set_param( 'content', '<p>Hi Jordan</p>' );

		$response = ( new Emails_Api() )->send_email( $request );

		remove_action( 'phpmailer_init', $this->capture, 999 );

		return [ $captured, $response ];
	}

	public function test_a_composed_email_sends_replies_to_the_inbox_by_a_token_for_that_email() {

		$contact = $this->create_contact( 'jordan' );

		[ $sent, $response ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertCount( 1, $sent['reply_to'] );

		$address = $sent['reply_to'][0];

		// the token is for the Message-ID that PHPMailer used, and that was stored
		$stored = \Groundhogg\get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] );

		$this->assertCount( 1, $stored );
		$this->assertSame( $sent['message_id'], $stored[0]->message_id );
		$this->assertSame( Inbox::reply_to( $sent['message_id'] ), $address );

		// so a reply to it finds the message, whoever it's from
		$message = Message::ingest( $this->payload( [
			'from'        => 'jordan.rivera@work.example.org',
			'envelope_to' => $address,
		] ) );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( $contact->get_id(), $message->object_id );
		$this->assertSame( $sent['message_id'], $message->thread_id );
	}

	public function test_a_composed_email_has_no_reply_to_when_the_filter_turns_it_off() {

		$this->create_contact( 'jordan' );

		add_filter( 'groundhogg/message/reply_to_enabled', '__return_false' );

		[ $sent ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertSame( [], $sent['reply_to'] );
	}

	public function test_a_composed_email_has_no_reply_to_without_an_inbox() {

		$this->create_contact( 'jordan' );

		delete_option( Inbox::REPLY_ADDRESS_OPTION );

		[ $sent ] = $this->send_composed();

		$this->assertNotNull( $sent, 'the email was not sent' );
		$this->assertSame( [], $sent['reply_to'] );
	}
}
