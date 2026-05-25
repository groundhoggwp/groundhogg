<?php

namespace Groundhogg\Classes;

use Groundhogg\Broadcast;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\array_apply_callbacks;
use function Groundhogg\array_order_sort;
use function Groundhogg\get_db;
use function Groundhogg\one_of;
use function Groundhogg\sanitize_payload;

class Recurring_Broadcast extends Broadcast {

	public function get_broadcast() {
		return new Broadcast( $this->get_object_id() );
	}

	public function get_title() {

		$broadcast = $this->get_broadcast();

		if ( ! $broadcast->exists() ){
			return esc_html__( '???', 'groundhogg' );
		}

		return $broadcast->get_title();
	}

	/**
	 * Stuff when the scheduled is initially started
	 *
	 * @return void
	 */
	public function start() {
		$this->reset_occurrences();
		$this->maybe_decrement_occurrences();
	}

	public function reset_occurrences() {
		$this->update_meta( 'occurrences_left', $this->get_meta( 'repeats_until_occurrences' ) );
		$this->update_meta( 'occurrences', 0 );
	}

	public function  maybe_decrement_occurrences() {
		$repeats_until = $this->get_meta( 'repeats_until' );
		$occurrences   = absint( $this->get_meta( 'occurrences' ) );
		$this->update_meta( 'occurrences', $occurrences + 1 );

		if ( $repeats_until === 'occurrences' ) {
			$this->update_meta( 'occurrences_left', max( absint( $this->get_meta( 'occurrences_left' ) ) - 1, 0 ) );
		}
	}

	public function count_scheduled_broadcasts() {
		return get_db()->broadcasts->count( [ 'schedule_id' => $this->get_id() ] );
	}

	/**
	 * Resume a schedule that was previously cancelled
	 * Schedules the next broadcast
	 *
	 * @throws \DateMalformedStringException
	 * @return bool
	 */
	public function resume() {

		// can't resume a schedule that isn't cancelled
		if ( ! $this->status_is( 'cancelled' ) ){
			return false;
		}

		$this->update( [ 'status' => 'active' ] );

		$this->schedule_next();

		return true;
	}

	/**
	 * Can't be in use by a schedule if it is a schedule
	 *
	 * @return false
	 */
	public function in_use_by_schedule() {
		return false;
	}

	public function is_email() {
		return $this->get_broadcast()->is_email();
	}

	public function is_sms() {
		return $this->get_broadcast()->is_sms();
	}

	/**
	 * Cancel a schedule, also cancels the next scheduled broadcast
	 *
	 * @return bool
	 */
	public function cancel() {

		// can't cancel a schedule that isn't active
		if ( ! $this->status_is( 'active' ) ){
			return false;
		}

		// cancel next broadcast
		$this->get_broadcast()->cancel();

		// Set status to cancelled finally
		$this->update( [ 'status' => 'cancelled' ] );

		return true;
	}

	public function get_end_date() {

		$until = $this->get_meta( 'repeats_until' );

		switch ( $until ) {
			default:
			case 'never':
				return null;
			case 'occurrences':

				$new_send_time = $this->calc_next_send_time( $this->get_send_time() );
				$occurrences = absint( $this->get_meta( 'occurrences_left' ) );

				while ( $occurrences > 0 ) {
					--$occurrences;
					$new_send_time = $this->calc_next_send_time( $new_send_time );
				}

				return $new_send_time;
			case 'date':
				return (new DateTimeHelper( $this->get_meta( 'repeats_until_date' ) ));
		}

	}

