<?php

class PluginTest extends WP_UnitTestCase
{

	// Check that that activation doesn't break
	function test_plugin_activated()
	{
//		$this->assertTrue( is_plugin_active( plugin_basename( GROUNDHOGG__FILE__ ) ) );
	}

	function testSample()
	{
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_sample_string()
	{
		$string = 'Unit tests are sweet';

		$this->assertEquals( 'Unit tests are sweet', $string );
	}
}
