<?php

use function Groundhogg\utils;

/**
 * The list of time zones in the contact editor
 *
 * @see \Groundhogg\Location::get_time_zones()
 * @see \Groundhogg\Location::format_time_zones()
 */
class Location_Time_Zones_Tests extends GH_UnitTestCase {

	public function test_a_time_zone_the_server_does_not_know_is_left_out_and_the_rest_are_listed() {

		$list = utils()->location->format_time_zones( [ 'America/New_York', 'Not/A_Zone', 'Europe/London', '' ] );

		$this->assertSame( [ 'America/New_York', 'Europe/London' ], array_keys( $list ) );
		$this->assertStringContainsString( 'America/New_York', $list['America/New_York'] );
		$this->assertMatchesRegularExpression( '/^\(UTC[+-]\d\d:\d\d\) Europe\/London$/', $list['Europe/London'] );
	}

	public function test_the_list_is_sorted_by_offset() {

		$list = utils()->location->format_time_zones( [ 'Asia/Tokyo', 'Pacific/Honolulu', 'UTC' ] );

		$this->assertSame( [ 'Pacific/Honolulu', 'UTC', 'Asia/Tokyo' ], array_keys( $list ) );
		$this->assertSame( '(UTC-10:00) Pacific/Honolulu', $list['Pacific/Honolulu'] );
		$this->assertSame( '(UTC+09:00) Asia/Tokyo', $list['Asia/Tokyo'] );
	}

	public function test_the_whole_list_is_the_same_every_time_it_is_asked_for() {

		$first  = utils()->location->get_time_zones();
		$second = utils()->location->get_time_zones();

		$this->assertNotEmpty( $first );
		$this->assertSame( $first, $second );
		$this->assertArrayHasKey( 'America/New_York', $second );
	}
}
