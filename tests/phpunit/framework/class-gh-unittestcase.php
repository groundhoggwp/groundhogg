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

	public function tearDown(): void {
		parent::tearDown();
	}

	public function setUp(): void {
		parent::setUp();
	}
}
