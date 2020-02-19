<?php

use Groundhogg\Plugin;

class Step_Manager_Tests extends GH_UnitTestCase {

	/**
	 * Ensure that the steps are initialized
	 */
	public function test_steps_initialized() {
		$this->assertNotEmpty( Plugin::instance()->step_manager->get_actions() );
		$this->assertNotEmpty( Plugin::instance()->step_manager->get_benchmarks() );
	}

}
