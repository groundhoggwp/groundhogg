<?php

use Groundhogg\Classes\Message;
use function Groundhogg\get_contactdata;

/**
 * @see \Groundhogg\Api\V4\Messages_Api::read() GET gh/v4/messages, which is of the messages on contacts that the user can see, and so are its counts
 */
class Messages_Read_Scope_Tests extends GH_UnitTestCase {

	/**
	 * The Groundhogg tables are not rolled back between tests or runs, so every address and word is unique to the run,
	 * and a test looks at what it made, and not everything that there is.
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

	protected function create_rep() {
		return self::factory()->user->create( [ 'role' => 'sales_rep' ] );
	}

	protected function create_contact( $local, $owner_id ) {
		return get_contactdata( self::factory()->contacts->create( [ 'email' => $local . '@' . $this->run_id . '.example.com', 'owner_id' => $owner_id ] ) );
	}

	protected function create_message( $contact, $direction, array $args = [] ) {

		$message = new Message();
		$message->create( array_merge( [
			'object_type'  => 'contact',
			'object_id'    => $contact->get_id(),
			'direction'    => $direction,
			'from_address' => $direction === 'inbound' ? $contact->get_email() : 'me@example.com',
			'to_address'   => $direction === 'inbound' ? 'me@example.com' : $contact->get_email(),
			'subject'      => 'Subject ' . uniqid(),
			'content'      => '<p>Some words</p>',
			'is_read'      => 1,
		], $args ) );

		return $message;
	}

	protected function read( array $params = [] ) {

		$request = new WP_REST_Request( 'GET', '/gh/v4/messages' );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}

	/** @return string[] the ids of the messages that came back, as get_id() gives them */
	protected function ids( array $params ) {

		$response = $this->read( $params );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );

