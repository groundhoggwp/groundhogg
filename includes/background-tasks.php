<?php

namespace Groundhogg;

use Groundhogg\Utils\Limits;

class Background_Tasks {

	const SCHEDULE_BROADCAST = 'groundhogg/schedule_pending_broadcast';
	const ADD_CONTACTS_TO_FUNNEL = 'groundhogg/add_contacts_to_funnel';

	const BATCH_LIMIT = 500;

	public function __construct() {
		add_action( self::SCHEDULE_BROADCAST, [ $this, '_schedule_pending_broadcast' ], 10, 1 );
		add_action( self::ADD_CONTACTS_TO_FUNNEL, [ $this, '_add_contacts_to_funnel' ], 10, 3 );
	}

	/**
	 * Schedules the background task wp-cron event
	 *
	 * @param $hook
	 * @param $args
	 *
	 * @return bool|\WP_Error
	 */
	public static function add( $hook, $args ) {

		$when = apply_filters( 'groundhogg/background_tasks/schedule_time', time(), $hook, $args );

		return wp_schedule_single_event( $when, $hook, $args );
	}

	public static function remove( $hook, $args = [] ) {
		return wp_clear_scheduled_hook( $hook, $args );
	}

	/**
	 * Wrapper function to add contacts to a funnel
	 *
	 * @param $step_id
	 * @param $query
	 * @param $batch
	 *
	 * @return void
	 */
	public static function add_contacts_to_funnel( $step_id, $query, $batch = 0 ) {
		self::add( self::ADD_CONTACTS_TO_FUNNEL, [ $step_id, $query, $batch ] );
	}

	/**
	 * Add contacts to a funnel with a background task
	 *
	 * @param $step_id int
	 * @param $query   array
	 * @param $batch   int
	 */
	public function _add_contacts_to_funnel( $step_id, $query_vars, $batch = 0 ) {

		Limits::start();

		Limits::raise_memory_limit();
		Limits::raise_time_limit( MINUTE_IN_SECONDS );

		$step = new Step( $step_id );

		// Funnel is not active
		if ( ! $step->is_active() ) {
			return;
		}

		while ( ! Limits::limits_exceeded() ) {

			$offset = $batch * self::BATCH_LIMIT;

			$query = new Contact_Query( array_merge( $query_vars, [
				'offset'        => $offset,
				'limit'         => self::BATCH_LIMIT,
				'no_found_rows' => true,
			] ) );

			$contacts = $query->query( null, true );

			// No more contacts to add to the funnel
			if ( empty( $contacts ) ) {
				return;
			}

			foreach ( $contacts as $contact ) {
				$step->enqueue( $contact );
			}

			$batch ++;

			Limits::processed_action();
		}

		self::add_contacts_to_funnel( $step_id, $query_vars, $batch );
	}

	/**
	 * Schedule a pending broadcast
	 *
	 * @param $broadcast_id
	 *
	 * @return bool|\WP_Error
	 */
	public static function schedule_pending_broadcast( $broadcast_id ) {
		return self::add( self::SCHEDULE_BROADCAST, [ $broadcast_id ] );
	}

	/**
	 * Schedules any pending broadcasts
	 */
	public function _schedule_pending_broadcast( $broadcast_id ) {

		$broadcast = new Broadcast( $broadcast_id );

		if ( ! $broadcast->exists() || ! $broadcast->is_pending() ) {
			return;
		}

		Limits::start();

		Limits::raise_memory_limit();
		Limits::raise_time_limit( MINUTE_IN_SECONDS );

		$scheduled = true;

		while ( ! Limits::limits_exceeded() && $broadcast->is_pending() && $scheduled !== false ) {
			$scheduled = $broadcast->enqueue_batch();
			Limits::processed_action();
		}

		if ( $broadcast->is_pending() ) {
			$broadcast->schedule();
		}

		Limits::stop();
	}
}
