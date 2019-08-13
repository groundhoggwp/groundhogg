<?php
namespace Groundhogg;

use Groundhogg\DB\DB;
use Groundhogg\DB\Events;
use Groundhogg\Queue\Email_Notification;
use Groundhogg\Queue\SMS_Notification;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) exit;

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
class Event extends Base_Object
{

    /** @var string Event statuses */
    const COMPLETE = 'complete';
    const CANCELLED = 'canceled';
    const SKIPPED = 'skipped';
    const WAITING = 'waiting';
    const FAILED = 'failed';
    const IN_PROGRESS = 'in_progress';
    const PAUSED = 'paused';

    /**
     * Supported Event Types
     */
    const FUNNEL = 1;
    const BROADCAST = 2;
    const EMAIL_NOTIFICATION = 3;
    const SMS_NOTIFICATION = 4;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @var Step
     */
    protected $step;

    /**
     * @var Funnel
     */
    protected $funnel;

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return Events
     */
    protected function get_db()
    {
        return Plugin::instance()->dbs->get_db('events' );
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'event';
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return absint( $this->ID );
    }

    /**
     * @return int
     */
    public function get_time()
    {
        return absint($this->time);
    }

    /**
     * @return int
     */
    public function get_event_type()
    {
        return absint($this->event_type);
    }

    /**
     * @return string
     */
    public function get_status()
    {
        return $this->status;
    }

	/**
	 * @return string
	 */
    public function get_claim()
    {
    	return $this->claim;
    }

    /**
     * @return int
     */
    public function get_funnel_id()
    {
        return absint( $this->funnel_id );
    }

    /**
     * @return Funnel
     */
    public function get_funnel()
    {
        return $this->funnel;
    }

    /**
     * @return int
     */
    public function get_contact_id()
    {
        return absint( $this->contact_id );
    }

    /**
     * @return String
     */
    public function get_failure_reason()
    {
        return $this->failure_reason;
    }

    /**
     * @return Contact
     */
    public function get_contact()
    {
        return $this->contact;
    }

    /**
     * @return int
     */
    public function get_step_id()
    {
        return absint( $this->step_id );
    }

    /**
     * @return Step|Email_Notification|SMS_Notification|Broadcast
     */
    public function get_step()
    {
        return $this->step;
    }

    /**
     * Get the email of an event.
     *
     * @return bool|Email
     */
    public function get_email()
    {
        switch ( $this->get_event_type() ){
            case Event::FUNNEL;
                return new Email( absint( $this->get_step()->get_meta( 'email_id' ) ) );
                break;
            case Event::EMAIL_NOTIFICATION;
                return new Email( $this->get_step()->get_id() );
                break;
            case Event::BROADCAST;
                return new Email( $this->get_step()->get_object_id() );
                break;
        }

        return false;
    }

    /**
     * @return string
     */
    public function get_error_code()
    {
        return $this->error_code;
    }

    /**
     * @return string
     */
    public function get_error_message()
    {
        return $this->error_message;
    }


    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {

        $this->contact = Plugin::$instance->utils->get_contact( $this->get_contact_id() );

        switch ( $this->get_event_type() ){
            case self::FUNNEL:
                $this->step = Plugin::$instance->utils->get_step( $this->get_step_id() );
                break;
            case self::EMAIL_NOTIFICATION:
                $this->step = new Email_Notification( $this->get_step_id() );
                break;
            case self::SMS_NOTIFICATION:
                $this->step = new SMS_Notification( $this->get_step_id() );
                break;
            case self::BROADCAST:
                $this->step = new Broadcast( $this->get_step_id() );
                break;
	        default:
	        	$class = apply_filters( 'groundhosgg/event/post_setup/step_class', false );

	        	if ( class_exists( $class ) ){
                    $this->step = new $class( $this->get_step_id() );
                }

	        	break;
        }

        do_action( 'groundhogg/event/post_setup', $this );
    }

    /**
     * Return whether the event is a funnel (automated) event.
     *
     * @since 1.2
     * @return bool
     */
    public function is_funnel_event()
    {
        return $this->get_event_type() === self::FUNNEL;
    }

