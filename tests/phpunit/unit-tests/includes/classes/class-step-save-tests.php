<?php

use Groundhogg\Funnel;
use Groundhogg\Step;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Benchmarks\Account_Created;

/**
 * Saving steps with settings passed in, instead of from the flow editor's request
 */
class Step_Save_Tests extends GH_UnitTestCase {

	/**
	 * @var Funnel
	 */
	protected $funnel;

	protected function make_funnel( $status = 'inactive' ) {

		$this->factory()->truncate();

		$this->funnel = new Funnel( [
			'title'  => 'test funnel',
			'status' => $status
		] );
	}

	protected function add_delay( $args = [] ) {
		return $this->funnel->add_step( array_merge( [
			'step_type'   => Delay_Timer::TYPE,
			'step_group'  => Step::ACTION,
			'step_title'  => 'delay',
			'step_status' => $this->funnel->get_status(),
			'step_order'  => 7,
			'branch'      => '123-yes',
			'meta'        => [
				'delay_amount' => 3,
				'delay_type'   => 'days',
				'run_when'     => 'now',
			]
		], $args ) );
	}

	public function tearDown(): void {
		unset( $_POST['steps'] );
		parent::tearDown();
	}

	public function test_save_uses_the_given_settings_and_keeps_the_step_order() {
		$this->make_funnel();
		$step = $this->add_delay();

		$step->save( [
			'delay_amount' => 5,
			'delay_type'   => 'hours',
			'run_when'     => 'now',
			'branch'       => '123-no',
		] );

		$step = new Step( $step->get_id() );

		$this->assertEquals( 5, $step->get_meta( 'delay_amount' ) );
		$this->assertEquals( 'hours', $step->get_meta( 'delay_type' ) );
		$this->assertEquals( '123-no', $step->branch );
		$this->assertEquals( 7, $step->get_order() );
	}

	public function test_save_resets_missing_branch_like_the_editor() {
		$this->make_funnel();
		$step = $this->add_delay();

		$step->save( [ 'delay_amount' => 5 ] );

		$this->assertEquals( 'main', ( new Step( $step->get_id() ) )->branch );
	}

	public function test_update_settings_keeps_the_other_settings() {
		$this->make_funnel();
		$step = $this->add_delay();

		$step->update_settings( [ 'delay_amount' => 5 ] );

		$step = new Step( $step->get_id() );

		$this->assertEquals( 5, $step->get_meta( 'delay_amount' ) );
		$this->assertEquals( 'days', $step->get_meta( 'delay_type' ) );
		$this->assertEquals( 'now', $step->get_meta( 'run_when' ) );
		$this->assertEquals( '123-yes', $step->branch );
		$this->assertEquals( 7, $step->get_order() );
	}

	public function test_update_settings_keeps_benchmark_flags() {
		$this->make_funnel();

		$step = $this->funnel->add_step( [
			'step_type'   => Account_Created::TYPE,
			'step_group'  => Step::BENCHMARK,
			'step_title'  => 'benchmark',
			'step_status' => 'inactive',
			'is_entry'    => true,
			'is_conversion' => true,
		] );

		$step->update_settings( [ 'can_passthru' => true ] );

		$step = new Step( $step->get_id() );

		$this->assertTrue( (bool) $step->is_entry );
		$this->assertTrue( (bool) $step->is_conversion );
		$this->assertTrue( (bool) $step->can_passthru );
	}

	public function test_update_settings_on_an_active_funnel_is_staged() {
		$this->make_funnel( 'active' );
		$step = $this->add_delay();
		$this->funnel->commit();

		$step = new Step( $step->get_id() );
		$step->update_settings( [ 'delay_amount' => 5 ] );

		$step = new Step( $step->get_id() );

		// live value is unchanged until the changes are committed
		$this->assertEquals( 3, $step->get_meta( 'delay_amount' ) );
		$this->assertEquals( 5, $step->changes['delay_amount'] );

		// staged values are what the next update builds on
		$step->update_settings( [ 'delay_type' => 'hours' ] );

		$step = new Step( $step->get_id() );
		$step->merge_changes();

		$this->assertEquals( 5, $step->get_meta( 'delay_amount' ) );
		$this->assertEquals( 'hours', $step->get_meta( 'delay_type' ) );
	}

	public function test_save_without_settings_reads_the_editor_request() {
		$this->make_funnel();
		$step = $this->add_delay();

		$_POST['steps'][ $step->get_id() ] = [
			'delay_amount' => 9,
			'branch'       => 'main',
		];

		Step::increment_step_order( 0 );
		$step->save();

		$step = new Step( $step->get_id() );

		$this->assertEquals( 9, $step->get_meta( 'delay_amount' ) );
		$this->assertEquals( 'main', $step->branch );
		// the editor saves steps in order
		$this->assertEquals( 1, $step->get_order() );
	}
}
