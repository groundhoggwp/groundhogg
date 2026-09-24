<?php

class GH_UnitTestCase extends WP_UnitTestCase_Base
{

	/**
	 * Generates a factory with the groundhogg objects available
	 *
	 * @return GH_UnitTest_Factory
	 */
	protected static function factory() {
		static $factory = null;

		if ( ! $factory ) {
			$factory = new GH_UnitTest_Factory();
		}

		return $factory;
	}

	/**
	 * Tracking is a singleton with protected, in-memory cookie state that would otherwise leak
	 * between tests, e.g. a contact tracked by creating a user changes how later merge tags render.
	 */
	protected function reset_tracking_state() {
		$ref = new ReflectionProperty( \Groundhogg\Tracking::class, 'cookie' );
		$ref->setAccessible( true );
		$ref->setValue( \Groundhogg\tracking(), [] );
	}

	public function tearDown(): void {
		$this->reset_tracking_state();
		parent::tearDown();
	}

	public function setUp(): void {
		parent::setUp();
		$this->reset_tracking_state();
	}
}
