<?php

use Groundhogg\Classes\Task;
use function Groundhogg\get_contactdata;

/**
 * What a task says it is about, which the task lists show under it
 *
 * @see Task::get_associated_data()
 */
class Task_Associated_Tests extends GH_UnitTestCase {

	protected function create_task( array $args ): Task {

		$task = new Task();
		$task->create( wp_parse_args( $args, [
			'summary' => 'a task',
			'type'    => 'task',
		] ) );

		return new Task( $task->get_id() );
	}

	public function test_a_task_about_a_contact_is_named_after_the_contact() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$contact_id = $this->factory()->contacts->create( [ 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane.task@example.com' ] );

		$associated = $this->create_task( [ 'object_type' => 'contact', 'object_id' => $contact_id ] )->get_associated_data();

		$this->assertSame( 'Jane Doe', $associated['name'] );
		$this->assertSame( 'contact', $associated['type'] );
		$this->assertNotEmpty( $associated['link'] );
	}

	public function test_a_task_about_nothing_has_no_name_and_no_link() {

		$associated = $this->create_task( [] )->get_associated_data();

		// the task lists show the name under the task, it was " #0"
		$this->assertSame( '', $associated['name'] );
		$this->assertSame( '', $associated['link'] );
	}

	public function test_a_task_about_something_else_is_named_by_what_and_which() {

		$associated = $this->create_task( [ 'object_type' => 'deal', 'object_id' => 12 ] )->get_associated_data();

		$this->assertSame( 'deal #12', $associated['name'] );
	}
}
