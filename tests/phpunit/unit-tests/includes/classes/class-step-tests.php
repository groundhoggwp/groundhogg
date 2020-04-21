<?php

use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Benchmarks\Account_Created;
use function Groundhogg\get_contactdata;

class Step_Tests extends GH_UnitTestCase {

	/**
	 * If deleting an action, move the contacts to the next action.
	 */
	public function test_step_delete_move_contacts_forward_to_next_action() {

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
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Step::ACTION,
			'step_title' => 'test step 2',
			'meta'       => [
				'delay_amount' => '3',
				'delay_type'   => 'days',
				'run_when'     => 'now',
			]
		] );

		$step1->enqueue( $contact );

		$step1->delete();

		$this->assertCount( 1, $step2->get_waiting_contacts() );
		$this->assertEquals( $contact->get_id(), $step2->get_waiting_contacts()[0]->get_id() );
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

		$step3 = $funnel->add_step( [
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Step::ACTION,
			'step_title' => 'test step 3',
			'meta'       => [
				'delay_amount' => '3',
				'delay_type'   => 'days',
				'run_when'     => 'now',
			]
		] );

		$step1->enqueue( $contact );

		$step1->delete();

		$this->assertEmpty( $step2->get_waiting_contacts() );
	}

}
