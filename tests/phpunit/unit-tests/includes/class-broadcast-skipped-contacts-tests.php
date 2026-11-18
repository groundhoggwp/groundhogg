<?php

use Groundhogg\Broadcast;
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\NoContactsException;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_db;

/**
 * Contacts the "marketable" query counts but the scheduler then skips, because another plugin or custom code
 * made them unmarketable through the groundhogg/contact/is_marketable filter (the SQL can't know about that).
 *
 * A batch where every contact is skipped used to stop scheduling: nothing was inserted, enqueue_batch()
 * returned 0 without moving past the batch, so the background task retried it forever and a "send now"
 * broadcast failed with "Unable to schedule events".
 */
class Broadcast_Skipped_Contacts_Tests extends GH_UnitTestCase {

	/**
	 * @var Email
	 */
	protected $email;

	/**
	 * @var int
	 */
	protected $tag;

	/**
	 * @var int[]
	 */
	protected $contacts = [];

	/**
	 * @var int[] contacts the is_marketable filter rejects
	 */
	protected $unmarketable = [];

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->email = new Email( [
			'title'   => 'Skipped contacts test',
			'subject' => 'Skipped contacts test',
			'content' => '<p>content</p>',
			'status'  => 'ready',
		] );

		$this->tag = get_db( 'tags' )->add( [ 'tag_name' => 'audience', 'tag_slug' => 'audience' ] );

		$this->contacts     = [];
		$this->unmarketable = [];

		for ( $i = 0; $i < 4; $i ++ ) {
			$id = $this->factory()->contacts->create( [ 'optin_status' => Preferences::UNCONFIRMED ] );
			( new Contact( $id ) )->apply_tag( [ $this->tag ] );
			$this->contacts[] = $id;
		}

		add_filter( 'groundhogg/contact/is_marketable', [ $this, 'filter_is_marketable' ], 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'groundhogg/contact/is_marketable', [ $this, 'filter_is_marketable' ], 10 );

