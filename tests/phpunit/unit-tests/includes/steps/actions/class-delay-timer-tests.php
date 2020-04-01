<?php

use Groundhogg\Funnel;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Benchmarks\Benchmark;

class Delay_Timer_Tests extends GH_UnitTestCase {

	/**
	 * An hour from now
	 */
	public function test_enqueue_1_hour_from_now() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => '1 hour from now',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'hours',
				'run_when'     => 'now',
				'run_time'     => '',
			]
		] );


		$this->assertEquals( strtotime( '+1 hours' ), $timer->get_delay_time() );
	}

	/**
	 * A day from now
	 */
	public function test_enqueue_1_day_from_now() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => '1 day from now',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'days',
				'run_when'     => 'now',
				'run_time'     => '',
			]
		] );


		$this->assertEquals( strtotime( '+1 days' ), $timer->get_delay_time() );
	}

	/**
	 * Next day at 8:00 AM
	 */
	public function test_enqueue_at_8_am() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => 'At 8 am',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'hours',
				'run_when'     => 'later',
				'run_time'     => '8:00:00',
			]
		] );

		if ( time() < strtotime( 'today 8:00:00' ) ){
			$this->assertEquals( strtotime( 'today 8:00:00' ), $timer->get_delay_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 8:00:00' ), $timer->get_delay_time() );
		}
	}

	/**
	 * Next day at 8:00 AM
	 */
	public function test_enqueue_at_12_01_am() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => 'At 12:01 am',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 4,
				'delay_type'   => 'minutes',
				'run_when'     => 'later',
				'run_time'     => '00:01:00',
			]
		] );

		if ( time() < strtotime( 'today 00:01:00' ) ){
			$this->assertEquals( strtotime( 'today 00:01:00' ), $timer->get_delay_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 00:01:00' ), $timer->get_delay_time() );
		}
	}

}