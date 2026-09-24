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

	public function test_get_current_event_when_coming_from_tracking_link() {

		$tracking = \Groundhogg\Plugin::$instance->tracking;

		$event_id = \Groundhogg\get_db( 'events' )->add( [
			'queued_id' => 1234,
			'time'      => time(), // events without a time are rejected
		] );

		// Links carry the queued_id, but it's converted to the event ID before it goes in the cookie
		$tracking->add_tracking_cookie_param( 'event_id', $event_id );

		$this->assertEquals( $event_id, $tracking->get_current_event()->get_id() );

	}

}
