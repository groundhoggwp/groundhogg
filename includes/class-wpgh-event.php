<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-03
 * Time: 10:47 AM
 */

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

        //todo, do I NEED the funnel accessible as an object?
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

        if ( ! $this->is_waiting() || ! $this->is_time() )
            return false;

        do_action( 'wpgh_event_run_before', $this );

        $result = $this->step->run( $this->contact, $this );

        if ( ! $result ){

            /* handle event failure */
            do_action( 'wpgh_event_run_failed', $this );

            $this->skip();

        } else {

            $this->complete();

        }

        do_action( 'wpgh_event_run_after', $this );

        return true;
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
            'status' => 'complete'
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