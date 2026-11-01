<?php

use Groundhogg\Classes\Page_Visit;
use Groundhogg\Submission;
use function Groundhogg\get_db;

/**
 * The REST API checks `view_`, `edit_` and `delete_` plus the object type against each object it reads or changes. A
 * meta cap nothing maps is a cap nobody has, so the object is closed to everyone.
 *
 * @see \Groundhogg\Main_Roles::map_meta_cap() for the step, submission and page visit caps
 */
class Single_Object_Caps_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function user( string $role ): int {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	protected function submission( int $contact_id ): Submission {

		$submission = new Submission();
		$submission->create( [ 'type' => 'custom', 'name' => 'Survey', 'contact_id' => $contact_id ] );

		return $submission;
	}

	protected function page_visit( int $contact_id ): Page_Visit {

		$visit = new Page_Visit();
		$visit->create( [ 'contact_id' => $contact_id, 'timestamp' => time(), 'path' => '/pricing' ] );

		return $visit;
	}

	protected function step_id(): int {
		return (int) get_db( 'steps' )->add( [ 'funnel_id' => 1, 'step_title' => 'A step', 'step_type' => 'send_email', 'step_group' => 'action', 'step_order' => 1 ] );
	}

	public function test_a_step_follows_the_flow_caps() {

		$step = $this->step_id();

		$admin = $this->user( 'administrator' );
		$rep   = $this->user( 'sales_rep' );

		foreach ( [ 'view_step', 'edit_step', 'delete_step' ] as $cap ) {
			$this->assertTrue( user_can( $admin, $cap, $step ), "administrator $cap" );
		}

		// a rep can see flows, not change them
		$this->assertTrue( user_can( $rep, 'view_step', $step ) );
		$this->assertFalse( user_can( $rep, 'edit_step', $step ) );
		$this->assertFalse( user_can( $rep, 'delete_step', $step ) );
	}

	public function test_a_submission_is_as_visible_as_its_contact() {

		$owner = $this->user( 'sales_rep' );
		$other = $this->user( 'sales_rep' );
		$admin = $this->user( 'administrator' );

		$contact_id = self::factory()->contacts->create( [ 'owner_id' => $owner ] );
		$submission = $this->submission( $contact_id );

		$this->assertTrue( user_can( $owner, 'view_submission', $submission ) );
		$this->assertTrue( user_can( $admin, 'view_submission', $submission ) );
		$this->assertFalse( user_can( $other, 'view_submission', $submission ) );

		// by ID as well as by object
		$this->assertTrue( user_can( $admin, 'view_submission', $submission->get_id() ) );
		$this->assertFalse( user_can( $other, 'view_submission', $submission->get_id() ) );
	}

	public function test_a_submission_about_a_contact_that_is_gone_is_for_those_that_see_everyones() {

		$rep   = $this->user( 'sales_rep' );
		$admin = $this->user( 'administrator' );

		$orphan = $this->submission( 999999 );

		$this->assertFalse( user_can( $rep, 'view_submission', $orphan ) );
		$this->assertTrue( user_can( $admin, 'view_submission', $orphan ) );
	}

	public function test_a_submission_that_does_not_exist_is_closed_to_everyone() {

		$admin = $this->user( 'administrator' );

		$this->assertFalse( user_can( $admin, 'view_submission', 999999 ) );
		$this->assertFalse( user_can( $admin, 'view_submission' ) );
	}

	public function test_no_one_edits_a_submission_and_deleting_one_is_for_those_that_delete_logs() {

		$admin = $this->user( 'administrator' );
		$rep   = $this->user( 'sales_rep' );

		$submission = $this->submission( self::factory()->contacts->create() );

		$this->assertFalse( user_can( $admin, 'edit_submission', $submission ) );
		$this->assertTrue( user_can( $admin, 'delete_submission', $submission ) );
		$this->assertFalse( user_can( $rep, 'delete_submission', $submission ) );
	}

	public function test_a_page_visit_is_as_visible_as_its_contact() {

		$owner = $this->user( 'sales_rep' );
		$other = $this->user( 'sales_rep' );
		$admin = $this->user( 'administrator' );

		$visit = $this->page_visit( self::factory()->contacts->create( [ 'owner_id' => $owner ] ) );

		$this->assertTrue( user_can( $owner, 'view_page_visit', $visit ) );
		$this->assertTrue( user_can( $admin, 'view_page_visit', $visit ) );
		$this->assertFalse( user_can( $other, 'view_page_visit', $visit ) );
		$this->assertFalse( user_can( $admin, 'view_page_visit', 999999 ) );
	}

	public function test_changing_a_page_visit_is_for_administrators() {

		$admin = $this->user( 'administrator' );
		$rep   = $this->user( 'sales_rep' );

		$visit = $this->page_visit( self::factory()->contacts->create() );

		$this->assertTrue( user_can( $admin, 'edit_page_visit', $visit ) );
		$this->assertTrue( user_can( $admin, 'delete_page_visit', $visit ) );
		$this->assertFalse( user_can( $rep, 'edit_page_visit', $visit ) );
		$this->assertFalse( user_can( $rep, 'delete_page_visit', $visit ) );
	}

	public function test_the_submissions_and_page_visits_routes_read_a_single_object() {

		$admin = $this->user( 'administrator' );
		wp_set_current_user( $admin );

		$contact_id = self::factory()->contacts->create();
		$submission = $this->submission( $contact_id );
		$visit      = $this->page_visit( $contact_id );

		$this->assertSame( 200, rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/submissions/' . $submission->get_id() ) )->get_status() );
		$this->assertSame( 200, rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/page_visits/' . $visit->get_id() ) )->get_status() );
		$this->assertSame( 200, rest_do_request( new WP_REST_Request( 'GET', '/gh/v4/steps/' . $this->step_id() ) )->get_status() );
	}
}
