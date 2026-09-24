<?php

use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\get_db;

/**
 * Writes that bypass DB::insert()/update()/delete() must still invalidate cached queries
 * for every table they touch, in both the static cache and the WP object cache
 */
class Query_Cache_Invalidation_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		DB::clear_whole_cache();
		wp_cache_flush();
	}

	public function tearDown(): void {
		delete_option( 'gh_use_object_cache' );
		delete_option( 'gh_purge_page_visits' );
		DB::clear_whole_cache();
		parent::tearDown();
	}

	public function cache_modes() {
		return [
			'static cache' => [ false ],
			'object cache' => [ true ],
		];
	}

	protected function set_cache_mode( bool $object_cache ) {
		if ( $object_cache ) {
			update_option( 'gh_use_object_cache', 'on' );
		} else {
			delete_option( 'gh_use_object_cache' );
		}
	}

	protected function get_meta_value( $contact_id, $meta_key ) {
		$query = new Table_Query( 'contactmeta' );
		$query->setSelect( 'meta_value' )
		      ->where()
		      ->equals( 'contact_id', $contact_id )
		      ->equals( 'meta_key', $meta_key );

		return $query->get_var();
	}

	protected function count_rows( $table, $where = [] ) {
		$query = new Table_Query( $table );
		foreach ( $where as $col => $val ) {
			$query->where()->equals( $col, $val );
		}

		return $query->count();
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_update_meta_invalidates_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$contact->update_meta( 'color', 'red' );

		$this->assertEquals( 'red', $this->get_meta_value( $contact->ID, 'color' ) );

		$contact->update_meta( 'color', 'blue' );

		$this->assertEquals( 'blue', $this->get_meta_value( $contact->ID, 'color' ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_add_meta_invalidates_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();

		$this->assertNull( $this->get_meta_value( $contact->ID, 'size' ) );

		$contact->add_meta( 'size', 'large' );

		$this->assertEquals( 'large', $this->get_meta_value( $contact->ID, 'size' ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_delete_meta_invalidates_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$contact->update_meta( 'color', 'red' );

		$this->assertEquals( 'red', $this->get_meta_value( $contact->ID, 'color' ) );

		$contact->delete_meta( 'color' );

		$this->assertNull( $this->get_meta_value( $contact->ID, 'color' ) );
	}

	/**
	 * Direct calls to the WP metadata API bypass Meta_DB entirely
	 *
	 * @dataProvider cache_modes
	 */
	public function test_direct_update_metadata_invalidates_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$contact->update_meta( 'color', 'red' );

		$this->assertEquals( 'red', $this->get_meta_value( $contact->ID, 'color' ) );

		get_db( 'contactmeta' )->maybe_resolve_table_conflict();
		update_metadata( 'contact', $contact->ID, 'color', 'green' );

		$this->assertEquals( 'green', $this->get_meta_value( $contact->ID, 'color' ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_move_events_to_history_invalidates_events_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->event_queue->create_many( 3, [
			'status' => Event::COMPLETE,
		] );

		$this->assertEquals( 0, $this->count_rows( 'events' ) );
		$this->assertEquals( 3, $this->count_rows( 'event_queue' ) );

		get_db( 'event_queue' )->move_events_to_history( [ 'status' => Event::COMPLETE ] );

		$this->assertEquals( 3, $this->count_rows( 'events' ) );
		$this->assertEquals( 0, $this->count_rows( 'event_queue' ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_move_events_to_queue_invalidates_queue_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->events->create_many( 2, [
			'status' => Event::COMPLETE,
		] );

		$this->assertEquals( 0, $this->count_rows( 'event_queue' ) );

		get_db( 'events' )->move_events_to_queue( [ 'status' => Event::COMPLETE ] );

		$this->assertEquals( 2, $this->count_rows( 'event_queue' ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_contact_merged_invalidates_tag_relationships_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$other   = $this->factory()->contacts->create_and_get();

		$contact->apply_tag( [ 'tag a' ] );
		$other->apply_tag( [ 'tag b' ] );

		$this->assertEquals( 1, $this->count_rows( 'tag_relationships', [ 'contact_id' => $contact->ID ] ) );

		get_db( 'tag_relationships' )->contact_merged( $contact, $other );

		$this->assertEquals( 2, $this->count_rows( 'tag_relationships', [ 'contact_id' => $contact->ID ] ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_page_visits_purge_invalidates_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );
		update_option( 'gh_purge_page_visits', 'on' );

		get_db( 'page_visits' )->insert( [
			'contact_id' => 1,
			'timestamp'  => time() - ( 365 * DAY_IN_SECONDS ),
			'path'       => '/old/',
		] );

		$this->assertEquals( 1, $this->count_rows( 'page_visits' ) );

		get_db( 'page_visits' )->purge();

		$this->assertEquals( 0, $this->count_rows( 'page_visits' ) );
	}
}