		parent::tearDown();
	}

	/**
	 * Stands in for a plugin like the one that marks contacts with a "Do Not Contact" tag as unmarketable
	 */
	public function filter_is_marketable( $is_marketable, $contact ) {
		return in_array( $contact->get_id(), $this->unmarketable, true ) ? false : $is_marketable;
	}

	protected function schedule( string $segment_type ) {
		return Broadcast::schedule( [
			'object_id'    => $this->email->get_id(),
			'object_type'  => 'email',
			'date'         => wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ),
			'time'         => '09:00:00',
			'segment_type' => $segment_type,
			'query'        => [ 'tags_include' => [ $this->tag ] ],
		] );
	}

	protected function queued_contact_ids( int $broadcast_id ): array {

		$events = event_queue_db()->query( [
			'event_type' => Event::BROADCAST,
			'step_id'    => $broadcast_id,
			'funnel_id'  => Broadcast::FUNNEL_ID,
		] );

		$ids = array_map( 'absint', wp_list_pluck( $events, 'contact_id' ) );
		sort( $ids );

		return $ids;
	}

	public function test_batch_where_every_contact_is_skipped_moves_on() {

		$this->unmarketable = $this->contacts;

		// dynamic, so nothing is queued until enqueue_batch() is called
		$broadcast = $this->schedule( 'dynamic' );

		$items = $broadcast->enqueue_batch();

		$this->assertSame( 4, $items, 'a fully skipped batch still counts as processed' );
		$this->assertSame( max( $this->contacts ), absint( $broadcast->get_meta( 'last_id' ) ), 'scheduling moved past the batch' );
		$this->assertSame( 4, absint( $broadcast->get_meta( 'num_scheduled' ) ) );
		$this->assertSame( 0, $broadcast->get_num_queued() );

		$this->assertTrue( $broadcast->enqueue_batch(), 'the next batch is empty, so scheduling finishes' );
		$this->assertTrue( $broadcast->is_scheduled() );
		$this->assertSame( [], $this->queued_contact_ids( $broadcast->get_id() ) );
	}

	public function test_skipped_contacts_do_not_stop_the_rest_being_queued() {

		// the lowest IDs come first in the batch
		$this->unmarketable = array_slice( $this->contacts, 0, 2 );

		$broadcast = $this->schedule( 'fixed' );

		$this->assertTrue( $broadcast->is_scheduled() );
		$this->assertSame( array_slice( $this->contacts, 2 ), $this->queued_contact_ids( $broadcast->get_id() ) );
		$this->assertSame( 2, $broadcast->get_num_queued() );
	}

	public function test_fixed_broadcast_nobody_can_receive_throws_a_clear_error() {

		$this->unmarketable = $this->contacts;

		$count_before = get_db( 'broadcasts' )->count();

		try {
			$this->schedule( 'fixed' );
			$this->fail( 'Expected a NoContactsException' );
		} catch ( NoContactsException $e ) {
			$this->assertStringContainsString( 'None of the 4 matching contacts', $e->getMessage() );
		}

		$this->assertSame( $count_before, get_db( 'broadcasts' )->count(), 'the empty broadcast is not left behind' );
	}

	public function test_legacy_batch_where_every_contact_is_skipped_moves_on() {

		$this->unmarketable = $this->contacts;

		$broadcast = $this->schedule( 'dynamic' );

		// a broadcast that started scheduling before last_id existed uses the legacy offset scheduler
		$broadcast->update_meta( 'num_scheduled', 1 );
		$this->assertTrue( $broadcast->use_legacy_schedule() );

		$items = $broadcast->enqueue_batch();

		$this->assertSame( 3, $items, 'a fully skipped batch still counts as processed' );
		$this->assertSame( 4, absint( $broadcast->get_meta( 'num_scheduled' ) ) );
		$this->assertTrue( $broadcast->is_scheduled() );
		$this->assertSame( [], $this->queued_contact_ids( $broadcast->get_id() ) );
	}

	public function test_status_text_explains_a_contact_made_unmarketable_by_a_filter() {

		$contact = new Contact( $this->contacts[0] );

		$this->assertSame( 'Will receive marketing.', Plugin::instance()->preferences->get_optin_status_text( $contact ) );

		$this->unmarketable = [ $contact->get_id() ];

		$this->assertFalse( $contact->is_marketable() );
		$this->assertStringContainsString( 'another plugin or custom code', Plugin::instance()->preferences->get_optin_status_text( $contact ) );
	}

	public function test_status_text_for_a_filtered_confirmed_contact() {

		$contact = new Contact( $this->contacts[0] );
		$contact->update( [ 'optin_status' => Preferences::CONFIRMED ] );

		$this->unmarketable = [ $contact->get_id() ];

		$this->assertStringContainsString( 'another plugin or custom code', Plugin::instance()->preferences->get_optin_status_text( $contact ) );
	}

	public function test_the_plugin_can_give_its_own_reason() {

		$contact            = new Contact( $this->contacts[0] );
		$this->unmarketable = [ $contact->get_id() ];

		$reason = function ( $text ) {
			return 'Tagged Do Not Contact.';
		};

		add_filter( 'groundhogg/preferences/optin_status_text', $reason );

		$text = Plugin::instance()->preferences->get_optin_status_text( $contact );

		remove_filter( 'groundhogg/preferences/optin_status_text', $reason );

		$this->assertSame( 'Tagged Do Not Contact.', $text );
	}

	public function test_status_text_unchanged_for_contacts_core_already_rejects() {

		$contact = new Contact( $this->contacts[0] );
		$contact->update( [ 'optin_status' => Preferences::UNSUBSCRIBED ] );

		$this->unmarketable = [ $contact->get_id() ];

		$this->assertSame( 'They will not receive marketing.', Plugin::instance()->preferences->get_optin_status_text( $contact ) );
	}
}
