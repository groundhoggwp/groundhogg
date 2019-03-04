<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Broadcast implements WPGH_Event_Process
{

    /**
     * The ID of the broadcast
     *
     * @var int
     */
    public $ID;

    /**
     * The email the broadcast is supposed to send
     *
     * @var WPGH_Email|object
     */
    public $email;

    /**
     * the time when the broadcast is to be sent
     * @var int
     */
    public $send_time;

    /**
     * the ID of the person who scheduled the broadcast
     *
     * @var int
     */
    public $scheduled_by;

    /**
     * A list of tag IDs that the broadcast is being sent to
     *
     * @var array
     */
    public $tags;

    /**
     * The date created
     *
     * @var string
     */
    public $date_scheduled;

    /**
     * The current status of the broadcast...
     *
     * @var string
     */
    public $status;

    /**
     * WPGH_Broadcast constructor.
     *
     * @param $id int the ID of the broadcast record
     */
    public function __construct( $id )
    {

        $this->ID = intval( $id );

        $broadcast = WPGH()->broadcasts->get_broadcast( $id );

        $this->setup_broadcast( $broadcast );

    }

    /**
     * Setup the properties...
     *
     * @param $broadcast object
     */
    public function setup_broadcast( $broadcast )
    {

        $this->email        = new WPGH_Email( $broadcast->email_id );
        $this->scheduled_by = intval( $broadcast->scheduled_by );
        $this->send_time    = intval( $broadcast->send_time );
        $this->tags         = maybe_unserialize( $broadcast->tags );
        $this->status       = $broadcast->status;
        $this->date_scheduled = $broadcast->date_scheduled;

    }

    /**
     * cancel the broadcast
     */
    public function cancel()
    {
        $this->update( array( 'status' => 'cancelled' ) );

        WPGH()->events->mass_update(
            array(
                'status' => 'cancelled'
            ),
            array(
                'step_id'   => $this->ID,
                'funnel_id' => WPGH_BROADCAST
            )
        );
    }

    /**
     * Update info about the broadcast
     *
     * @param $args array of info
     * @return bool
     */
    public function update( $args )
    {
        $result = WPGH()->broadcasts->update( $this->ID, $args );

        if ( $result ){
            $broadcast = WPGH()->broadcasts->get( $this->ID );

            $this->setup_broadcast( $broadcast );
        }

        return $result;
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

        do_action( 'wpgh_send_broadcast_email_before', $this, $contact, $event );

        $result = $this->email->send( $contact, $event );

        do_action( 'wpgh_send_broadcast_email_after', $this, $contact, $event );

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