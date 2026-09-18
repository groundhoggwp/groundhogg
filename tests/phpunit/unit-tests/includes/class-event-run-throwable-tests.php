<?php

use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Delay_Timer;
use function Groundhogg\get_db;

/**
 * Event::run() must contain anything a misbehaving step throws, including \Error types
 */
class Event_Run_Throwable_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		remove_all_filters( 'groundhogg/steps/run/do_step' );
		parent::tearDown();
	}

	/**
	 * Create a queued event for an active step, claimed and waiting so it can be run
	 *
	 * @return Event
	 */
	protected function make_event() {

		$this->factory()->truncate();

		$funnel = new Funnel( $this->factory()->funnels->create( [ 'status' => 'active' ] ) );

		$step = $funnel->add_step( [
			'step_title'  => 'Bad step',
			'step_type'   => Delay_Timer::TYPE,
			'step_group'  => Action::GROUP,
			'step_status' => 'active',
		] );

		$contact_id = $this->factory()->contacts->create();

		// Claims live on the event queue table, which is where the queue runs events from
		$event_id = $this->factory()->event_queue->create( [
			'funnel_id'  => $funnel->get_id(),
			'step_id'    => $step->get_id(),
			'contact_id' => $contact_id,
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
			'claim'      => 'test-claim',
		] );

		return new Event( $event_id, 'event_queue' );
	}

	protected function assert_event_failed_with_exception() {
		// The queue moves finished events to history afterward, so right after run() it's still in the queue table
		$this->assertEquals( 1, get_db( 'event_queue' )->count( [
			'status'     => Event::FAILED,
			'error_code' => 'exception',
		] ) );
	}

	public function test_exception_from_step_fails_event() {

		add_filter( 'groundhogg/steps/run/do_step', function () {
			throw new \Exception( 'step blew up' );
		} );

		$result = $this->make_event()->run();

		$this->assertWPError( $result );
		$this->assertEquals( 'exception', $result->get_error_code() );
		$this->assertEquals( 'step blew up', $result->get_error_message() );
		$this->assert_event_failed_with_exception();
	}

	public function test_error_from_step_fails_event() {

		add_filter( 'groundhogg/steps/run/do_step', function () {
			// TypeError extends \Error, not \Exception
			throw new \TypeError( 'bad type' );
		} );

		$result = $this->make_event()->run();

		$this->assertWPError( $result );
		$this->assertEquals( 'exception', $result->get_error_code() );
		$this->assertEquals( 'bad type', $result->get_error_message() );
		$this->assert_event_failed_with_exception();
	}

}
