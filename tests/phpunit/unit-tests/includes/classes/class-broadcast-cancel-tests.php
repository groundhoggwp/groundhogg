<?php

use Groundhogg\Broadcast;
use Groundhogg\Event;
use function Groundhogg\get_db;

class Broadcast_Cancel_Tests extends GH_UnitTestCase {

	protected function make_broadcast(): Broadcast {
		$id = get_db( 'broadcasts' )->add( [
			'object_id'   => 1,
			'object_type' => 'email',
			'query'       => [],
			'status'      => 'scheduled',
		] );

		return new Broadcast( $id );
	}

	protected function queue_event( array $args ) {
		return $this->factory()->event_queue->create( $args );
	}

	/**
	 * Cancelling a broadcast moves only its own cancelled events to the history table, cancelled events that belong
	 * to other broadcasts or to flows are left where they are
	 */
	public function test_cancel_only_moves_its_own_events_to_history() {

		$broadcast = $this->make_broadcast();
		$other     = $this->make_broadcast();

		$this->queue_event( [
			'funnel_id'  => Broadcast::FUNNEL_ID,
			'step_id'    => $broadcast->get_id(),
			'event_type' => Event::BROADCAST,
			'status'     => Event::WAITING,
		] );

		$this->queue_event( [
			'funnel_id'  => Broadcast::FUNNEL_ID,
			'step_id'    => $broadcast->get_id(),
			'event_type' => Event::BROADCAST,
			'status'     => Event::WAITING,
		] );

		$other_broadcast_event = $this->queue_event( [
			'funnel_id'  => Broadcast::FUNNEL_ID,
			'step_id'    => $other->get_id(),
			'event_type' => Event::BROADCAST,
			'status'     => Event::CANCELLED,
		] );

		$funnel_event = $this->queue_event( [
			'funnel_id'  => 5,
			'step_id'    => 7,
			'event_type' => Event::FUNNEL,
			'status'     => Event::CANCELLED,
		] );

		$this->assertTrue( $broadcast->cancel() );

		$this->assertEquals( 2, get_db( 'events' )->count( [ 'step_id' => $broadcast->get_id(), 'funnel_id' => Broadcast::FUNNEL_ID, 'status' => Event::CANCELLED ] ) );
		$this->assertEquals( 0, get_db( 'event_queue' )->count( [ 'step_id' => $broadcast->get_id(), 'funnel_id' => Broadcast::FUNNEL_ID ] ) );

		$this->assertEquals( 1, get_db( 'event_queue' )->count( [ 'ID' => $other_broadcast_event ] ), 'Another broadcast\'s cancelled event stays in the queue' );
		$this->assertEquals( 1, get_db( 'event_queue' )->count( [ 'ID' => $funnel_event ] ), 'A flow\'s cancelled event stays in the queue' );
		$this->assertEquals( 0, get_db( 'events' )->count( [ 'step_id' => 7, 'funnel_id' => 5 ] ) );
	}
}
