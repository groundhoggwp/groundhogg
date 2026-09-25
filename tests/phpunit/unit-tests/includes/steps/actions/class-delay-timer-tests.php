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


		$this->assertEquals( strtotime( '+1 hours' ), $timer->get_run_time() );
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


		$this->assertEquals( strtotime( '+1 days' ), $timer->get_run_time() );
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
			$this->assertEquals( strtotime( 'today 8:00:00' ), $timer->get_run_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 8:00:00' ), $timer->get_run_time() );
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
			$this->assertEquals( strtotime( 'today 00:01:00' ), $timer->get_run_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 00:01:00' ), $timer->get_run_time() );
		}
	}

	protected function add_timer( array $meta ) {
		$funnel = new Funnel( $this->factory()->funnels->create() );

		return $funnel->add_step( [
			'step_title' => 'Delay Timer',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => $meta
		] );
	}

	protected function generated_title( array $meta ) {
		$timer = $this->add_timer( $meta );

		return $timer->get_step_element()->generate_step_title( $timer );
	}

	/**
	 * Without a delay_preview from the editor, the title comes from the actual settings, not a hardcoded "Wait 3 days"
	 */
	public function test_title_without_preview_uses_settings() {
		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', $this->generated_title( [
			'delay_amount' => 5,
			'delay_type'   => 'days',
		] ) );

		$this->assertEquals( 'Wait at least <b>1 hour</b> and then run at any time', $this->generated_title( [
			'delay_amount' => 1,
			'delay_type'   => 'hours',
		] ) );
	}

	public function test_title_without_any_settings_uses_defaults() {
		$this->assertEquals( 'Wait at least <b>3 days</b> and then run at any time', $this->generated_title( [] ) );
	}

	public function test_title_uses_editor_preview_when_present() {
		$this->assertEquals( 'Custom preview', $this->generated_title( [
			'delay_amount'  => 5,
			'delay_type'    => 'days',
			'delay_preview' => 'Custom preview',
		] ) );
	}

	public function test_title_run_when_variants() {
		update_option( 'time_format', 'g:i a' );

		$this->assertEquals( 'Run at <b>9:30 am</b>', $this->generated_title( [
			'delay_type' => 'none',
			'run_when'   => 'later',
			'run_time'   => '09:30:00',
		] ) );

		$this->assertEquals( 'Wait at least <b>2 weeks</b> and then run between <b>9:00 am</b> and <b>5:00 pm</b>', $this->generated_title( [
			'delay_amount' => 2,
			'delay_type'   => 'weeks',
			'run_when'     => 'between',
			'run_time'     => '09:00:00',
			'run_time_to'  => '17:00:00',
		] ) );
	}

	public function test_title_run_on_variants() {
		$this->assertEquals( 'Wait at least <b>1 day</b> and then run on <b>a weekday</b> at any time', $this->generated_title( [
			'delay_amount' => 1,
			'delay_type'   => 'days',
			'run_on_type'  => 'weekday',
		] ) );

		$this->assertEquals( 'Run on any <b>Monday</b> or <b>Friday</b> of <b>any month</b> at any time', $this->generated_title( [
			'delay_type'  => 'none',
			'run_on_type' => 'day_of_week',
			'run_on_dow'  => [ 'monday', 'friday' ],
		] ) );

		$this->assertEquals( 'Run on the first <b>Tuesday</b> of <b>March</b> at any time', $this->generated_title( [
			'delay_type'        => 'none',
			'run_on_type'       => 'day_of_week',
			'run_on_dow_type'   => 'first',
			'run_on_dow'        => [ 'tuesday' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'march' ],
		] ) );

		$this->assertEquals( 'Run on the <b>1st</b> or <b>15th</b> of <b>June</b> or <b>July</b> at any time', $this->generated_title( [
			'delay_type'        => 'none',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 1, 15 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'june', 'july' ],
		] ) );

		$this->assertEquals( 'Run on the <b>last day</b> of <b>any month</b> at any time', Delay_Timer::delay_preview( [
			'delay_type'        => 'none',
			'run_when'          => 'now',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 'last' ],
			'run_on_month_type' => 'any',
		] ) );
	}

	/**
	 * Saving the step (what the editor does) stores the generated title
	 */
	public function test_after_save_stores_title_from_settings() {
		$timer = $this->add_timer( [
			'delay_amount' => 5,
			'delay_type'   => 'days',
		] );

		$timer->get_step_element()->after_save( $timer );

		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', ( new \Groundhogg\Step( $timer->get_id() ) )->get_title_formatted() );
	}

	/**
	 * Delay timers built by groundhogg/create-flow get a title matching their settings
	 */
	public function test_create_flow_ability_title() {

		\Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'delay-title-test' ] );

		// Invoke the ability's callback directly, without the constructor registering it again
		$create_flow = ( new ReflectionClass( \Groundhogg\Abilities\Funnels\Create_Flow::class ) )->newInstanceWithoutConstructor();

		$result = $create_flow( [
			'title' => 'Delay title test',
			'steps' => [
				[ 'type' => 'tag_applied', 'settings' => [ 'tags' => [ 'delay-title-test' ] ] ],
				[ 'type' => 'delay_timer', 'settings' => [ 'delay_amount' => 5, 'delay_type' => 'days' ] ],
			],
		] );

		$this->assertNotWPError( $result );

		$timer = new \Groundhogg\Step( $result['steps'][1]['id'] );

		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', $timer->get_step_element()->get_title( $timer ) );
	}

}
