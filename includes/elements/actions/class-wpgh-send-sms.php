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

    public function __construct()
    {
        # Give your action a custom identifier. NOTE: Your identifier must be less than 20 characters in length.
        $this->type = 'send_sms';

        # You need to define that this is in fact a action.
        $this->group = 'action';

        # This is the name of the benchmark as seen from the funnel builder panel.
        $this->name = __('Send SMS');

        # Define a url to the image you'd like to use as an Icon. Square images are reccomended.
        $this->icon = 'send-email.png';

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
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <?php echo esc_html__( 'Text Message:', 'groundhogg' ); ?>
                </th>
                <?php $args = array(
                    'id'    => $step->prefix( 'text_message' ),
                    'name'  => $step->prefix( 'text_message' ),
                    'value' => $mesg,
                    'cols'  => 64,
                    'rows'  => 4
                ); ?>
                <td>
                    <?php echo WPGH()->html->textarea( $args ) ?>
                    <p class="description">
                        <?php _e( 'Use any valid replacement codes', 'groundhogg' ); ?>
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

            $note_text = sanitize_textarea_field(  $_POST[ $step->prefix( 'text_message' ) ] );

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

        if ( $contact->is_marketable() ){

            //send to groundhogg

            $phone = $contact->get_meta( 'primary_phone' );
            $message = $event->step->get_meta( 'text_message' );

            /* implement the rest */
            $domain = parse_url( site_url(), PHP_URL_HOST );

            $data = array(
                'number' => $phone,
                'message' => WPGH()->replacements->process( $message, $contact->ID ),
                'sender' => wpgh_get_option( 'gh_business_name' ),
                'gh_key' => wpgh_get_option( 'gh_email_token' ),
                'domain' => $domain,//$domain,
                'ip' => $contact->get_meta( 'ip_address' )
            );

            $url = 'https://www.groundhogg.io';
            $req = array(
                'send_sns_sms' => 'send_sms',
                'data' => $data,
            );

            $request    = wp_remote_post( $url, array( 'body' => $req ) );
            $result     = wp_remote_retrieve_body( $request );
            $result     = json_decode( $result );

            if ( $result->status === 'failed' ){
                /* mail failed */

                do_action( 'wpgh_sms_failed' ,new WP_Error( 'API_SMS_FAILED', $result->message ) );
                $contact->add_note( $result->message );
                return false;

            }

            return true;
        }
        return false;

    }

}