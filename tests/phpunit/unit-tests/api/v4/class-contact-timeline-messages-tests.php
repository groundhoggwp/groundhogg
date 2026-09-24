<?php

use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see \Groundhogg\Api\V4\Contacts_Api::read_timeline() the messages on a contact's timeline
 * @see Message::get_timeline_array() what a message is on the timeline
 */
class Contact_Timeline_Messages_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address is unique to the run.
	 *
	 * @var string
	 */
	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function addr( $local ) {
		return $local . '@' . $this->run_id . '.example.com';
	}

	protected function create_contact( $local = 'jordan', array $args = [] ) {
		return get_contactdata( self::factory()->contacts->create( array_merge( [ 'email' => $this->addr( $local ), 'first_name' => 'Jordan', 'last_name' => 'Rivera' ], $args ) ) );
	}

	/**
	 * @param string $when anything strtotime() takes, in UTC
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
			'user_id'      => $direction === 'inbound' ? 0 : get_current_user_id(),
			'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( $when . ' UTC' ) ),
		], $args ) );

		return $message;
	}

	/**
	 * @return array the response
	 */
	protected function timeline( $contact, array $params = [] ) {

		$request = new WP_REST_Request( 'GET', '/gh/v4/contacts/' . $contact->get_id() . '/timeline' );
		$request->set_query_params( $params );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		return $response->get_data();
	}

	public function test_the_timeline_has_the_messages_of_the_contact_both_ways_and_not_anyone_elses() {

		$contact = $this->create_contact( 'jordan' );
		$other   = $this->create_contact( 'other' );

		$sent     = $this->create_message( $contact, 'outbound', '-2 days' );
		$received = $this->create_message( $contact, 'inbound', '-1 day' );
		$this->create_message( $other, 'inbound', '-1 day' );

		$messages = $this->timeline( $contact )['messages'];

		$this->assertEqualsCanonicalizing( [ $sent->get_id(), $received->get_id() ], array_map( 'intval', array_column( $messages, 'ID' ) ) );
	}

	public function test_a_message_on_the_timeline_says_who_and_when_and_not_what_it_said() {

		$contact = $this->create_contact();
		$message = $this->create_message( $contact, 'inbound', '-3 hours', [ 'subject' => 'Question about my order' ] );

		[ $item ] = $this->timeline( $contact )['messages'];

		$this->assertSame( 'Question about my order', $item['data']['subject'] );
		$this->assertSame( 'inbound', $item['data']['direction'] );
		$this->assertSame( $contact->get_email(), $item['data']['from_address'] );

		// the body can be a megabyte, it's read when the message is opened
		$this->assertArrayNotHasKey( 'content', $item['data'] );
		$this->assertStringNotContainsString( 'The whole body', wp_json_encode( $item ) );

		$this->assertSame( strtotime( $message->date_created . ' UTC' ), $item['timestamp'] );
		$this->assertNotEmpty( $item['i18n']['diff_time'] );
		$this->assertNotEmpty( $item['i18n']['ymdhis'] );
		$this->assertArrayNotHasKey( 'preview', $item, 'it is the start of the body' );
	}

	public function test_the_body_is_still_there_to_be_read_when_the_message_is_opened() {

		$contact = $this->create_contact();
		$message = $this->create_message( $contact, 'inbound', '-1 hour' );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/messages/' . $message->get_id() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringContainsString( 'The whole body', $response->get_data()['item']['data']['content'] );
	}

	public function test_who_sent_it_is_the_person_and_who_wrote_it_is_the_contact() {

		$user_id = self::factory()->user->create( [ 'role' => 'sales_rep', 'display_name' => 'Adrian Tobey' ] );
		$contact = $this->create_contact();

		$this->create_message( $contact, 'outbound', '-3 days', [ 'user_id' => $user_id ] );
		$this->create_message( $contact, 'outbound', '-2 days', [ 'user_id' => 0, 'from_address' => 'shared-inbox@example.org' ] ); // sent from somewhere else
		$this->create_message( $contact, 'inbound', '-1 day' );
		$this->create_message( $contact, 'inbound', '-1 hour', [ 'from_address' => 'jordan.rivera@work.example.org' ] ); // an alias

		$items = $this->timeline( $contact, [ 'order' => 'ASC' ] )['messages'];

		$this->assertSame( 'Adrian Tobey', $items[0]['i18n']['sent_by'] );
		$this->assertSame( 'shared-inbox@example.org', $items[1]['i18n']['sent_by'], 'no one to name, so the address' );
		$this->assertSame( 'Jordan Rivera', $items[2]['i18n']['sender'], 'the contact, by name' );
		$this->assertSame( 'jordan.rivera@work.example.org', $items[3]['i18n']['sender'], 'not their address, so the address it was' );
	}

	public function test_only_the_messages_in_the_time_range_are_on_the_timeline() {

		$contact = $this->create_contact();

		$old    = $this->create_message( $contact, 'outbound', '-40 days' );
		$middle = $this->create_message( $contact, 'inbound', '-10 days' );
		$new    = $this->create_message( $contact, 'outbound', '-1 day' );

		$ids = fn( $params ) => array_map( 'intval', array_column( $this->timeline( $contact, $params )['messages'], 'ID' ) );

		$this->assertEqualsCanonicalizing( [ $middle->get_id(), $new->get_id() ], $ids( [ 'after' => strtotime( '-20 days' ) ] ) );
		$this->assertEqualsCanonicalizing( [ $old->get_id() ], $ids( [ 'before' => strtotime( '-20 days' ) ] ) );
		$this->assertEqualsCanonicalizing( [ $middle->get_id() ], $ids( [ 'after' => strtotime( '-20 days' ), 'before' => strtotime( '-5 days' ) ] ) );
		$this->assertCount( 3, $ids( [] ) );
	}

	public function test_the_messages_are_in_the_order_that_was_asked_for() {

		$contact = $this->create_contact();

		$first  = $this->create_message( $contact, 'outbound', '-3 days' );
		$second = $this->create_message( $contact, 'inbound', '-2 days' );
		$third  = $this->create_message( $contact, 'outbound', '-1 day' );

		$ids = fn( $order ) => array_map( 'intval', array_column( $this->timeline( $contact, [ 'order' => $order ] )['messages'], 'ID' ) );

		$this->assertSame( array_map( 'intval', [ $third->get_id(), $second->get_id(), $first->get_id() ] ), $ids( 'DESC' ) );
		$this->assertSame( array_map( 'intval', [ $first->get_id(), $second->get_id(), $third->get_id() ] ), $ids( 'ASC' ) );
	}

	public function test_a_contact_with_no_messages_has_none_on_the_timeline() {

		$this->assertSame( [], $this->timeline( $this->create_contact() )['messages'] );
	}

	public function test_someone_that_can_not_see_the_contact_can_not_see_their_timeline() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->create_contact( 'jordan', [ 'owner_id' => $owner_id ] );

		$this->create_message( $contact, 'inbound', '-1 hour' );

		wp_set_current_user( $owner_id );
		$this->assertCount( 1, $this->timeline( $contact )['messages'] );

		wp_set_current_user( $other_id );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/contacts/' . $contact->get_id() . '/timeline' ) );

		$this->assertNotSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'messages', $response->get_data() );
	}
}
