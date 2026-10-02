<?php

use Groundhogg\Background\Migrate_Composed_Emails;
use Groundhogg\Classes\Activity;
use function Groundhogg\get_db;
use function Groundhogg\tracking;

class Migrate_Composed_Emails_Tests extends GH_UnitTestCase {

	protected function composed( int $contact_id, array $meta = [] ): Activity {

		$activity = $this->factory()->activity->create_and_get( [
			'activity_type' => Migrate_Composed_Emails::ACTIVITY_TYPE,
			'contact_id'    => $contact_id,
		] );

		foreach ( array_merge( [ 'subject' => 'Hello there', 'from' => 'me@example.com' ], $meta ) as $key => $value ) {
			$activity->update_meta( $key, $value );
		}

		return $activity;
	}

	protected function log( string $content ): int {
		return get_db( 'email_log' )->add( [
			'recipients'   => 'someone@example.com',
			'from_address' => 'me@example.com',
			'subject'      => 'Hello there',
			'content'      => $content,
			'status'       => 'sent',
		] );
	}

	protected function run_task() {
		$task  = new Migrate_Composed_Emails();
		$steps = 0;

		do {
			$done = $task->process();
		} while ( $done === false && ++ $steps < 10 );

		$this->assertTrue( $done );

		return $task;
	}

	protected function messages_of( int $contact_id ): int {
		return get_db( 'messages' )->count( [ 'object_type' => 'contact', 'object_id' => $contact_id ] );
	}

	protected function activity_exists( Activity $activity ): bool {
		return get_db( 'activity' )->count( [ 'ID' => $activity->get_id() ] ) === 1;
	}

	public function test_a_composed_email_becomes_a_message_and_the_activity_is_removed() {

		$contact  = $this->factory()->contacts->create_and_get();
		$log_id   = $this->log( '<p>The body</p>' );
		$activity = $this->composed( $contact->get_id(), [ 'log_id' => $log_id ] );

		$this->run_task();

		$this->assertSame( 1, $this->messages_of( $contact->get_id() ) );
		$this->assertFalse( $this->activity_exists( $activity ) );

		$message = get_db( 'messages' )->query( [ 'object_type' => 'contact', 'object_id' => $contact->get_id() ] )[0];
		$this->assertSame( 'Hello there', $message->subject );
		$this->assertSame( $log_id, (int) $message->email_log_id );
		$this->assertStringContainsString( 'The body', $message->content );
	}

	/**
	 * An empty ID given to get_contactdata() means whoever the request is about, so an activity with no contact was
	 * stored on the tracked contact
	 */
	public function test_an_activity_with_no_contact_is_not_stored_on_the_tracked_contact() {

		$tracked = $this->factory()->contacts->create_and_get();
		tracking()->set_current_contact( $tracked );
		$this->assertSame( $tracked->get_id(), tracking()->get_current_contact()->get_id(), 'the contact is being tracked' );

		$activity = $this->composed( 0 );

		$this->run_task();

		$this->assertSame( 0, $this->messages_of( $tracked->get_id() ) );
		$this->assertSame( 0, $this->messages_of( 0 ) );
		$this->assertTrue( $this->activity_exists( $activity ), 'the activity is left where it is' );
	}

	public function test_an_activity_of_a_contact_that_does_not_exist_is_left_alone() {

		$activity = $this->composed( 987654321 );

		$task = $this->run_task();

		$this->assertTrue( $this->activity_exists( $activity ) );
		$this->assertSame( 0, $this->messages_of( 987654321 ) );
		$this->assertEquals( 100, $task->get_progress() );
	}

	public function test_a_composed_email_that_was_already_copied_is_not_copied_again() {

		$contact  = $this->factory()->contacts->create_and_get();
		$log_id   = $this->log( '<p>The body</p>' );
		$this->composed( $contact->get_id(), [ 'log_id' => $log_id ] );

		$this->run_task();
		$this->assertSame( 1, $this->messages_of( $contact->get_id() ) );

		// the activity of a run that stopped after it made the message and before it deleted the activity
		$again = $this->composed( $contact->get_id(), [ 'log_id' => $log_id ] );

		$this->run_task();

		$this->assertSame( 1, $this->messages_of( $contact->get_id() ), 'no second message' );
		$this->assertFalse( $this->activity_exists( $again ) );
	}

	public function test_rows_that_are_left_do_not_hold_up_the_ones_after_them() {

		$contact = $this->factory()->contacts->create_and_get();

		$first = $this->composed( 0 );
		$next  = $this->composed( $contact->get_id() );

		$this->run_task();

		$this->assertTrue( $this->activity_exists( $first ) );
		$this->assertFalse( $this->activity_exists( $next ) );
		$this->assertSame( 1, $this->messages_of( $contact->get_id() ) );
	}
}
