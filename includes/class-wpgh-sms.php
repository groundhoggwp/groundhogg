<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-04
 * Time: 9:15 AM
 */

Class WPGH_SMS
{

    /**
     * @var int
     */
    public $ID;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $message;

    /**
     * @var WPGH_Contact
     */
    private $contact;

    /**
     * @var WPGH_Event
     */
    private $event;

    /**
     * WPGH_SMS constructor.
     * @param $id int
     */
    public function __construct( $id )
    {

        $sms = WPGH()->sms->get( intval( $id ) );

        if ( $sms ){
            $this->title = $sms->title;
            $this->message = $sms->message;
        }

    }

    /**
     * Whether the sms is valid or not
     *
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->message;
    }

    public function get_message()
    {
        return WPGH()->replacements->process( wp_strip_all_tags( stripslashes( $this->message ) ), $this->contact->ID );
    }

    /**
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|WP_Error
     */
    public function send( $contact, $event = null )
    {

        $this->contact = $contact;

        if ( is_numeric( $contact ) ) {

            /* catch if contact ID given rather than WPGH_Contact */
            $contact = new WPGH_Contact( $contact );

        }

        if ( ! is_object( $contact )  )
            return new WP_Error( 'BAD_CONTACT', __( 'No contact provided...' ) );

        $this->contact = $contact;

        /* we got an event so all is well */
        if ( is_object( $event ) ){
            $this->event  = $event;

        } else if ( is_object( WPGH()->event_queue->cur_event ) ) {

            /* We didn't get an event, but it looks like one is happening so we'll get it from global scope */
            $this->event = WPGH()->event_queue->cur_event;

        } else {

            /* set a default basic event */
            $this->event = new stdClass();
            $this->event->ID = 0;

        }

        return WPGH()->service_manager->send_sms( $contact, $this->get_message() );

    }

}