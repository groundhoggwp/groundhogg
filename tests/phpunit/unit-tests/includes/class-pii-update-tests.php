<?php

use Groundhogg\Main_Updater;
use Groundhogg\Pii;

/**
 * The 4.11 update gives the view_contact_pii capability to everyone who can view contacts, so nothing changes until it is
 * taken away. It runs after the caps of the current user were worked out, so it must not leave them without it.
 *
 * @see Main_Updater::grant_view_contact_pii()
 */
class Pii_Update_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		// a site from before the capability
		get_role( 'administrator' )->remove_cap( Pii::CAP );
		add_role( 'gh_test_contacts_viewer', 'Test viewer', [ 'read' => true, 'view_contacts' => true ] );
		add_role( 'gh_test_denied', 'Test denied', [ 'read' => true, 'view_contacts' => true, Pii::CAP => false ] );
		add_role( 'gh_test_reader', 'Test reader', [ 'read' => true ] );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );

		remove_role( 'gh_test_contacts_viewer' );
		remove_role( 'gh_test_denied' );
		remove_role( 'gh_test_reader' );
		get_role( 'administrator' )->add_cap( Pii::CAP );

		parent::tearDown();
	}

	protected function user( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	public function test_the_roles_that_can_view_contacts_get_it() {

		Main_Updater::grant_view_contact_pii();

		$this->assertTrue( get_role( 'administrator' )->has_cap( Pii::CAP ) );
		$this->assertTrue( get_role( 'gh_test_contacts_viewer' )->has_cap( Pii::CAP ) );
		$this->assertFalse( Pii::is_restricted( $this->user( 'gh_test_contacts_viewer' ) ) );
	}

	public function test_a_role_that_cannot_view_contacts_does_not_get_it() {

		Main_Updater::grant_view_contact_pii();

		$this->assertArrayNotHasKey( Pii::CAP, get_role( 'gh_test_reader' )->capabilities );
		$this->assertTrue( Pii::is_restricted( $this->user( 'gh_test_reader' ) ) );
	}

	public function test_a_role_that_was_denied_it_stays_denied() {

		Main_Updater::grant_view_contact_pii();

		$this->assertFalse( get_role( 'gh_test_denied' )->capabilities[ Pii::CAP ] );
		$this->assertTrue( Pii::is_restricted( $this->user( 'gh_test_denied' ) ) );
	}

	public function test_the_user_who_runs_the_update_is_not_restricted_on_that_request() {

		$admin = $this->user( 'administrator' );

		// the caps of the user are worked out when they are set, before the update runs
		wp_set_current_user( 0 );
		wp_set_current_user( $admin );

		$this->assertTrue( Pii::is_restricted() );
		$this->assertFalse( current_user_can( Pii::CAP ) );

		Main_Updater::grant_view_contact_pii();

		$this->assertFalse( Pii::is_restricted() );
		$this->assertTrue( current_user_can( Pii::CAP ) );
	}

	public function test_a_user_who_was_given_view_contacts_themselves_gets_it() {

		$user_id = $this->user( 'gh_test_reader' );
		get_userdata( $user_id )->add_cap( 'view_contacts' );

		$other = $this->user( 'gh_test_reader' );

		Main_Updater::grant_view_contact_pii();

		$this->assertFalse( Pii::is_restricted( $user_id ) );
		$this->assertTrue( Pii::is_restricted( $other ) );
	}

	public function test_the_current_user_who_was_given_view_contacts_themselves_is_not_restricted_on_that_request() {

		$user_id = $this->user( 'gh_test_reader' );
		get_userdata( $user_id )->add_cap( 'view_contacts' );

		wp_set_current_user( 0 );
		wp_set_current_user( $user_id );

		$this->assertTrue( Pii::is_restricted() );
		$this->assertFalse( current_user_can( Pii::CAP ) );

		Main_Updater::grant_view_contact_pii();

		$this->assertFalse( Pii::is_restricted() );
		$this->assertTrue( current_user_can( Pii::CAP ) );
	}

	public function test_a_user_who_was_denied_it_stays_denied() {

		$user_id = $this->user( 'gh_test_reader' );
		$user    = get_userdata( $user_id );
		$user->add_cap( 'view_contacts' );
		$user->add_cap( Pii::CAP, false );

		Main_Updater::grant_view_contact_pii();

		$this->assertTrue( Pii::is_restricted( $user_id ) );
	}
}
