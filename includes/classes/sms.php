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

    public function get_author()
    {
        return absint( $this->author );
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
        if ( Plugin::$instance->sending_service->is_active_for_sms() ){
            $sent = $this->send_with_gh();
        } else {
            $sent = apply_filters( 'groundhogg/sms/send_custom', false, $this->get_merged_message(), $this );
        }

        do_action( 'groundhogg/sms/sent', $sent, $this );

        return $sent;
    }

    /**
     * Send SMS message via Groundhogg service
     *
     * @return bool|WP_Error
     */
    public function send_with_gh()
    {

        $contact = $this->get_contact();

        if ( ! $contact->is_marketable() ){
            return new WP_Error( 'non_marketable', __( 'This contact is currently unmarketable.', 'groundhogg' ) );
        }

        // Send to groundhogg
        $phone = $contact->get_phone_number();

        if ( ! $phone ){
            return new WP_Error( 'no_phone', sprintf( __( 'Contact %s has no phone number.', 'groundhogg' ), $contact->get_email() ) );
        }

        $country_code = $contact->get_meta( 'country' );

        if ( ! $country_code ){
            return new WP_Error( 'invalid_country_code', __( 'A country code is required to send SMS.', 'groundhogg' ) );
        }

        $message = sanitize_textarea_field( $this->get_merged_message() );
        $sender_name = sanitize_from_name( Plugin::$instance->settings->get_option( 'business_name', get_bloginfo( 'name' ) ) );

        $data = array(
            'message'       => $message,
            'sender'        => $sender_name,
            'phone_number'  => $phone,
            'country_code'  => $country_code
        );

        $response = Plugin::$instance->sending_service->request( 'sms/send', $data, 'POST' );

        if ( is_wp_error( $response ) ){
            do_action( 'groundhogg/sms/failed', $response );
            return $response;
        }

        return true;

    }
}