	/**
	 * Schedule the next broadcast
	 *
	 * @throws \DateMalformedStringException
	 * @return void
	 */
	public function schedule_next() {

		$new_send_time = $this->calc_next_send_time( $this->get_send_time() );
		$repeats_until = $this->get_meta( 'repeats_until' );

		switch ( $repeats_until ) {
			case 'occurrences':

				$occurrences = absint( $this->get_meta( 'occurrences_left' ) );

				if ( $occurrences === 0 ) {
					$this->update( [ 'status' => 'done' ] );

					return;
				}

				break;
			case 'date':

				$end_date = $this->get_meta( 'repeats_until_date' );

				// the next date would be after the end date, so no more
				if ( $new_send_time > new DateTimeHelper( $end_date ) ) {
					$this->update( [ 'status' => 'done' ] );

					return;
				}

				break;
		}

		// get the initial broadcast
		$initial = $this->get_broadcast();

		// if the broadcast is not available for some reason, maybe it was deleted?
		if ( ! $initial->exists() ) {
			$this->update( [ 'status' => 'done' ] );
			return;
		}

		// make a copy of it
		/* @var $new Broadcast */
		$new = $initial->duplicate( [
			'send_time'   => $new_send_time,
			'status'      => 'pending',
			'schedule_id' => $this->get_id()
		] );

		// reset these meta keys for scheduling to work
		$new->delete_meta( 'batch_delay' );
		$new->delete_meta( 'batch_time_elapsed' );
		$new->delete_meta( 'num_scheduled' );
		$new->delete_meta( 'schedule_lock' );
		$new->delete_meta( 'is_scheduled' );
		$new->delete_meta( 'last_id' );
		$new->delete_meta( 'task_id' );

		// set the object ID to the most recently scheduled broadcast
		// and update the send time so that the corn job can pick up the next send
		$this->update( [
			'object_id' => $new->get_id(),
			'send_time' => $new_send_time
		] );

		// occurrence
		$this->maybe_decrement_occurrences();

		// background task
		$new->maybe_schedule_in_background();

		/**
		 * Fires after the broadcast is added to the DB but before the user is redirected to the scheduler
		 *
		 * @param  int  $broadcast_id  the ID of the broadcast
		 * @param  array  $meta  the config object which is passed to the scheduler
		 * @param  Broadcast  $broadcast  the broadcast object
		 */
		do_action( 'groundhogg/admin/broadcast/scheduled', $new->get_id(), $new->get_meta(), $new );
	}

	public static function days_of_month() {
		return array_merge( range( 1, 31 ), [ 'last' ] );
	}

	/**
	 * Calculates the next date of sending based on the previous send time of a broadcast
	 *
	 * @throws \DateMalformedStringException
	 *
	 * @param  mixed  $last_send_time  accepts string, DateTime, int, must be date of last sent broadcast
	 *
	 * @return \DateTimeInterface
	 */
	public function calc_next_send_time( $last_send_time ) {

		$from = new DateTimeHelper( $last_send_time );

		$interval = $this->get_meta( 'repeats_every_interval' ); // days, weeks, months, years
		$amount   = $this->get_meta( 'repeats_every_amount' ); // positive integer

		switch ( $interval ) {
			case 'days':
			case 'years':

				$from->modify( "+{$amount} {$interval}" );

				break;
			case 'months':

				switch ( $this->get_meta( 'repeats_month_occurrence_type' ) ) {
					case 'm':

						// loop through picked days and see if they are > than $from
						$days = array_order_sort( $this->get_meta( 'repeats_dom' ), self::days_of_month() ); // 1-31 or 'last'

						// let's get the day of last sending
						$current_day = (int) $from->format( 'j' );
						$last_day    = (int) $from->format( 't' );

						$available_days = array_map( function ( $day ) use ( $last_day ) {
							return $day === 'last' ? $last_day : (int) $day;
						}, $days );

						$available_days = array_unique( $available_days );
						sort( $available_days, SORT_NUMERIC );

						// if there exists a day in the current month within the selected days
						foreach ( $available_days as $day ) {
							if ( $day > $current_day && $day <= $last_day ) {
								// return here early
								$from->setDate( (int) $from->format( 'Y' ), (int) $from->format( 'm' ), $day );

								break 2;
							}
						}

						// otherwise, advance by interval and use the first selected day of that month
						$from->modify( "+{$amount} {$interval}" );
						$from->setDate( (int) $from->format( 'Y' ), (int) $from->format( 'm' ), $days[0] === 'last' ? (int) $from->format( 't' ) : (int) $days[0] );

						break;
					case 'w':

						$occurrences = array_order_sort(
							$this->get_meta( 'repeats_dow_occurrence' ),
							[ 'first', 'second', 'third', 'fourth', 'last' ]
						);

						$weekdays = array_order_sort(
							$this->get_meta( 'repeats_dow' ),
							[ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ]
						);

						$get_next_candidate = function ( \DateTimeInterface $from, bool $allow_same_day = false ) use ( $occurrences, $weekdays ) {
							$next = null;

							foreach ( $occurrences as $occurrence ) {
								foreach ( $weekdays as $weekday ) {
									$candidate = clone $from;
									$candidate->modify( "$occurrence $weekday this month" );

									$valid = $allow_same_day
										? $candidate >= $from
										: $candidate > $from;

									if ( $valid && ( ! $next || $candidate < $next ) ) {
										$next = $candidate;
									}
								}
							}

							return $next;
						};

						$next = $get_next_candidate( $from );

						if ( $next ) {
							$from->setTimestamp($next->getTimestamp());
							break;
						}

						$from->modify( "+{$amount} months" );
						$from->modify( 'first day of this month' );

						$from->setTimestamp($get_next_candidate( $from, true )->getTimestamp());
						break;
				}

				break;
			case 'weeks':

				$weekdays = array_order_sort(
					$this->get_meta( 'repeats_dow' ),
					[ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ]
				);

				$get_next_weekday = function ( \DateTimeInterface $from, bool $allow_same_day = false ) use ( $weekdays ) {
					$next = null;

					foreach ( $weekdays as $weekday ) {
						$candidate = clone $from;
						$candidate->modify( "$weekday this week" );

						$valid = $allow_same_day
							? $candidate >= $from
							: $candidate > $from;

						if ( $valid && ( ! $next || $candidate < $next ) ) {
							$next = $candidate;
						}
					}

					return $next;
				};

				$next = $get_next_weekday( $from );

				if ( $next ) {
					$from->setTimestamp($next->getTimestamp());
					break;
				}

				// otherwise advance by interval and use first available weekday in that week
				$from->modify( "+{$amount} weeks" );
				$from->modify( 'monday this week' );

				$from->setTimestamp($get_next_weekday( $from, true )->getTimestamp());
				break;
		}

		// if the calculated time is in the past, fast forward to the next day in the schedule
		if ( $from->isPast() ) {
			return $this->calc_next_send_time( $from );
		}

		return $from;

	}

