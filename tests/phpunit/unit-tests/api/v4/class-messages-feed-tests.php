<?php

use Groundhogg\Classes\Message;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Send_Email;
use function Groundhogg\get_db;

/**
 * @see \Groundhogg\Api\V4\Messages_Api::read_feed() GET gh/v4/messages/feed, the conversation of a contact, a page at a time
 */
class Messages_Feed_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function create_message( $contact, $when, $direction = 'inbound' ) {

		$message = new Message();
		$message->create( [
			'object_type'  => 'contact',
			'object_id'    => $contact->get_id(),
			'direction'    => $direction,
			'from_address' => 'someone@example.com',
			'to_address'   => 'me@example.com',
			'subject'      => 'Hello ' . uniqid(),
			'content'      => '<p>Hi</p>',
			'is_read'      => 1,
			'date_created' => gmdate( 'Y-m-d H:i:s', $when ),
		] );

		return $message;
	}

	/**
	 * A completed email of a flow
	 */
	protected function create_automated( $contact, $step, $when ) {
		return get_db( 'events' )->add( [
			'time'       => $when,
			'funnel_id'  => $step->get_funnel_id(),
			'step_id'    => $step->get_id(),
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
			'queued_id'  => 0,
		] );
	}

	protected function feed( $contact, array $params = [] ) {

		$request = new WP_REST_Request( 'GET', '/gh/v4/messages/feed' );
		$request->set_query_params( array_merge( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
		], $params ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		return $response->get_data();
	}

	/**
	 * Page through everything the way the messages tab does, each page from what the last one ended with
	 *
	 * @return array the keys, in the order that they came in
	 */
	protected function all_pages( $contact, array $params, $max_pages = 12 ) {

		$keys   = [];
		$cursor = [];

		for ( $page = 0; $page < $max_pages; $page ++ ) {

			$data = $this->feed( $contact, array_merge( $params, $cursor ) );

			array_push( $keys, ...array_column( $data['items'], 'key' ) );

			if ( ! $data['has_more'] || ! $data['next'] ) {
				return $keys;
			}

			$cursor = $data['next'];
		}

		$this->fail( 'It never got to the end: ' . implode( ', ', $keys ) );
	}

	public function test_a_page_of_messages_from_one_second_moves_on() {

		$contact = $this->factory()->contacts->create_and_get();
		$when    = strtotime( '-3 days' );

		// an import or the move of composed emails, all written in the same second
		$ids = [];
		for ( $i = 0; $i < 5; $i ++ ) {
			$ids[] = 'message-' . $this->create_message( $contact, $when )->get_id();
		}

		$keys = $this->all_pages( $contact, [ 'limit' => 2 ] );

		$this->assertSame( array_reverse( $ids ), $keys, 'all of them, newest first, none twice' );
	}

	public function test_the_pages_are_in_order_across_seconds() {

		$contact = $this->factory()->contacts->create_and_get();

		$ids = [];
		foreach ( [ '-5 days', '-5 days', '-4 days', '-4 days', '-4 days', '-1 day' ] as $when ) {
			$ids[] = 'message-' . $this->create_message( $contact, strtotime( $when ) )->get_id();
		}

		$keys = $this->all_pages( $contact, [ 'limit' => 4 ] );

		// by time and then by ID, the newest first
		$this->assertSame( [ $ids[5], $ids[4], $ids[3], $ids[2], $ids[1], $ids[0] ], $keys );
	}

	public function test_messages_and_automated_emails_from_one_second_page_together() {

		$contact = $this->factory()->contacts->create_and_get();
		$funnel  = new Funnel( $this->factory()->funnels->create() );
		$step    = $funnel->add_step( [
			'step_title' => 'Send an email',
			'step_type'  => Send_Email::TYPE,
			'step_group' => Action::GROUP,
		] );

		$when = strtotime( '-2 days' );

		$messages = [];
		$events   = [];

		for ( $i = 0; $i < 3; $i ++ ) {
			$messages[] = 'message-' . $this->create_message( $contact, $when, 'outbound' )->get_id();
			$events[]   = 'event-' . $this->create_automated( $contact, $step, $when );
		}

		// a message is before an automated email of the same time, and the higher ID is before
		$expected = array_merge( array_reverse( $messages ), array_reverse( $events ) );

		foreach ( [ 1, 2, 4 ] as $limit ) {
			$this->assertSame( $expected, $this->all_pages( $contact, [ 'limit' => $limit, 'include_automated' => true ] ), "limit $limit" );
		}
	}

	public function test_what_is_before_a_time_is_still_inclusive_without_the_rest_of_the_cursor() {

		$contact = $this->factory()->contacts->create_and_get();
		$when    = strtotime( '-2 days' );

		$older = $this->create_message( $contact, $when - 100 );
		$same  = $this->create_message( $contact, $when );
		$newer = $this->create_message( $contact, $when + 100 );

		$data = $this->feed( $contact, [ 'before' => $when ] );

		$this->assertSame( [ 'message-' . $same->get_id(), 'message-' . $older->get_id() ], array_column( $data['items'], 'key' ) );
		$this->assertSame( $older->get_id(), $data['next']['before_id'] );
		$this->assertSame( 'message', $data['next']['before_kind'] );
	}
}
