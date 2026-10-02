<?php

use Groundhogg\Background\Add_Activity_Indexes;
use Groundhogg\Background\Migrate_Composed_Emails;
use Groundhogg\Classes\Background_Task;
use Groundhogg\Background_Tasks;
use Groundhogg\DB\Activity;
use Groundhogg\Plugin;
use function Groundhogg\db;

/**
 * The activity table's indexes for type and per-contact-over-time lookups, on install and through the 4.9 update
 */
class Activity_Indexes_Tests extends GH_UnitTestCase {

	public function tearDown(): void {
		// ALTER TABLE commits, so the table isn't rolled back between tests: put the fresh install indexes back
		$this->activity()->add_index_online( 'contact_idx', [ 'contact_id' ] );
		while ( ! $this->activity()->add_next_performance_index() ) {
		}

		parent::tearDown();
	}

	protected function activity(): Activity {
		return db()->activity;
	}

	/**
	 * Put the table back how it was before 4.9
	 */
	protected function make_legacy_table() {
		foreach ( array_keys( Activity::PERFORMANCE_INDEXES ) as $index ) {
			$this->activity()->drop_index( $index );
		}

		$this->activity()->add_index_online( 'contact_idx', [ 'contact_id' ] );

		$this->assertFalse( $this->activity()->has_performance_indexes() );
		$this->assertTrue( $this->activity()->index_exists( 'contact_idx' ) );
	}

	/**
	 * The background tasks of a class queued since the given task, the update might queue more than one
	 *
	 * @param int    $since_id
	 * @param string $class
	 *
	 * @return array
	 */
	protected function get_tasks_added_since( int $since_id, string $class ): array {
		$tasks = [];

		for ( $id = $since_id + 1; $id <= Background_Tasks::get_last_added_task_id(); $id ++ ) {
			$task = ( new Background_Task( $id ) )->getTask();

			if ( $task instanceof $class ) {
				$tasks[] = $task;
			}
		}

		return $tasks;
	}

	protected function assertHasPerformanceIndexes() {
		global $wpdb;

		foreach ( Activity::PERFORMANCE_INDEXES as $index => $columns ) {
			$actual = $wpdb->get_col( $wpdb->prepare( "SHOW INDEX FROM {$this->activity()->table_name} WHERE Key_name = %s", $index ), 4 ); // Column_name, in Seq_in_index order
			$this->assertSame( $columns, $actual, $index );
		}

		$this->assertFalse( $this->activity()->index_exists( 'contact_idx' ), 'contact_idx is a prefix of contact_time_idx and should be gone' );
	}

	public function test_install_has_indexes() {
		global $wpdb;

		// the test DB keeps its tables between runs, so move the table aside to install a new one
		$table = $this->activity()->table_name;
		$wpdb->query( "RENAME TABLE $table TO {$table}_moved" );

		try {
			$this->assertFalse( $this->activity()->installed() );
			$this->activity()->create_table(); // the test framework makes this a temporary table
			$this->assertHasPerformanceIndexes();
		} finally {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
			$wpdb->query( "RENAME TABLE {$table}_moved TO $table" );
		}
	}

	public function test_create_table_leaves_an_existing_table_to_the_background_task() {
		$this->make_legacy_table();

		$this->activity()->create_table();

		// dbDelta didn't add them, but the SQL for a new table still has them
		$this->assertFalse( $this->activity()->has_performance_indexes() );
		$this->assertTrue( $this->activity()->index_exists( 'contact_idx' ) );
		$this->assertStringContainsString( 'KEY contact_time_idx (contact_id,timestamp,activity_type,email_id,step_id)', $this->activity()->create_table_sql_command() );
	}

	public function test_update_adds_indexes() {
		$this->make_legacy_table();

		$updater = Plugin::instance()->updater;
		$updater->forget_version_update( '4.9' );
		delete_transient( 'gh_main_doing_updates' );

		$this->assertContains( '4.9', $updater->get_automatic_updates_to_do() );

		$before = Background_Tasks::get_last_added_task_id();

		$updater->do_automatic_updates();

		$this->assertTrue( $updater->did_update( '4.9' ) );
		$this->assertFalse( $this->activity()->has_performance_indexes(), 'the update only queues the task' );

		$task = $this->get_tasks_added_since( $before, Add_Activity_Indexes::class )[0] ?? null;
		$this->assertInstanceOf( Add_Activity_Indexes::class, $task );

		// run the task itself rather than Background_Task::process(), which counts toward Limits' static processed actions that Event_Queue_Tests asserts on
		$steps = 0;
		do {
			$done = $task->process();
		} while ( $done === false && ++ $steps < 10 );

		$this->assertTrue( $done );
		$this->assertSame( 2, $steps, 'one step per index, then drop contact_idx' );
		$this->assertHasPerformanceIndexes();
	}

