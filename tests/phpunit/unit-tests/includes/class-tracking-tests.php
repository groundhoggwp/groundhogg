<?php

class Tracking_Tests extends GH_UnitTestCase {

	public function test_update_referer_hash(){

		$referer = 'https://groundhogg.io';

		$activity_id = \Groundhogg\get_db( 'activity' )->add( [
			'referer' => 'https://groundhogg.io'
		] );

		$activity = \Groundhogg\get_db( 'activity' )->get( $activity_id );

		$this->assertEquals( $referer, $activity->referer );
		$this->assertEquals( \Groundhogg\generate_referer_hash( $referer ), $activity->referer_hash );

	}

}
