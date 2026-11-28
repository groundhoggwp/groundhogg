<?php

use Groundhogg\Background\Remove_Duplicate_Events;
use Groundhogg\Broadcast;
use Groundhogg\Event;
use function Groundhogg\get_db;

/**
 * Moving finished events from the queue to the history table must add each event once, however many moves overlap,
 * and reports must not count an event that is in the history table twice.
 */
class Event_History_Duplicates_Tests extends GH_UnitTestCase {

	protected bool $raced = false;

	public function setUp(): void {
		parent::setUp();
		$this->factory()->truncate();
		$this->raced = false;
	}

	protected function make_broadcast(): Broadcast {
		$id = get_db( 'broadcasts' )->add( [
			'object_id'   => 1,
			'object_type' => 'email',
			'query'       => [],
			'status'      => 'sent',
		] );

		return new Broadcast( $id );
	}

	/**
	 * Queue a finished broadcast event for each of $num contacts
	 *
	 * @return int[] the queue IDs
	 */
	protected function queue_finished_broadcast_events( Broadcast $broadcast, int $num ) {
		$ids = [];

		for ( $i = 1; $i <= $num; $i ++ ) {
			$ids[] = $this->factory()->event_queue->create( [
				'contact_id' => $i,
				'funnel_id'  => Broadcast::FUNNEL_ID,
				'step_id'    => $broadcast->get_id(),
				'email_id'   => 1,
				'event_type' => Event::BROADCAST,
				'status'     => Event::COMPLETE,
			] );
		}

		return $ids;
	}

	protected function move_finished_events() {
		get_db( 'event_queue' )->move_events_to_history( [
			'status' => [
				Event::SKIPPED,
				Event::COMPLETE,
				Event::FAILED,
				Event::CANCELLED,
			]
		] );
	}

	/**
	 * How many history rows each queue ID has
	 *
	 * @return int[] queued_id => rows
	 */
	protected function history_rows_per_queued_id() {
		global $wpdb;
		$events = get_db( 'events' )->get_table_name();

		return array_map( 'intval', wp_list_pluck( $wpdb->get_results( "SELECT queued_id, COUNT(*) AS num FROM $events GROUP BY queued_id ORDER BY queued_id" ), 'num', 'queued_id' ) );
	}

