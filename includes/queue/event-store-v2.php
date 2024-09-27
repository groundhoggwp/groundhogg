<?php

namespace Groundhogg\Queue;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use function Groundhogg\array_map_to_class;
use function Groundhogg\event_queue_db;
use function Groundhogg\generate_claim;

/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2019-06-04
 * Time: 8:12 AM
 */
class Event_Store_V2 {

	protected $claim;

	/**
	 * @return \Groundhogg\DB\Event_Queue
	 */
	public function db() {
		return event_queue_db();
	}

	/**
	 * Get raw events by claim
	 *
	 * @return Event_Queue_Item[]
	 */
	public function get_events_by_claim() {

		$events = $this->db()->query( [
			'select'  => [
				'ID',
				'time',
				'micro_time',
				'time_scheduled',
				'funnel_id',
				'step_id',
				'email_id',
				'contact_id',
				'event_type',
				'status',
				'claim'
			],
			'claim'   => $this->claim,
			'orderby' => 'ID',
			'order'   => 'asc'
		] );

		return array_map_to_class( $events, Event_Queue_Item::class );
	}

	/**
	 * @param $count
	 *
	 * @return Event_Queue_Item[]
	 */
	public function get_events( $count = 100 ) {

		$this->generate_claim_id();

		$claimed = $this->claim_events( $count );

		// Claim did not claim any events, so no point in doing the select
		if ( $claimed === false || $claimed === 0 ) {
			return [];
		}

		return $this->get_events_by_claim();
	}

	/**
	 * Generate a claim ID.
	 *
	 * @return string
	 */
	public function generate_claim_id() {
		$this->claim = generate_claim();

		return $this->claim;
	}

	/**
	 * Update the claim in the events queue
	 *
	 * @param $count int
	 *
	 * @return bool|int
	 */
	public function claim_events( $count ) {

		$query = new Table_Query( 'event_queue' );
		$query->setLimit( $count )->setOrderby( [ 'priority', 'ASC' ], [ 'ID', 'ASC' ] )->where()
		      ->equals( 'status', Event::WAITING )
		      ->lessThanEqualTo( 'time', time() )
		      ->empty( 'claim' );

		do_action_ref_array( 'groundhogg/queue/event_store/claim_events', [
			&$query
		] );

		$result = $query->update( [
			'claim'        => $this->claim,
			'time_claimed' => time()
		] );

		// Deadlock maybe?
		if ( $result === false ) {
			global $wpdb;
			$wpdb->print_error( 'Restarting transaction after deadlock' );

			return $this->claim_events( $count );
		}

		// No rows affected
		if ( $result === 0 ) {
			return false;
		}

		return $result;
	}

	/**
	 * Release waiting events that have a claim from the event store.
	 *
	 * @return bool
	 */
	public function release_events() {

		$query = new Table_Query( 'event_queue' );
		$query->where()
		      ->equals( 'claim', $this->claim );

		return $query->update( [
			'claim'        => '',
			'time_claimed' => 0
		] );
	}


}
