<?php
namespace Groundhogg;

use Groundhogg\DB\SMS as SMS_DB;
use WP_Error;

Class SMS extends Base_Object
{
    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var Event
     */
    private $event;


    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return SMS_DB
     */
    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'sms' );
    }

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    public function get_id()
    {
        return absint( $this->ID );
    }

    public function get_message()
    {
        return $this->message;
    }

    public function get_title()
    {
        return $this->title;
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'sms';
    }

    public function get_event()
    {
        return $this->event;
    }

    public function get_contact()
    {
        return $this->contact;
    }

    /**
     * @return mixed
     */
    public function get_merged_message()
    {
        return Plugin::$instance->replacements->process( wp_strip_all_tags( wp_unslash( $this->get_message() ) ), $this->contact->ID );
    }

    /**
     * @param $contact_id_or_email Contact|int|string
     * @param $event Event
     *
     * @return bool|WP_Error
     */
    public function send( $contact_id_or_email, $event = null )
    {

        $contact = $contact_id_or_email instanceof Contact ? $contact_id_or_email : Plugin::$instance->utils->get_contact( $contact_id_or_email );

        if ( ! $contact ){
            return new WP_Error('no_recipient', __( 'No valid recipient was provided.' ) );
        }

        $this->contact = $contact;

        /* we got an event so all is well */
        if ( is_object( $event ) ){
            $this->event  = $event;
        }

        /**
         * Allow other services to hook into this process.
         */
        if ( wpgh_using_ghss_for_sms() ){

            $sent = WPGH()->service_manager->send_sms( $contact, $this->get_message() );

        } else {
            $sent = apply_filters( 'groundhogg/sms/send_custom', true, $contact, $this->get_message(), $this );
        }

        do_action( 'groundhogg/sms/sent', $sent, $this );

        return $sent;
    }
}