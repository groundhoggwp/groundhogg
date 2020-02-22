<?php

class Event_Store_Tests extends GH_UnitTestCase {

	/**
	 * Test that the event IDs are returned in the proper order
	 */
	public function test_get_queued_event_ids_with_priority() {
		\Groundhogg\Plugin::$instance->dbs->truncate_dbs();

		$store = new \Groundhogg\Queue\Event_Store();

		// Should be first
		$this->factory()->events->create_many( 5, [ 'priority' => 10 ] );
		// Should be ignored
		$this->factory()->events->create_many( 5, [ 'priority' => 10, 'time' => time() + MINUTE_IN_SECONDS ] );
		// Should be last
		$this->factory()->events->create_many( 10, [ 'priority' => 100 ] );

		$ids = $store->get_queued_event_ids();

		$top_event    = new \Groundhogg\Event( array_shift( $ids ) );
		$bottom_event = new \Groundhogg\Event( array_pop( $ids ) );

		$this->assertEquals( 10, $top_event->get_priority() );
		$this->assertEquals( 100, $bottom_event->get_priority() );
	}

	/**
	 * Test that higher priority events are run first regardless of the scheduled run time.
	 */
	public function test_get_queued_event_ids_first_in_first_out_by_priority() {
		\Groundhogg\Plugin::$instance->dbs->truncate_dbs();

		$store = new \Groundhogg\Queue\Event_Store();

		$base_time = time();

		// Should be last
		$this->factory()->events->create_many( 1, [
			'time'      => $base_time - HOUR_IN_SECONDS,
			'priority'  => 100,
			'funnel_id' => 1,
		] );

		// Should be third
		$this->factory()->events->create_many( 1, [
			'time'      => $base_time - WEEK_IN_SECONDS,
			'priority'  => 50,
			'funnel_id' => 4,
		] );

		// Should be second
		$this->factory()->events->create_many( 1, [
			'time'      => $base_time - MINUTE_IN_SECONDS,
			'priority'  => 10,
			'funnel_id' => 2,
		] );

		// Should be first
		$this->factory()->events->create_many( 1, [
			'time'      => $base_time - DAY_IN_SECONDS,
			'priority'  => 10,
			'funnel_id' => 3,
		] );

		$ids = $store->get_queued_event_ids();

		$events = [];

		foreach ( $ids as $id ) {
			$events[] = new \Groundhogg\Event( $id );
		}

		$this->assertEquals( 3, $events[0]->get_funnel_id() );
		$this->assertEquals( 2, $events[1]->get_funnel_id() );
		$this->assertEquals( 4, $events[2]->get_funnel_id() );
		$this->assertEquals( 1, $events[3]->get_funnel_id() );
	}

}
