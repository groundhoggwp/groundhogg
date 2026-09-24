<?php

use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Saved_Searches;

/**
 * Queries that allow stale results base rolling date ranges on the start of the minute, so their cache keys are stable
 */
class Query_Rolling_Date_Range_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		DB::clear_whole_cache();
	}

	protected function opened_in_last_7_days() {
		return [ [ 'type' => 'email_opened', 'date_range' => '7_days' ] ];
	}

	/**
	 * Timestamps in the SQL, the before and after bounds of any date ranges
	 */
	protected function get_timestamps( string $sql ) {
		preg_match_all( '/\b1\d{9}\b/', $sql, $matches );

		$this->assertNotEmpty( $matches[0], 'Expected timestamps in the SQL' );

		return array_map( 'intval', $matches[0] );
	}

	protected function assertMinuteAligned( string $sql ) {
		foreach ( $this->get_timestamps( $sql ) as $timestamp ) {
			$this->assertEquals( 0, $timestamp % MINUTE_IN_SECONDS, "$timestamp is not at the start of a minute" );
			$this->assertLessThanOrEqual( time(), $timestamp );
		}
	}

	public function test_stale_tolerant_rolling_range_is_minute_aligned() {
		$query = new Contact_Query( [ 'cache' => 300, 'filters' => [ $this->opened_in_last_7_days() ] ] );

		$this->assertMinuteAligned( $query->get_sql() );
	}

	public function test_stale_tolerant_queries_share_sql_within_a_minute() {

		// Don't straddle a minute boundary
		if ( time() % MINUTE_IN_SECONDS > 55 ) {
			sleep( 5 );
		}

		$a = ( new Contact_Query( [ 'cache' => 300, 'filters' => [ $this->opened_in_last_7_days() ] ] ) )->get_sql();
		sleep( 1 );
		$b = ( new Contact_Query( [ 'cache' => 300, 'filters' => [ $this->opened_in_last_7_days() ] ] ) )->get_sql();

		$this->assertEquals( $a, $b );
	}

	public function test_strict_rolling_range_uses_current_time() {
		$before_query = time();

		$query = new Contact_Query( [ 'filters' => [ $this->opened_in_last_7_days() ] ] );

		// The upper bound of "in the last 7 days" is now
		$this->assertGreaterThanOrEqual( $before_query, max( $this->get_timestamps( $query->get_sql() ) ) );
	}

	public function test_nested_query_inherits_stale_tolerance() {

		Saved_Searches::instance()->add( 'recent_openers', [
			'name'  => 'Recent openers',
			'query' => [ 'filters' => [ $this->opened_in_last_7_days() ] ],
		] );

		$query = new Contact_Query( [
			'cache'   => 300,
			'filters' => [ [ [ 'type' => 'saved_search', 'compare' => 'not_in', 'search' => 'recent_openers' ] ] ],
		] );

		$this->assertMinuteAligned( $query->get_sql() );

		Saved_Searches::instance()->delete( 'recent_openers' );
	}

	public function test_table_query_range_param_is_minute_aligned() {

		// cache after range, it must still apply
		$query = new Table_Query( 'activity', [ 'range' => '7_days', 'cache' => 300 ] );

		$this->assertMinuteAligned( $query->get_select_sql() );
	}
}
