<?php

use Groundhogg\Broadcast;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\SchedulingException;

/**
 * Covers the removal of the legacy contact query engine. Filters the query engine can't apply must narrow
 * the results to nothing, never fall back to another engine or widen the results.
 */
class Contact_Query_Filter_Errors_Tests extends GH_UnitTestCase {

	const FILTER_ERROR = 'Groundhogg\DB\Query\Filters::handle_filter_error';
	const QUERY_ERROR = 'Groundhogg\Contact_Query::handle_query_exception';

	protected $contacts = [];

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		$this->contacts = $this->factory()->contacts->create_many( 5 );

		foreach ( $this->contacts as $contact_id ) {
			$this->factory()->activity->create( [
				'contact_id'    => $contact_id,
				'activity_type' => 'wp_login',
			] );
		}
	}

	public function test_legacy_contact_query_stub_uses_contact_query() {
		$this->setExpectedDeprecated( 'Groundhogg\Legacy_Contact_Query' );

		$query = new \Groundhogg\Legacy_Contact_Query( [
			'include' => [ $this->contacts[0], $this->contacts[1] ]
		] );

		$this->assertCount( 2, $query->query() );
		$this->assertEquals( 2, $query->count() );
	}

	public function test_legacy_contact_query_stub_never_widens() {
		$this->setExpectedDeprecated( 'Groundhogg\Legacy_Contact_Query::register_filter' );
		$this->setExpectedDeprecated( 'Groundhogg\Legacy_Contact_Query::generic_number_compare' );
		$this->setExpectedDeprecated( 'Groundhogg\Legacy_Contact_Query::get_before_and_after_from_filter_date_range' );

		$this->assertFalse( \Groundhogg\Legacy_Contact_Query::register_filter( 'some_filter', '__return_true' ) );
		$this->assertEquals( '1=0', \Groundhogg\Legacy_Contact_Query::generic_number_compare( 'ID', 'equals', 1 ) );
		$this->assertEquals( [ 'before' => '', 'after' => '' ], \Groundhogg\Legacy_Contact_Query::get_before_and_after_from_filter_date_range( [] ) );
	}

	public function test_unregistered_filter_matches_nothing() {
		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		$query = new Contact_Query();
		$items = $query->query( [
			'filters' => [
				[
					[ 'type' => 'not_a_registered_filter' ],
				]
			]
		] );

		$this->assertCount( 0, $items );
	}

	public function test_unregistered_filter_only_affects_its_own_group() {
		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		$query = new Contact_Query();
		$items = $query->query( [
			'filters' => [
				// AND group containing a bad filter matches nothing
				[
					[ 'type' => 'contact_id', 'compare' => 'equals', 'value' => $this->contacts[0] ],
					[ 'type' => 'not_a_registered_filter' ],
				],
				// OR group is unaffected
				[
					[ 'type' => 'contact_id', 'compare' => 'equals', 'value' => $this->contacts[1] ],
				],
			]
		] );

		$this->assertEquals( [ $this->contacts[1] ], wp_list_pluck( $items, 'ID' ) );
	}

	public function test_filter_error_action_fires() {
		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		$fired = 0;
		$track = function () use ( &$fired ) {
			$fired ++;
		};

		add_action( 'groundhogg/query/filter_error', $track );

		$query = new Contact_Query();
		$query->count( [
			'exclude_filters' => [
				[
					[ 'type' => 'not_a_registered_filter' ],
				]
			]
		] );

		remove_action( 'groundhogg/query/filter_error', $track );

		$this->assertEquals( 1, $fired );
	}

	/**
	 * Shape of the Patchstack report: an unregistered filter to force the old fallback, alongside
	 * a custom_activity filter carrying raw SQL in value/value_compare, and SQL appended to number.
	 */
	public function test_reported_injection_does_not_reach_the_database() {
		global $wpdb;

		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		$query = new Contact_Query();
		$items = $query->query( [
			'number'  => '100 NOT_VALID_SQL',
			'filters' => [
				[
					[ 'type' => 'not_a_registered_filter' ],
					[
						'type'          => 'custom_activity',
						'activity'      => 'wp_login',
						'value_compare' => 'IN',
						'value'         => '-1) OR (1=1',
					],
				]
			]
		] );

		$this->assertEmpty( $wpdb->last_error );
		$this->assertCount( 0, $items );
	}

	/**
	 * Without the fallback, the same input goes through the modern engine, which prepares the value.
	 * Were the predicate injected, every contact with a wp_login activity would match.
	 */
	public function test_custom_activity_value_is_prepared() {
		global $wpdb;

		$query = new Contact_Query();
		$items = $query->query( [
			'number'  => '100 NOT_VALID_SQL',
			'filters' => [
				[
					[
						'type'          => 'custom_activity',
						'activity'      => 'wp_login',
						'value_compare' => 'IN',
						'value'         => '-1) OR (1=1',
					],
				]
			]
		] );

		$this->assertEmpty( $wpdb->last_error );
		$this->assertCount( 0, $items );
	}

	public function test_deprecated_register_filter_is_not_applied() {
		$this->setExpectedDeprecated( 'Groundhogg\Contact_Query::register_filter' );
		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		Contact_Query::register_filter( 'old_style_filter', function () {
			return '1=1';
		} );

		$query = new Contact_Query();
		$items = $query->query( [
			'filters' => [
				[
					[ 'type' => 'old_style_filter' ],
				]
			]
		] );

		$this->assertCount( 0, $items );
	}

	public function test_query_var_error_matches_nothing_and_gives_valid_sql() {
		global $wpdb;

		$this->setExpectedIncorrectUsage( self::QUERY_ERROR );

		$query = new Contact_Query();
		$sql   = $query->get_sql( [
			'select'       => 'ID',
			'meta_key'     => 'some_key',
			'meta_value'   => 'some_value',
			'meta_compare' => 'NOT_A_COMPARISON',
		] );

		$this->assertStringContainsString( '1=0', $sql );

		$ids = $wpdb->get_col( $sql );

		$this->assertEmpty( $wpdb->last_error );
		$this->assertCount( 0, $ids );
	}

	public function test_broadcast_with_unappliable_filter_is_not_scheduled() {
		$this->setExpectedIncorrectUsage( self::FILTER_ERROR );

		$email = new Email();
		$email->create( [
			'title'   => 'Test',
			'subject' => 'Test',
			'content' => 'Test',
			'status'  => 'ready',
		] );

		$this->expectException( SchedulingException::class );

		Broadcast::schedule( [
			'object_id'   => $email->get_id(),
			'object_type' => 'email',
			'send_now'    => true,
			'query'       => [
				'exclude_filters' => [
					[
						[ 'type' => 'not_a_registered_filter' ],
					]
				]
			],
		] );
	}
}
