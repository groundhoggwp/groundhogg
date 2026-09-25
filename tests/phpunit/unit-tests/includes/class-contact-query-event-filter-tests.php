<?php

use Groundhogg\Contact_Query;
use Groundhogg\Event;

/**
 * When an AND group has more than one event filter, the second one is a sub query instead of a join,
 * and should apply the same conditions
 */
class Contact_Query_Event_Filter_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
	}

	protected function complete_funnel_event( $contact_id, $step_id, $time = null ) {
		$this->factory()->events->create( [
			'contact_id' => $contact_id,
			'funnel_id'  => 1,
			'step_id'    => $step_id,
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
			'time'       => $time ?: time(),
		] );
	}

	protected function query_ids( array $and_group ) {
		$query = new Contact_Query( [ 'filters' => [ $and_group ] ] );

		return array_map( 'absint', wp_list_pluck( $query->query(), 'ID' ) );
	}

	public function test_and_funnel_history_filters_with_different_steps() {

		[ $both, $only_first, $only_second, $neither ] = $this->factory()->contacts->create_many( 4 );

		$this->complete_funnel_event( $both, 10 );
		$this->complete_funnel_event( $both, 20 );
		$this->complete_funnel_event( $only_first, 10 );
		$this->complete_funnel_event( $only_second, 20 );

		$ids = $this->query_ids( [
			[ 'type' => 'funnel_history', 'funnel_id' => 1, 'step_id' => 10 ],
			[ 'type' => 'funnel_history', 'funnel_id' => 1, 'step_id' => 20 ],
		] );

		$this->assertEquals( [ $both ], $ids );
	}

	public function test_and_funnel_history_filters_respect_date_range() {

		[ $recent, $stale ] = $this->factory()->contacts->create_many( 2 );

		$this->complete_funnel_event( $recent, 10 );
		$this->complete_funnel_event( $recent, 20 );
		$this->complete_funnel_event( $stale, 10 );
		$this->complete_funnel_event( $stale, 20, time() - 30 * DAY_IN_SECONDS );

		$ids = $this->query_ids( [
			[ 'type' => 'funnel_history', 'funnel_id' => 1, 'step_id' => 10 ],
			[ 'type' => 'funnel_history', 'funnel_id' => 1, 'step_id' => 20, 'date_range' => 'after', 'after' => '-7 days' ],
		] );

		$this->assertEquals( [ $recent ], $ids );
	}
}