    /**
     * Return whether the event is a broadcast event
     *
     * @return bool
     */
    public function is_broadcast_event()
    {
        return $this->get_event_type() === self::BROADCAST;
    }

    /**
     * @since 1.2
     * @return bool
     */
    public function is_sms_notification_event()
    {
        return $this->get_event_type() === self::EMAIL_NOTIFICATION;
    }

    /**
     * @since 1.2
     * @return bool
     */
    public function is_email_notification_event()
    {
        return $this->get_event_type() === self::SMS_NOTIFICATION;
    }

    /**
     * @return string
     */
    public function get_step_title()
    {
        if ( $this->get_step() ){
            return $this->get_step()->get_step_title(); //todo
        }

        return __( 'Unknown', 'groundhogg' );

    }


    /**
     * @return string
     */
    public function get_funnel_title()
    {
        if ( $this->get_step() ){
            return $this->get_step()->get_funnel_title();
        }

        return __( 'Unknown', 'groundhogg' );
    }

    /**
     * Run the event
     *
     * Wrapper function for the step call in WPGH_Step
     */
    public function run()
    {
    	if ( ! $this->get_claim() ){
    		return false;
	    }

        do_action('groundhogg/event/run/before', $this);

        $this->in_progress();

        if ( ! $this->get_step() ){
            return false;
        }

        $result = $this->get_step()->run( $this->get_contact(), $this );

        // Soft fail when return false
        if ( ! $result ){
            $this->skip();
            return false;
        }

        // Hard fail when WP Error
        if ( is_wp_error( $result ) ) {
            /* handle event failure */
            $this->add_error( $result );

            $this->fail();

            return false;

        }

        $this->complete();

        do_action('groundhogg/event/run/after', $this );

        return true;
    }

    /**
     * Due to the nature of WP and cron, let's DOUBLE check that at the time of running this event has not been run by another instance of the queue.
     *
     * @return bool whether the event has run or not
     */
    public function has_run()
    {
        return $this->get_status() !== self::WAITING;
    }

    /**
     * Return whether this event is in the appropriate time range to be executed
     *
     * @return bool
     */
    public function is_time()
    {
        return $this->get_time() <= time();
    }

    /**
     * Is the current status 'waiting'
     *
     * @return bool;
     */
    public function is_waiting()
    {
        return $this->get_status() === self::WAITING;
    }

    /**
     * Reset the status to waiting so that it may be re-enqueud
     */
    public function queue()
    {
        do_action( 'groundhogg/event/queued', $this );

        return $this->update( [
            'status' => self::WAITING
        ] );
    }

    /**
     * Mark the event as canceled
     */
    public function cancel()
    {
        do_action( 'groundhogg/event/cancelled', $this );

        return $this->update( [
            'status' => self::CANCELLED,
            'time'   => time(),
        ] );
    }

    /**
     * @return bool
     */
    public function fail()
    {
        $args = [
            'status' => self::FAILED
        ];

        /**
         * Report a failure reason for better debugging.
         *
         * @since 1.2
         */
        if ( $this->has_errors() ){
            $args[ 'error_code' ] = $this->get_last_error()->get_error_code();
            $args[ 'error_message' ] = $this->get_last_error()->get_error_message();
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

        return $this->update( [
            'status' => self::SKIPPED,
            'time'   => time(),
        ] );
    }

    /**
     * Mark the event as skipped
     */
    public function in_progress()
    {
        do_action( 'groundhogg/event/in_progress', $this );

        return $this->update( [
            'status' => self::IN_PROGRESS
        ] );
    }

    /**
     * Mark the event as skipped
     */
    public function pause()
    {
        do_action( 'groundhogg/event/pause', $this );

        return $this->update( [
            'status' => self::PAUSED
        ] );
    }

    /**
     * Mark the event as complete
     */
    public function complete()
    {

        do_action( 'groundhogg/event/complete', $this );

        return $this->update( [
            'status' => self::COMPLETE,
            'error_code' => '',
            'error_message' => '',
        ] );
    }
}