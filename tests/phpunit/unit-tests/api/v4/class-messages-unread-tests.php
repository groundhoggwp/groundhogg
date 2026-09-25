<?php

use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see \Groundhogg\Api\V4\Messages_Api::read_unread() GET gh/v4/messages/unread, the contacts that have replied and not been read
 * @see \Groundhogg\Api\V4\Messages_Api::mark_read() POST gh/v4/messages/read
 * @see Message::ingest() where a message that was received starts as not read
 */
class Messages_Unread_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address is unique to the run and what
	 * a test looks at is only what it made, not everything that there is.
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local, array $args = [] ) {
		return get_contactdata( self::factory()->contacts->create( array_merge( [ 'email' => $this->addr( $local ) ], $args ) ) );
	}

	protected function create_rep() {
		return self::factory()->user->create( [ 'role' => 'sales_rep' ] );
	}

	/**
	 * @param string $when anything strtotime() takes, in UTC
	 * @param array  $args
	 *
	 * @return Message
	 */
	protected function create_message( $contact, $direction, $when, array $args = [] ) {

		$message = new Message();
		$message->create( array_merge( [
			'object_type'  => 'contact',
			'object_id'    => $contact->get_id(),
			'direction'    => $direction,
			'from_address' => $direction === 'inbound' ? $contact->get_email() : 'me@example.com',
			'to_address'   => $direction === 'inbound' ? 'me@example.com' : $contact->get_email(),
			'subject'      => 'Hello ' . uniqid(),
			'content'      => '<p>The whole body of the message, that can be long</p>',
			'is_read'      => $direction === 'inbound' ? 0 : 1,
			'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( $when . ' UTC' ) ),
		], $args ) );

		return $message;
	}

	/**
	 * @param \Groundhogg\Contact[] $only what the response is looked at for, the rest of what there is is not the test's
	 * @param array                 $params
	 *
	 * @return array[] the items, by the id of the contact
	 */
	protected function unread( array $only, array $params = [] ) {

		$request = new WP_REST_Request( 'GET', '/gh/v4/messages/unread' );
		$request->set_query_params( $params );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		$ids   = array_map( fn( $contact ) => $contact->get_id(), $only );
		$items = array_values( array_filter( $response->get_data()['items'], fn( $item ) => in_array( $item['object_id'], $ids, true ) ) );

		return array_column( $items, null, 'object_id' );
	}

	protected function mark( $contact, $read = true ) {

		$request = new WP_REST_Request( 'POST', '/gh/v4/messages/read' );
		$request->set_param( 'object_type', 'contact' );
		$request->set_param( 'object_id', $contact->get_id() );
		$request->set_param( 'read', $read );

		return rest_do_request( $request );
	}

	/* ---------------------------------------------------------------------
	 * what is not read
	 * ------------------------------------------------------------------- */

	public function test_a_message_that_is_received_is_not_read() {

		$this->create_contact( 'jordan' );

		$message = Message::ingest( [
			'from'       => $this->addr( 'jordan' ),
			'to'         => 'me@example.com',
			'subject'    => 'Re: Hello',
			'text'       => 'Thanks!',
			'message_id' => '<unread-' . $this->run_id . '@mail.example.com>',
		] );

		$this->assertInstanceOf( Message::class, $message );
		$this->assertEquals( 0, $message->is_read );
	}

	public function test_what_we_send_is_read() {

		$contact = $this->create_contact( 'jordan' );

		$message = Message::record_composed_email( $contact, [
			'subject'      => 'Hello',
			'content'      => '<p>Hi!</p>',
			'from_address' => 'me@example.com',
			'user_id'      => 1,
			'message_id'   => Message::generate_message_id(),
		] );

		$this->assertEquals( 1, $message->is_read );
	}

	public function test_a_message_is_read_unless_it_is_said_that_it_is_not() {

		// what was there before there was a read state, and anything that makes a message without saying
		$message = new Message();
		$message->create( [
			'object_type' => 'contact',
			'object_id'   => $this->create_contact( 'jordan' )->get_id(),
			'direction'   => 'inbound',
			'subject'     => 'Old',
		] );

		$this->assertEquals( 1, $message->is_read );
	}

	/* ---------------------------------------------------------------------
	 * the list
	 * ------------------------------------------------------------------- */

	public function test_it_lists_the_contacts_that_have_replied_the_last_first_with_how_many() {

		wp_set_current_user( $rep = $this->create_rep() );

		$a = $this->create_contact( 'a', [ 'owner_id' => $rep ] );
		$b = $this->create_contact( 'b', [ 'owner_id' => $rep ] );
		$c = $this->create_contact( 'c', [ 'owner_id' => $rep ] );

		$this->create_message( $a, 'inbound', '-3 hours' );
		$a_last = $this->create_message( $a, 'inbound', '-2 hours', [ 'subject' => 'The last from a' ] );
		$b_last = $this->create_message( $b, 'inbound', '-1 hour', [ 'subject' => 'The last from b' ] );
		// read, and what we sent, are not what has to be read
		$this->create_message( $c, 'inbound', '-1 hour', [ 'is_read' => 1 ] );
		$this->create_message( $c, 'outbound', '-30 minutes' );

		$items = $this->unread( [ $a, $b, $c ] );

		$this->assertSame( [ $b->get_id(), $a->get_id() ], array_keys( $items ), 'the one that replied last is first' );

		$this->assertSame( 2, $items[ $a->get_id() ]['unread'] );
		$this->assertSame( 1, $items[ $b->get_id() ]['unread'] );

		$this->assertEquals( $a_last->get_id(), $items[ $a->get_id() ]['latest']['ID'] );
		$this->assertSame( 'The last from a', $items[ $a->get_id() ]['latest']['data']['subject'] );
		$this->assertEquals( $b_last->get_id(), $items[ $b->get_id() ]['latest']['ID'] );

		$this->assertSame( $this->addr( 'a' ), $items[ $a->get_id() ]['contact']['email'] );
		$this->assertArrayHasKey( 'preview', $items[ $a->get_id() ]['latest'] );
		$this->assertArrayNotHasKey( 'content', $items[ $a->get_id() ]['latest']['data'], 'the body is for when it is opened' );
	}

	public function test_it_is_the_contacts_of_the_user_by_default_and_everything_that_they_can_see_when_asked() {

		$rep   = $this->create_rep();
		$other = $this->create_rep();
		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$mine   = $this->create_contact( 'mine', [ 'owner_id' => $rep ] );
		$theirs = $this->create_contact( 'theirs', [ 'owner_id' => $other ] );

		$this->create_message( $mine, 'inbound', '-2 hours' );
		$this->create_message( $theirs, 'inbound', '-1 hour' );

		wp_set_current_user( $rep );
		$this->assertSame( [ $mine->get_id() ], array_keys( $this->unread( [ $mine, $theirs ] ) ) );
		$this->assertSame( [ $mine->get_id() ], array_keys( $this->unread( [ $mine, $theirs ], [ 'scope' => 'mine' ] ) ) );

		// all of it is what they can see of it, and a rep does not see what someone else owns
		$this->assertSame( [ $mine->get_id() ], array_keys( $this->unread( [ $mine, $theirs ], [ 'scope' => 'all' ] ) ) );

		// nobody owns it, or it's someone else's, whoever it is
		wp_set_current_user( $admin );
		$this->assertSame( [], $this->unread( [ $mine, $theirs ] ) );
		$this->assertEqualsCanonicalizing( [ $mine->get_id(), $theirs->get_id() ], array_keys( $this->unread( [ $mine, $theirs ], [ 'scope' => 'all' ] ) ) );
	}

	public function test_there_is_nothing_in_the_list_for_someone_that_can_not_view_contacts() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/messages/unread' ) );

		$this->assertNotSame( 200, $response->get_status() );
	}

	/* ---------------------------------------------------------------------
	 * read and not read
	 * ------------------------------------------------------------------- */

	public function test_everything_that_was_received_from_a_contact_is_read_at_once() {

		wp_set_current_user( $rep = $this->create_rep() );

		$a = $this->create_contact( 'a', [ 'owner_id' => $rep ] );
		$b = $this->create_contact( 'b', [ 'owner_id' => $rep ] );

		$this->create_message( $a, 'inbound', '-3 hours' );
		$this->create_message( $a, 'inbound', '-2 hours' );
		$this->create_message( $b, 'inbound', '-1 hour' );

		$response = $this->mark( $a );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 2, $response->get_data()['updated'] );
		$this->assertSame( 0, $response->get_data()['unread'] );

		// only theirs
		$this->assertSame( [ $b->get_id() ], array_keys( $this->unread( [ $a, $b ] ) ) );
	}

	public function test_not_read_is_the_last_message_that_was_received() {

		wp_set_current_user( $rep = $this->create_rep() );

		$a = $this->create_contact( 'a', [ 'owner_id' => $rep ] );

		$first = $this->create_message( $a, 'inbound', '-3 hours', [ 'is_read' => 1 ] );
		$last  = $this->create_message( $a, 'inbound', '-2 hours', [ 'is_read' => 1 ] );

		$response = $this->mark( $a, false );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 1, $response->get_data()['unread'] );

		$items = $this->unread( [ $a ] );

		$this->assertSame( 1, $items[ $a->get_id() ]['unread'] );
		$this->assertEquals( $last->get_id(), $items[ $a->get_id() ]['latest']['ID'] );
		$this->assertEquals( 1, ( new Message( $first->get_id() ) )->is_read );
	}

	public function test_reading_what_there_is_none_of_is_not_a_failure() {

		wp_set_current_user( $rep = $this->create_rep() );

		$response = $this->mark( $this->create_contact( 'quiet', [ 'owner_id' => $rep ] ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $response->get_data()['updated'] );
	}

	public function test_what_someone_else_owns_can_not_be_marked_by_a_rep() {

		$owner = $this->create_rep();
		$other = $this->create_rep();
		$a     = $this->create_contact( 'a', [ 'owner_id' => $owner ] );

		$this->create_message( $a, 'inbound', '-1 hour' );

		wp_set_current_user( $other );
		$response = $this->mark( $a );

		$this->assertSame( 403, $response->get_status() );

		wp_set_current_user( $owner );
		$this->assertArrayHasKey( $a->get_id(), $this->unread( [ $a ] ), 'it is still not read' );
	}

	protected function mark_message( $message, $read = true ) {

		$request = new WP_REST_Request( 'POST', '/gh/v4/messages/read' );
		$request->set_param( 'message_id', $message->get_id() );
		$request->set_param( 'read', $read );

		return rest_do_request( $request );
	}

	public function test_a_message_can_be_read_and_not_read_by_itself() {

		wp_set_current_user( $rep = $this->create_rep() );

		$a = $this->create_contact( 'a', [ 'owner_id' => $rep ] );

		$first = $this->create_message( $a, 'inbound', '-3 hours' );
		$last  = $this->create_message( $a, 'inbound', '-2 hours' );

		$response = $this->mark_message( $first );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 1, $response->get_data()['is_read'] );
		$this->assertSame( 1, $response->get_data()['unread'], 'the other is still to be read' );
		$this->assertEquals( 1, ( new Message( $first->get_id() ) )->is_read );
		$this->assertEquals( 0, ( new Message( $last->get_id() ) )->is_read );

		// and back, not the last one, the one that it's said of
		$response = $this->mark_message( $first, false );

		$this->assertSame( 0, $response->get_data()['is_read'] );
		$this->assertSame( 2, $response->get_data()['unread'] );
		$this->assertEquals( 0, ( new Message( $first->get_id() ) )->is_read );
	}

	public function test_only_a_message_that_was_received_can_be_marked_by_itself() {

		wp_set_current_user( $rep = $this->create_rep() );

		$a    = $this->create_contact( 'a', [ 'owner_id' => $rep ] );
		$sent = $this->create_message( $a, 'outbound', '-1 hour' );

		$this->assertSame( 400, $this->mark_message( $sent, false )->get_status() );

		$request = new WP_REST_Request( 'POST', '/gh/v4/messages/read' );
		$request->set_param( 'message_id', 99999999 );

		$this->assertSame( 400, rest_do_request( $request )->get_status(), 'a message that does not exist' );
	}

	public function test_a_message_of_a_contact_that_someone_else_owns_can_not_be_marked_by_itself() {

		$owner = $this->create_rep();
		$other = $this->create_rep();
		$a     = $this->create_contact( 'a', [ 'owner_id' => $owner ] );

		$message = $this->create_message( $a, 'inbound', '-1 hour' );

		wp_set_current_user( $other );
		$this->assertSame( 403, $this->mark_message( $message )->get_status() );
		$this->assertEquals( 0, ( new Message( $message->get_id() ) )->is_read );
	}

	public function test_only_a_contact_can_be_marked() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'POST', '/gh/v4/messages/read' );
		$request->set_param( 'object_type', 'company' );
		$request->set_param( 'object_id', 1 );

		$this->assertSame( 400, rest_do_request( $request )->get_status() );
	}
}
