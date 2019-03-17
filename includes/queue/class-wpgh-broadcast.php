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
	 * @var WPGH_SMS
	 */
    public $sms;

	/**
	 * @var WPGH_SMS|WPGH_Email
	 */
    public $object;

	/**
	 * @var int
	 */
    public $object_id;

	/**
	 * @var string
	 */
    public $object_type;

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
	 * @return string
	 */
    public function get_type()
    {
    	return $this->object_type;
    }

	/**
	 * Whether the broadcast is sending an sms
	 *
	 * @return bool
	 */
    public function is_sms()
    {
        return $this->object_type === 'sms';
    }

	/**
	 * Whether the broadcast is sending an email
	 *
	 * @return bool
	 */
    public function is_email()
    {
    	return $this->object_type === 'email';
    }

	/**
	 * Get the column row title for the broadcast.
	 *
	 * @return string
	 */
    public function get_title()
    {

    	if ( ! $this->object->exists() ){
    		return __( '(The associated Email or SMS was deleted.)', 'groundhogg' );
	    }

    	if ( $this->is_sms() ){
    		return $this->sms->title;
	    } else {
    		return $this->email->subject;
	    }

    }


    /**
     * Setup the properties...
     *
     * @param $broadcast object
     */
    public function setup_broadcast( $broadcast )
    {

    	$this->object_type = isset( $broadcast->object_type ) && $broadcast->object_type === 'sms' ? 'sms' : 'email';
    	$this->object_id = intval( $broadcast->object_id );

    	if ( $this->object_type === 'email' ){
		    $this->email = new WPGH_Email( $this->object_id );
		    $this->object = $this->email;
	    } else {
		    $this->sms = new WPGH_SMS( $this->object_id );
		    $this->object = $this->sms;
	    }

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

        if ( WPGH()->events->mass_update(
            array(
                'status' => 'cancelled'
            ),
            array(
                'step_id'   => $this->ID,
                'funnel_id' => WPGH_BROADCAST
            )
        ) ){
            $this->update( array( 'status' => 'cancelled' ) );
        }

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
     * Send the associated object to the given contact
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|WP_Error whether the email sent or not.
     */
    public function run( $contact, $event = null )
    {

	    do_action( "groundhogg/broadcast/{$this->object_type}/before", $this, $contact, $event );
        $result = $this->object->send( $contact, $event );
        do_action( "groundhogg/broadcast/{$this->object_type}/after", $this, $contact, $event );

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