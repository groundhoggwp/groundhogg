<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 3:28 PM
 */

class Groundhogg_Service_Manager
{

    /**
     * API endpoint for Email & SMS service domain verification
     */
    const ENDPOINT = 'https://www.groundhogg.io/wp-json/gh/aws/v1/domain';

    /**
     * Maximum length for
     */
    const MAX_LENGTH = 280;

    public function __construct()
    {
        $should_listen = get_transient( 'gh_listen_for_connect' );
        if ( $should_listen && is_admin() ){
        	add_action( 'init', array( $this, 'connect_email_api' ) );
        }

        $should_verify = wpgh_is_option_enabled( 'gh_email_api_check_verify_status' );
        if ( $should_verify ){
        	add_action( 'admin_init', array( $this, 'setup_cron' ) );
        	add_action( 'groundhogg/service/verify_domain', array( $this, 'check_verification_status' ) );
        }

        if ( wpgh_get_option( 'gh_email_api_dns_records', false ) ){
            add_action( 'groundhogg/settings/email/after_settings', array( $this, 'show_dns_in_settings' ) );
        }
    }

	/**
	 * Setup a job to check the domain verification status.
	 */
	public function setup_cron()
	{
		if ( ! wp_next_scheduled( 'groundhogg/service/verify_domain' )  ){
			wp_schedule_event( time(), 'hourly' , 'groundhogg/service/verify_domain' );
		}
	}

    /**
     * Sends a request to Groundhogg.io to add this domain
     * Request returns a text record and a list of DKIM records
     */
    public function connect_email_api()
    {

        if ( ! is_admin()
             || $this->email_api_is_active()
             || ! key_exists( 'action', $_GET )
             || 'connect_to_gh' !== $_GET['action']
             || ! key_exists( 'token',  $_GET )
             || ! current_user_can( 'manage_options' )
        ){
            return;
        }

        $token  = sanitize_text_field( urldecode( $_GET[ 'token' ] ) );
        $gh_uid = intval( $_GET[ 'user_id' ] );

        $post = [
            'domain'    => site_url(),
            'user_id'   => $gh_uid,
            'token'     => $token
        ];

        $response = wp_remote_post( self::ENDPOINT, array( 'body' => $post ) );

        if ( is_wp_error( $response ) ){
            WPGH()->notices->add( $response );
            return;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ) );

        if ( $this->is_json_error( $json ) ){
            WPGH()->notices->add( $this->get_json_error( $json ) );
            return;
        }

        //todo check if is correct params
        if ( ! isset( $json->dns_records ) ){
            WPGH()->notices->add( 'NO_DNS', 'Could not retrieve DNS records.', 'error' );
            return;
        }

	    /* Don't listen for connect anymore */
        delete_transient( 'gh_listen_for_connect' );

        /* Let WP know we should check for verification stats */
	    wpgh_update_option( 'gh_email_api_check_verify_status', 1 );

	    /* Update relevant options for further requests */
	    wpgh_update_option( 'gh_email_api_user_id', $gh_uid );
	    wpgh_update_option( 'gh_email_api_oauth_token', $token );