	public function create( $data = [] ) {

		// force
		$data['object_type'] = 'recurring_broadcast';
		$data['query'] = [];

		return parent::create( $data );
	}

	protected function sanitize_columns( $data = [] ) {
		return array_apply_callbacks( $data, [
			'object_id'      => fn( $val ) => absint( $val ),
			'object_type'    => fn( $val ) => 'recurring_broadcast', // force to recurring_broadcast
			'scheduled_by'   => fn( $val ) => absint( $val ),
			'send_time'      => fn( $val ) => ( new DateTimeHelper( $val ) )->getTimestamp(),
			'query'          => fn( $val ) => sanitize_payload( $val ),
			'date_scheduled' => fn( $val ) => ( new DateTimeHelper( $val ) )->ymdhis(),
			'status'         => fn( $val ) => one_of( $val, [ 'active', 'cancelled', 'done' ] ),
		] );
	}

	protected function sanitize_meta( $key, $value ) {

		$value = parent::sanitize_meta( $key, $value );

		if ( empty( $value ) ) {
			return $value;
		}

		switch ( $key ) {
			case 'occurrences_left':
			case 'repeats_until_occurrences':
			case 'repeats_every_amount':
				return absint( $value );
			case 'repeats_every_interval':
				return one_of( $value, [ 'days', 'weeks', 'months', 'years' ] );
			case 'repeats_dow':
				return array_intersect( $value, [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] );
			case 'repeats_dow_occurrence':
				return array_intersect( $value, [ 'first', 'second', 'third', 'fourth', 'last' ] );
			case 'repeats_month_occurrence_type':
				return one_of( $value, [ 'm', 'w' ] );
			case 'repeats_dom':
				return array_intersect( $value, self::days_of_month() );
			case 'repeats_until':
				return one_of( $value, [ 'never', 'date', 'occurrences' ] );
			case 'repeats_until_date':
				return ( new DateTimeHelper( $value ) )->ymd();
			default:
				return $value;
		}
	}

}
