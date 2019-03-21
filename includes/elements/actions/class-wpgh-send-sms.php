<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/19/2018
 * Time: 11:43 AM
 */

//$contacr->get_meta( 'primary_phone' );
class WPGH_Send_SMS extends WPGH_Funnel_Step
{

    const MAX_LENGTH = 280;

    public function __construct()
    {
        # Give your action a custom identifier. NOTE: Your identifier must be less than 20 characters in length.
        $this->type = 'send_sms';

        # You need to define that this is in fact a action.
        $this->group = 'action';

        # This is the name of the benchmark as seen from the funnel builder panel.
        $this->name         = _x( 'Send SMS', 'element_name', 'groundhogg' );
        $this->description  = _x( 'Send a one way text message to the contact.', 'element_description', 'groundhogg' );

        # Define a url to the image you'd like to use as an Icon. Square images are reccomended.
        $this->icon = 'send-sms.png';

        # you MUST call the parent __construct method as well.
        parent::__construct();
    }

    /**
     * @param WPGH_Step $step
     */
    public function settings( $step )
    {

        $mesg = $step->get_meta( 'sms_id' );

        /* Check to see if we are sending sms with the GH System. If another system is active then do not display the message. */
        if ( ! wpgh_ghss_is_active() && wpgh_using_ghss_for_sms() ): ?>
        <p style="margin-left: 10px;" class="description">
            <?php _e( 'SMS uses the <a target="_blank" href="https://www.groundhogg.io/downloads/email-credits/">Groundhogg Sending Service</a> & requires that you have setup your <a target="_blank" href="https://www.groundhogg.io/downloads/email-credits/">Groundhogg account</a>.', 'groundhogg' ); ?>
        </p>
        <?php endif; ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Select Message:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'id'    => $step->prefix( 'sms_id' ),
                    'name'  => $step->prefix( 'sms_id' ),
                    'selected' => [ intval( $mesg ) ],
                ); ?>
                <td>
                    <?php echo WPGH()->html->dropdown_sms( $args ) ?>
                    <p>
                        <?php

                        echo WPGH()->html->checkbox( array(
                            'name'  => $step->prefix( 'skip_if_no_phone' ),
                            'id'    => $step->prefix( 'skip_if_no_phone' ),
                            'value' => 1,
                            'label' => __( 'Skip phone number unavailable.', 'groundhogg' ),
                            'checked' => $step->get_meta( 'skip_if_no_phone' ),
                        ) );

                        ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        if ( isset( $_POST[ $step->prefix( 'sms_id' ) ] ) ){
            $step->update_meta( 'sms_id', intval( $_POST[ $step->prefix( 'sms_id' ) ] ) );
        }

        if ( isset( $_POST[ $step->prefix( 'skip_if_no_phone' ) ] ) ){
            $step->update_meta( 'skip_if_no_phone', true );
        } else {
            $step->delete_meta( 'skip_if_no_phone' );
        }

        if ( ! wpgh_ghss_is_active() && wpgh_using_ghss_for_sms() ){
            WPGH()->notices->add( new WP_Error( 'NO_TOKEN', __( 'Your SMS steps will not work until you active the Groundhogg Sending Service.', 'groundhogg' ) ) );
        }

    }

    /**
     * Process the apply note step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool
     */
    public function run( $contact, $event )
    {
        $sms_id = $event->step->get_meta( 'sms_id' );
        $skip_if_no_phone = $event->step->get_meta( 'skip_if_no_phone' );

        $sms = new WPGH_SMS( intval( $sms_id ) );

        if ( ! $sms->exists() || ! $contact->is_marketable() ){
            return false;
        }

        /* Skip if the contact does not have a phone number. */
        if ( ! $contact->primary_phone && $skip_if_no_phone ){
            return true;
        }

        $result = $sms->send( $contact, $event );

        if ( is_wp_error( $result ) || ! $result ){
            return $result;
        }

        return true;
    }

    /**
     * Export the sms content
     * 
     * @param array $args
     * @param WPGH_Step $step
     * @return array
     */
    public function export($args, $step)
    {
        $sms_id = intval( $step->get_meta( 'sms_id' ) );

        $sms = new WPGH_SMS( $sms_id );

        if ( ! $sms->exists() )
            return $args;

        $args[ 'title'] = $sms->title;
        $args[ 'message' ] = $sms->message;

        return $args;    
    }

    /**
     * Import SMS content
     *
     * @param array $args
     * @param WPGH_Step $step
     */
    public function import($args, $step)
    {

        if ( ! gisset_not_empty( $args, 'title' ) || ! gisset_not_empty( $args, 'message' ) ){
            return;
        }

        $sms_id = WPGH()->sms->add( array(
            'title'   => $args['title'],
            'message' => $args['message'],
            'author'  => get_current_user_id()
        ) );

        if ( $sms_id ){
            $step->update_meta( 'sms_id', $sms_id );
        }
    }

}