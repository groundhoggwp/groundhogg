<?php

use Groundhogg\Queue\Compatibility;
use Groundhogg\Utils\Limits;

/**
 * The time and memory limits of a request: resetting them, and not lowering the timeout of PHP when raising it.
 *
 * @see Limits
 */
class Limits_Tests extends GH_UnitTestCase {

	/**
	 * @var string
	 */
	protected $max_execution_time;

	public function setUp(): void {
		parent::setUp();
		$this->max_execution_time = ini_get( 'max_execution_time' );
	}

	public function tearDown(): void {
		ini_set( 'max_execution_time', $this->max_execution_time );
		Limits::reset();
		parent::tearDown();
	}

	protected function set_limits_exceeded( bool $exceeded ) {
		$property = new ReflectionProperty( Limits::class, 'limits_exceeded' );
		$property->setAccessible( true );
		$property->setValue( null, $exceeded );
	}

	protected function skip_if_time_limit_is_locked() {
		if ( false !== strpos( (string) ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
			$this->markTestSkipped( 'set_time_limit() is disabled.' );
		}
	}

	/* ---------------------------------------------------------------------
	 * resetting
	 * ------------------------------------------------------------------- */

	public function test_the_limits_stay_exceeded_until_they_are_reset() {

		$this->set_limits_exceeded( true );

		$this->assertTrue( Limits::limits_exceeded() );

		// not by starting or stopping, so that a queue that ran out of room means the next one doesn't start
		Limits::start();
		Limits::stop();
		$this->assertTrue( Limits::limits_exceeded() );

		Limits::reset();
		$this->assertFalse( Limits::limits_exceeded() );
	}

	public function test_resetting_keeps_the_totals_of_the_request() {

		Limits::start();
		Limits::processed_action();
		Limits::stop();

		$actions = Limits::get_actions_processed();
		$elapsed = Limits::total_time_elapsed();

		Limits::reset();

		$this->assertSame( $actions, Limits::get_actions_processed() );
		$this->assertGreaterThanOrEqual( $elapsed, Limits::total_time_elapsed() );
	}

	public function test_resetting_stops_the_clock_that_was_running() {

		Limits::start();
		Limits::processed_action();
		Limits::reset();

		$this->assertSame( 0, Limits::time_elapsed() );
	}

	/* ---------------------------------------------------------------------
	 * raising the time limit
	 * ------------------------------------------------------------------- */

	public function test_it_does_not_put_a_limit_on_a_process_that_has_none() {

		$this->skip_if_time_limit_is_locked();

		ini_set( 'max_execution_time', '0' );

		Limits::raise_time_limit( MINUTE_IN_SECONDS );
		$this->assertSame( '0', (string) ini_get( 'max_execution_time' ) );

		// with no limit given, it's the one it would have used
		Limits::raise_time_limit();
		$this->assertSame( '0', (string) ini_get( 'max_execution_time' ) );

		Compatibility::raise_time_limit( MINUTE_IN_SECONDS );
		$this->assertSame( '0', (string) ini_get( 'max_execution_time' ) );
	}

	public function test_it_raises_a_limit_that_is_lower() {

		$this->skip_if_time_limit_is_locked();

		ini_set( 'max_execution_time', '30' );

		Limits::raise_time_limit( 90 );

		$this->assertSame( '90', (string) ini_get( 'max_execution_time' ) );
	}

	public function test_it_does_not_lower_a_limit_that_is_higher() {

		$this->skip_if_time_limit_is_locked();

		ini_set( 'max_execution_time', '300' );

		Limits::raise_time_limit( MINUTE_IN_SECONDS );
		$this->assertSame( '300', (string) ini_get( 'max_execution_time' ) );

		Compatibility::raise_time_limit( MINUTE_IN_SECONDS );
		$this->assertSame( '300', (string) ini_get( 'max_execution_time' ) );
	}

	/* ---------------------------------------------------------------------
	 * CPU time
	 * ------------------------------------------------------------------- */

	public function test_the_cpu_time_is_user_and_system_time_together() {

		if ( ! function_exists( 'getrusage' ) ) {
			$this->markTestSkipped( 'getrusage() is not available.' );
		}

		$usage = getrusage();

		if ( ! isset( $usage['ru_utime.tv_sec'], $usage['ru_stime.tv_sec'] ) ) {
			$this->markTestSkipped( 'getrusage() does not say.' );
		}

		$before = $usage['ru_utime.tv_sec'] + $usage['ru_utime.tv_usec'] / 1000000 + $usage['ru_stime.tv_sec'] + $usage['ru_stime.tv_usec'] / 1000000;
		$cpu    = Limits::get_cpu_time();

		$this->assertIsFloat( $cpu );
		$this->assertGreaterThanOrEqual( $before, $cpu );
	}

	public function test_the_cpu_time_is_used_for_the_execution_time_when_a_host_asks_for_it() {

		if ( Limits::get_cpu_time() === false ) {
			$this->markTestSkipped( 'getrusage() is not available.' );
		}

		add_filter( 'action_scheduler_use_cpu_execution_time', '__return_true' );

		try {
			$before = Limits::get_cpu_time();
			$time   = Limits::get_execution_time();
			$after  = Limits::get_cpu_time();
		} finally {
			remove_filter( 'action_scheduler_use_cpu_execution_time', '__return_true' );
		}

		// the CPU time of the whole process, and not the time since the limits started
		$this->assertGreaterThanOrEqual( $before, $time );
		$this->assertLessThanOrEqual( $after, $time );
	}
}
