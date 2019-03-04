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
     * @var object|WPGH_Step|WPGH_Broadcast|WPGH_Email_Notification|WPGH_SMS_Notification
     *
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
     * The type of event
     *
     * @var int|bool
     */
    public $type = false;

    /**
     * @param $id int ID of the event
     */
    public function __construct($id)
    {

        $this->ID = intval($id);

        $event = WPGH()->events->get($this->ID);

        if (!is_object($event)) {
            return false;
        }

        $this->setup_event($event);

    }

    /**
     * Setup the event based on the DB object
     *
     * @param $event
     */
    private function setup_event($event)
    {

        $this->time = intval($event->time);
        $this->status = $event->status;

        if ($event->event_type) {
            $this->type = intval($event->event_type);
        }

        $this->funnel_id = intval($event->funnel_id);
        //definitely need this...
        $this->contact = new WPGH_Contact($event->contact_id);

        /**
         * Check for an event type if POST 1.2
         *
         * @since 1.2
         */
        if ($this->type) {
            switch ($this->type) {
                default:
                case WPGH_FUNNEL_EVENT:
                    $this->step = new WPGH_Step($event->step_id);
                    break;
                case WPGH_BROADCAST_EVENT:
                    $this->step = new WPGH_Broadcast($event->step_id);
                    break;
                case WPGH_EMAIL_NOTIFICATION_EVENT:
                    $this->step = new WPGH_Email_Notification($event->step_id);
                    break;
                case WPGH_SMS_NOTIFICATION_EVENT:
                    $this->step = new WPGH_SMS_Notification( $event->step_id );
                    break;

            }
        } else {
            if ($this->is_broadcast_event()) {

                /* Special handling for the broadcast event type */
                $this->step = new WPGH_Broadcast($event->step_id);

            } else {

                /*regular step event handling */
                $this->step = new WPGH_Step($event->step_id);

            }
        }

    }

    /**
     * Get the type of event
     *
     * @since 1.2
     * @return int the type opf event.
     */
    public function get_event_type()
    {
        if ($this->type) {
            return $this->type;
        }

        if ($this->is_broadcast_event()) {
            return WPGH_BROADCAST_EVENT;
        }

        return WPGH_FUNNEL_EVENT;
    }

    /**
     * Return whether the event is a broadcast event
     *
     * @return bool
     */
    public function is_broadcast_event()
    {
        return $this->funnel_id === WPGH_BROADCAST;
    }

    /**
     * Return whether the event is a funnel (automated) event.
     *
     * @since 1.2
     * @return bool
     */
    public function is_funnel_event()
    {
        if ($this->type) {
            return $this->type === WPGH_FUNNEL_EVENT;
        }

        return $this->funnel_id !== WPGH_BROADCAST;
    }

    /**
     * Run the event
     *
     * Wrapper function for the step call in WPGH_Step
     */
    public function run()
    {

        if (!$this->is_waiting() || $this->has_run() || $this->has_similar() || !$this->is_time() || !$this->step->can_run())
            return false;

        do_action('wpgh_event_run_before', $this);

        $result = $this->step->run($this->contact, $this);

        if ( ! $result || is_wp_error( $result ) ) {
            /* handle event failure */
            do_action('wpgh_event_run_failed', $this, $result );
            $this->fail();
            return false;
        } else {
            $this->complete();
        }

        /* special handling for the broadcast event. Make sure it's status is updated to sent... */
        if ($this->is_broadcast_event() && $this->step->status !== 'sent') {
            $this->step->update(array('status' => 'sent'));
        }

        do_action('wpgh_event_run_after', $this);

        return true;
    }

    /**
     * Due to the nature of WP and cron, let's DOUBLE check that at the time of running this event has not been run by another instance of the queue.
     *
     * @return bool whether the event has run or not
     */
    public function has_run()
    {
        $event = WPGH()->events->get($this->ID);

        if ($event->status === 'complete') {
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
    public function has_similar()
    {

        $similar_events = WPGH()->events->get_events(
            array(
                'start' => $this->time - (5 * 60),
                'end' => $this->time + (5 * 60),
                'funnel_id' => $this->funnel_id,
                'step_id' => $this->step->ID,
                'contact_id' => $this->contact->ID,
                'status' => 'complete'
            )
        );

        if ($similar_events && count($similar_events) > 0) {

            //double check this event...
            $event = WPGH()->events->get($this->ID);

            if ($event->status !== 'complete') {
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
        return $this->update(array(
            'status' => 'waiting'
        ));
    }

    /**
     * Mark the event as canceled
     */
    public function cancel()
    {
        return $this->update(array(
            'status' => 'cancelled'
        ));
    }

    public function fail()
    {
        return $this->update(array(
            'status' => 'failed'
        ));
    }

    /**
     * Mark the event as skipped
     */
    public function skip()
    {
        return $this->update(array(
            'status' => 'skipped'
        ));
    }

    /**
     * Mark the event as complete
     */
    public function complete()
    {
        return $this->update(array(
            'status' => 'complete',
        ));
    }

    /**
     * Update the event
     *
     * @param $args array of new info
     *
     * @return bool
     */
    public function update($args)
    {
        return WPGH()->events->update($this->ID, $args);
    }

}