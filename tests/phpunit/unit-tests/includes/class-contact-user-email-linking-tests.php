<?php

use Groundhogg\Background\Import_Contacts;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\files;
use function Groundhogg\get_contactdata;

/**
 * A contact is linked to a user when they have the same email address, and nothing proves the address is the user's
 * (a registration can set the password without confirming it, and a user can change their own address in the REST
 * API), so a contact that already has the address keeps what it says about the person.
 *
 * @see create_contact_from_user()
 */
class Contact_User_Email_Linking_Tests extends GH_UnitTestCase {

	protected $csv = '';

	public function tear_down() {
		wp_set_current_user( 0 );
		remove_all_filters( 'groundhogg/should_convert_user_to_contact_when_user_registered' );
		remove_all_filters( 'groundhogg/create_contact_from_user/adopt_existing_contact' );

		if ( $this->csv && file_exists( $this->csv ) ) {
			unlink( $this->csv );
		}

		parent::tear_down();
	}

	/**
	 * A user the way a site that had the user before the contact would have them, with no contact
	 */
	protected function user_without_contact( array $args = [] ): int {

		add_filter( 'groundhogg/should_convert_user_to_contact_when_user_registered', '__return_false' );
		$id = self::factory()->user->create( array_merge( [ 'role' => 'subscriber' ], $args ) );
		remove_all_filters( 'groundhogg/should_convert_user_to_contact_when_user_registered' );

		$this->assertFalse( get_contactdata( $id, true ) && get_contactdata( $id, true )->exists(), 'The user has no contact' );

		return $id;
	}

	public function test_registering_with_the_email_of_a_contact_keeps_what_the_contact_says() {

		$contact = self::factory()->contacts->create_and_get( [ 'first_name' => 'Victim', 'last_name' => 'Person', 'email' => 'victim-link@example.test' ] );

		$user = self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'victim-link@example.test', 'first_name' => 'Mallory', 'last_name' => 'Attacker' ] );

		$contact = get_contactdata( $contact->get_id() );

		$this->assertSame( $user, $contact->get_user_id(), 'It is still their contact' );
		$this->assertSame( 'Victim', $contact->get_first_name() );
		$this->assertSame( 'Person', $contact->get_last_name() );
	}

	public function test_what_the_user_has_fills_in_what_the_contact_does_not() {

		$contact = self::factory()->contacts->create_and_get( [ 'first_name' => '', 'last_name' => '', 'email' => 'blank-link@example.test' ] );

		self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'blank-link@example.test', 'first_name' => 'Sam', 'last_name' => 'Signup' ] );

		$contact = get_contactdata( $contact->get_id() );

		$this->assertSame( 'Sam', $contact->get_first_name() );
		$this->assertSame( 'Signup', $contact->get_last_name() );
	}

	public function test_an_adopted_contact_keeps_its_meta_and_locale() {

		$contact = self::factory()->contacts->create_and_get( [ 'email' => 'meta-link@example.test' ] );
		$contact->update_meta( 'locale', 'fr_FR' );
		$contact->update_meta( 'nickname', 'Kept' );

		$this->user_without_contact( [ 'user_email' => 'meta-link@example.test', 'locale' => 'de_DE' ] );
		$user = get_user_by( 'email', 'meta-link@example.test' );
		update_user_meta( $user->ID, 'nickname', 'Overwritten' );

		create_contact_from_user( $user, true );

		$contact = get_contactdata( $contact->get_id() );

		$this->assertSame( $user->ID, $contact->get_user_id() );
		$this->assertSame( 'fr_FR', $contact->get_meta( 'locale' ) );
		$this->assertSame( 'Kept', $contact->get_meta( 'nickname' ) );
	}

	public function test_a_site_can_leave_a_contact_with_the_same_email_alone() {

		add_filter( 'groundhogg/create_contact_from_user/adopt_existing_contact', '__return_false' );

		$contact = self::factory()->contacts->create_and_get( [ 'first_name' => 'Victim', 'email' => 'refuse-link@example.test' ] );

		$user = self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'refuse-link@example.test', 'first_name' => 'Mallory' ] );

		$contact = get_contactdata( $contact->get_id() );

		$this->assertSame( 0, $contact->get_user_id() );
		$this->assertSame( 'Victim', $contact->get_first_name() );
		$this->assertFalse( create_contact_from_user( $user ) );
	}

	public function test_a_contact_that_is_another_users_is_not_taken_over() {

		$first = self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'first-link@example.test' ] );
		$owned = get_contactdata( $first, true );

		$this->assertTrue( $owned->exists() );

		// the contact's address moved on without the user's
		$owned->update( [ 'email' => 'moved-link@example.test' ] );

		$second = self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'moved-link@example.test' ] );

		$this->assertSame( $first, get_contactdata( $owned->get_id() )->get_user_id() );
		$this->assertFalse( create_contact_from_user( get_userdata( $second ) ) === $owned );
	}

	public function test_a_users_own_contact_still_follows_the_user() {

		$user    = self::factory()->user->create( [ 'role' => 'subscriber', 'user_email' => 'own-link@example.test', 'first_name' => 'Old', 'last_name' => 'Name' ] );
		$contact = get_contactdata( $user, true );

		wp_update_user( [ 'ID' => $user, 'first_name' => 'New', 'last_name' => 'Name' ] );

		$this->assertSame( 'New', get_contactdata( $contact->get_id() )->get_first_name() );
	}

	/**
	 * @return Import_Contacts
	 */
	protected function import( int $by, array $map, array $row ) {

		$name      = 'link-' . uniqid() . '.csv';
		$this->csv = files()->get_csv_imports_dir( $name );

		wp_mkdir_p( dirname( $this->csv ) );
		file_put_contents( $this->csv, implode( ',', array_keys( $row ) ) . "\n" . implode( ',', array_values( $row ) ) . "\n" );

		wp_set_current_user( $by );
		$task = new Import_Contacts( $name, [ 'field_map' => $map ] );
		$this->assertTrue( $task->can_run() );

		// it runs in the background, where nobody is logged in
		wp_set_current_user( 0 );
		$task->process();
		$task->stop();
	}

	public function test_an_import_can_only_link_to_users_whoever_ran_it_can_edit() {

		$target  = $this->user_without_contact( [ 'role' => 'administrator', 'user_email' => 'admin-link@example.test' ] );
		$manager = self::factory()->user->create( [ 'role' => 'sales_manager' ] );

		$this->assertTrue( user_can( $manager, 'import_contacts' ) );
		$this->assertFalse( user_can( $manager, 'edit_user', $target ) );

		$this->import( $manager, [ 'email' => 'email', 'user_id' => 'user_id' ], [ 'email' => 'imported-link@example.test', 'user_id' => $target ] );

		$contact = get_contactdata( 'imported-link@example.test' );

		$this->assertTrue( $contact->exists(), 'The contact is imported' );
		$this->assertSame( 0, $contact->get_user_id(), 'But not linked to an administrator by someone who can not edit them' );
	}

	public function test_an_import_by_someone_who_can_edit_the_user_links_it() {

		$target = $this->user_without_contact( [ 'user_email' => 'sub-link@example.test' ] );
		$admin  = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$this->import( $admin, [ 'email' => 'email', 'user_id' => 'user_id' ], [ 'email' => 'imported2-link@example.test', 'user_id' => $target ] );

		$this->assertSame( $target, get_contactdata( 'imported2-link@example.test' )->get_user_id() );
	}
}
