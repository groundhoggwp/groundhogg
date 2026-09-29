<?php

use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_db;

class Event_Queue_Tests extends GH_UnitTestCase {

	public function test_run_queue_with_no_events() {
		$this->factory()->truncate();

		// run_queue() returns the total processed in the request, which other tests add to
		$before = Limits::get_actions_processed();

		$count = Plugin::instance()->event_queue->run_queue();

		$this->assertEquals( 0, $count - $before );
	}

	public function test_run_queue_with_10_events() {

		$this->factory()->truncate();

		$this->factory()->event_queue->create_many( 10 );

		$before = Limits::get_actions_processed();

		$count = Plugin::instance()->event_queue->run_queue();

		$this->assertEquals( 10, $count - $before );
	}

	public function test_run_queue_with_non_existent_contacts() {

		$this->factory()->truncate();

		$this->factory()->event_queue->create_many( 10 );

		Plugin::instance()->event_queue->run_queue();

		$count = get_db( 'events' )->count( [ 'status' => Event::FAILED ] );

		$this->assertEquals( 10, $count );

	}

	public function test_run_queue_with_existing_contacts() {

		$this->factory()->truncate();

		$ids = $this->factory()->contacts->create_many( 10 );

		foreach ( $ids as $id ){
			get_db( 'event_queue' )->add( [
				'time'           => time(),
				'contact_id'     => $id,
				'event_type'     => Event::TEST_SUCCESS,
				'status'         => 'waiting',
				'priority'       => 10,
			] );
		}

		Plugin::instance()->event_queue->run_queue();

		$count = get_db( 'events' )->count( [ 'status' => Event::COMPLETE ] );

		$this->assertEquals( 10, $count );

	}

}
