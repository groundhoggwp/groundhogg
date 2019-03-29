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
     * Maximum length for
     */
    const MAX_LENGTH = 280;

    /**
     * List of errors.
     *
     * @var WP_Error[]
     */
    public $errors = [];

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

        if ( is_admin() && isset( $_GET[ 'test_gh_ss_connection' ] ) ){
            add_action( 'init', array( $this, 'send_test_email' ) );
        }

    }

    /**
     * Listen for option change for cron
     *
     * @param $val
     * @return array|false
     */
    public function manage_cron( $val )
    {
        if( $val === 'on' ){
            if( ! wpgh_get_option('gh_enable_cron_ghss') ) {
                $post = [
                    'domain'    => site_url(),
                    'user_id'   => $this->get_gh_uid(),
                ];
                $response = $this->request( 'cron/cron_enable', $post, 'POST' );
                if ( is_wp_error( $response ) ){
                    WPGH()->notices->add( $response );
                }
            }
            return true;
        } else {
            if( wpgh_get_option('gh_enable_cron_ghss') ) {
                $post = [
                    'domain'    => site_url(),
                    'user_id'   => $this->get_gh_uid(),
                ];
                $response = $this->request( 'cron/cron_disable', $post, 'POST' );

                if ( is_wp_error( $response ) ){
                    WPGH()->notices->add( $response );
                }
            }
            return false;
        }
    }


    public function test_connection_ui(){

        if ( wpgh_has_email_token() ){
            ?>
            <a href="<?php echo add_query_arg( 'test_gh_ss_connection', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>" class="button-secondary"><?php _ex( 'Send Test Email', 'action', 'groundhogg' ) ?></a>
            <?php
        }

    }

    /**
     * Send a test email via GH_SS
     */
    public function send_test_email()
    {
        add_action( 'wp_mail_failed', [ $this, 'test_email_failed' ] );

        if ( wpgh_is_option_enabled( 'gh_send_all_email_through_ghss' ) ){
            $result = wp_mail( wp_get_current_user()->user_email, '[TEST] from the Groundhogg Sending Service', 'This is a test message to ensure the Groundhogg Sending Service is working.' );
        } else {
            $result = gh_ss_mail( wp_get_current_user()->user_email, '[TEST] from the Groundhogg Sending Service', 'This is a test message to ensure the Groundhogg Sending Service is working.' );
        }

        remove_action( 'wp_mail_failed', [ $this, 'test_email_failed' ] );

        if ( $result ){
            WPGH()->notices->add( 'mail_success', 'Test Message Sent Successfully!' );
        }
    }

    /**
     * If the test email fails.
     *
     * @param $error WP_Error
     */
    public function test_email_failed( $error ){
        WPGH()->notices->add( $error );
    }

    /**
     * @param string $endpoint the REST endpoint
     * @param array $body the body of the request
     * @param string $method The request method
     * @param array $headers optional headers to override a request
     * @return object|WP_Error
     */
    public function request( $endpoint, $body=[], $method='POST', $headers=[] )
    {

        $method = strtoupper( $method );
        $url = sprintf( 'https://aws.groundhogg.io/wp-json/aws/v1/%s', $endpoint );

        /* Set Default Headers */
        if ( empty( $headers ) ){
            $headers = [
                'Sender-Token' => md5( wpgh_get_option( 'gh_email_token', '' ) ),
                'Sender-Domain' => site_url(),
                'Content-Type' => sprintf( 'application/json; charset=%s', get_bloginfo( 'charset' ) )
            ];
        }

        $body = is_array( $body ) ? wp_json_encode( $body ) : $body;

        $args = [
            'method'        => $method,
            'headers'       => $headers,
            'body'          => $body,
            'data_format'   => 'body',
            'sslverify'     => true
        ];

//        var_dump( $args );
//        die();

        if ( $method === 'GET' ){
            $response = wp_remote_get( $url, $args );
        } else {
            $response = wp_remote_post( $url, $args );
        }

        if ( ! $response ){
            $error = new WP_Error( 'unknown_error', sprintf( 'Failed to initialize remote %s.', $method ), $response );
            $this->add_error( $error );
            return $error;
        }

        if ( is_wp_error( $response ) ){
            $this->add_error( $response );
            return $response;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! $json ){
            $error = new WP_Error( 'unknown_error', sprintf( 'Failed to initialize remote %s.', $method ), wp_remote_retrieve_body( $response )  );
            $this->add_error( $error );
            return $error;
        }

        if ( wpgh_is_json_error( $json ) ){
            $error = wpgh_get_json_error( $json );
            $this->add_error( $error );
            return $error;
        }

        /**
         * Handle this at global scope.
         */
        if ( isset( $response->credits_remaining ) ){

            $credits = intval( $response->credits_remaining );
            wpgh_update_option( 'gh_remaining_api_credits', $credits );
            do_action( "groundhogg/ghss/credits_used", $credits );
        }

        return $json;

    }

    /**
     * Get the number of remaining credits.
     *
     * @return int
     */
    public function get_remaining_credit()
    {
        return intval( wpgh_get_option( 'gh_remaining_api_credits', 0 ) );
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
     * @return int
     */
	public function get_gh_uid()
    {
        return apply_filters( 'groundhogg/service_manager/register_domain/user_id', wpgh_get_option( 'gh_email_api_user_id' ) );
    }

    /**
     * @return string
     */
	public function get_oauth_token()
    {
        return apply_filters( 'groundhogg/service_manager/register_domain/oauth_token', wpgh_get_option( 'gh_email_api_oauth_token' ) );
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

        /* Update relevant options for further requests */
        wpgh_update_option( 'gh_email_api_user_id', $gh_uid );
        wpgh_update_option( 'gh_email_api_oauth_token', $token );

        $result = $this->register_domain( site_url() );

        if ( is_wp_error( $result ) ){
            WPGH()->notices->add( $result );
            return;
        }

        WPGH()->notices->add( 'RETRIEVED_DNS', 'Successfully registered your domain.' );

    }

    /**
     * Register this domain
     * @param $domain string url of the site to register
     * @return bool|WP_Error
     */
    public function register_domain( $domain = '' )
    {

        if ( ! $domain ){
            $domain = site_url();
        }

        /* Use filters to retrieve the UID and TOKEN if whitelabel solution */
        $gh_uid = $this->get_gh_uid();
        $token = $this->get_oauth_token();

        if ( ! $gh_uid || ! $token ){
            return new WP_Error( 'INVALID_CREDENTIALS', 'Missing token or User ID.' );
        }

        $headers = [
            'Oauth-Token' => $token
        ];

        $post = [
            'domain'    => $domain,
            'user_id'   => $gh_uid,
        ];

        $json = $this->request( 'domains/add', $post, 'POST', $headers );

//        var_dump( $response );

        if ( is_wp_error( $json ) ){
            return $json;
        }

        if ( ! isset( $json->dns_records ) ){
            return new WP_Error( 'NO_DNS', 'Could not retrieve DNS records.' );
        }

        /* Don't listen for connect anymore */
        delete_transient( 'gh_listen_for_connect' );

        /* Let WP know we should check for verification stats */
        wpgh_update_option( 'gh_email_api_check_verify_status', 1 );

        /* @type $json->dns_records array */
        wpgh_update_option( 'gh_email_api_dns_records', $json->dns_records );

        /**
         * @var $json object the JSON response from Groundhogg.io
         * @var $gh_uid int the User ID used to login
         * @var $token string the token used to connect.
         */
        do_action( 'groundhogg/service_manager/domain/registered', $json, $gh_uid, $token );

        return true;
    }

    /**
     * If the JSON is your typical error response
     *
     * @deprecated since 1.2.2
     * @param $json
     * @return bool
     */
    public function is_json_error( $json ){
        return isset( $json->code ) && isset( $json->message ) && isset( $json->data );
    }

    /**
     * Convert JSON to a WP_Error
     *
     * @deprecated since 1.2.2
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
        /* Use filters to retrieve the UID and TOKEN if whitelabel solution */
        $gh_uid = $this->get_gh_uid();
        $token = $this->get_oauth_token();

        if ( ! $gh_uid || ! $token ){
            return;
        }

        $headers = [
            'Oauth-Token' => $token
        ];

        $post = [
            'domain'    => site_url(),
            'user_id'   => $gh_uid,
        ];

        $response = $this->request( 'domains/verify', $post, 'POST', $headers );

        if ( is_wp_error( $response ) ){
            return;
        }

        /* If we got the token, set it and auto enable */
        if ( isset( $json->token ) ){
        	wpgh_update_option( 'gh_email_token', sanitize_text_field( $response->token ) );
        	wpgh_update_option( 'gh_send_with_gh_api', [ 'on' ] );

        	/* Domain is verified, no longer need to check verification */
        	wpgh_delete_option( 'gh_email_api_check_verify_status' );
	        wp_clear_scheduled_hook( 'groundhogg/service/verify_domain' );

            do_action( 'groundhogg/service_manager/domain/verified', $response, $gh_uid, $token );
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

    /**
     * Show the DNS Records table
     */
    public function get_dns_table()
    {
        ?>
        <p><?php _ex( 'Your account has been enabled to send emails & text messages! To finish this configuration, please add the following DNS records to your DNS zone.', 'guided_setup', 'groundhogg' ); ?>&nbsp;
            <a target="_blank" href="https://www.google.com/search?q=how+to+add+dns+record"><?php _ex( 'Learn about adding DNS records.', 'guided_setup', 'groundhogg' ); ?></a></p>
        <p><?php _ex( 'After you have added the DNS records your domain will be automatically verified and Groundhogg will start using the Groundhogg Sending Service.', 'guided_setup', 'groundhogg' ); ?></p>
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

    /**
     * Show the DNS table in the settings where the email is located
     */
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
        $data = array(
            'message'       => WPGH()->replacements->process( $message, $contact->ID ),
            'sender'        => wpgh_sanitize_from_name( wpgh_get_option( 'gh_business_name', get_bloginfo( 'name' ) ) ),
            'number'        => $phone,
            'ip'            => $ip,
            'country_code'  => $contact->country
        );

        $response = $this->request( 'sms/send', $data );

        if ( is_wp_error( $response ) ){
            do_action( 'wpgh_sms_failed', $response );
            return $response;
        }

        return true;

    }

    /**
     * @param $wperror WP_Error
     */
    public function add_error( $wperror ){
        if ( $wperror instanceof WP_Error ){
            $this->errors[] = $wperror;
        }
    }

    /**
     * @return bool
     */
    public function has_errors()
    {
        return ! empty( $this->errors );
    }

    /**
     * @return WP_Error[]
     */
    public function get_errors()
    {
        return $this->errors;
    }

    /**
     * @return WP_Error
     */
    public function get_last_error()
    {
        return $this->errors[ count( $this->errors ) - 1 ];
    }



}