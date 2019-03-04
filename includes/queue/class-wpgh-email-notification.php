<?php
/**
 * Email Notification
 *
 * This is a simple class that allows for manually sent emails to be added to the event queque rather than running right away.
 * The reason for this is so that an event can be created that will allow for tracking.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Email_Notification implements WPGH_Event_Process
{

    public $ID;

    /**
     * The email for the notification
     *
     * @var WPGH_Email|object
     */
    public $email;

    /**
     * WPGH_Broadcast constructor.
     *
     * @param $id int the ID of the email to send
     */
    public function __construct( $id )
    {
        $this->ID = intval( $id );
        $this->email = new WPGH_Email( intval( $id ) );
    }

    /**
     * Send the associated email to the given contact
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool, whether the email sent or not.
     */
    public function run( $contact, $event = null )
    {
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
    public function can_run()
    {
        return true;
    }

}