<?php
/**
 * SMS Notification
 *
 * This is a simple class that allows for manually sent sms to be added to the event queue rather than running right away.
 * The reason for this is so that an event can be created that will allow for tracking.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_SMS_Notification implements WPGH_Event_Process
{

    public $ID;
    /**
     * The sms for the notification
     *
     * @var WPGH_SMS|object
     */
    public $sms;

    /**
     * WPGH_Broadcast constructor.
     *
     * @param $id int the ID of the sms to send
     */
    public function __construct( $id )
    {
        $this->ID = intval( $id );
        $this->sms = new WPGH_SMS( intval( $id ) );
    }

    /**
     * Send the associated sms to the given contact
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool, whether the sms sent or not.
     */
    public function run( $contact, $event = null )
    {
        do_action( 'groundhogg/sms_notification/run/before', $this, $contact, $event );
        $result = $this->sms->send( $contact, $event );
        do_action( 'groundhogg/sms_notification/run/after', $this, $contact, $event );
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