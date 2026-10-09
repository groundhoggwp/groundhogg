<?php

use Groundhogg\License;
use Groundhogg\License_Manager;

/**
 * @see License_Manager::add_license() saving a license that was just activated
 */
class License_Manager_Add_License_Tests extends GH_UnitTestCase {

	protected $licenses;
	protected $property;

	public function setUp(): void {
		parent::setUp();

		$this->property = new ReflectionProperty( License_Manager::class, 'licenses' );
		$this->property->setAccessible( true );

		// typed, so it's not there until the licenses have been loaded once
		License_Manager::get_licenses();
		$this->licenses = $this->property->getValue();

		// a site that hasn't activated a license yet
		$this->property->setValue( null, [] );
		delete_option( 'gh_licenses' );
	}

	public function tearDown(): void {
		$this->property->setValue( null, $this->licenses );
		delete_option( 'gh_licenses' );
		parent::tearDown();
	}

	protected function response( array $args = [] ): object {
		return (object) array_merge( [
			'success'          => true,
			'license'          => 'valid',
			'item_id'          => 12,
			'item_name'        => 'Groundhogg Plan',
			'expires'          => 'lifetime',
			'license_limit'    => 5,
			'site_count'       => 1,
			'activations_left' => 4,
			'items'            => [ 1, 2, 3 ],
		], $args );
	}

	/**
	 * The first license on a site is not in what was loaded before it was added, which was where it was read from, and
	 * that's a warning with the license key in it, and nothing returned
	 */
	public function test_the_first_license_is_returned_and_saved() {

		$license = License_Manager::add_license( 'key-for-the-first-license', $this->response() );

		$this->assertInstanceOf( License::class, $license );
		$this->assertTrue( $license->is_valid() );
		$this->assertSame( [ 1, 2, 3 ], $license->items );

		$this->assertArrayHasKey( 'key-for-the-first-license', get_option( 'gh_licenses' ) );
		$this->assertTrue( License_Manager::license_is_registered( 'key-for-the-first-license' ) );
	}

	public function test_a_license_added_to_ones_that_are_there_is_returned() {

		License_Manager::add_license( 'key-one', $this->response( [ 'items' => [ 1 ] ] ) );
		$second = License_Manager::add_license( 'key-two', $this->response( [ 'items' => [ 2, 3 ] ] ) );

		$this->assertSame( [ 2, 3 ], $second->items );
		$this->assertSame( [ 'key-one', 'key-two' ], array_keys( get_option( 'gh_licenses' ) ) );
	}

	public function test_activating_a_key_again_returns_what_was_just_activated_not_what_was_there() {

		License_Manager::add_license( 'key-one', $this->response( [ 'license' => 'expired', 'expires' => '2020-01-01 00:00:00' ] ) );
		$again = License_Manager::add_license( 'key-one', $this->response() );

		$this->assertTrue( $again->is_valid(), 'the new one, not the expired one that was saved before' );
		$this->assertCount( 1, get_option( 'gh_licenses' ) );
	}
}
