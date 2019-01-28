<?php
/**
 * Event
 *
 * This is an event from the event queue. it contains info about the step, broadcast, funnel, contact etc... that is necessary for processing the event.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Event
{

    /**
     * The event's ID
     *
     * @var int
     */
    public $ID;

    /**
     * The associated step
     *
     * @var object|WPGH_Step|WPGH_Broadcast
     */
    public $step;

    /**
     * The associated funnel
     *
     * @var int
     */
    public $funnel_id;

    /**
     * The associated contact
     *
     * @var object|WPGH_Contact
     */
    public $contact;

    /**
     * The time to run the event
     *
     * @var int
     */
    public $time;

    /**
     * The current event status
     *
     * @var string
     */
    public $status;

    /**
     * @param $id int ID of the event
     */
    public function __construct( $id )
    {

        $this->ID = intval( $id );

        $event = WPGH()->events->get( $this->ID );

        if ( ! is_object( $event ) ) {
            return false;
        }

        $this->setup_event( $event );

    }

    /**
     * Setup the event based on the DB object
     *
     * @param $event
     */
    private function setup_event( $event )
    {

        $this->time = intval( $event->time );
        $this->status = $event->status;

        //do I NEED the funnel accessible as an object?
        /* No, no I don't */
        $this->funnel_id = intval( $event->funnel_id );

        //definitely need this...
        $this->contact = new WPGH_Contact( $event->contact_id );

        if ( $this->is_broadcast_event() ){

            /* Special handling for the broadcast event type */
            $this->step = new WPGH_Broadcast( $event->step_id );

        } else {

            /*regular step event handling */
            $this->step = new WPGH_Step( $event->step_id );

        }

    }

    /**
     * Return whether the event is a broadcast event
     */
    public function is_broadcast_event()
    {

        return $this->funnel_id === WPGH_BROADCAST;

    }

    /**
     * Run the event
     *
     * Wrapper function for the step call in WPGH_Step
     */
    public function run()
    {

        if ( ! $this->is_waiting() || $this->has_run() || $this->has_similar() || ! $this->is_time() || ! $this->step->can_run()  )
            return false;

        do_action( 'wpgh_event_run_before', $this );

        $result = $this->step->run( $this->contact, $this );

        if ( ! $result ){

            /* handle event failure */
            do_action( 'wpgh_event_run_failed', $this );

            $this->fail();

            return false;

        } else {

            $this->complete();

        }

        /* special handling for the broadcast event. Make sure it's status is update to sent... */
        if ( $this->is_broadcast_event() && $this->step->status !== 'sent' ){
           $this->step->update( array( 'status' => 'sent' ) );
        }

        do_action( 'wpgh_event_run_after', $this );

        return true;
    }

    /**
     * Due to the nature of WP and cron, let's DOUBLE check that at the time of running this event has not been run by another instance of the queue.
     *
     * @return bool whether the event has run or not
     */
    public function has_run()
    {
        $event = WPGH()->events->get( $this->ID );

        if ( $event->status === 'complete' ){
            return true;
        }

        return false;

    }

    /**
     * Check if an event similar to this one has run in the last 5 minutes.
     * If it has, SKIP IT!!!!!!!!!!!!!!
     *
     * @return bool
     */
    public function has_similar(){

        $similar_events = WPGH()->events->get_events(
            array(
                'start'         => $this->time - ( 5 * 60 ),
                'end'           => $this->time + ( 5 * 60 ),
                'funnel_id'     => $this->funnel_id,
                'step_id'       => $this->step->ID,
                'contact_id'    => $this->contact->ID,
                'status'        => 'complete'
            )
        );

        if ( $similar_events && count( $similar_events ) > 0 ){

            //double check this event...
            $event = WPGH()->events->get( $this->ID );

            if ( $event->status !== 'complete' ){
                $this->skip();
            }

            return true;
        }

        return false;

    }

    /**
     * Return whether this event is in the appropriate time range to be executed
     *
     * @return bool
     */
    public function is_time()
    {

        return $this->time <= time();

    }

    /**
     * Is the current status 'waiting'
     *
     * @return bool;
     */
    public function is_waiting()
    {
        return $this->status === 'waiting';
    }

    /**
     * Reset the status to waiting so that it may be re-enqueud
     */
    public function queue()
    {
        return $this->update( array(
            'status' => 'waiting'
        ) );
    }

    /**
     * Mark the event as canceled
     */
    public function cancel()
    {
        return $this->update( array(
            'status' => 'cancelled'
        ) );
    }

    public function fail()
    {
        return $this->update( array(
            'status' => 'failed'
        ) );
    }

    /**
     * Mark the event as skipped
     */
    public function skip()
    {
        return $this->update( array(
            'status' => 'skipped'
        ) );
    }

    /**
     * Mark the event as complete
     */
    public function complete()
    {
        return $this->update( array(
            'status' => 'complete',
        ) );
    }

    /**
     * Update the event
     *
     * @param $args array of new info
     *
     * @return bool
     */
    public function update( $args )
    {

        return WPGH()->events->update( $this->ID, $args );

    }

}