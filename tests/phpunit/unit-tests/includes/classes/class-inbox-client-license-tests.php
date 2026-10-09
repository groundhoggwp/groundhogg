<?php

use Groundhogg\Classes\Inbox_Client;
use Groundhogg\License;
use Groundhogg\License_Manager;

/**
 * @see Inbox_Client::license_key() the license that Messages is set up with
 */
class Inbox_Client_License_Tests extends GH_UnitTestCase {

	protected $licenses;
	protected $property;

	public function setUp(): void {
		parent::setUp();

		$this->property = new ReflectionProperty( License_Manager::class, 'licenses' );
		$this->property->setAccessible( true );
		// typed, so it's not there until the licenses have been loaded once
		License_Manager::get_licenses();
		$this->licenses = $this->property->getValue();
	}

	public function tearDown(): void {
		$this->property->setValue( null, $this->licenses );
		parent::tearDown();
	}

	protected function license( array $items, bool $valid ): License {
		return License::from_array( [
			'license' => $valid ? 'valid' : 'expired',
			'expires' => $valid ? gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) ) : gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) ),
			'items'   => $items,
		] );
	}

	protected function set_licenses( array $licenses ) {
		$this->property->setValue( null, $licenses );
	}

	public function test_a_valid_single_product_license_is_used_when_the_master_license_has_expired() {

		$this->set_licenses( [
			'expired-all-access' => $this->license( [ 1, 2, 3 ], false ),
			'valid-single'       => $this->license( [ 4 ], true ),
		] );

		// the master license is still the expired one, what else uses it is as it was
		$this->assertSame( 'expired-all-access', License_Manager::get_master_license() );

		$this->assertSame( 'valid-single', Inbox_Client::license_key() );
	}

	public function test_a_valid_master_license_is_used_ahead_of_a_valid_single_product_license() {

		$this->set_licenses( [
			'valid-single' => $this->license( [ 4 ], true ),
			'valid-all'    => $this->license( [ 1, 2, 3 ], true ),
		] );

		$this->assertSame( 'valid-all', Inbox_Client::license_key() );
	}

	public function test_an_expired_master_license_alone_is_not_used() {

		$this->set_licenses( [
			'expired-all-access' => $this->license( [ 1, 2, 3 ], false ),
		] );

		$this->assertSame( '', Inbox_Client::license_key() );
	}

	public function test_a_valid_single_product_license_alone_is_used() {

		$this->set_licenses( [
			'valid-single' => $this->license( [ 4 ], true ),
		] );

		$this->assertSame( 'valid-single', Inbox_Client::license_key() );
	}

	public function test_the_key_filter_still_has_the_last_word() {

		$this->set_licenses( [
			'valid-single' => $this->license( [ 4 ], true ),
		] );

		add_filter( 'groundhogg/inbox/license_key', fn() => 'from-filter' );

		try {
			$this->assertSame( 'from-filter', Inbox_Client::license_key() );
		} finally {
			remove_all_filters( 'groundhogg/inbox/license_key' );
		}
	}
}
