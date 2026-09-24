<?php

use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Table_Query;

/**
 * Found rows are cached with the results they belong to, and the base Query class is not cached
 */
class Query_Found_Rows_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		DB::clear_whole_cache();
		wp_cache_flush();
	}

	public function tearDown(): void {
		delete_option( 'gh_use_object_cache' );
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

	protected function paged_contacts_query() {
		$query = new Table_Query( 'contacts' );
		$query->setLimit( 2 )->setFoundRows( true );

		return $query;
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_found_rows_match_results_after_a_write( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->contacts->create_many( 5 );

		$query = $this->paged_contacts_query();
		$this->assertCount( 2, $query->get_results() );

		// Bumps last_changed and runs other queries before found rows are read
		$this->factory()->contacts->create();

		$this->assertEquals( 5, $query->get_found_rows() );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_found_rows_on_cache_hit( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->contacts->create_many( 5 );

		$this->paged_contacts_query()->get_results();

		$query = $this->paged_contacts_query();

		global $wpdb;
		$num_queries = $wpdb->num_queries;

		$this->assertCount( 2, $query->get_results() );

		// Something else runs in between
		$wpdb->get_results( 'SELECT 1' );

		$this->assertEquals( 5, $query->get_found_rows() );
		$this->assertEquals( $num_queries + 1, $wpdb->num_queries, 'Results and found rows should both come from the cache' );
	}

	public function test_found_rows_without_get_results() {
		$this->factory()->contacts->create_many( 5 );

		$this->assertEquals( 5, $this->paged_contacts_query()->get_found_rows() );
	}

	public function test_found_rows_after_query_changes() {
		$this->factory()->contacts->create_many( 5 );

		$query = $this->paged_contacts_query();
		$query->get_results();

		$this->assertEquals( 5, $query->get_found_rows() );

		$query->where()->lessThan( 'ID', 0 );

		$this->assertEquals( 0, $query->get_found_rows() );
	}

	public function test_found_rows_without_calc_found_rows_is_result_count() {
		$this->factory()->contacts->create_many( 5 );

		$query = new Table_Query( 'contacts' );
		$query->setLimit( 3 );
		$query->get_results();

		$this->assertEquals( 3, $query->get_found_rows() );
	}

	public function test_get_objects_found_rows() {
		$this->factory()->contacts->create_many( 5 );

		$query = $this->paged_contacts_query();

		$this->assertCount( 2, $query->get_objects() );
		$this->assertEquals( 5, $query->get_found_rows() );
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_contact_query_found_items_on_cache_hit( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		$this->factory()->contacts->create_many( 5 );

		foreach ( [ 'miss', 'hit' ] as $run ) {
			$query = new Contact_Query( [ 'limit' => 2, 'found_rows' => true ] );
			$this->assertCount( 2, $query->query(), $run );
			$this->assertEquals( 5, $query->found_items, $run );
		}
	}

	/**
	 * @dataProvider cache_modes
	 */
	public function test_base_query_is_not_cached( $object_cache ) {
		$this->set_cache_mode( $object_cache );

		global $wpdb;

		$user_id = self::factory()->user->create();

		$count = function () use ( $wpdb, $user_id ) {
			$query = new Query( $wpdb->usermeta );
			$query->where()
			      ->equals( 'user_id', $user_id )
			      ->equals( 'meta_key', 'gh_cache_test' );

			return $query->count();
		};

		$this->assertEquals( 0, $count() );

		add_user_meta( $user_id, 'gh_cache_test', 'yes' );

		$this->assertEquals( 1, $count() );
	}
}
