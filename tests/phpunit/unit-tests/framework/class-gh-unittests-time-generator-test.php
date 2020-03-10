<?php

class GH_UnitTests_Time_Generator_Tests extends GH_UnitTestCase {
	public function test_generate_time(){

		$start = time() - WEEK_IN_SECONDS;
		$end = time();

		$generator = new GH_UnitTest_Time_Generator( $start, $end, 'U' );

		$given = $generator->generate();

		$this->assertLessThanOrEqual( $end, $given );
		$this->assertGreaterThanOrEqual( $start, $given );
	}
}
