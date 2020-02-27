<?php

use Groundhogg\DB\Activity;
use Groundhogg\Event;
use function Groundhogg\get_db;

class GH_UnitTests_ID_Generator_Tests extends GH_UnitTestCase {

	public function test_start() {
		$generator = new GH_UnitTest_ID_Generator( 3 );
		$this->assertEquals( 3, $generator->generate() );
		$this->assertEquals( 4, $generator->generate() );
	}

	public function test_create() {

		get_db( 'activity' )->truncate();

		$activity = $this->factory()->activity->create_many( 10 );

		var_dump( $activity );

		foreach ( $activity as $i => $activity_id ){
			$activity = new \Groundhogg\Classes\Activity( $activity_id );

			$this->assertEquals( $i+1, $activity->get_step_id() );
		}

	}

}
