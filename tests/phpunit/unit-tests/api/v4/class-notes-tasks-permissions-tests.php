<?php

use Groundhogg\Api\V4\Notes_Api;
use Groundhogg\Api\V4\Tasks_Api;
use Groundhogg\Classes\Note;
use Groundhogg\Classes\Task;
use function Groundhogg\get_contactdata;

/**
 * Covers the association-based permission cascade added in 1de453136
 * ("enhance permission system for notes and tasks with association-based view checks"):
 *
 * @see \Groundhogg\Main_Roles::map_meta_cap() the view_note/edit_note/delete_note (and task
 *      equivalent) cascade into "can the user view the associated object" (contact/deal/company
 *      by default, filterable via groundhogg/roles/note_association_cap_check_types).
 * @see Notes_Api::create_permissions_callback() the same association check applied when creating
 *      a note/task via the REST API, on top of the blanket add_notes/add_tasks cap.
 * @see Tasks_Api which inherits create_permissions_callback() from Notes_Api.
 */
class Notes_Tasks_Permissions_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @return Note
	 */
	protected function create_note( array $args ) {
		$note = new Note();
		$note->create( wp_parse_args( $args, [
			'object_type' => 'contact',
			'content'     => 'a note',
		] ) );

		return $note;
	}

	/**
	 * @return Task
	 */
	protected function create_task( array $args ) {
		$task = new Task();
		$task->create( wp_parse_args( $args, [
			'object_type' => 'contact',
			'summary'     => 'a task',
		] ) );

		return $task;
	}

	protected function create_json_request( $payload ) {
		$request = new WP_REST_Request( 'POST', '/gh/v4/notes' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $payload ) );

		return $request;
	}

	/* ---------------------------------------------------------------------
	 * Main_Roles::map_meta_cap() cascade for view_note / edit_note / delete_note
	 * ------------------------------------------------------------------- */

	public function test_owner_can_view_edit_delete_own_note_on_own_contact() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$note = $this->create_note( [
			'object_id' => $contact->get_id(),
			'user_id'   => $owner_id,
		] );

		$this->assertTrue( user_can( $owner_id, 'view_note', $note ) );
		$this->assertTrue( user_can( $owner_id, 'edit_note', $note ) );
		$this->assertTrue( user_can( $owner_id, 'delete_note', $note ) );
	}

	public function test_other_rep_without_others_contacts_cap_cannot_view_note_on_someone_elses_contact() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$note = $this->create_note( [
			'object_id' => $contact->get_id(),
			'user_id'   => $owner_id,
		] );

		// sales_rep has view_others_notes, but not view_others_contacts, and there's no team
		// relationship between the two reps - the cascade must still block the read.
		$this->assertTrue( user_can( $other_id, 'view_others_notes' ) );
		$this->assertFalse( user_can( $other_id, 'view_others_contacts' ) );

		$this->assertFalse( user_can( $other_id, 'view_note', $note ) );
		$this->assertFalse( user_can( $other_id, 'edit_note', $note ) );
		$this->assertFalse( user_can( $other_id, 'delete_note', $note ) );
	}

	public function test_sales_manager_can_view_note_via_view_others_contacts() {

		$owner_id   = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$manager_id = self::factory()->user->create( [ 'role' => 'sales_manager' ] );
		$contact    = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$note = $this->create_note( [
			'object_id' => $contact->get_id(),
			'user_id'   => $owner_id,
		] );

		$this->assertTrue( user_can( $manager_id, 'view_others_contacts' ) );
		$this->assertTrue( user_can( $manager_id, 'view_note', $note ) );
	}

	public function test_association_cascade_does_not_apply_to_unassociated_note() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		// No object_type/object_id: create_object_from_type() can't resolve anything, so the
		// cascade must be a no-op and the note falls back to the plain author/view_others_notes
		// behaviour that existed before this change.
		$note = $this->create_note( [
			'object_type' => '',
			'object_id'   => 0,
			'user_id'     => $owner_id,
		] );

		$this->assertTrue( user_can( $other_id, 'view_others_notes' ) );
		$this->assertTrue( user_can( $other_id, 'view_note', $note ) );
	}

	public function test_note_association_cap_check_types_filter_can_narrow_cascade() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$note = $this->create_note( [
			'object_id' => $contact->get_id(),
			'user_id'   => $owner_id,
		] );

		// Confirm the cascade is active by default.
		$this->assertFalse( user_can( $other_id, 'view_note', $note ) );

		$remove_contact_from_cascade = function ( $types ) {
			return array_values( array_diff( $types, [ 'contact' ] ) );
		};

		add_filter( 'groundhogg/roles/note_association_cap_check_types', $remove_contact_from_cascade );

		// With 'contact' excluded from the cascade, this falls back to the blanket
		// view_others_notes cap again.
		$this->assertTrue( user_can( $other_id, 'view_note', $note ) );

		remove_filter( 'groundhogg/roles/note_association_cap_check_types', $remove_contact_from_cascade );
	}

	public function test_task_cascade_mirrors_note_cascade() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$task = $this->create_task( [
			'object_id' => $contact->get_id(),
			'user_id'   => $owner_id,
		] );

		$this->assertTrue( user_can( $owner_id, 'view_task', $task ) );
		$this->assertTrue( user_can( $other_id, 'view_others_tasks' ) );
		$this->assertFalse( user_can( $other_id, 'view_task', $task ) );
		$this->assertFalse( user_can( $other_id, 'edit_task', $task ) );
	}

	/* ---------------------------------------------------------------------
	 * Notes_Api::create_permissions_callback()
	 * ------------------------------------------------------------------- */

	public function test_notes_api_create_denied_without_add_notes_cap() {

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$api = new Notes_Api();

		$this->assertFalse( $api->create_permissions_callback() );
	}

	public function test_notes_api_create_allowed_without_request_object() {

		$user_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		wp_set_current_user( $user_id );

		$api = new Notes_Api();

		$this->assertTrue( $api->create_permissions_callback( null ) );
	}

	public function test_notes_api_create_allowed_for_viewable_associated_contact() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );
		wp_set_current_user( $owner_id );

		$api     = new Notes_Api();
		$request = $this->create_json_request( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'content'     => 'hi',
		] );

		$this->assertTrue( $api->create_permissions_callback( $request ) );
	}

	public function test_notes_api_create_denied_for_unviewable_associated_contact() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );
		wp_set_current_user( $other_id );

		$api     = new Notes_Api();
		$request = $this->create_json_request( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'content'     => 'sneaky note',
		] );

		$result = $api->create_permissions_callback( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'cannot_create', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] ?? null );
	}

	public function test_notes_api_create_allowed_when_no_association_given() {

		$user_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		wp_set_current_user( $user_id );

		$api     = new Notes_Api();
		$request = $this->create_json_request( [ 'content' => 'just a floating note' ] );

		$this->assertTrue( $api->create_permissions_callback( $request ) );
	}

	public function test_notes_api_create_bulk_request_blocked_if_any_item_targets_unviewable_contact() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );

		$own_contact   = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $other_id ] ) );
		$others_contact = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		wp_set_current_user( $other_id );

		$api     = new Notes_Api();
		$request = $this->create_json_request( [
			[ 'object_type' => 'contact', 'object_id' => $own_contact->get_id(), 'content' => 'fine' ],
			[ 'object_type' => 'contact', 'object_id' => $others_contact->get_id(), 'content' => 'not fine' ],
		] );

		$result = $api->create_permissions_callback( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'cannot_create', $result->get_error_code() );
	}

	public function test_notes_api_create_accepts_wrapped_data_key_payload() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );
		wp_set_current_user( $other_id );

		$api     = new Notes_Api();
		$request = $this->create_json_request( [
			'data' => [
				'object_type' => 'contact',
				'object_id'   => $contact->get_id(),
				'content'     => 'wrapped',
			],
		] );

		$result = $api->create_permissions_callback( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'cannot_create', $result->get_error_code() );
	}

	/* ---------------------------------------------------------------------
	 * Tasks_Api inherits create_permissions_callback() from Notes_Api
	 * ------------------------------------------------------------------- */

	public function test_tasks_api_create_permissions_gate_on_add_tasks_and_inherit_association_check() {

		$owner_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$other_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'owner_id' => $owner_id ] ) );

		$api = new Tasks_Api();

		// The owner can add a task against their own (viewable) contact.
		wp_set_current_user( $owner_id );
		$own_request = $this->create_json_request( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'summary'     => 'follow up',
		] );
		$this->assertTrue( $api->create_permissions_callback( $own_request ) );

		// A different rep, who can't view that contact, is blocked even though they have add_tasks.
		wp_set_current_user( $other_id );
		$this->assertTrue( current_user_can( 'add_tasks' ) );

		$others_request = $this->create_json_request( [
			'object_type' => 'contact',
			'object_id'   => $contact->get_id(),
			'summary'     => 'sneaky task',
		] );
		$result = $api->create_permissions_callback( $others_request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'cannot_create', $result->get_error_code() );

		// A subscriber without add_tasks is blocked outright.
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );
		$this->assertFalse( $api->create_permissions_callback( $own_request ) );
	}
}
