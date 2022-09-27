<?php

namespace Groundhogg\Queue;

use Groundhogg\DB\Events;
use Groundhogg\Event;
use Groundhogg\Event_Queue_Item;
use function Groundhogg\array_map_to_class;
use function Groundhogg\get_db;

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
		return get_db( 'event_queue' );
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
		], false );

		return array_map_to_class( $events, Event_Queue_Item::class );
	}

	/**
	 * @param $count
	 *
	 * @return Event_Queue_Item[]
	 */
	public function get_events( $count = 100 ) {

		$this->generate_claim_id();
		$this->claim_events( $count );

		return $this->get_events_by_claim();
	}

	/**
	 * Generate a claim ID.
	 *
	 * @return string
	 */
	public function generate_claim_id() {
		$claim_id    = md5( uniqid( microtime() ) );
		$claim_id    = substr( $claim_id, 0, 20 );
		$this->claim = $claim_id;

		return $this->claim;
	}

	/**
	 * Update the claim in the events queue
	 *
	 * @param $count int
	 *
	 * @return bool
	 */
	public function claim_events( $count ) {

		global $wpdb;

		$clauses = apply_filters( 'groundhogg/queue/event_store/get_queued_event_ids/clauses', [
			$wpdb->prepare( 'status = %s', Event::WAITING ),
			$wpdb->prepare( 'time <= %d', time() ),
			"claim = ''",
		] );

		$clauses      = implode( ' AND ', $clauses );
		$current_time = time();

		$result = $wpdb->query( "
	UPDATE {$this->db()->get_table_name()}
	SET claim = '{$this->claim}', time = $current_time
	WHERE $clauses
	ORDER BY priority ASC, ID ASC
	LIMIT $count" );

		// Deadlock maybe?
		if ( $result === false ) {
			$wpdb->print_error( 'Restarting transaction after deadlock' );

			return $this->claim_events( $count );
		}

		$this->db()->cache_set_last_changed();

		return $result;
	}

	/**
	 * Release waiting events that have a claim from the event store.
	 *
	 * @param $claim
	 *
	 * @return bool
	 */
	public function release_events() {

		global $wpdb;

		$result = $wpdb->query( "
	UPDATE {$this->db()->get_table_name()}
	SET claim = ''
	WHERE claim = '{$this->claim}'" );

		$this->db()->cache_set_last_changed();

		return $result;
	}


}
