<?php

use Groundhogg\Abilities\Schemas\Segment_Schema;
use Groundhogg\Broadcast;
use Groundhogg\Contact_Query;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_db;

/**
 * Segment_Schema's behavioural properties: email_activity, page_visits, form_submissions,
 * flow_conversions and activity. Each is checked for the Filters-DSL it produces and for the
 * contacts it matches, both through to_contact_query() and by running the to_filters() output
 * directly, since stored rules only keep the latter.
 */
class Segment_Schema_Behaviour_Tests extends GH_UnitTestCase {

	const DAYS = 7;

	/**
	 * @var int[] contact IDs keyed by label
	 */
	protected $contacts = [];

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		$this->contacts = [];
	}

	/**
	 * A timestamp just inside the within_days window
	 *
	 * @return int
	 */
	protected function inside() {
		return time() - ( self::DAYS * DAY_IN_SECONDS ) + HOUR_IN_SECONDS;
	}

	/**
	 * A timestamp just outside the within_days window
	 *
	 * @return int
	 */
	protected function outside() {
		return time() - ( self::DAYS * DAY_IN_SECONDS ) - HOUR_IN_SECONDS;
	}

	/**
	 * Create contacts with the given labels
	 *
	 * @param string ...$labels
	 */
	protected function make_contacts( ...$labels ) {
		foreach ( $labels as $label ) {
			$this->contacts[ $label ] = $this->factory()->contacts->create();
		}
	}

	protected function add_activity( $label, $type, $timestamp, $args = [] ) {
		$this->factory()->activity->create( array_merge( [
			'contact_id'    => $this->contacts[ $label ],
			'activity_type' => $type,
			'timestamp'     => $timestamp,
			'funnel_id'     => 0,
			'step_id'       => 0,
			'email_id'      => 0,
			'event_id'      => 0,
		], $args ) );
	}

	protected function add_page_visit( $label, $path, $timestamp ) {
		// insert() rather than add(), which merges repeat visits within the last hour into one row
		get_db( 'page_visits' )->insert( [
			'contact_id' => $this->contacts[ $label ],
			'path'       => $path,
			'timestamp'  => $timestamp,
		] );
	}

	protected function add_submission( $label, $step_id, $timestamp, $type = 'form' ) {
		get_db( 'submissions' )->add( [
			'contact_id'   => $this->contacts[ $label ],
			'step_id'      => $step_id,
			'type'         => $type,
			// submissions are stored in site time
			'date_created' => ( new DateTimeHelper( $timestamp ) )->ymdhis(),
		] );
	}

	/**
	 * Map contact IDs back to their labels, sorted
	 *
	 * @param array $rows
	 *
	 * @return string[]
	 */
	protected function labels( array $rows ) {
		$ids    = array_map( 'absint', wp_list_pluck( $rows, 'ID' ) );
		$labels = array_keys( array_intersect( $this->contacts, $ids ) );
		sort( $labels );

		return $labels;
	}

	/**
	 * Assert the segment matches exactly these contacts, via to_contact_query() and via to_filters()
	 *
	 * @param string[] $expected
	 * @param array    $input
	 */
	protected function assertSegment( array $expected, array $input ) {

		sort( $expected );

		$query = Segment_Schema::to_contact_query( $input );
		$this->assertNotWPError( $query );
		$this->assertSame( $expected, $this->labels( $query->query() ), 'to_contact_query() ' . wp_json_encode( $input ) );

		$filters = Segment_Schema::to_filters( $input );
		$this->assertNotWPError( $filters );
		$query = new Contact_Query();
		$this->assertSame( $expected, $this->labels( $query->query( [ 'include_filters' => $filters ] ) ), 'to_filters() ' . wp_json_encode( $input ) );
	}

	/* email_activity */

	public function test_email_activity_to_filters() {

		$this->assertSame( [
			[
				[
					'type'          => 'custom_activity',
					'activity'      => 'email_link_click',
					'email_id'      => [ 5, 6 ],
					'count'         => 2,
					'count_compare' => 'greater_than_or_equal_to',
					'date_range'    => 'x_days',
					'days'          => 7,
				],
			],
		], Segment_Schema::to_filters( [
			'email_activity' => [ 'action' => 'clicked', 'email_ids' => [ 5, 6 ], 'min_count' => 2, 'within_days' => 7 ],
		] ) );

		// Empty object: opened any email, any time
		$this->assertSame( [
			[
				[
					'type'          => 'custom_activity',
					'activity'      => 'email_opened',
					'email_id'      => [],
					'count'         => 1,
					'count_compare' => 'greater_than_or_equal_to',
				],
			],
		], Segment_Schema::to_filters( [ 'email_activity' => [] ] ) );
	}

	public function test_email_activity_contacts() {

		$this->make_contacts( 'broadcast', 'old', 'other_email', 'clicker', 'twice' );

		// a broadcast open counts too
		$this->add_activity( 'broadcast', 'email_opened', $this->inside(), [ 'email_id' => 10, 'funnel_id' => Broadcast::FUNNEL_ID ] );
		$this->add_activity( 'old', 'email_opened', $this->outside(), [ 'email_id' => 10, 'funnel_id' => 50 ] );
		$this->add_activity( 'other_email', 'email_opened', $this->inside(), [ 'email_id' => 11, 'funnel_id' => 50 ] );
		$this->add_activity( 'clicker', 'email_link_click', $this->inside(), [ 'email_id' => 10, 'funnel_id' => 50 ] );
		$this->add_activity( 'twice', 'email_opened', $this->inside(), [ 'email_id' => 10, 'funnel_id' => 50 ] );
		$this->add_activity( 'twice', 'email_opened', time() - HOUR_IN_SECONDS, [ 'email_id' => 10, 'funnel_id' => 50 ] );

		$this->assertSegment( [ 'broadcast', 'twice' ], [ 'email_activity' => [ 'email_ids' => [ 10 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'broadcast', 'old', 'twice' ], [ 'email_activity' => [ 'email_ids' => [ 10 ] ] ] );
		$this->assertSegment( [ 'broadcast', 'other_email', 'twice' ], [ 'email_activity' => [ 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'broadcast', 'old', 'other_email', 'twice' ], [ 'email_activity' => [] ] );
		$this->assertSegment( [ 'broadcast', 'other_email', 'twice' ], [ 'email_activity' => [ 'email_ids' => [ 10, 11 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'twice' ], [ 'email_activity' => [ 'email_ids' => [ 10 ], 'min_count' => 2 ] ] );
		$this->assertSegment( [ 'clicker' ], [ 'email_activity' => [ 'action' => 'clicked', 'within_days' => self::DAYS ] ] );
	}

	/* page_visits */

	public function test_page_visits_to_filters() {

		$this->assertSame( [
			[
				[
					'type'          => 'page_visited',
					'link'          => '/pricing/',
					'compare'       => 'equals',
					'count'         => 2,
					'count_compare' => 'greater_than_or_equal_to',
					'date_range'    => 'x_days',
					'days'          => 7,
				],
			],
		], Segment_Schema::to_filters( [
			'page_visits' => [ 'path' => '/pricing/', 'compare' => 'equals', 'min_count' => 2, 'within_days' => 7 ],
		] ) );

		// Distinct pages alone doesn't also check visit counts
		$this->assertSame( [
			[
				[
					'type'          => 'distinct_pages_visited',
					'link'          => '/blog/',
					'compare'       => 'starts_with',
					'count'         => 3,
					'count_compare' => 'greater_than_or_equal_to',
				],
			],
		], Segment_Schema::to_filters( [
			'page_visits' => [ 'path' => '/blog/', 'min_distinct_pages' => 3 ],
		] ) );

		// Both together give both conditions
		$filters = Segment_Schema::to_filters( [
			'page_visits' => [ 'path' => '/blog/', 'min_distinct_pages' => 3, 'min_count' => 2 ],
		] );

		$this->assertSame( [ 'page_visited', 'distinct_pages_visited' ], wp_list_pluck( $filters[0], 'type' ) );
		$this->assertSame( [ 2, 3 ], wp_list_pluck( $filters[0], 'count' ) );

		// Empty object: any page
		$this->assertSame( [
			[
				[
					'type'          => 'page_visited',
					'link'          => '',
					'compare'       => 'starts_with',
					'count'         => 1,
					'count_compare' => 'greater_than_or_equal_to',
				],
			],
		], Segment_Schema::to_filters( [ 'page_visits' => [] ] ) );
	}

	public function test_page_visits_contacts() {

		$this->make_contacts( 'pricing', 'pricing_old', 'pricing_twice', 'reader', 'rereader', 'partial_reader' );

		$this->add_page_visit( 'pricing', '/pricing/', $this->inside() );
		$this->add_page_visit( 'pricing_old', '/pricing/', $this->outside() );
		$this->add_page_visit( 'pricing_twice', '/pricing/', $this->inside() );
		$this->add_page_visit( 'pricing_twice', '/pricing/', time() - HOUR_IN_SECONDS );

		// three different posts
		$this->add_page_visit( 'reader', '/blog/a/', $this->inside() );
		$this->add_page_visit( 'reader', '/blog/b/', $this->inside() );
		$this->add_page_visit( 'reader', '/blog/c/', $this->inside() );

		// the same post three times
		$this->add_page_visit( 'rereader', '/blog/a/', $this->inside() );
		$this->add_page_visit( 'rereader', '/blog/a/', $this->inside() );
		$this->add_page_visit( 'rereader', '/blog/a/', $this->inside() );

		// three different posts, one of them too long ago
		$this->add_page_visit( 'partial_reader', '/blog/a/', $this->inside() );
		$this->add_page_visit( 'partial_reader', '/blog/b/', $this->inside() );
		$this->add_page_visit( 'partial_reader', '/blog/c/', $this->outside() );

		$this->assertSegment( [ 'pricing', 'pricing_twice' ], [ 'page_visits' => [ 'path' => '/pricing/', 'compare' => 'equals', 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'pricing', 'pricing_old', 'pricing_twice' ], [ 'page_visits' => [ 'path' => '/pricing' ] ] );
		$this->assertSegment( [ 'pricing', 'pricing_old', 'pricing_twice' ], [ 'page_visits' => [ 'path' => 'https://example.com/pricing/', 'compare' => 'equals' ] ] );
		$this->assertSegment( [ 'pricing_twice' ], [ 'page_visits' => [ 'path' => '/pricing/', 'min_count' => 2 ] ] );
		$this->assertSegment( [ 'rereader' ], [ 'page_visits' => [ 'path' => 'blog', 'compare' => 'contains', 'min_count' => 3 ] ] );

		$this->assertSegment( [ 'reader' ], [ 'page_visits' => [ 'path' => '/blog/', 'min_distinct_pages' => 3, 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'partial_reader', 'reader' ], [ 'page_visits' => [ 'path' => '/blog/', 'min_distinct_pages' => 3 ] ] );
		$this->assertSegment( [ 'partial_reader', 'reader' ], [ 'page_visits' => [ 'min_distinct_pages' => 2, 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'rereader' ], [ 'page_visits' => [ 'path' => '/blog/', 'min_distinct_pages' => 1, 'min_count' => 3 ] ] );

		$this->assertSegment( [ 'partial_reader', 'pricing', 'pricing_twice', 'reader', 'rereader' ], [ 'page_visits' => [ 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'partial_reader', 'pricing', 'pricing_old', 'pricing_twice', 'reader', 'rereader' ], [ 'page_visits' => [] ] );
	}

	/* form_submissions */

	public function test_form_submissions_to_filters() {

		$this->assertSame( [
			[
				[
					'type'       => 'form_submissions',
					'form_id'    => [ 100, 200 ],
					'date_range' => 'x_days',
					'days'       => 7,
				],
			],
		], Segment_Schema::to_filters( [
			'form_submissions' => [ 'form_ids' => [ 100, 200 ], 'within_days' => 7 ],
		] ) );

		$this->assertSame( [ [ [ 'type' => 'form_submissions', 'form_id' => [] ] ] ], Segment_Schema::to_filters( [ 'form_submissions' => [] ] ) );
	}

	public function test_form_submissions_contacts() {

		$this->make_contacts( 'recent', 'old', 'other_form', 'webhook' );

		$this->add_submission( 'recent', 100, $this->inside() );
		$this->add_submission( 'old', 100, $this->outside() );
		$this->add_submission( 'other_form', 200, $this->inside() );
		$this->add_submission( 'webhook', 100, $this->inside(), 'webhook' );

		$this->assertSegment( [ 'recent' ], [ 'form_submissions' => [ 'form_ids' => [ 100 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'old', 'recent' ], [ 'form_submissions' => [ 'form_ids' => [ 100 ] ] ] );
		$this->assertSegment( [ 'other_form', 'recent' ], [ 'form_submissions' => [ 'form_ids' => [ 100, 200 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'other_form', 'recent' ], [ 'form_submissions' => [ 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'old', 'other_form', 'recent' ], [ 'form_submissions' => [] ] );
	}

	/* flow_conversions */

	public function test_flow_conversions_to_filters() {

		$this->assertSame( [
			[
				[
					'type'          => 'custom_activity',
					'activity'      => 'funnel_conversion',
					'funnel_id'     => [ 50 ],
					'step_id'       => [ 500, 501 ],
					'count'         => 1,
					'count_compare' => 'greater_than_or_equal_to',
					'date_range'    => 'x_days',
					'days'          => 7,
				],
			],
		], Segment_Schema::to_filters( [
			'flow_conversions' => [ 'funnel_ids' => [ 50 ], 'step_ids' => [ 500, 501 ], 'within_days' => 7 ],
		] ) );
	}

	public function test_flow_conversions_contacts() {

		$this->make_contacts( 'recent', 'old', 'other_flow', 'not_converted' );

		$this->add_activity( 'recent', 'funnel_conversion', $this->inside(), [ 'funnel_id' => 50, 'step_id' => 500 ] );
		$this->add_activity( 'old', 'funnel_conversion', $this->outside(), [ 'funnel_id' => 50, 'step_id' => 500 ] );
		$this->add_activity( 'other_flow', 'funnel_conversion', $this->inside(), [ 'funnel_id' => 60, 'step_id' => 600 ] );
		// other activity at the conversion step isn't a conversion
		$this->add_activity( 'not_converted', 'email_opened', $this->inside(), [ 'funnel_id' => 50, 'step_id' => 500 ] );

		$this->assertSegment( [ 'recent' ], [ 'flow_conversions' => [ 'step_ids' => [ 500 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'old', 'recent' ], [ 'flow_conversions' => [ 'funnel_ids' => [ 50 ] ] ] );
		$this->assertSegment( [ 'other_flow', 'recent' ], [ 'flow_conversions' => [ 'funnel_ids' => [ 50, 60 ], 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [], [ 'flow_conversions' => [ 'funnel_ids' => [ 60 ], 'step_ids' => [ 500 ] ] ] );
		$this->assertSegment( [ 'old', 'other_flow', 'recent' ], [ 'flow_conversions' => [] ] );
	}

	/**
	 * The filter must match what Step::run() actually records for a conversion step
	 */
	public function test_flow_conversions_matches_tracked_conversion() {

		$funnel = $this->factory()->funnels->create_and_get( [ 'status' => 'active' ] );
		$step   = $this->factory()->steps->create_and_get( [
			'funnel_id'     => $funnel->get_id(),
			'step_type'     => 'tag_applied',
			'step_group'    => 'benchmark',
			'step_status'   => 'active',
			'is_conversion' => 1,
		] );

		$this->assertTrue( $step->is_conversion() );

		$this->make_contacts( 'converted', 'other' );

		$event = $this->factory()->events->create_and_get( [
			'funnel_id'  => $funnel->get_id(),
			'step_id'    => $step->get_id(),
			'contact_id' => $this->contacts['converted'],
		] );

		$result = $step->run( \Groundhogg\get_contactdata( $this->contacts['converted'] ), $event );
		$this->assertNotWPError( $result );
		$this->assertTrue( (bool) $result );

		$this->assertSegment( [ 'converted' ], [ 'flow_conversions' => [ 'step_ids' => [ $step->get_id() ], 'within_days' => 1 ] ] );
		$this->assertSegment( [ 'converted' ], [ 'flow_conversions' => [ 'funnel_ids' => [ $funnel->get_id() ] ] ] );
	}

	/* activity */

	public function test_activity_to_filters() {

		$this->assertSame( [
			[
				[
					'type'          => 'custom_activity',
					'activity'      => 'wp_login',
					'count'         => 3,
					'count_compare' => 'greater_than_or_equal_to',
					'date_range'    => 'x_days',
					'days'          => 7,
				],
			],
		], Segment_Schema::to_filters( [
			'activity' => [ 'type' => 'wp_login', 'min_count' => 3, 'within_days' => 7 ],
		] ) );
	}

	public function test_activity_requires_type() {
		$this->assertWPError( Segment_Schema::to_filters( [ 'activity' => [] ] ) );
		$this->assertWPError( Segment_Schema::to_query( [ 'activity' => [ 'min_count' => 2 ] ] ) );
		$this->assertWPError( Segment_Schema::to_contact_query( [ 'activity' => [ 'type' => '' ] ] ) );
	}

	public function test_activity_contacts() {

		$this->make_contacts( 'recent', 'old', 'frequent', 'logged_out' );

		$this->add_activity( 'recent', 'wp_login', $this->inside() );
		$this->add_activity( 'old', 'wp_login', $this->outside() );
		$this->add_activity( 'frequent', 'wp_login', $this->inside() );
		$this->add_activity( 'frequent', 'wp_login', $this->inside() );
		$this->add_activity( 'frequent', 'wp_login', time() - HOUR_IN_SECONDS );
		$this->add_activity( 'logged_out', 'wp_logout', $this->inside() );

		$this->assertSegment( [ 'frequent', 'recent' ], [ 'activity' => [ 'type' => 'wp_login', 'within_days' => self::DAYS ] ] );
		$this->assertSegment( [ 'frequent', 'old', 'recent' ], [ 'activity' => [ 'type' => 'wp_login' ] ] );
		$this->assertSegment( [ 'frequent' ], [ 'activity' => [ 'type' => 'wp_login', 'min_count' => 3 ] ] );
		$this->assertSegment( [ 'logged_out' ], [ 'activity' => [ 'type' => 'wp_logout' ] ] );
	}

	/* combining */

	public function test_behaviour_combines_with_exclude_segment_and_other_params() {

		$this->make_contacts( 'engaged', 'engaged_customer', 'idle' );

		$this->add_activity( 'engaged', 'wp_login', $this->inside() );
		$this->add_activity( 'engaged_customer', 'wp_login', $this->inside() );
		$this->add_submission( 'engaged_customer', 100, $this->inside() );

		// "logged in, but hasn't submitted a form" goes through exclude_segment
		$query = Segment_Schema::to_contact_query( [
			'activity'        => [ 'type' => 'wp_login' ],
			'exclude_segment' => [ 'form_submissions' => [] ],
		] );

		$this->assertSame( [ 'engaged' ], $this->labels( $query->query() ) );

		// ANDed with the native params
		$this->assertSegment( [ 'engaged_customer' ], [
			'include'          => [ $this->contacts['engaged_customer'], $this->contacts['idle'] ],
			'activity'         => [ 'type' => 'wp_login' ],
			'form_submissions' => [ 'within_days' => self::DAYS ],
		] );
	}

	public function test_to_query_folds_behaviour_into_include_filters() {

		$query = Segment_Schema::to_query( [
			'include'        => [ 1 ],
			'email_activity' => [ 'within_days' => 3 ],
		] );

		$this->assertSame( [ 1 ], $query['include'] );
		$this->assertSame( [ 'custom_activity' ], wp_list_pluck( $query['include_filters'][0], 'type' ) );
		$this->assertSame( 3, $query['include_filters'][0][0]['days'] );
	}

	public function test_behaviour_properties_are_in_schema_and_exclude_segment() {

		$properties = Segment_Schema::properties();

		foreach ( [ 'email_activity', 'page_visits', 'form_submissions', 'flow_conversions', 'activity' ] as $key ) {
			$this->assertSame( 'object', $properties[ $key ]['type'], $key );
			$this->assertArrayHasKey( 'within_days', $properties[ $key ]['properties'], $key );
			$this->assertArrayHasKey( $key, $properties['exclude_segment']['properties'], $key );
		}
	}

	public function test_behaviour_properties_are_audiences_even_when_empty() {

		foreach ( [ 'email_activity', 'page_visits', 'form_submissions', 'flow_conversions' ] as $key ) {
			$this->assertTrue( Segment_Schema::has_audience( [ $key => [] ] ), $key );
		}

		$this->assertTrue( Segment_Schema::has_audience( [ 'activity' => [ 'type' => 'wp_login' ] ] ) );

		$this->assertFalse( Segment_Schema::has_audience( [] ) );
		$this->assertFalse( Segment_Schema::has_audience( [ 'marketable' => true ] ) );
		// Non-object params still need a value
		$this->assertFalse( Segment_Schema::has_audience( [ 'tags_include' => [] ] ) );
	}
}
