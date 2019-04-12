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

    /** @var string Event statuses */
    const COMPLETE  = 'complete';
    const CANCELLED = 'canceled';
    const SKIPPED   = 'skipped';
    const WAITING   = 'waiting';
    const FAILED    = 'failed';

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
     * @var WP_Error
     */
    public $error;

    /**
     * @var string
     */
    public $failure_reason;

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
        $this->failure_reason = $event->failure_reason;

        if ($event->event_type) {
            $this->type = intval($event->event_type);
        }

        $this->funnel_id = intval($event->funnel_id);
        //definitely need this...
        $this->contact = wpgh_get_contact($event->contact_id);

        /**
         * Check for an event type if POST 1.2
         *
         * @since 1.2
         */
        if ($this->type) {
            switch ($this->type) {
                default:
                case GROUNDHOGG_FUNNEL_EVENT:
                    $this->step = wpgh_get_funnel_step($event->step_id);
                    break;
                case GROUNDHOGG_BROADCAST_EVENT:
                    $this->step = new WPGH_Broadcast($event->step_id);
                    break;
                case GROUNDHOGG_EMAIL_NOTIFICATION_EVENT:
                    $this->step = new WPGH_Email_Notification($event->step_id);
                    break;
                case GROUNDHOGG_SMS_NOTIFICATION_EVENT:
                    $this->step = new WPGH_SMS_Notification( $event->step_id );
                    break;

            }
        } else {
            if ($this->is_broadcast_event()) {

                /* Special handling for the broadcast event type */
                $this->step = new WPGH_Broadcast($event->step_id);

            } else {

                /*regular step event handling */
                $this->step = wpgh_get_funnel_step($event->step_id);

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
            return GROUNDHOGG_BROADCAST_EVENT;
        }

        return GROUNDHOGG_FUNNEL_EVENT;
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
            return $this->type === GROUNDHOGG_FUNNEL_EVENT;
        }

        return $this->funnel_id !== WPGH_BROADCAST;
    }

    /**
     * @since 1.2
     * @return bool
     */
    public function is_sms_notification_event()
    {
        return $this->type === GROUNDHOGG_EMAIL_NOTIFICATION_EVENT;
    }

    /**
     * @since 1.2
     * @return bool
     */
    public function is_email_notification_event()
    {
        return $this->type === GROUNDHOGG_SMS_NOTIFICATION_EVENT;
    }

    /**
     * @param $error WP_Error
     * @return WP_Error|false;
     */
    public function set_error( $error ){
        if ( is_wp_error( $error ) ){
            return $this->error = $error;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function has_error()
    {
        return is_wp_error( $this->error );
    }

    /**
     * @return string
     */
    public function get_step_title()
    {
        if ($this->type) {
            switch ($this->type) {
                default:
                case GROUNDHOGG_FUNNEL_EVENT:
                    $step_title = $this->step->title;
                    break;
                case GROUNDHOGG_BROADCAST_EVENT:
                    $step_title = $this->step->get_title();
                    break;
                case GROUNDHOGG_EMAIL_NOTIFICATION_EVENT:
                    $step_title = $this->step->email->subject;
                    break;
                case GROUNDHOGG_SMS_NOTIFICATION_EVENT:
                    $step_title = $this->step->sms->title;
                    break;
            }
        } else {
            if ($this->is_broadcast_event()) {
                $step_title = $this->step->email->subject;
            } else {
                $step_title = $this->step->title;
            }
        }

        return $step_title;
    }

    /**
     * @return string
     */
    public function get_funnel_title()
    {
        if ($this->type) {
            switch ($this->type) {
                default:
                case GROUNDHOGG_FUNNEL_EVENT:
                    $funnel = WPGH()->funnels->get( $this->funnel_id );
                    $title = ( $funnel )? $funnel->title : sprintf( '(%s)', _x( 'funnel deleted', 'status', 'groundhogg' ) ) ;
                    break;
                case GROUNDHOGG_BROADCAST_EVENT:
                    $title =  sprintf( __( '%s Broadcast', 'groundhogg' ), ucfirst( $this->step->get_type() ) );
                    break;
                case GROUNDHOGG_EMAIL_NOTIFICATION_EVENT:
                    $title =  __( 'Email Notification', 'groundhogg' );
                    break;
                case GROUNDHOGG_SMS_NOTIFICATION_EVENT:
                    $title =  __( 'SMS Notification', 'groundhogg' );
                    break;

            }
        } else {
            if ($this->is_broadcast_event()) {
                $title =  __( 'Broadcast Email', 'groundhogg' );
            } else {
                $funnel = WPGH()->funnels->get( $this->funnel_id );
                $title = ( $funnel )? $funnel->title : sprintf( '(%s)', _x( 'funnel deleted', 'status', 'groundhogg' ) ) ;
            }
        }

        return $title;
    }


    /**
     * Run the event
     *
     * Wrapper function for the step call in WPGH_Step
     */
    public function run()
    {
        if (!$this->is_waiting() || $this->has_run() || $this->has_similar() || !$this->is_time() )
            return false;

        do_action('wpgh_event_run_before', $this);

        $result = $this->step->run($this->contact, $this);

        // Soft fail when return false
        if ( ! $result ){
            $this->skip();
            return false;
        }

        // Hard fail when WP Error
        if ( is_wp_error( $result ) ) {
            /* handle event failure */
            $this->set_error( $result );

            do_action('wpgh_event_run_failed', $this, $result );

            $this->fail();

            return false;

        }

        $this->complete();

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
        do_action( 'groundhogg/event/queued', $this );

        return $this->update(array(
            'status' => 'waiting'
        ));
    }

    /**
     * Mark the event as canceled
     */
    public function cancel()
    {
        do_action( 'groundhogg/event/cancelled', $this );

        return $this->update(array(
            'status' => 'cancelled'
        ));
    }

    public function fail()
    {
        $args = array(
            'status' => 'failed'
        );

        /**
         * Report a failure reason for better debugging.
         *
         * @since 1.2
         */
        if ( $this->has_error() ){
            $error = sprintf( "%s: %s", $this->error->get_error_code(), $this->error->get_error_message() );
            $args[ 'failure_reason' ] = $error;
        }

        do_action( 'groundhogg/event/failed', $this );

        return $this->update( $args );
    }

    /**
     * Mark the event as skipped
     */
    public function skip()
    {

        do_action( 'groundhogg/event/skipped', $this );

        return $this->update(array(
            'status' => 'skipped'
        ));
    }

    /**
     * Mark the event as complete
     */
    public function complete()
    {

        do_action( 'groundhogg/event/complete', $this );

        return $this->update(array(
            'status' => 'complete',
            'failure_reason' => '',
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

    /**
     * Get the failure reason.
     *
     * @return bool|string
     */
    public function get_failure_reason()
    {
        if ( $this->failure_reason ){
            return $this->failure_reason;
        }

        if ( $this->has_error() ){
            return sprintf( '%s: %s', $this->error->get_error_code(), $this->error->get_error_message() );
        }

        return false;
    }

}