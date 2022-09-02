<?php

namespace Groundhogg\Queue;

use Groundhogg\DB\Events;
use Groundhogg\Event;
use function Groundhogg\get_db;

/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2019-06-04
 * Time: 8:12 AM
 */
class Event_Store {

	/**
	 * Get a number of events by a claim ID
	 *
	 * @param $claim
	 *
	 * @return int[]
	 */
	public function get_events_by_claim( $claim ) {

		if ( empty( $claim ) ) {
			return [];
		}

		$queued_events = $this->db()->query( [
			'claim'   => $claim,
			'status'  => Event::WAITING,
			'orderby' => 'time',
			'select'  => 'ID'
		], false );

		return wp_parse_id_list( wp_list_pluck( $queued_events, 'ID' ) );
	}

	/**
	 * @return \Groundhogg\DB\Event_Queue
	 */
	public function db() {
		return get_db( 'event_queue' );
	}

	/**
	 * Stake a claim in the DB
	 *
	 * @param int $count
	 *
	 * @return bool|string
	 */
	public function stake_claim( $count = 100 ) {
		$claim  = $this->generate_claim_id();
		$events = $this->get_queued_event_ids( $count );

		if ( empty( $events ) || ! $this->claim_events( $events, $claim ) ) {
			return false;
		}

		return $claim;
	}

	/**
	 * Generate a claim ID.
	 *
	 * @return bool|string
	 */
	public function generate_claim_id() {
		$claim_id = md5( uniqid( microtime( true ) ) );

		return substr( $claim_id, 0, 20 ); // to fit in db field with 20 char limit
	}

	/**
	 * Get a number of queued events.
	 *
	 * @param $count
	 *
	 * @return array
	 */
	public function get_queued_event_ids( $count = 100 ) {

		global $wpdb;

		$count = absint( $count );

		if ( ! $count ) {
			return [];
		}

		$clauses = apply_filters( 'groundhogg/queue/event_store/get_queued_event_ids/clauses', [
			$wpdb->prepare( '`status` = %s', Event::WAITING ),
			$wpdb->prepare( '`time` <= %d',  time() ),
		] );

		$clauses = implode( ' AND ', $clauses );

		$queued_events = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$this->db()->get_table_name()}
		WHERE $clauses
		ORDER BY `priority` ASC, `time` ASC
		LIMIT %d", $count ) );

		return wp_parse_id_list( wp_list_pluck( $queued_events, 'ID' ) );
	}

	/**
	 * Update the claim in the events queue
	 *
	 * @param $event_ids int[]
	 * @param $claim     string
	 *
	 * @return bool
	 */
	public function claim_events( $event_ids, $claim ) {
		global $wpdb;

		if ( empty( $event_ids ) || empty( $claim ) ) {
			return false;
		}

		$ids = implode( ',', $event_ids );

		// Double check claim is empty, because if it's not, bail.
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->db()->get_table_name()} SET `claim` = %s 
WHERE `ID` IN ( $ids ) AND `claim` = '' AND `time` <= %d AND `status` = %s", $claim, time(), Event::WAITING ) );

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
	public function release_events( $claim ) {
		return $this->db()->mass_update(
			[
				'claim' => ''
			],
			[
				'claim'  => $claim,
				'status' => Event::WAITING
			]
		);
	}


}
