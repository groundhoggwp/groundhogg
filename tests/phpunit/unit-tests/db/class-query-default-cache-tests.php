<?php

use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Reports;
use function Groundhogg\get_db;

/**
 * Query::with_cache() sets the cache mode for queries created within it, which reports use to allow stale results
 */
class Query_Default_Cache_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		DB::clear_whole_cache();
	}

	protected function count_openers( array $query_vars = [] ) {
		return ( new Contact_Query( array_merge( [ 'filters' => [ [ [ 'type' => 'email_opened' ] ] ] ], $query_vars ) ) )->count();
	}

	protected function log_open( $contact_id ) {
		get_db( 'activity' )->add( [
			'contact_id'    => $contact_id,
			'activity_type' => 'email_opened',
			'funnel_id'     => 2,
			'step_id'       => 1,
			'email_id'      => 1,
			'timestamp'     => time(),
		] );
	}

	public function test_with_cache_sets_default_for_new_queries() {
		$this->assertFalse( ( new Table_Query( 'contacts' ) )->allows_stale_results() );

		Query::with_cache( 300, function () {
			$this->assertTrue( ( new Table_Query( 'contacts' ) )->allows_stale_results() );
		} );

		$this->assertFalse( ( new Table_Query( 'contacts' ) )->allows_stale_results() );
	}

	public function test_with_cache_nests() {
		Query::with_cache( 300, function () {
			Query::with_cache( false, function () {
				$this->assertFalse( ( new Table_Query( 'contacts' ) )->allows_stale_results() );
			} );

			$this->assertTrue( ( new Table_Query( 'contacts' ) )->allows_stale_results() );
		} );
	}

	public function test_with_cache_restores_after_exception() {
		try {
			Query::with_cache( 300, function () {
				throw new Exception( 'oops' );
			} );
		} catch ( Exception $e ) {
		}

		$this->assertFalse( ( new Table_Query( 'contacts' ) )->allows_stale_results() );
	}

	public function test_explicit_cache_var_overrides_default() {
		$contact = $this->factory()->contacts->create_and_get();

		Query::with_cache( 300, function () use ( $contact ) {
			$this->assertEquals( 0, $this->count_openers() );
			$this->log_open( $contact->ID );

			// Stale by default, fresh when asked
			$this->assertEquals( 0, $this->count_openers() );
			$this->assertEquals( 1, $this->count_openers( [ 'cache' => true ] ) );
		} );
	}

	public function test_report_queries_allow_stale_results() {
		$contact = $this->factory()->contacts->create_and_get();

		$reports = new Reports( time() - DAY_IN_SECONDS, time() );
		$reports->add( 'test_openers', fn() => $this->count_openers() );

		$this->assertEquals( 0, $reports->get_data( 'test_openers' ) );

		$this->log_open( $contact->ID );

		$this->assertEquals( 0, $reports->get_data( 'test_openers' ) );

		// Outside of a report the query is strict
		$this->assertEquals( 1, $this->count_openers() );
	}
}
