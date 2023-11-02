<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Broadcast_Meta;
use Groundhogg\DB\Broadcasts;
use Groundhogg\Utils\Limits;
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

	public function is_scheduled() {
		return $this->get_status() === 'scheduled';
	}

	public function is_pending() {
		return $this->get_status() === 'pending';
	}

	public function is_sent() {
		return $this->get_status() === 'sent';
	}

	public function schedule(){
		return Background_Tasks::add( 'groundhogg/schedule_pending_broadcast', [ $this->get_id() ] );
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

	public function create( $data = [] ) {
		$created = parent::create( $data );

		// Start scheduling it
		if ( $created ){
			$this->schedule();
		}

		return $created;
	}

	/**
	 * Cancel the broadcast
	 *
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function cancel() {
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

		get_db( 'event_queue' )->move_events_to_history( [
			'status' => Event::CANCELLED,
		] );

		$this->update( [ 'status' => 'cancelled' ] );
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

		// Wait until broadcast is fully scheduled before updating status to sent
		if ( $this->is_scheduled() && ! $this->is_sent() ) {
			$this->update( [ 'status' => 'sent' ] );
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
	public function get_report_data( $email_id = 0 ) {

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

		if ( $this->is_sent() ) {

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
					'select'     => 'count(ID)',
					'step_id'    => $this->get_id(),
					'event_type' => Event::BROADCAST,
					'status'     => Event::COMPLETE,
					'groupby'    => 'time',
					'orderby'    => false,
					'order'      => false,
				] );

				$counts = wp_list_pluck( $events, 'count(ID)' );

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
	 * Schedules a batch of events!
	 *
	 * @return false|float false if failed, a number of percentage complete
	 */
	public function schedule_batch() {

		if ( ! $this->is_pending() ){
			return false;
		}

		$query                  = $this->get_query();
		$in_lt                  = (bool) $this->get_meta( 'send_in_local_time' );
		$send_now               = (bool) $this->get_meta( 'send_now' );
		$offset                 = absint( $this->get_meta( 'num_scheduled' ) ) ?: 0;
		$limit                  = self::BATCH_LIMIT;
		$query['number']        = $limit;
		$query['offset']        = $offset;
		$query['no_found_rows'] = false;

		$c_query  = new Contact_Query();
		$contacts = $c_query->query( $query, true );
		$total    = $c_query->found_items;

		foreach ( $contacts as $contact ) {

			$offset ++;

			// Can't be delivered at all
			if ( ! $contact->is_deliverable() ) {
				continue;
			}

			// No point in scheduling an email to a contact that is not marketable.
			if ( ! $this->is_transactional() && ! $contact->is_marketable() ) {
				continue;
			}

			$local_time = $this->get_send_time();

			if ( $in_lt && ! $send_now ) {

				$local_time = $contact->get_local_time_in_utc_0( $local_time );

				if ( $local_time < time() ) {
					$local_time += DAY_IN_SECONDS;
				}
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

		if ( $total > 0 && ! $inserted ) {
			return false;
		}

		$this->update_meta( 'num_scheduled', $offset );
		$this->update_meta( 'total_contacts', $total );

		// Finished scheduling
		if ( $offset >= $total ) {
			$this->update( [ 'status' => 'scheduled' ] );
		}

		return percentage( $total, $offset, 0 );

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
