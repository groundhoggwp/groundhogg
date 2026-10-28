<?php

use Groundhogg\License;
use Groundhogg\License_Manager;

/**
 * @see License_Manager::get_licenses() a subsite uses the licenses of the main site when it has none, on a network that
 * is white labeled
 *
 * Needs a multisite install of the tests, and the White Label add-on to be loaded for the tests where it is on.
 */
class License_Manager_Network_Licenses_Tests extends GH_UnitTestCase {

	protected $licenses;
	protected $property;
	protected $network_property;

	public function setUp(): void {
		parent::setUp();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Needs multisite.' );
		}

		$this->property = new ReflectionProperty( License_Manager::class, 'licenses' );
		$this->property->setAccessible( true );
		$this->network_property = new ReflectionProperty( License_Manager::class, 'network_licenses' );
		$this->network_property->setAccessible( true );

		// typed, so it's not there until the licenses have been loaded once
		License_Manager::get_licenses();
		$this->licenses = $this->property->getValue();

		delete_option( 'gh_licenses' );
		$this->reset_caches();
	}

	public function tearDown(): void {

		while ( is_multisite() && ms_is_switched() ) {
			restore_current_blog();
		}

		if ( $this->property ) {
			delete_option( 'gh_licenses' );
			delete_site_option( 'active_sitewide_plugins' );
			delete_blog_option( \Groundhogg\get_main_blog_id(), 'gh_white_label_enable' );
			$this->property->setValue( null, $this->licenses );
			$this->network_property->setValue( null, null );
		}

		parent::tearDown();
	}

	/**
	 * The licenses are kept on the class for the request, a request is only ever one site, so this is what switching
	 * to another site is
	 */
	protected function reset_caches() {
		$this->property->setValue( null, [] );
		$this->network_property->setValue( null, null );
	}

	protected function license( bool $valid = true ): License {
		return License::from_array( [
			'license' => $valid ? 'valid' : 'expired',
			'expires' => $valid ? gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) ) : gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) ),
			'items'   => [ 1, 2, 3 ],
		] );
	}

	/**
	 * A network with a license on the main site, and a subsite that is the current site
	 */
	protected function subsite_of_a_network_with_a_license( string $key = 'main-site-key' ) {

		update_blog_option( \Groundhogg\get_main_blog_id(), 'gh_licenses', [ $key => $this->license() ] );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		$this->reset_caches();
	}

	protected function white_label_is_on() {

		if ( ! function_exists( 'Groundhogg\white_label_is_network_active' ) ) {
			$this->markTestSkipped( 'Needs the White Label add-on to be loaded.' );
		}

		update_site_option( 'active_sitewide_plugins', [ GROUNDHOGG_WHITE_LABEL_PLUGIN_BASE => time() ] );
		update_blog_option( \Groundhogg\get_main_blog_id(), 'gh_white_label_enable', 1 );
	}

	public function test_a_subsite_uses_the_license_of_the_main_site_when_white_label_is_on_and_network_active() {

		$this->white_label_is_on();
		$this->subsite_of_a_network_with_a_license();

		$this->assertSame( 'main-site-key', License_Manager::get_master_license() );
		$this->assertTrue( License_Manager::is_valid( 'main-site-key' ) );
		$this->assertSame( 'main-site-key', \Groundhogg\Classes\Inbox_Client::license_key() );
		$this->assertTrue( License_Manager::is_network_license( 'main-site-key' ) );

	}

	public function test_a_subsite_with_a_license_of_its_own_uses_that_one() {

		$this->white_label_is_on();
		$this->subsite_of_a_network_with_a_license();

		update_option( 'gh_licenses', [ 'subsite-key' => $this->license() ] );
		$this->reset_caches();

		$this->assertSame( 'subsite-key', License_Manager::get_master_license() );
		$this->assertFalse( License_Manager::license_is_registered( 'main-site-key' ) );
		$this->assertFalse( License_Manager::is_network_license( 'main-site-key' ) );

	}

	public function test_a_subsite_does_not_use_the_license_of_the_main_site_when_white_label_is_off() {

		$this->white_label_is_on();
		update_blog_option( \Groundhogg\get_main_blog_id(), 'gh_white_label_enable', 0 );
		$this->subsite_of_a_network_with_a_license();

		$this->assertFalse( License_Manager::get_master_license() );
		$this->assertSame( '', \Groundhogg\Classes\Inbox_Client::license_key() );

	}

	public function test_a_subsite_does_not_use_the_license_of_the_main_site_when_white_label_is_not_network_active() {

		$this->white_label_is_on();
		update_site_option( 'active_sitewide_plugins', [] );
		$this->subsite_of_a_network_with_a_license();

		$this->assertFalse( License_Manager::get_master_license() );

	}

	public function test_a_subsite_does_not_use_the_license_of_the_main_site_without_the_white_label_add_on() {

		if ( function_exists( 'Groundhogg\white_label_is_network_active' ) ) {
			$this->markTestSkipped( 'The White Label add-on is loaded.' );
		}

		$this->subsite_of_a_network_with_a_license();

		$this->assertFalse( License_Manager::get_master_license() );

	}

	public function test_the_main_site_uses_its_own_licenses() {

		update_option( 'gh_licenses', [ 'main-site-key' => $this->license() ] );
		$this->reset_caches();

		$this->assertSame( 'main-site-key', License_Manager::get_master_license() );
		$this->assertFalse( License_Manager::is_network_license( 'main-site-key' ) );
	}

	public function test_a_license_of_the_main_site_is_not_checked_or_removed_from_a_subsite() {

		$this->white_label_is_on();
		$this->subsite_of_a_network_with_a_license();

		// nothing is requested from the license server, so these would be errors from it without a connection
		$checked = License_Manager::check_license( 'main-site-key' );
		$this->assertInstanceOf( License::class, $checked );

		$removed = License_Manager::deactivate_license( 'main-site-key' );
		$this->assertWPError( $removed );
		$this->assertSame( 'license_error', $removed->get_error_code() );

		// and nothing was saved to the subsite
		$this->assertFalse( get_option( 'gh_licenses' ) );

	}
}