		return array_map( fn( $item ) => (string) $item->ID, $response->get_data()['items'] );
	}

	/**
	 * A rep with a contact of their own, and someone else with another that says something secret
	 *
	 * @return array [ rep id, own contact, other contact, own message, other message ]
	 */
	protected function scenario() {

		$rep   = $this->create_rep();
		$other = $this->create_rep();

		$mine   = $this->create_contact( 'mine', $rep );
		$theirs = $this->create_contact( 'theirs', $other );

		$own    = $this->create_message( $mine, 'inbound', [ 'content' => '<p>Nothing to see</p>' ] );
		$secret = $this->create_message( $theirs, 'inbound', [ 'subject' => 'Re: invoice', 'content' => '<p>The code is ' . $this->run_id . '-swordfish</p>' ] );

		return [ $rep, $mine, $theirs, $own, $secret ];
	}

	public function test_a_rep_is_told_of_the_messages_on_their_contacts_and_not_of_the_rest() {

		[ $rep, $mine, $theirs, $own, $secret ] = $this->scenario();

		wp_set_current_user( $rep );

		$this->assertSame( [ $own->get_id() ], $this->ids( [ 'object_id' => $mine->get_id() ] ) );
		$this->assertSame( [], $this->ids( [ 'object_id' => $theirs->get_id() ] ) );
	}

	public function test_the_count_of_a_rep_does_not_say_anything_of_messages_on_contacts_that_are_not_theirs() {

		[ $rep, $mine, $theirs ] = $this->scenario();

		wp_set_current_user( $rep );

		// whether a contact they can't see has replied
		$response = $this->read( [ 'count' => 1, 'object_id' => $theirs->get_id(), 'direction' => 'inbound' ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $response->get_data()['total_items'] );

		// and it is what it should be for their own
		$this->assertSame( 1, $this->read( [ 'count' => 1, 'object_id' => $mine->get_id() ] )->get_data()['total_items'] );
	}

	public function test_a_search_of_a_rep_does_not_find_what_someone_else_was_written_and_the_total_does_not_say_it_is_there() {

		[ $rep, , , , $secret ] = $this->scenario();

		wp_set_current_user( $rep );

		foreach ( [ [ 'search' => $this->run_id . '-swordfish' ], [ 'search' => $this->run_id . '-swordfish', 'found_rows' => true ], [ 'search' => 'Re: invoice', 'count' => 1 ] ] as $params ) {

			$response = $this->read( $params );

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( 0, $response->get_data()['total_items'], wp_json_encode( $params ) );
			$this->assertSame( [], $response->get_data()['items'] ?? [], wp_json_encode( $params ) );
		}

		// the same search, by someone that can see it, finds it
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( 1, $this->read( [ 'search' => $this->run_id . '-swordfish', 'count' => 1 ] )->get_data()['total_items'] );
		$this->assertSame( [ $secret->get_id() ], $this->ids( [ 'search' => $this->run_id . '-swordfish' ] ) );
	}

	public function test_what_the_generic_read_took_to_get_around_it_is_not_taken() {

		[ $rep, , $theirs ] = $this->scenario();

		wp_set_current_user( $rep );

		$attempts = [
			// their own search columns, and functions and selects
			[ 'search' => $this->run_id . '-swordfish', 'search_columns' => 'content', 'count' => 1 ],
			[ 'func' => 'count', 'object_id' => $theirs->get_id() ],
			[ 'select' => 'content', 'object_id' => $theirs->get_id() ],
			// a where of their own
			[ 'where' => [ [ 'object_id', '=', $theirs->get_id() ] ], 'count' => 1 ],
			[ 'content' => [ 'LIKE', '%swordfish%' ], 'count' => 1 ],
		];

		foreach ( $attempts as $params ) {

			$response = $this->read( $params );

			$this->assertSame( 200, $response->get_status(), wp_json_encode( $params ) );
			$this->assertSame( [], $response->get_data()['items'] ?? [], wp_json_encode( $params ) );
			$this->assertLessThanOrEqual( 1, $response->get_data()['total_items'], wp_json_encode( $params ) );
			$this->assertArrayNotHasKey( 'content', (array) ( $response->get_data()['items'][0]->data ?? [] ) );
		}

		// an array is not an id, so it filters nothing, and what is left is still only what the rep can see: their own message
		$this->assertSame( 1, $this->read( [ 'count' => 1, 'object_id' => [ $theirs->get_id() ] ] )->get_data()['total_items'], 'an array is not an id' );
	}

	public function test_an_administrator_is_told_of_every_message() {

		[ , $mine, $theirs, $own, $secret ] = $this->scenario();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertSame( [ $secret->get_id() ], $this->ids( [ 'object_id' => $theirs->get_id() ] ) );
		$this->assertSame( [ $own->get_id() ], $this->ids( [ 'object_id' => $mine->get_id() ] ) );
		$this->assertSame( 1, $this->read( [ 'count' => 1, 'object_id' => $theirs->get_id() ] )->get_data()['total_items'] );
	}

	public function test_a_manager_with_a_team_is_told_of_the_messages_of_their_team_and_no_one_else() {

		$rep_a = $this->create_rep();
		$rep_b = $this->create_rep();
		$rep_c = $this->create_rep();

		$manager = self::factory()->user->create( [ 'role' => 'sales_manager' ] );
		update_user_meta( $manager, 'gh_sales_team_ids', [ $rep_a, $rep_b ] );

		$a = $this->create_message( $this->create_contact( 'a', $rep_a ), 'inbound' );
		$b = $this->create_message( $this->create_contact( 'b', $rep_b ), 'inbound' );
		$c = $this->create_message( $this->create_contact( 'c', $rep_c ), 'inbound' );

		wp_set_current_user( $manager );

		$mine = $this->ids( [ 'include' => implode( ',', [ $a->get_id(), $b->get_id(), $c->get_id() ] ) ] );

		$this->assertEqualsCanonicalizing( [ $a->get_id(), $b->get_id() ], $mine );
		$this->assertSame( 2, $this->read( [ 'count' => 1, 'include' => implode( ',', [ $a->get_id(), $b->get_id(), $c->get_id() ] ) ] )->get_data()['total_items'] );
	}

	public function test_the_list_can_still_be_filtered_ordered_and_paged() {

		$rep     = $this->create_rep();
		$contact = $this->create_contact( 'talker', $rep );

		$first  = $this->create_message( $contact, 'inbound', [ 'date_created' => '2026-01-01 10:00:00' ] );
		$second = $this->create_message( $contact, 'outbound', [ 'date_created' => '2026-01-02 10:00:00' ] );
		$third  = $this->create_message( $contact, 'inbound', [ 'date_created' => '2026-01-03 10:00:00' ] );

		wp_set_current_user( $rep );

		$base = [ 'object_id' => $contact->get_id() ];

		// newest first, by default
		$this->assertSame( [ $third->get_id(), $second->get_id(), $first->get_id() ], $this->ids( $base ) );
		$this->assertSame( [ $first->get_id(), $second->get_id(), $third->get_id() ], $this->ids( $base + [ 'orderby' => 'date_created', 'order' => 'asc' ] ) );

		// a column
		$this->assertSame( [ $third->get_id(), $first->get_id() ], $this->ids( $base + [ 'direction' => 'inbound' ] ) );

		// the times are UTC
		$this->assertSame( [ $second->get_id() ], $this->ids( $base + [ 'after' => '2026-01-02 00:00:00', 'before' => '2026-01-02 23:59:59' ] ) );

		// pages, and the total is of all of them
		$response = $this->read( $base + [ 'limit' => 2, 'offset' => 2, 'orderby' => 'date_created', 'order' => 'asc' ] );
		$this->assertSame( 3, $response->get_data()['total_items'] );
		$this->assertCount( 1, $response->get_data()['items'] );
		$this->assertSame( $third->get_id(), (string) $response->get_data()['items'][0]->ID );
	}

	public function test_it_is_still_only_for_someone_that_can_view_contacts() {

		[ , , $theirs ] = $this->scenario();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertNotSame( 200, $this->read( [ 'object_id' => $theirs->get_id() ] )->get_status() );

		wp_set_current_user( 0 );
		$this->assertNotSame( 200, $this->read( [ 'count' => 1 ] )->get_status() );
	}
}
