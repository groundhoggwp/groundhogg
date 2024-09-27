<?php

namespace Groundhogg;

use Groundhogg\Background\Schedule_Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Classes\Background_Task;
use Groundhogg\DB\Broadcast_Meta;
use Groundhogg\DB\Broadcasts;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\Micro_Time_Tracker;
use GroundhoggSMS\Classes\SMS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Broadcast
 *
 * This is a simple class that inits a broadcast like object for easy use and manipulation.
 * Also contains some api methods for the event queue
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Broadcast extends Base_Object_With_Meta implements Event_Process {

	const TYPE_SMS = 'sms';
	const TYPE_EMAIL = 'email';
	const FUNNEL_ID = 1;

	/**
	 * @var SMS|Email
	 */
	protected $object;

	/**
	 * Flag for whether the hook was added to check if the broadcast has been fully sent or not.
	 *
	 * @var bool
	 */
	static $sent_hook_set = false;

	/**
	 * Whether the object is transactional and thus avoids marketability.
	 *
	 * @return bool
	 */
	public function is_transactional() {

		$object = $this->get_object();
		if ( method_exists( $object, 'is_transactional' ) ) {
			return $object->is_transactional();
		}

		return false;
	}

	/**
	 * If the broadcast has been fully scheduled.
	 * Not the same as if the status is scheduled, which is mostly for display in the admin
	 *
	 * @return bool
	 */
	public function is_scheduled() {
		return boolval( $this->get_meta( 'is_scheduled' ) );
	}

	public function is_pending() {
		return $this->get_status() === 'pending';
	}

	public function is_cancelled() {
		return $this->get_status() === 'cancelled';
	}

	public function is_sent() {
		return $this->status_is( 'sent' );
	}

	public function status_is( $status ) {
		return $this->get_status() === $status;
	}

	/**
	 * If the broadcast is in the process of sending
	 *
	 * @return bool
	 */
	public function is_sending() {
		return $this->status_is( 'sending' );
	}

	/**
	 * If the broadcast is sent
	 * or if there are no pending events remaining
	 *
	 * @return bool
	 */
	public function is_fully_sent() {
		return $this->is_sent() || ! $this->has_pending_events();
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {

		$this->query = maybe_unserialize( $this->query );

		switch ( $this->get_broadcast_type() ) {
			case self::TYPE_EMAIL:
				$this->object = new Email( $this->get_object_id() );
				break;
			case self::TYPE_SMS:

				if ( is_sms_plugin_active() ) {
					$this->object = new SMS( $this->get_object_id() );
				}

				break;
		}
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Broadcasts
	 */
	protected function get_db() {
		return get_db( 'broadcasts' );
	}

	/**
	 * Returns meta db for the Broadcast
	 *
	 * @return Broadcast_Meta
	 */
	protected function get_meta_db() {
		return get_db( 'broadcastmeta' );
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'broadcast';
	}

	/**
	 * @return string|void
	 */
	public function get_funnel_title() {
		if ( $this->is_email() ) {
			return __( 'Broadcast Email', 'groundhogg' );
		} else {
			return __( 'Broadcast SMS', 'groundhogg' );
		}
	}

	/**
	 * @return string
	 */
	public function get_step_title() {
		return $this->get_title();
	}

	/**
	 * The query object
	 *
	 * @return array
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * @return string
	 */
	public function get_broadcast_type() {
		return $this->object_type;
	}

	/**
	 * @return int
	 */
	public function get_object_id() {
		return absint( $this->object_id );
	}

	/**
	 * Whether the broadcast is sending an sms
	 *
	 * @return bool
	 */
	public function is_sms() {
		return $this->get_broadcast_type() === self::TYPE_SMS;
	}

	/**
	 * Whether the broadcast is sending an email
	 *
	 * @return bool
	 */
	public function is_email() {
		return $this->get_broadcast_type() === self::TYPE_EMAIL;
	}

	/**
	 * @return Email|SMS|null
	 */
	public function get_object() {
		return $this->object;
	}

	public function get_send_time() {
		return absint( $this->send_time );
	}

	public function get_scheduled_by_id() {
		return absint( $this->scheduled_by );
	}

	public function get_funnel_id() {
		return self::FUNNEL_ID;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_date_scheduled() {
		return $this->date_scheduled;
	}

	/**
	 * Get the column row title for the broadcast.
	 *
	 * @return string
	 */
	public function get_title() {

		if ( ! $this->get_object() || ! $this->get_object()->exists() ) {
			return __( '(The associated Email or SMS was deleted.)', 'groundhogg' );
		}

		return $this->get_object()->get_title();
	}

	/**
	 * Calls the background task to schedule the broadcast
	 *
	 * @return bool|\WP_Error
	 */
	public function maybe_schedule_in_background() {

		if ( ! $this->is_schedulable() ) {
			return false;
		}

		if ( $this->get_meta( 'segment_type' ) === 'dynamic' ) {
			$added = Background_Tasks::add( new Schedule_Broadcast( $this->get_id() ), $this->get_send_time() );
		} else {
			$added = Background_Tasks::schedule_pending_broadcast( $this->get_id() );
		}

		if ( is_wp_error( $added ) ) {
			return $added;
		}

		$task_id = Background_Tasks::get_last_added_task_id();
		$this->update_meta( 'task_id', $task_id );

		return true;
	}

	/**
	 * If there is at least 1 pending event
	 *
	 * @return bool
	 */
	public function has_pending_events() {

		$query = new Table_Query( 'event_queue' );
		$query->setLimit( 1 )->where()->equals( 'step_id', $this->ID )
		      ->equals( 'funnel_id', self::FUNNEL_ID )
		      ->equals( 'event_type', Event::BROADCAST )
		      ->equals( 'status', Event::WAITING );

		return count( $query->get_results() ) > 0;
	}


	public function count_pending_events() {

		$query = new Table_Query( 'event_queue' );
		$query->setLimit( 1 )->where()->equals( 'step_id', $this->ID )
		      ->equals( 'funnel_id', self::FUNNEL_ID )
		      ->equals( 'event_type', Event::BROADCAST )
		      ->equals( 'status', Event::WAITING );

		return $query->count();
	}

	/**
	 * Cancel the broadcast
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function cancel() {

		// already cancelled
		if ( $this->is_cancelled() ) {
			return true;
		}

		// if there are no pending events for this broadcast that means it's already fully sent
		if ( $this->is_sent() ) {
			return false;
		}

		// Also cancel the associated background task
		if ( $task_id = $this->get_meta( 'task_id' ) ) {
			$task = new Background_Task( $task_id );
			if ( ! $task->is_done() ) {
				$task->cancel();
			}
		}

		// Cancel events in the event queue
		get_db( 'event_queue' )->mass_update(
			[
				'status' => Event::CANCELLED
			],
			[
				'step_id'    => $this->get_id(),
				'funnel_id'  => Broadcast::FUNNEL_ID,
				'event_type' => Event::BROADCAST
			]
		);

		// Move them to the history table
		get_db( 'event_queue' )->move_events_to_history( [
			'status' => Event::CANCELLED,
		] );

		// Set status to cancelled finally
		$this->update( [ 'status' => 'cancelled' ] );

		return true;
	}

	/**
	 * If the broadcast can be scheduled
	 *
	 * @return bool
	 */
	public function is_schedulable() {
		return ( $this->status_is( 'pending' )
		       || $this->status_is( 'sending' ) ) && ! $this->is_scheduled();
	}

	/**
	 * Schedules a batch of events!
	 *
	 * @return false|float false if failed, a number of percentage complete
	 */
	public function enqueue_batch() {

		$lock = absint( $this->get_meta( 'schedule_lock' ) );

		// This broadcast is already being scheduled
		if ( $lock > 1 && time() - $lock < MINUTE_IN_SECONDS ) {
			return 0;
		}

		// This broadcast has already been scheduled
		if ( ! $this->is_schedulable() ) {
			return false;
		}

		$timer = new Micro_Time_Tracker();
		$items = 0;

		// Lock scheduling
		$this->update_meta( 'schedule_lock', time() );

		$query               = $this->get_query();
		$in_lt               = (bool) $this->get_meta( 'send_in_local_time' );
		$send_now            = (bool) $this->get_meta( 'send_now' );
		$offset              = absint( $this->get_meta( 'num_scheduled' ) ) ?: 0;
		$limit               = self::BATCH_LIMIT;
		$query['number']     = $limit;
		$query['offset']     = $offset;
		$query['found_rows'] = true;

		$batch_interval        = $this->get_meta( 'batch_interval' );
		$batch_interval_length = absint( $this->get_meta( 'batch_interval_length' ) );
		$batch_amount          = absint( $this->get_meta( 'batch_amount' ) ) ?: 100;
		$batch_delay           = absint( $this->get_meta( 'batch_delay' ) ) ?: 0;

		$c_query  = new Contact_Query( $query );
		$contacts = $c_query->query( null, true );
		$total    = $c_query->found_items;

		// No contacts to send to?
		if ( $total === 0 ) {
			return false;
		}

		foreach ( $contacts as $contact ) {

			$offset ++;
			$items ++;

			// if the number of scheduled items has reached the batch amount threshold
			if ( $batch_interval && $offset > 0 && $offset % $batch_amount === 0 ) {
				// increase the batch delay
				$batch_delay = strtotime( "+$batch_interval_length $batch_interval", $batch_delay );
			}

			// Can't be delivered at all
			if ( ! $contact->is_deliverable() ) {
				continue;
			}

			// No point in scheduling an email to a contact that is not marketable.
			if ( ! $this->is_transactional() && ! $contact->is_marketable() ) {
				continue;
			}

			$local_time = $this->get_send_time();

			// Send in the local time, maybe
			if ( $in_lt && ! $send_now ) {

				$local_time = $contact->get_local_time_in_utc_0( $local_time );

				if ( $local_time < time() ) {
					$local_time += DAY_IN_SECONDS;
				}
			}

			if ( $batch_interval && $batch_delay ) {
				$local_time += $batch_delay;
			}

			$args = [
				'time'       => $local_time,
				'contact_id' => $contact->get_id(),
				'funnel_id'  => Broadcast::FUNNEL_ID,
				'step_id'    => $this->get_id(),
				'event_type' => Event::BROADCAST,
				'status'     => Event::WAITING,
				'priority'   => 100,
			];

			if ( $this->is_email() ) {
				$args['email_id'] = $this->get_object_id();
			}

			event_queue_db()->batch_insert( $args );
		}

		$inserted = event_queue_db()->commit_batch_insert();

		// Failed to add an events, but there are contacts to send to
		if ( $total > 0 && ! $inserted ) {
			// Reset the lock
			$this->delete_meta( 'schedule_lock' );

			return 0;
		}

		$time_elapsed = $timer->time_elapsed();

		$this->update_meta( 'num_scheduled', $offset );
		$this->update_meta( 'total_contacts', $total );
		$this->update_meta( 'batch_time_elapsed', number_format( $time_elapsed, 2 ) );
		$this->update_meta( 'batch_delay', $batch_delay );

		// Finished scheduling
		if ( $offset >= $total ) {
			$this->update_meta( 'is_scheduled', true );

			// if the broadcast is still pending, we can update the status to scheduled
			// the scheduled status is now for display only, use the is_scheduled meta flag
			if ( $this->is_pending() ) {
				$this->update( [ 'status' => 'scheduled' ] );
			}
		}

		$this->delete_meta( 'schedule_lock' );

		return $items;

	}

	/**
	 * Add an action for status changes
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$prevStatus = $this->get_status();

		$result = parent::update( $data );

		$newStatus = $this->get_status();

		if ( $prevStatus !== $newStatus ) {

			/**
			 * When a broadcast's status changes
			 *
			 * @param $broadcast Broadcast
			 */
			do_action( "groundhogg/broadcast/$newStatus", $this );
		}

		return $result;
	}

	/**
	 * Updates the broadcast's status to sent if criteria are met
	 *
	 * @return bool
	 */
	public function maybe_set_status_to_sent() {

		// pull any changes that might have been made by other events
		$this->pull();

		// if the broadcast has not finished scheduling, no go.
		if ( ! $this->is_scheduled() ) {
			return false;
		}

		// We're not done sending events yet
		if ( $this->has_pending_events() ){
			return false;
		}

		// We have to at least sent a few emails, right?
		if ( ! $this->status_is( 'sending' ) ) {
			return false;
		}

		$this->update( [ 'status' => 'sent' ] );

		return true;
	}

	/**
	 * Send the associated object to the given contact
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error whether the email sent or not.
	 */
	public function run( $contact, $event = null ) {

		/**
		 * Fires before the broadcast is sent
		 *
		 * @param Broadcast $broadcast
		 * @param Contact   $contact
		 * @param Event     $event
		 */
		do_action( "groundhogg/broadcast/{$this->get_broadcast_type()}/before", $this, $contact, $event );
		do_action( "groundhogg/broadcast/before", $this, $contact, $event );

		/**
		 * Filter the object to send...
		 *
		 * @param Email|SMS $object
		 * @param Broadcast $broadcast
		 */
		$object = apply_filters( "groundhogg/broadcast/{$this->get_broadcast_type()}/object", $this->get_object(), $this, $contact, $event );

		if ( ! $object || ! $object->exists() ) {
			return new \WP_Error( 'object_error', 'Could not find email or SMS to send.' );
		}

		$result = $object->send( $contact, $event );

		/**
		 * Fires after the broadcast is sent
		 *
		 * @param Broadcast $broadcast
		 * @param Contact   $contact
		 * @param Event     $event
		 */
		do_action( "groundhogg/broadcast/{$this->get_broadcast_type()}/after", $this, $contact, $event );
		do_action( "groundhogg/broadcast/after", $this, $contact, $event );

		// Set the status to sending while we're sending the broadcast
		// Regardless of the previous status
		if ( ! $this->status_is( 'sending' ) ) {

			// directly from pending
			if ( $this->is_pending() ){
				// set the status to schedule first to run any hooks temporarily
				$this->update( [ 'status' => 'scheduled' ] );
			}

			// immediately then set it to sending
			$this->update( [ 'status' => 'sending' ] );
		}

		if ( ! self::$sent_hook_set ){
			// set a hook after the event queue has finished processing to see if the broadcast is done sending.
			add_action( 'groundhogg/event_queue/after_process', [ $this, 'maybe_set_status_to_sent' ] );
			self::$sent_hook_set = true;
		}

		return $result;
	}

	/**
	 * Just return true for now cuz I'm lazy...
	 *
	 * @return bool
	 */
	public function can_run() {
		return true;
	}

	protected $report_data = [];

	/**
	 * @return array
	 */
	public function get_report_data( $unused = 0 ) {

		if ( ! empty( $this->report_data ) ) {
			return $this->report_data;
		}

		$data = [];

		$data['waiting'] = get_db( 'event_queue' )->count( [
			'step_id'    => $this->get_id(),
			'event_type' => Event::BROADCAST,
			'status'     => Event::WAITING,
		] );

		$data['id'] = $this->get_id();

		if ( $this->is_sent() || $this->status_is( 'sending' ) ) {

			$data['sent'] = get_db( 'events' )->count( [
				'step_id'    => $this->get_id(),
				'event_type' => Event::BROADCAST,
				'status'     => Event::COMPLETE,
			] );

			if ( $this->is_sms() ) {
				$data['sms_id']     = $this->get_object_id();
				$data['clicked']    = get_db( 'activity' )->count( [
					'select'        => 'DISTINCT contact_id',
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::SMS_CLICKED
				] );
				$data['all_clicks'] = get_db( 'activity' )->count( [
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::SMS_CLICKED
				] );

				$data['click_through_rate'] = percentage( $data['sent'], $data['clicked'] );

			} else {
				$data['email_id']           = $this->get_object_id();
				$data['opened']             = get_db( 'activity' )->count( [
					'select'        => 'DISTINCT contact_id',
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::EMAIL_OPENED
				] );
				$data['open_rate']          = percentage( $data['sent'], $data['opened'] );
				$data['clicked']            = get_db( 'activity' )->count( [
					'select'        => 'DISTINCT contact_id',
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::EMAIL_CLICKED
				] );
				$data['all_clicks']         = get_db( 'activity' )->count( [
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::EMAIL_CLICKED
				] );
				$data['click_through_rate'] = percentage( $data['opened'], $data['clicked'] );
				$data['unopened']           = $data['sent'] - $data['opened'];
				$data['opened_not_clicked'] = $data['opened'] - $data['clicked'];
			}

			$data['unsubscribed'] = get_db( 'activity' )->count( [
				'funnel_id'     => $this->get_funnel_id(),
				'step_id'       => $this->get_id(),
				'activity_type' => Activity::UNSUBSCRIBED
			] );

			// only if broadcast was actually sent and experimental is enabled.
			if ( use_experimental_features() && $data['sent'] > 0 ) {

				$events = get_db( 'events' )->query( [
					'select'     => 'COUNT(ID) as total',
					'step_id'    => $this->get_id(),
					'event_type' => Event::BROADCAST,
					'status'     => Event::COMPLETE,
					'groupby'    => 'time',
					'orderby'    => false,
					'order'      => false,
				] );

				$counts = wp_list_pluck( $events, 'total' );

				$total = array_sum( $counts );
				$count = count( $counts );

				// Speed = total sent / ( time_end - time_start )
				$data['speed'] = round( $total / $count, 2 );
			}

		}

		$this->report_data = $data;

		return $data;
	}

	/**
	 * @return array
	 */
	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'object'           => $this->get_object(),
			'date_sent_pretty' => format_date( convert_to_local_time( $this->get_send_time() ) )
		] );
	}

	const BATCH_LIMIT = 500;

	/**
	 * Get the number of items remaining that require scheduling
	 *
	 * @return array|false|mixed
	 */
	public function get_items_remaining() {
		$total     = $this->get_meta( 'total_contacts' );
		$scheduled = $this->get_meta( 'num_scheduled' );

		if ( ! $total || ! $scheduled ) {
			return false;
		}

		return $total - $scheduled;
	}

	/**
	 * The estimated time to completed scheduling all the events in seconds
	 *
	 * @return false|float time in seconds
	 */
	public function get_estimated_scheduling_time_remaining() {

		$remaining    = $this->get_items_remaining();
		$time_elapsed = $this->get_meta( 'batch_time_elapsed' );

		if ( ! $remaining || ! $time_elapsed ) {
			return false;
		}

		return ceil( ( $remaining / self::BATCH_LIMIT ) * $time_elapsed );
	}

	/**
	 * A percentage value of the
	 *
	 * @return float|int
	 */
	public function get_percent_scheduled() {

		$offset = absint( $this->get_meta( 'num_scheduled' ) );
		$total  = absint( $this->get_meta( 'total_contacts' ) );

		return percentage( $total, $offset );
	}
}