	/**
	 * Make the online ALTER fail like it does on a server without online DDL, and keep the ALTERs that were sent
	 *
	 * @param array $alters filled with the ALTER TABLE queries that reach the database
	 *
	 * @return callable the filter, to remove
	 */
	protected function fail_online_alter( array &$alters ) {
		$filter = function ( $query ) use ( &$alters ) {
			if ( stripos( $query, 'ALTER TABLE ' . $this->activity()->table_name ) !== 0 ) {
				return $query;
			}

			if ( stripos( $query, 'ALGORITHM=INPLACE' ) !== false ) {
				$alters[] = $query;

				return 'SELECT * FROM gh_table_that_does_not_exist';
			}

			$alters[] = $query;

			return $query;
		};

		add_filter( 'query', $filter );

		return $filter;
	}

	public function test_a_failed_online_build_does_not_fall_back_to_a_locking_alter() {
		global $wpdb;

		$this->make_legacy_table();

		$alters = [];
		$filter = $this->fail_online_alter( $alters );

		$added = $this->activity()->add_index_online( 'contact_time_idx', Activity::PERFORMANCE_INDEXES['contact_time_idx'] );

		remove_filter( 'query', $filter );

		$this->assertFalse( $added );
		$this->assertFalse( $this->activity()->index_exists( 'contact_time_idx' ) );
		$this->assertCount( 1, $alters, 'only the online ALTER was tried' );
		$this->assertStringContainsString( 'LOCK=NONE', $alters[0] );
	}

	public function test_the_task_tries_again_a_few_times_then_fails_with_the_database_error() {
		$this->make_legacy_table();

		$alters = [];
		$filter = $this->fail_online_alter( $alters );

		$task = new Add_Activity_Indexes();

		try {
			// not complete and not failed, null stops this pass and the task is picked up again on the next one
			for ( $attempt = 1; $attempt < Add_Activity_Indexes::MAX_ATTEMPTS; $attempt ++ ) {
				$this->assertNull( $task->process(), "attempt $attempt" );
			}

			$result = $task->process();
		} finally {
			remove_filter( 'query', $filter );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'index_not_added', $result->get_error_code() );
		$this->assertNotSame( '', $result->get_error_message(), 'the database error is kept' );
		$this->assertCount( Add_Activity_Indexes::MAX_ATTEMPTS, $alters );
		$this->assertFalse( $this->activity()->has_performance_indexes() );
	}

	public function test_the_task_counts_only_failures_in_a_row() {
		$this->make_legacy_table();

		$alters = [];
		$filter = $this->fail_online_alter( $alters );

		$task = new Add_Activity_Indexes();

		$this->assertNull( $task->process() );
		$this->assertNull( $task->process() );

		remove_filter( 'query', $filter );

		// it works this time, and the count starts over
		$this->assertFalse( $task->process() );

		$filter = $this->fail_online_alter( $alters );

		for ( $attempt = 1; $attempt < Add_Activity_Indexes::MAX_ATTEMPTS; $attempt ++ ) {
			$this->assertNull( $task->process(), "attempt $attempt" );
		}

		remove_filter( 'query', $filter );
	}

	public function test_update_does_nothing_when_indexes_exist() {
		$updater = Plugin::instance()->updater;
		$updater->forget_version_update( '4.9' );
		delete_transient( 'gh_main_doing_updates' );

		$before = Background_Tasks::get_last_added_task_id();

		$updater->do_automatic_updates();

		$this->assertTrue( $updater->did_update( '4.9' ) );

		$this->assertEmpty( $this->get_tasks_added_since( $before, Add_Activity_Indexes::class ) );
		$this->assertNotEmpty( $this->get_tasks_added_since( $before, Migrate_Composed_Emails::class ), 'the composed emails are still moved' );
	}
}
