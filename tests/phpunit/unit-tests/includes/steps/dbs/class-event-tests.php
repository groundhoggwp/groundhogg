<?php

class Event_Tests extends GH_UnitTestCase{

	public function test_get_by_desc_order(){

		$ids = $this->factory()->events->create_many( 2, [ 'queued_id' => 1234 ] );

		$event = \Groundhogg\get_event_by_queued_id( 1234 );

		$this->assertEquals( $ids[1], $event->get_id() );

	}

}
