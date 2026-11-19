<?php

use Groundhogg\Classes\Task;

/**
 * What the mobile app's tasks need of the server: a task with a summary and no details, a note and outcome added after
 * it is completed, and the list of people a task can be given to.
 */
class Mobile_Task_Assign_Tests extends GH_UnitTestCase {

	public function tear_down() {
		wp_set_current_user( 0 );
		delete_option( 'gh_owners' );
		parent::tear_down();
	}

	protected function request( string $method, string $route, array $body = [] ) {

		$request = new WP_REST_Request( $method, $route );

		if ( $body ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}

		return rest_do_request( $request );
	}

	public function test_a_task_can_have_a_summary_and_no_details_and_be_given_to_someone() {

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$other = self::factory()->user->create( [ 'role' => 'administrator' ] );

		wp_set_current_user( $admin );

		$response = $this->request( 'POST', '/gh/v4/tasks', [ 'data' => [ 'summary' => 'Call back', 'content' => '', 'type' => 'task', 'user_id' => $other, 'due_date' => '2026-12-01 09:00:00' ] ] );

		$this->assertSame( 200, $response->get_status() );

		$task = new Task( $response->get_data()['item']->ID );

		$this->assertSame( 'Call back', $task->summary );
		$this->assertSame( '', $task->content );
		$this->assertSame( $other, (int) $task->user_id );
	}

	public function test_a_note_and_outcome_can_be_added_after_a_task_is_completed() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$task = new Task();
		$task->create( [ 'summary' => 'Call back', 'type' => 'task', 'user_id' => get_current_user_id() ] );

		$this->assertSame( 200, $this->request( 'PATCH', "/gh/v4/tasks/{$task->get_id()}/complete" )->get_status() );

		$response = $this->request( 'PATCH', "/gh/v4/tasks/{$task->get_id()}", [ 'data' => [ 'activity' => 1, 'type' => 'task_complete', 'outcome' => 'Won', 'note' => '<p>Signed</p>' ] ] );

		$this->assertSame( 200, $response->get_status() );

		$task = new Task( $task->get_id() );

		$this->assertTrue( $task->is_complete() );
		$this->assertSame( 'task', $task->type, 'The activity type is not the task type' );

		$activity = $task->get_as_array()['activity'];

		$this->assertCount( 1, $activity );
		$this->assertSame( 'task_complete', $activity[0]->activity_type );
		$this->assertSame( 'Won', $activity[0]->get_meta( 'outcome' ) );
		$this->assertSame( '<p>Signed</p>', $activity[0]->get_meta( 'note' ) );
	}

	public function test_whoever_can_edit_others_tasks_gets_the_people_to_give_one_to_with_themselves_first() {

		$admin = self::factory()->user->create( [ 'role' => 'administrator', 'display_name' => 'Ada Admin' ] );

		wp_set_current_user( $admin );

		$response = $this->request( 'GET', '/gh/v4/mobile/task-assignees' );

		$this->assertSame( 200, $response->get_status() );

		$items = $response->get_data()['items'];

		$this->assertSame( $admin, $items[0]['id'] );
		$this->assertSame( 'Ada Admin', $items[0]['name'] );

		// the owners the desktop offers are all there (get_owners() holds on to the first answer it gives in a run)
		foreach ( \Groundhogg\get_owners() as $owner ) {
			$this->assertContains( (int) $owner->ID, array_column( $items, 'id' ) );
		}

		$this->assertCount( count( $items ), array_unique( array_column( $items, 'id' ) ), 'Nobody is listed twice' );
	}

	public function test_it_is_closed_to_those_who_cannot_edit_others_tasks() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertGreaterThanOrEqual( 401, $this->request( 'GET', '/gh/v4/mobile/task-assignees' )->get_status() );
	}
}
