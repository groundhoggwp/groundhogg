<?php

use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Saved_Searches;
use function Groundhogg\get_db;

/**
 * Cached query results depend on every table the query reads from, including joins and sub queries built by filters
 */
class Query_Cache_Dependencies_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		DB::clear_whole_cache();
		wp_cache_flush();
	}

	public function tearDown(): void {
		delete_option( 'gh_use_object_cache' );
		remove_all_filters( 'groundhogg/query/table_cache_group' );
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

	protected function count_contacts( array $filters, array $query_vars = [] ) {
		$query = new Contact_Query( array_merge( [ 'filters' => [ $filters ] ], $query_vars ) );

		return $query->count();
	}

	/**
	 * Run the callback and return how many db queries it ran
	 */
	protected function num_queries( callable $callback ) {
		global $wpdb;
		$before = $wpdb->num_queries;
		call_user_func( $callback );

		return $wpdb->num_queries - $before;
	}

	protected function tag_filter( $tag_id ) {
		return [ [ 'type' => 'tags', 'compare' => 'includes', 'tags' => [ $tag_id ] ] ];
	}

	protected function opened_filter() {
		return [ [ 'type' => 'email_opened' ] ];
	}

	protected function log_open( $contact_id ) {
		get_db( 'activity' )->add( [
			'contact_id'    => $contact_id,
			'activity_type' => 'email_opened',
			'funnel_id'     => 2, // funnel_id 1 is broadcasts, which the filter excludes
			'step_id'       => 1,
			'email_id'      => 1,
			'timestamp'     => time(),
		] );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_tag_filter_sees_applied_tag( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$tag_id  = get_db( 'tags' )->add( [ 'tag_name' => 'Customer' ] );

		$this->assertEquals( 0, $this->count_contacts( $this->tag_filter( $tag_id ) ) );

		get_db( 'tag_relationships' )->add( $tag_id, $contact->ID );

		$this->assertEquals( 1, $this->count_contacts( $this->tag_filter( $tag_id ) ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_activity_filter_sees_logged_activity( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();

		$this->assertEquals( 0, $this->count_contacts( $this->opened_filter() ) );

		$this->log_open( $contact->ID );

		$this->assertEquals( 1, $this->count_contacts( $this->opened_filter() ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_meta_filter_sees_updated_meta( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$filter  = [ [ 'type' => 'meta', 'meta' => 'color', 'compare' => 'equals', 'value' => 'red' ] ];

		$this->assertEquals( 0, $this->count_contacts( $filter ) );

		$contact->update_meta( 'color', 'red' );

		$this->assertEquals( 1, $this->count_contacts( $filter ) );
	}

	/**
	 * A saved search excluded with not_in becomes a nested Contact_Query sub query
	 *
	 * @dataProvider cache_modes
	 */
	public function test_nested_sub_query_dependencies( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contacts = $this->factory()->contacts->create_many( 3 );

		Saved_Searches::instance()->add( 'openers', [
			'name'  => 'Openers',
			'query' => [ 'filters' => [ $this->opened_filter() ] ],
		] );

		$filter = [ [ 'type' => 'saved_search', 'compare' => 'not_in', 'search' => 'openers' ] ];

		$this->assertEquals( 3, $this->count_contacts( $filter ) );

		$this->log_open( $contacts[0] );

		$this->assertEquals( 2, $this->count_contacts( $filter ) );

		Saved_Searches::instance()->delete( 'openers' );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_unrelated_write_keeps_cache( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();
		$tag_id  = get_db( 'tags' )->add( [ 'tag_name' => 'Customer' ] );

		$this->count_contacts( $this->tag_filter( $tag_id ) );

		$this->log_open( $contact->ID );

		$this->assertEquals( 0, $this->num_queries( fn() => $this->count_contacts( $this->tag_filter( $tag_id ) ) ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_repeated_filtered_query_is_cached( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->contacts->create_many( 2 );

		$this->count_contacts( $this->opened_filter() );

		$this->assertEquals( 0, $this->num_queries( fn() => $this->count_contacts( $this->opened_filter() ) ) );
	}

	public function test_untracked_table_is_not_cached() {
		global $wpdb;

		$this->factory()->contacts->create_many( 2 );

		$count = function () use ( $wpdb ) {
			$users = new Query( $wpdb->users );
			$users->setSelect( 'ID' );

			$query = new Table_Query( 'contacts' );
			$query->where()->notIn( 'user_id', $users );

			return $query->count();
		};

		$count();

		$this->assertEquals( 1, $this->num_queries( $count ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_untracked_table_with_cache_group_is_cached( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		global $wpdb;

		add_filter( 'groundhogg/query/table_cache_group', function ( $group, $table ) use ( $wpdb ) {
			return $table === $wpdb->users ? 'test/users' : $group;
		}, 10, 2 );

		$this->factory()->contacts->create_many( 2 );

		$count = function () use ( $wpdb ) {
			$users = new Query( $wpdb->users );
			$users->setSelect( 'ID' );

			$query = new Table_Query( 'contacts' );
			$query->where()->notIn( 'user_id', $users );

			return $query->count();
		};

		$count();

		$this->assertEquals( 0, $this->num_queries( $count ) );

		DB::set_group_last_changed( 'test/users' );

		$this->assertEquals( 1, $this->num_queries( $count ) );
	}

	/**
	 * Sub queries built as raw SQL during filter setup are still tracked
	 */
	public function test_query_created_during_filter_setup_is_tracked() {
		global $wpdb;

		$this->factory()->contacts->create_many( 2 );

		Contact_Query::filters()->register( 'test_not_a_user', function ( $filter, $where ) use ( $wpdb ) {
			$users = new Query( $wpdb->users );
			$users->setSelect( 'ID' );
			$where->addCondition( "{$where->query->alias}.user_id NOT IN ( $users )" );
		} );

		$filter = [ [ 'type' => 'test_not_a_user' ] ];

		$this->assertEquals( 2, $this->count_contacts( $filter ) );
		$this->assertEquals( 1, $this->num_queries( fn() => $this->count_contacts( $filter ) ) );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_cache_false_always_queries( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->contacts->create_many( 2 );

		$this->count_contacts( [], [ 'cache' => false ] );

		$this->assertEquals( 1, $this->num_queries( fn() => $this->count_contacts( [], [ 'cache' => false ] ) ) );

		$query = new Table_Query( 'contacts', [ 'cache' => false ] );
		$query->count();

		$this->assertEquals( 1, $this->num_queries( fn() => ( new Table_Query( 'contacts', [ 'cache' => false ] ) )->count() ) );
	}

	/**
	 * Stale tolerant queries ignore changes to tables other than the main one
	 *
	 * @dataProvider cache_modes
	 */
	public function test_cache_seconds_tolerates_stale_dependencies( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$contact = $this->factory()->contacts->create_and_get();

		$this->assertEquals( 0, $this->count_contacts( $this->opened_filter(), [ 'cache' => 300 ] ) );

		$this->log_open( $contact->ID );

		$this->assertEquals( 0, $this->count_contacts( $this->opened_filter(), [ 'cache' => 300 ] ) );
		$this->assertEquals( 1, $this->count_contacts( $this->opened_filter() ) );
	}

	public function test_get_var_offsets_are_cached_separately() {
		$this->factory()->contacts->create();

		$query = new Table_Query( 'contacts' );
		$query->setSelect( 'ID', 'email' );

		$id    = $query->get_var( 0 );
		$email = $query->get_var( 1 );

		$this->assertNotEquals( $id, $email );
	}
}
