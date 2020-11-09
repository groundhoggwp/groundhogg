<?php

namespace Groundhogg\Queue;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Event_Process;

/**
 * Email Notification
 *
 * This is a simple class that allows for manually sent emails to be added to the event queque rather than running right away.
 * The reason for this is so that an event can be created that will allow for tracking.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Email_Notification implements Event_Process {

	public $ID;

	/**
	 * The email for the notification
	 *
	 * @var Email|object
	 */
	public $email;

	/**
	 * WPGH_Broadcast constructor.
	 *
	 * @param $id int the ID of the email to send
	 */
	public function __construct( $id ) {
		$this->ID    = absint( $id );
		$this->email = new Email( $this->ID );
	}

	public function get_id() {
		return absint( $this->ID );
	}

	/**
	 * Send the associated email to the given contact
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool, whether the email sent or not.
	 */
	public function run( $contact, $event = null ) {
		do_action( 'groundhogg/email_notification/run/before', $this, $contact, $event );
		$result = $this->email->send( $contact, $event );
		do_action( 'groundhogg/email_notification/run/after', $this, $contact, $event );

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

	public function get_funnel_title() {
		return __( 'Email Notification', 'groundhogg' );
	}

	public function get_step_title() {
		return $this->email->get_title();
	}

	/**
	 * Event callback for the email notification
	 *
	 * @param $event Event
	 *
	 * @return bool
	 */
	public static function event_callback( $event ){
		$notification = new Email_Notification( $event->get_step_id() );
		return $notification->run( $event->get_contact(), $event );
	}
}