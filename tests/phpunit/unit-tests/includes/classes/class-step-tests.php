<?php

use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Benchmarks\Account_Created;
use function Groundhogg\event_queue_db;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class Step_Tests extends GH_UnitTestCase {

	/**
	 * A funnel with a delay, a benchmark, and another delay, with a contact waiting at the first delay
	 *
	 * @param string $status the funnel status
	 *
	 * @return array [ Funnel, Step[], Contact ]
	 */
	protected function make_funnel_with_waiting_contact( $status = 'active' ) {

		// Clear existing data
		$this->factory()->truncate();

		$contact = get_contactdata( $this->factory()->contacts->create() );

		$funnel = new Funnel( [
			'title'  => 'test funnel',
			'status' => $status
		] );

		$delay = [
			'delay_amount' => '3',
			'delay_type'   => 'days',
			'run_when'     => 'now',
		];

		$steps = [
			$funnel->add_step( [
				'step_type'   => Delay_Timer::TYPE,
				'step_group'  => Step::ACTION,
				'step_title'  => 'test step 1',
				'step_status' => $status,
				'meta'        => $delay
			] ),
			$funnel->add_step( [
				'step_type'   => Account_Created::TYPE,
				'step_group'  => Step::BENCHMARK,
				'step_title'  => 'test benchmark',
				'step_status' => $status,
			] ),
			$funnel->add_step( [
				'step_type'   => Delay_Timer::TYPE,
				'step_group'  => Step::ACTION,
				'step_title'  => 'test step 2',
				'step_status' => $status,
				'meta'        => $delay
			] ),
		];

		// active steps save settings as pending changes, commit them like the funnel editor does
		foreach ( $steps as $step ) {
			$step->commit();
		}

		$steps[0]->enqueue( $contact );

		if ( $status !== 'active' ) {
			$funnel->pause_events();
		}

		return [ $funnel, $steps, $contact ];
	}

	/**
	 * The contact's events still in the queue, with any status
	 *
	 * @param $contact
	 *
	 * @return array
	 */
	protected function get_queued_events( $contact ) {
		return event_queue_db()->query( [
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
		] );
	}

	public function test_deleted_step_with_waiting_contacts_is_pending_until_commit() {

		[ $funnel, $steps ] = $this->make_funnel_with_waiting_contact();

		$steps[0]->delete();

		$deleted = $funnel->get_deleted_steps();

		$this->assertCount( 1, $deleted );
		$this->assertEquals( $steps[0]->get_id(), $deleted[0]->get_id() );
		$this->assertEquals( 1, $funnel->count_pending_events( $deleted[0] ) );

		// only actions that aren't deleted can be moved to
		$this->assertEquals( [ $steps[2]->get_id() ], wp_list_pluck( $funnel->get_move_targets(), 'ID' ) );
	}

	public function test_committing_a_deleted_step_cancels_waiting_events_by_default() {

		[ $funnel, $steps, $contact ] = $this->make_funnel_with_waiting_contact();

		$steps[0]->delete();
		$funnel->commit();

		$this->assertEmpty( $this->get_queued_events( $contact ) );
		$this->assertCount( 0, $steps[2]->get_waiting_contacts() );

		// the cancelled event is kept in the history
		$history = get_db( 'events' )->query( [
			'contact_id' => $contact->get_id(),
			'step_id'    => $steps[0]->get_id(),
		] );

		$this->assertCount( 1, $history );
		$this->assertEquals( Event::CANCELLED, $history[0]->status );
		$this->assertEquals( 'step_deleted', $history[0]->error_code );

		// so the step is archived rather than deleted, and is no longer part of the flow
		$step = new Step( $steps[0]->get_id() );
		$this->assertTrue( $step->exists() );
		$this->assertTrue( $step->is_archived() );
		$this->assertNotContains( $step->get_id(), wp_list_pluck( $funnel->get_steps(), 'ID' ) );
		$this->assertNotContains( $step->get_id(), wp_list_pluck( $funnel->get_real_steps(), 'ID' ) );
	}

	public function test_deleted_step_without_history_is_removed() {

		[ $funnel, $steps ] = $this->make_funnel_with_waiting_contact();

		// nobody has been through the last step
		$steps[2]->delete();
		$funnel->commit();

		$this->assertFalse( ( new Step( $steps[2]->get_id() ) )->exists() );
	}

	public function test_deleted_step_with_completed_events_is_archived() {

		[ $funnel, $steps, $contact ] = $this->make_funnel_with_waiting_contact();

		get_db( 'events' )->add( [
			'time'       => time(),
			'funnel_id'  => $funnel->get_id(),
			'step_id'    => $steps[2]->get_id(),
			'contact_id' => $contact->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
		] );

		$steps[2]->delete();
		$funnel->commit();

		$step = new Step( $steps[2]->get_id() );
		$this->assertTrue( $step->is_archived() );

		// the history stays and still points to the step
		$this->assertTrue( get_db( 'events' )->exists( [ 'step_id' => $step->get_id() ] ) );

		// deactivating the funnel doesn't bring it back
		$funnel->update( [ 'status' => 'inactive' ] );
		$this->assertTrue( ( new Step( $step->get_id() ) )->is_archived() );
	}

	public function test_committing_a_deleted_step_can_move_waiting_contacts_to_another_action() {

		[ $funnel, $steps, $contact ] = $this->make_funnel_with_waiting_contact();

		$steps[0]->delete();
		$funnel->commit( [
			$steps[0]->get_id() => [ 'action' => 'move', 'to' => $steps[2]->get_id() ]
		] );

		$this->assertFalse( ( new Step( $steps[0]->get_id() ) )->exists() );

		$waiting = $steps[2]->get_waiting_contacts();

		$this->assertCount( 1, $waiting );
		$this->assertEquals( $contact->get_id(), $waiting[0]->get_id() );
	}

	public function test_waiting_contacts_can_not_be_moved_to_a_benchmark() {

		[ $funnel, $steps, $contact ] = $this->make_funnel_with_waiting_contact();

		$steps[0]->delete();
		$funnel->commit( [
			$steps[0]->get_id() => [ 'action' => 'move', 'to' => $steps[1]->get_id() ]
		] );

		// an invalid target cancels instead
		$this->assertEmpty( $this->get_queued_events( $contact ) );
		$this->assertTrue( get_db( 'events' )->exists( [
			'step_id' => $steps[0]->get_id(),
			'status'  => Event::CANCELLED,
		] ) );
	}

	public function test_activating_a_funnel_moves_paused_contacts_from_steps_deleted_while_inactive() {

		[ $funnel, $steps, $contact ] = $this->make_funnel_with_waiting_contact( 'inactive' );

		$steps[0]->delete();

		// can't commit while inactive, the step and its paused event are still there
		$this->assertCount( 1, $funnel->get_deleted_steps() );
		$this->assertEquals( 1, $funnel->count_pending_events( $steps[0] ) );

		// what Funnels_Page::process_edit() does when activating
		$funnel->resolve_deleted_step_events( [
			$steps[0]->get_id() => [ 'action' => 'move', 'to' => $steps[2]->get_id() ]
		] );
		$funnel->remove_deleted_steps();
		$funnel->update( [ 'status' => 'active' ] );

		$this->assertFalse( ( new Step( $steps[0]->get_id() ) )->exists() );

		$waiting = $steps[2]->get_waiting_contacts();

		$this->assertCount( 1, $waiting );
		$this->assertEquals( $contact->get_id(), $waiting[0]->get_id() );
	}

	public function test_step_delete_do_not_move_contacts() {
		// Clear existing data
		$this->factory()->truncate();

		$contact = $this->factory()->contacts->create();
		$contact = get_contactdata( $contact );

		$funnel = new Funnel( [
			'title'  => 'test funnel',
			'status' => 'active'
		] );

		$step1 = $funnel->add_step( [
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Step::ACTION,
			'step_title' => 'test step 1',
			'meta'       => [
				'delay_amount' => '3',
				'delay_type'   => 'days',
				'run_when'     => 'now',
			]
		] );

		$step2 = $funnel->add_step( [
			'step_type'  => Account_Created::TYPE,
			'step_group' => Step::BENCHMARK,
			'step_title' => 'test step 2',
		] );

		$step1->enqueue( $contact );

		$step1->delete();

		$this->assertCount( 0, $step2->get_waiting_contacts() );
	}

}