	    /* @type $json->dns_records array */
	    /*
	     * [
	     *   [
	     *    'name' => 'abc.'
	     *    'type' => 'CNAME'
	     *    'value' => 'aws.com'
	     *   ],
	     * ]
	     * */
	    wpgh_update_option( 'gh_email_api_dns_records', $json->dns_records );
        WPGH()->notices->add( 'RETRIEVED_DNS', 'Successfully registered your domain.', 'error' );

    }

    /**
     * If the JSON is your typical error response
     *
     * @param $json
     * @return bool
     */
    public function is_json_error( $json ){
        return isset( $json->code ) && isset( $json->message ) && isset( $json->data );
    }

    /**
     * Convert JSON to a WP_Error
     *
     * @param $json
     * @return bool|WP_Error
     */
    public function get_json_error( $json ){
        if ( $this->is_json_error( $json ) ){
            return new WP_Error( $json->code, $json->message, $json->data );
        }

        return false;
    }

    /**
     * Send a request to Groundhogg.io to verify this domains status
     * Request provides domain status, and if verified an email token to use for sending
     */
    public function check_verification_status()
    {
        $token  = wpgh_get_option( 'gh_email_api_oauth_token' );
        $gh_uid = wpgh_get_option( 'gh_email_api_user_id' );

        $post = [
            'domain'    => site_url(),
            'user_id'   => $gh_uid,
            'token'     => $token
        ];

        $response = wp_remote_post( self::ENDPOINT, array( 'body' => $post ) );

        if ( is_wp_error( $response ) ){
            return;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ) );

        if ( $this->is_json_error($json)){
            return;
        }

        /* If we got the token, set it and auto enable */
        if ( isset( $json->token ) ){
        	wpgh_update_option( 'gh_email_token', sanitize_text_field( $json->token ) );
        	wpgh_update_option( 'gh_send_with_gh_api', [ 'on' ] );

        	/* Domain is verified, no longer need to check verification */
        	wpgh_delete_option( 'gh_email_api_check_verify_status' );
	        wp_clear_scheduled_hook( 'groundhogg/service/verify_domain' );
        }
    }

	/**
	 * Returns whether or not the API is currently active.
	 *
	 * @return bool
	 */
    public function email_api_is_active()
    {
        return wpgh_is_option_enabled( 'gh_send_with_gh_api' );
    }

    public function get_dns_table()
    {
        ?>
        <p><?php _ex( 'Your Groundhogg account has been enabled to send emails & text messages! To finish this configuration, please add the following DNS records to your DNS zone.', 'guided_setup', 'groundhogg' ); ?>&nbsp;
            <a target="_blank" href="https://www.google.com/search?q=how+to+add+dns+record"><?php _ex( 'Learn about adding DNS records.', 'guided_setup', 'groundhogg' ); ?></a></p>
        <p><?php _ex( 'After you have added the DNS records your domain will be automatically verified and Groundhogg will start using the Groundhogg sending service.', 'guided_setup', 'groundhogg' ); ?></p>
        <style>
            .full-width{ width: 100%}
            .widefat tr td:nth-child(2), .widefat tr th:nth-child(2){width: 50px;}
        </style>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th><?php _ex( 'Name', 'column_label' , 'groundhogg' ); ?></th>
                <th><?php _ex( 'Type', 'column_label' , 'groundhogg' ); ?></th>
                <th><?php _ex( 'Value', 'column_label', 'groundhogg'  ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $records = wpgh_get_option( 'gh_email_api_dns_records' );
            foreach ( $records as $record ): ?>
                <tr>
                    <td>
                        <input
                            type="text"
                            onfocus="this.select()"
                            class="full-width"
                            value="<?php echo esc_attr( $record->name ); ?>"
                            readonly>
                    </td>
                    <td><?php esc_html_e( $record->type ); ?></td>
                    <td> <input
                            type="text"
                            onfocus="this.select()"
                            class="full-width"
                            value="<?php echo esc_attr( $record->value ); ?>"
                            readonly></td>
                </tr>
            <?php endforeach;?>
            </tbody>
            <tfoot>
            <tr>
                <th><?php _ex( 'Name', 'column_label' , 'groundhogg' ); ?></th>
                <th><?php _ex( 'Type', 'column_label' , 'groundhogg' ); ?></th>
                <th><?php _ex( 'Value', 'column_label', 'groundhogg'  ); ?></th>
            </tr>
            </tfoot>
        </table>
        <?php
    }

    public function show_dns_in_settings()
    {

        if ( ! wpgh_is_option_enabled( 'gh_send_with_gh_api' ) ){
            ?>
            <h2><?php _ex( 'DNS Records', 'settings_page', 'groundhogg' ); ?></h2>
            <div style="max-width: 800px">
                <?php $this->get_dns_table(); ?>
            </div>
            <?php
        }

    }

    /**
     * Send SMS message via Groundhogg service
     *
     * @param $contact WPGH_Contact
     * @param $message string the message to send
     *
     * @return bool|WP_Error
     */
    public function send_sms( $contact, $message )
    {

        if ( ! wpgh_get_option( 'gh_email_token', false ) ){
            return new WP_Error( 'NO_TOKEN', __( 'You require a Groundhogg Sending Service account before you can send SMS.', 'groundhogg' ) );
        }

        if ( ! $contact->is_marketable() ){
            return new WP_Error( 'UNMARKETABLE', __( 'This contact is currently unmarketable.', 'groundhogg' ) );
        }

        //send to groundhogg
        $phone = $contact->get_meta( 'primary_phone' );
        if ( ! $phone ){
            return new WP_Error( 'NO_PHONE', __( 'This contact has no phone.', 'groundhogg' ) );
        }

        $ip = $contact->get_meta( 'ip_address' );
        if ( ! $ip ){
            return new WP_Error( 'NO_IP', __( 'An IP address is required to determine the country code.', 'groundhogg' ) );
        }

        if ( strlen( $message > self::MAX_LENGTH ) ){
            $message = substr( $message, 0, self::MAX_LENGTH );
        }

        $message = sanitize_textarea_field( $message );
        $domain = parse_url( site_url(), PHP_URL_HOST );
        $data = array(
            'token' => md5( wpgh_get_option( 'gh_email_token' ) ),
            'message' => WPGH()->replacements->process( $message, $contact->ID ),
            'sender' => wpgh_get_option( 'gh_business_name', get_bloginfo( 'name' ) ),
            'domain' => $domain,
            'number' => $phone,
            'ip' => $ip
        );

        $url = 'https://www.groundhogg.io/wp-json/gh/aws/v1/send-sms/';

        $request    = wp_remote_post( $url, array( 'body' => $data ) );
        $result     = wp_remote_retrieve_body( $request );
        $result     = json_decode( $result );

        if ( $this->is_json_error( $result ) ){
            $error = $this->get_json_error( $result );
            do_action( 'wpgh_sms_failed', $error );
            return $error;
        }

        if ( ! isset( $result->status ) || $result->status !== 'success' ){
            /* mail failed */
            $error = new WP_Error( $result->code, $result->message );
            $contact->add_note( $result->message );
            do_action( 'wpgh_sms_failed', $error );
            return $error;
        }

        return true;

    }

}