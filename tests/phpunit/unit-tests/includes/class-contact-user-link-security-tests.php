<?php

use Groundhogg\Contact;
use function Groundhogg\can_link_contact_to_user;
use function Groundhogg\do_replacements;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\is_sending;

/**
 * A contact's linked WP user decides whose account its {auto_login_link} signs in as, so linking
 * a contact to a user must not be possible for everyone who can merely edit contacts.
 *
 * The reported chain (CVE-2026-104725): a sales_rep points one of their contacts at an
 * administrator via the `user` / `user_id` field, writes a note containing {auto_login_link},
 * reads the minted key back and visits the auto-login URL.
 *
 * Note the contacts table only lets one contact hold a given user_id (a second link is dropped
 * and re-derived from matching emails), so the chain needs a target user that has no contact of
 * its own. Creating a user also creates its contact, hence detach_user_contact() below: without it
 * every link attempt is dropped by the table and these tests would pass without testing the guard.
 *
 * @see \Groundhogg\can_link_contact_to_user()
 * @see Contact::sanitize_columns() the guard that covers both create() and update()
 * @see \Groundhogg\maybe_permissions_key_url()
 * @see \Groundhogg\Replacements::replacement_auto_login_link()
 */
class Contact_User_Link_Security_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		// is_sending() is process-wide static state that email tests can leave switched on, which
		// would make {auto_login_link} take the "sending an email" branch instead of the one under test
		is_sending( false );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @return Contact a contact owned by the given rep, not linked to any user
	 */
	protected function owned_contact( $owner_id ) {
		return get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );
	}

	/**
	 * Frees up a user so another contact can be linked to it.
	 */
	protected function detach_user_contact( $user_id ) {
		get_db( 'contacts' )->update( [ 'user_id' => $user_id ], [ 'user_id' => 0 ] );
	}

	/**
	 * What is actually stored, bypassing the per-request contact cache.
	 */
	protected function linked_user_id( $contact_id ) {
		return absint( get_db( 'contacts' )->get( $contact_id )->user_id );
	}

	/**
	 * @return int an administrator with no contact record, the target of the reported chain
	 */
	protected function contactless_admin() {
		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->detach_user_contact( $admin_id );

		return $admin_id;
	}

	/* ---------------------------------------------------------------------
	 * Linking a contact to a user
	 * ------------------------------------------------------------------- */

	public function test_control_link_to_a_free_user_works_without_a_logged_in_user() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $rep_id );

		// cron / CLI / registration hooks run without a logged-in user
		$contact->update( [ 'user_id' => $admin_id ] );

		$this->assertSame( $admin_id, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_rep_cannot_link_contact_to_admin_via_update() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		$this->assertTrue( current_user_can( 'edit_contact', $contact ) );
		$this->assertFalse( current_user_can( 'edit_users' ) );

		$contact->update( [ 'user_id' => $admin_id ] );

		$this->assertSame( 0, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_rep_cannot_link_contact_to_admin_via_create() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		wp_set_current_user( $rep_id );

		$contact = new Contact( [
			'email'   => 'created-by-rep@example.test',
			'user_id' => $admin_id,
		] );

		$this->assertTrue( $contact->exists() );
		$this->assertSame( 0, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_rep_cannot_link_contact_to_admin_via_rest() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		$request = new WP_REST_Request( 'PATCH', '/gh/v4/contacts/' . $contact->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'data' => [ 'user_id' => $admin_id, 'first_name' => 'Changed' ] ] ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		// The rest of the update still goes through, only the user link is dropped
		$this->assertSame( 'Changed', get_db( 'contacts' )->get( $contact->get_id() )->first_name );
		$this->assertSame( 0, $this->linked_user_id( $contact->get_id() ) );
	}

	/**
	 * @param array $contact the v3 `contact` payload
	 *
	 * @return WP_REST_Response
	 */
	protected function v3_create_contact( array $contact ) {
		$request = new WP_REST_Request( 'POST', '/gh/v3/contacts' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( [ 'contact' => $contact ] ) );

		return rest_do_request( $request );
	}

	/**
	 * POST /gh/v3/contacts upserts by email. It used to write straight to the DB class, which
	 * skipped the guard in Contact::sanitize_columns() (the reported chain's first step).
	 */
	public function test_rep_cannot_rebind_own_contact_to_admin_via_v3_create() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		$response = $this->v3_create_contact( [
			'email'      => $contact->get_email(),
			'first_name' => 'Changed',
			'user_id'    => $admin_id,
		] );

		$this->assertSame( 200, $response->get_status() );

		// The rest of the upsert still goes through, only the user link is dropped
		$this->assertSame( 'Changed', get_db( 'contacts' )->get( $contact->get_id() )->first_name );
		$this->assertSame( 0, $this->linked_user_id( $contact->get_id() ) );
		$this->assertSame( 'Changed', $response->get_data()['contact']['data']['first_name'] );
	}

	public function test_rep_cannot_create_contact_linked_to_admin_via_v3_create() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		wp_set_current_user( $rep_id );

		$response = $this->v3_create_contact( [
			'email'   => 'v3-created-by-rep@example.test',
			'user_id' => $admin_id,
		] );

		$this->assertSame( 200, $response->get_status() );

		$id = $response->get_data()['contact']['ID'];

		$this->assertSame( 'v3-created-by-rep@example.test', get_db( 'contacts' )->get( $id )->email );
		$this->assertSame( 0, $this->linked_user_id( $id ) );
	}

	public function test_v3_create_cannot_upsert_a_contact_the_rep_cannot_edit() {

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $admin_id );

		wp_set_current_user( $rep_id );

		$this->assertFalse( current_user_can( 'edit_contact', $contact ) );

		$response = $this->v3_create_contact( [
			'email'      => $contact->get_email(),
			'first_name' => 'Hijacked',
		] );

		$this->assertSame( 401, $response->get_status() );
		$this->assertNotSame( 'Hijacked', get_db( 'contacts' )->get( $contact->get_id() )->first_name );
	}

	public function test_v3_create_still_creates_with_meta_and_tags() {

		$rep_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		wp_set_current_user( $rep_id );

		$response = $this->v3_create_contact( [
			'email'      => 'v3-new@example.test',
			'first_name' => 'Nova',
			'meta'       => [ 'favourite_colour' => 'green' ],
			'tags'       => [ 'v3-tag' ],
		] );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data()['contact'];

		$this->assertSame( 'Nova', $data['data']['first_name'] );
		$this->assertSame( $rep_id, $data['data']['owner_id'] );
		$this->assertSame( 'green', $data['meta']['favourite_colour'] );
		$this->assertCount( 1, $data['tags'] );
	}

	public function test_rep_cannot_link_contact_to_user_that_does_not_exist() {

		$rep_id  = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		$contact->update( [ 'user_id' => 999999 ] );

		$this->assertSame( 0, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_admin_can_link_contact_to_user() {

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$this->detach_user_contact( $other_id );
		$contact = $this->owned_contact( $admin_id );

		wp_set_current_user( $admin_id );

		$contact->update( [ 'user_id' => $other_id ] );

		$this->assertSame( $other_id, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_rep_can_link_a_contact_to_themselves_via_update() {

		$rep_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$this->detach_user_contact( $rep_id );
		$contact = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		$contact->update( [ 'user_id' => $rep_id ] );

		$this->assertSame( $rep_id, $this->linked_user_id( $contact->get_id() ) );
	}

	public function test_unlinking_and_background_contexts_are_unaffected() {

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		wp_set_current_user( $rep_id );
		$this->assertTrue( can_link_contact_to_user( 0 ), 'unlinking' );
		$this->assertTrue( can_link_contact_to_user( $rep_id ), 'linking to yourself' );
		$this->assertFalse( can_link_contact_to_user( $admin_id ), 'linking to someone else' );

		wp_set_current_user( 0 );
		$this->assertTrue( can_link_contact_to_user( $admin_id ), 'no logged in user' );
	}

	public function test_can_link_filter_can_deny() {

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$this->assertTrue( can_link_contact_to_user( $admin_id ) );

		add_filter( 'groundhogg/contact/can_link_to_user', '__return_false' );

		$this->assertFalse( can_link_contact_to_user( $admin_id ) );

		remove_filter( 'groundhogg/contact/can_link_to_user', '__return_false' );
	}

	/* ---------------------------------------------------------------------
	 * {auto_login_link} outside of email sending
	 *
	 * These cover contacts that were linked before the fix was installed, and the
	 * email-match route to a link, so they don't depend on the guard above.
	 * ------------------------------------------------------------------- */

	public function test_auto_login_link_has_no_key_for_a_contact_linked_to_another_user() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		$target = $this->owned_contact( $rep_id );
		$target->update( [ 'user_id' => $admin_id ] ); // no logged in user, as a pre-fix link would have been
		$this->assertSame( $admin_id, $this->linked_user_id( $target->get_id() ) );

		wp_set_current_user( $rep_id );

		$content = do_replacements( 'Link: {auto_login_link}', $target->get_id() );

		$this->assertStringContainsString( '/auto-login', $content );
		$this->assertStringNotContainsString( 'pk=', $content );
	}

	public function test_auto_login_link_has_no_key_even_if_the_reps_own_contact_was_linked_to_admin() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		$own = get_contactdata( $rep_id, true );
		$this->assertTrue( $own->exists() );

		// The rep's own contact is the "current contact" (their email, tracked by their cookie)
		// but it was linked to the admin
		$own->update( [ 'user_id' => $admin_id ] );
		$this->assertSame( $admin_id, $this->linked_user_id( $own->get_id() ) );
		\Groundhogg\tracking()->add_tracking_cookie_param( 'contact_id', $own->get_id() );

		wp_set_current_user( $rep_id );

		$content = do_replacements( 'Link: {auto_login_link}', $own->get_id() );

		$this->assertStringNotContainsString( 'pk=', $content );
	}

	public function test_auto_login_link_has_no_key_outside_of_sending_even_for_the_logged_in_users_own_contact() {

		$rep_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$own    = get_contactdata( $rep_id, true );

		$this->assertTrue( $own->exists() );
		$this->assertSame( $rep_id, $this->linked_user_id( $own->get_id() ) );

		wp_set_current_user( $rep_id );

		$content = do_replacements( 'Link: {auto_login_link}', $own->get_id() );

		$this->assertStringContainsString( '/auto-login', $content );
		$this->assertStringNotContainsString( 'pk=', $content );
		$this->assertCount( 0, get_db( 'permissions_keys' )->query( [
			'contact_id' => $own->get_id(),
			'usage_type' => 'auto_login',
		] ) );
	}

	public function test_full_reported_chain_does_not_yield_an_admin_login_key() {

		$admin_id = $this->contactless_admin();
		$rep_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = $this->owned_contact( $rep_id );

		wp_set_current_user( $rep_id );

		// 1. link the contact to the admin
		$contact->update( [ 'user_id' => $admin_id ] );

		// 2. note containing the merge tag, created as the rep would over REST
		$note = new \Groundhogg\Classes\Note();
		$note->create( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'content'     => '{auto_login_link}',
		] );

		// 3. there is no key to read back, and none was stored
		$this->assertStringNotContainsString( 'pk=', $note->content );
		$this->assertCount( 0, get_db( 'permissions_keys' )->query( [
			'contact_id' => $contact->get_id(),
			'usage_type' => 'auto_login',
		] ) );
	}
}