	protected function count_queue() {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . get_db( 'event_queue' )->get_table_name() );
	}

	/**
	 * Add an exact copy of every history row, like the overlapping moves did
	 */
	protected function copy_history_rows() {
		global $wpdb;
		$events  = get_db( 'events' )->get_table_name();
		$columns = 'time,micro_time,time_scheduled,queued_id,contact_id,funnel_id,step_id,email_id,event_type,error_code,error_message,priority,status,args';

		$wpdb->query( "INSERT INTO $events ($columns) SELECT $columns FROM $events ORDER BY ID" );
	}

	/**
	 * Two queue runs that finish at the same time: the second move runs after the first one copied the events and
	 * before it deleted them from the queue. This is what added every event of a broadcast to the history twice.
	 */
	public function test_a_second_move_between_the_copy_and_the_delete_adds_no_copies() {

		$broadcast = $this->make_broadcast();
		$queued    = $this->queue_finished_broadcast_events( $broadcast, 5 );
		$queue     = get_db( 'event_queue' )->get_table_name();

		$race = function ( $query ) use ( $queue ) {
			if ( ! $this->raced && str_starts_with( ltrim( $query ), "DELETE FROM $queue" ) ) {
				$this->raced = true;
				$this->move_finished_events();
			}

			return $query;
		};

		add_filter( 'query', $race );
		$this->move_finished_events();
		remove_filter( 'query', $race );

		$this->assertTrue( $this->raced, 'The second move ran in between' );
		$this->assertSame( array_fill_keys( $queued, 1 ), $this->history_rows_per_queued_id() );
		$this->assertSame( 0, $this->count_queue() );
	}

	/**
	 * Running the move again over the same rows, e.g. after a request died between the copy and the delete, copies
	 * nothing again and finishes the delete
	 */
	public function test_moving_events_that_are_in_the_history_already_only_deletes_them_from_the_queue() {

		global $wpdb;

		$broadcast = $this->make_broadcast();
		$queued    = $this->queue_finished_broadcast_events( $broadcast, 3 );

		// the copy of an earlier move that never got to its delete
		$events = get_db( 'events' )->get_table_name();
		$queue  = get_db( 'event_queue' )->get_table_name();
		$wpdb->query( "INSERT INTO $events (queued_id,time,micro_time,time_scheduled,contact_id,funnel_id,step_id,email_id,event_type,error_code,error_message,priority,status,args)
			SELECT ID,time,micro_time,time_scheduled,contact_id,funnel_id,step_id,email_id,event_type,error_code,error_message,priority,status,args FROM $queue" );

		$this->move_finished_events();
		$this->move_finished_events();

		$this->assertSame( array_fill_keys( $queued, 1 ), $this->history_rows_per_queued_id() );
		$this->assertSame( 0, $this->count_queue() );
	}

	/**
	 * A history row with the same queued_id but from another event, which happens when the queue's AUTO_INCREMENT
	 * starts again, doesn't stop an event from moving
	 */
	public function test_a_reused_queue_id_still_moves() {

		$broadcast = $this->make_broadcast();
		[ $queued ] = $this->queue_finished_broadcast_events( $broadcast, 1 );

		$this->factory()->events->create( [
			'queued_id'  => $queued,
			'contact_id' => 99,
			'time'       => time() - YEAR_IN_SECONDS,
			'status'     => Event::COMPLETE,
		] );

		$this->move_finished_events();

		$this->assertSame( [ $queued => 2 ], $this->history_rows_per_queued_id() );
		$this->assertSame( 0, $this->count_queue() );
	}

	/**
	 * Waiting events stay in the queue
	 */
	public function test_waiting_events_are_not_moved() {

		$waiting = $this->factory()->event_queue->create( [ 'status' => Event::WAITING ] );

		$this->move_finished_events();

		$this->assertSame( [], $this->history_rows_per_queued_id() );
		$this->assertEquals( 1, get_db( 'event_queue' )->count( [ 'ID' => $waiting ] ) );
	}

	/**
	 * The clean-up removes exact copies and keeps the row with the lowest ID, and leaves alone legacy rows without a
	 * queued_id and different events that share a queued_id
	 */
	public function test_remove_duplicate_events_keeps_the_original_of_each_event() {

		global $wpdb;

		$broadcast = $this->make_broadcast();
		$queued    = $this->queue_finished_broadcast_events( $broadcast, 4 );
		$this->move_finished_events();

		$events    = get_db( 'events' )->get_table_name();
		$originals = array_map( 'intval', $wpdb->get_col( "SELECT ID FROM $events ORDER BY ID" ) );

		$this->copy_history_rows();
		$this->copy_history_rows(); // three of each

		// legacy rows from before queued_id, identical but not ours to judge
		$legacy = [
			'queued_id'  => 0,
			'contact_id' => 50,
			'time'       => 1000,
			'status'     => Event::COMPLETE,
		];
		$this->factory()->events->create( $legacy );
		$this->factory()->events->create( $legacy );

		// another event that got a used queue ID
		$this->factory()->events->create( [
			'queued_id'  => $queued[0],
			'contact_id' => 99,
			'time'       => 2000,
			'status'     => Event::COMPLETE,
		] );

		$broadcast->update_meta( 'cached_report_data', [ 'sent' => 12 ] );

		$task = new Remove_Duplicate_Events();

		$passes = 0;
		while ( $task->process() !== true && $passes < 100 ) {
			$passes ++;
		}

		$this->assertEquals( 100, $task->get_progress() );

		$expected                = array_fill_keys( $queued, 1 );
		$expected[0]             = 2;
		$expected[ $queued[0] ]  = 2;
		$this->assertEquals( $expected, $this->history_rows_per_queued_id() );

		$kept = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $events WHERE event_type = %d ORDER BY ID", Event::BROADCAST ) ) );
		$this->assertSame( $originals, $kept, 'The lowest ID of each event is kept' );

		$this->assertFalse( metadata_exists( 'broadcast', $broadcast->get_id(), 'cached_report_data' ), 'The cached report of the broadcast is cleared' );
	}

	/**
	 * A copy is found even when the row it copies is in an earlier batch
	 */
	public function test_delete_duplicate_events_finds_the_original_outside_the_batch() {

		global $wpdb;

		$broadcast = $this->make_broadcast();
		$this->queue_finished_broadcast_events( $broadcast, 2 );
		$this->move_finished_events();
		$this->copy_history_rows();

		$events = get_db( 'events' )->get_table_name();
		$ids    = array_map( 'intval', $wpdb->get_col( "SELECT ID FROM $events ORDER BY ID" ) );

		// only the copies are in this range
		$result = get_db( 'events' )->delete_duplicate_events( $ids[1], max( $ids ) );

		$this->assertSame( 2, $result['deleted'] );
		$this->assertSame( [ $broadcast->get_id() ], $result['broadcast_ids'] );
		$this->assertSame( array_slice( $ids, 0, 2 ), array_map( 'intval', $wpdb->get_col( "SELECT ID FROM $events ORDER BY ID" ) ) );
	}

	/**
	 * The sent count of a broadcast is the number of contacts it went to, even when events are in the history twice
	 */
	public function test_broadcast_sent_count_counts_each_contact_once() {

		$broadcast = $this->make_broadcast();
		$this->queue_finished_broadcast_events( $broadcast, 3 );
		$this->move_finished_events();
		$this->copy_history_rows();

		$this->assertEquals( 6, get_db( 'events' )->count( [ 'step_id' => $broadcast->get_id() ] ) );

		$stats = $broadcast->get_broadcast_stats();

		$this->assertSame( 3, $stats['sent'] );
	}
}
