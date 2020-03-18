<?php

use Groundhogg\Plugin;

class Event_Queue_Tests extends GH_UnitTestCase {

	public function test_run_queue_with_no_events() {
		$this->factory()->truncate();

		$count = Plugin::instance()->event_queue->run_queue();

		$this->assertEquals( 0, $count );
	}

	public function test_run_queue_with_10_events() {

		$this->factory()->truncate();

		$this->factory()->events->create_many( 10 );

		$count = Plugin::instance()->event_queue->run_queue();

		$this->assertEquals( 10, $count );
	}

}
