<?php

use Groundhogg\Main_Updater;
use Groundhogg\Plugin;
use Groundhogg\Preferences;

class Main_Updater_Tests extends GH_UnitTestCase {

	/**
	 * @var Main_Updater
	 */
	protected $updater;

	/**
	 * Main_Updater_Tests constructor.
	 *
	 * @param null   $name
	 * @param array  $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->updater = Plugin::instance()->updater;
	}

	/**
	 * Test that the wp cron event is scheduled when the upgrade process is complete
	 */
	public function test_schedule_update_cron() {
		$next_scheduled = wp_next_scheduled( "groundhogg/main/do_updates" );

		$this->assertFalse( $next_scheduled );

		$this->updater->plugin_upgrader_listener( null, [
			'action'  => 'update',
			'type'    => 'plugin',
			'plugins' => [
				basename( GROUNDHOGG__FILE__ )
			]
		] );

		$next_scheduled = wp_next_scheduled( "groundhogg/main/do_updates" );

		$this->assertEquals( time(), $next_scheduled );
	}

	/**
	 * Test that updates run on the desired hook
	 */
	public function test_update_lock_set_on_hook() {
		do_action( 'groundhogg/main/do_updates' );

		$update_lock = get_transient( 'gh_main_doing_updates' );

		// test that the update lock has been set
		$this->assertEquals( time(), $update_lock );
	}

	/**
	 * Test that when Groundhogg is activate the updates are initiallized
	 */
	public function test_previous_updates_installed() {
		$this->assertNotEmpty( $this->updater->get_previous_versions() );
	}

	/**
	 * Test that attempting to save the previous updates after they have already been saved returns false
	 */
	public function test_saving_previous_updates_after_groundhogg_activated(){
		$this->assertFalse( $this->updater->save_previous_updates_when_installed() );
	}

	/**
	 * Test the automatic update path when installing and no updates are available
	 */
	public function test_do_updates_no_updates() {
		$this->assertFalse( $this->updater->do_updates() );
	}

	/**
	 * Test that an update is performed when "forgetting" a previous update
	 */
	public function test_do_update_2_0_7()
	{
		$this->assertTrue( $this->updater->forget_version_update( '2.0.7' ) );
		$this->assertTrue( $this->updater->do_updates() );
		$this->assertTrue( in_array( '2.0.7', $this->updater->get_previous_versions() ) );
	}

	/**
	 * Test the 2.1.13 update that will increment all the optin statuses of contacts by 1
	 */
	public function test_update_2_1_13() {

		// Old statuses
		$contact_ids_unconfirmed  = $this->factory()->contacts->create_many( 2, [ 'optin_status' => 0 ] );
		$contact_ids_confirmed    = $this->factory()->contacts->create_many( 2, [ 'optin_status' => 1 ] );
		$contact_ids_unsubscribed = $this->factory()->contacts->create_many( 2, [ 'optin_status' => 2 ] );

		Plugin::instance()->updater->version_2_1_13();

		// Test unconfirmed
		foreach ( $contact_ids_unconfirmed as $id ) {
			$contact = $this->factory()->contacts->get_object_by_id( $id );
			$this->assertEquals( Preferences::UNCONFIRMED, $contact->get_optin_status() );
		}

		// Test confirmed
		foreach ( $contact_ids_confirmed as $id ) {
			$contact = $this->factory()->contacts->get_object_by_id( $id );
			$this->assertEquals( Preferences::CONFIRMED, $contact->get_optin_status() );
		}

		// Test Unsubscribed
		foreach ( $contact_ids_unsubscribed as $id ) {
			$contact = $this->factory()->contacts->get_object_by_id( $id );
			$this->assertEquals( Preferences::UNSUBSCRIBED, $contact->get_optin_status() );
		}
	}

}
