<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Broadcast_Meta;
use Groundhogg\DB\Broadcasts;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
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
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
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
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {

		$this->query = maybe_unserialize( $this->query );

		switch ( $this->get_broadcast_type() ) {
			case self::TYPE_EMAIL:
				$this->object = Plugin::$instance->utils->get_email( $this->get_object_id() );
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

	public function is_sent() {
		return $this->get_status() === 'sent';
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
	 * Cancel the broadcast
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
	 * @param $event Event
	 *
	 * @return bool|\WP_Error whether the email sent or not.
	 */
	public function run( $contact, $event = null ) {

		/**
		 * Fires before the broadcast is sent
		 *
		 * @param Broadcast $broadcast
		 * @param Contact $contact
		 * @param Event $event
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
		 * @param Contact $contact
		 * @param Event $event
		 */
		do_action( "groundhogg/broadcast/{$this->get_broadcast_type()}/after", $this, $contact, $event );

		if ( ! $this->is_sent() ) {
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

		if ( ! $email_id ) {
			$email_id = $this->get_object_id();
		}

		$data = [];

		$data['waiting'] = get_db( 'event_queue' )->count( [
			'step_id'    => $this->get_id(),
			'event_type' => Event::BROADCAST,
			'status'     => Event::WAITING,
//			'email_id'   => $email_id
		] );

		$data['id'] = $this->get_id();
		$data[ 'email_id' ] = $email_id ;

		if ( $this->is_sent() ) {

			$data['sent'] = get_db( 'events' )->count( [
				'step_id'    => $this->get_id(),
				'event_type' => Event::BROADCAST,
				'status'     => Event::COMPLETE,
//				'email_id'   => $email_id
			] );

			if ( ! $this->is_sms() ) {
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
				$data['unsubscribed']       = get_db( 'activity' )->count( [
					'funnel_id'     => $this->get_funnel_id(),
					'step_id'       => $this->get_id(),
					'activity_type' => Activity::UNSUBSCRIBED
				] );
				$data['click_through_rate'] = percentage( $data['clicked'], $data['opened'] );
				$data['unopened']           = $data['sent'] - $data['opened'];
				$data['opened_not_clicked'] = $data['opened'] - $data['clicked'];

			}

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
		return [
			'data'   => $this->data,
			'title'  => $this->get_title(),
			'report' => $this->get_report_data(),
			'user' => get_userdata($this->get_scheduled_by_id() )
		];
	}


}