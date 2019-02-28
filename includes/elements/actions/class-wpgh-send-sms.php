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

    public function settings( $step )
    {

        $mesg = $step->get_meta( 'text_message' );
        if ( ! $mesg ) {
            $mesg = '';
        }
        ?>
        <?php if ( ! wpgh_get_option( 'gh_email_token', false ) ): ?>
        <p style="margin-left: 10px;" class="description">
            <?php _e( 'SMS uses the <a target="_blank" href="https://www.groundhogg.io/downloads/email-credits/">Groundhogg Sending Service</a> & requires that you have setup your <a target="_blank" href="https://www.groundhogg.io/downloads/email-credits/">Groundhogg account</a>.', 'groundhogg' ); ?>
        </p>
        <?php endif; ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Text Message:', 'groundhogg' ); ?>
                    <p>
                        <?php WPGH()->replacements->show_replacements_button(); ?>
                    </p>
                </th>
                <?php $args = array(
                    'id'    => $step->prefix( 'text_message' ),
                    'name'  => $step->prefix( 'text_message' ),
                    'value' => $mesg,
                    'cols'  => 64,
                    'rows'  => 4,
                    'attributes' => sprintf(' maxlength="%d"', self::MAX_LENGTH )
                ); ?>
                <td>
                    <?php echo WPGH()->html->textarea( $args ) ?>
                    <p class="description">
                        <?php _e( 'Use any valid replacement codes in your text message. Do not use html! Limit 280 characters.', 'groundhogg' ); ?>
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

        if ( isset( $_POST[ $step->prefix( 'text_message' ) ] ) ){
            $note_text = substr( sanitize_textarea_field( wp_strip_all_tags( stripslashes( $_POST[ $step->prefix( 'text_message' ) ] ) ) ), 0, self::MAX_LENGTH );
            $step->update_meta( 'text_message', $note_text );
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
        $message = $event->step->get_meta( 'text_message' );
        $result = WPGH()->service_manager->send_sms( $contact, $message );

        if ( is_wp_error( $result ) || ! $result ){
            return false;
        }

        return true;
    }

}