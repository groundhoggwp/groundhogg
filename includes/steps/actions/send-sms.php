<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\isset_not_empty;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

class Send_SMS extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Send SMS', 'action_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'send_sms';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Send a one way text message to the contact.', 'element_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-sms.png';
    }

    /**
     * @param Step $step
     */
    public function settings( $step )
    {

        $this->start_controls_section();

        $this->add_control( 'sms_id', [
            'label'         => __( 'Message:', 'groundhogg' ),
            'type'          => HTML::DROPDOWN_SMS,
            'field'         => [],
            'description'   => __( 'Choose the message you want to send.', 'groundhogg' ),
        ] );

        $this->end_controls_section();

    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'sms_id', absint( $this->get_posted_data( 'sms_id' ) ) );

        if ( Plugin::$instance->sending_service->is_active_for_sms() && ! Plugin::$instance->sending_service->has_api_token() ){
            $this->add_error( new \WP_Error( 'sms_not_sending', __( 'SMS message will not be sent until you active an SMS sender.', 'groundhogg' ) ) );
        }

    }

    /**
     * Process the apply note step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return bool
     */
    public function run( $contact, $event )
    {
        $sms_id = absint( $this->get_setting( 'sms_id' ) );
        $sms = Plugin::$instance->utils->get_sms( $sms_id );

        if ( ! $sms || ! $sms->exists() || ! $contact->is_marketable() ){
            return false;
        }

        $result = $sms->send( $contact, $event );

	    /**
	     * Skip if there is an error in accordance with the $skip param.
	     */
        if ( is_wp_error( $result ) || ! $result ){
            return $result;
        }

        return true;
    }

    /**
     * Export the sms content
     *
     * @param array $args
     * @param Step $step
     * @return array
     */
    public function export($args, $step)
    {
        $sms_id = absint( $this->get_setting( 'sms_id' ) );
        $sms = Plugin::$instance->utils->get_sms( $sms_id );

        if ( !$sms || ! $sms->exists() )
            return $args;

        $args[ 'title'] = $sms->get_title();
        $args[ 'message' ] = $sms->get_message();

        return $args;
    }

    /**
     * Import SMS content
     *
     * @param array $args
     * @param Step $step
     */
    public function import($args, $step)
    {
        if ( ! isset_not_empty( $args, 'title' ) || ! isset_not_empty( $args, 'message' ) ){
            return;
        }

        $sms_id = Plugin::$instance->dbs->get_db( 'sms' )->add( [
            'title'   => $args['title'],
            'message' => $args['message'],
            'author'  => get_current_user_id()
        ] );

        if ( $sms_id ){
            $this->save_setting( 'sms_id', $sms_id );
        }
    }
}