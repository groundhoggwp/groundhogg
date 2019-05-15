<?php
namespace Groundhogg;

// TODO Remove old cron "wpgh_do_stats_collection"

class Stats_Collection
{

    public function __construct()
    {
        add_action( 'groundhogg/init', [ $this, 'init' ] );
    }

    public function init()
    {
        if ( $this->is_enabled() ){
            add_action( 'admin_init', [ $this, 'init_cron' ] );
            add_action( 'gh_do_stats_collection', [ $this, 'send_stats' ] );
        }

        if ( is_admin() && get_request_var( 'action' ) === 'opt_in_to_stats' ){
            add_action( 'admin_init', [ $this, 'stats_tracking_optin' ] );
        }
    }

    public function is_enabled()
    {
        return Plugin::$instance->settings->is_option_enabled( 'gh_opted_in_stats_collection' );
    }

	public function init_cron()
	{
		if ( ! wp_next_scheduled( 'gh_do_stats_collection' )  ){
			wp_schedule_event( time(), 'daily' , 'gh_do_stats_collection' );
		}
	}

	/**
     * Optin to the stats tracker system
     */
    public function stats_tracking_optin()
    {

        if ( ! wp_verify_nonce( get_request_var( '_wpnonce' ), 'opt_in_to_stats' ) || ! current_user_can( 'manage_options' ) ){
            wp_die( new \WP_Error( 'invalid_permissions', _x( 'You have insufficient permissions to do this.', 'notice', 'groundhogg' ) ) );
        }

        $response = $this->optin();

        if ( is_wp_error( $response ) ){

            if ( $response->get_error_code() === 'already_registered' ){
                Plugin::$instance->notices->add( $response->get_error_code(), _x( 'This site was already registered and has been opted back in.', 'notice', 'groundhogg' ), 'success' );
                update_option( 'gh_opted_in_stats_collection', 1 );
            } else {
                Plugin::$instance->notices->add( $response );
            }

            return;
        }

        $discount = sanitize_text_field( $response->discount );

        Plugin::$instance->notices->add( 'opted_in', sprintf( _x( 'You are now signed up! Your discount code is: %s (This code was also sent to %s)', 'notice', 'groundhogg' ), $discount, wp_get_current_user()->user_email ) );

        $message = sprintf( __( "Hi %s,

Thank you for opting in to our anonymous usage tracking system. Because of you we will be able to create better WordPress products for you in the near and distant future.
Your reward discount code for 30%% off any premium extension is: %s
We appreciate your help, best of luck!

@ the Groundhogg Team", 'groundhogg' ), wp_get_current_user()->display_name, $discount );

        wp_mail( wp_get_current_user()->user_email, __( 'Groundhogg Discount Code', 'groundhogg' ), $message );
    }

    /**
     * Send the initial request to the GH server and get a response.
     *
     * @return false|WP_Error|Object
     */
    public function optin()
    {

        $stats = [
            'site_key'  => md5( str_replace( 'www.' , '', parse_url( site_url(), PHP_URL_HOST ) ) ),
            'site_email' => base64_encode( wp_get_current_user()->user_email ),
            'display_name' => base64_encode( wp_get_current_user()->display_name ),
        ];

        $response = wp_remote_post( 'https://www.groundhogg.io/wp-json/gh/stats/optin/', array( 'body' => $stats ) );

        /* Success */
        if ( ! is_wp_error( $response ) ){

            $body = wp_remote_retrieve_body( $response );
            $json = json_decode( $body );

            if ( ! isset( $json->code ) ){
                return new WP_Error( 'optin_error', _x( 'An unknown error occurred', 'notice', 'groundhogg' ) );
            }

            if ( $json->code !== 'success' ){
                return new WP_Error( $json->code, $json->message );
            }

            wpgh_update_option( 'gh_site_key', $stats[ 'site_key' ] );
            wpgh_update_option( 'gh_opted_in_stats_collection', 1 );
            update_user_meta( wp_get_current_user()->ID, 'gh_discount_code', $json->discount );

            return $json;

        }

        return $response;
    }

    /**
     * Continue continuous tracking of the site.
     * Include anonymous site key
     */
    public function send_stats()
    {

        global $wpdb;
        $events = WPGH()->events->table_name;
        $steps  = WPGH()->steps->table_name;
        $time = time();

        $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE e.time <= $time AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );
        $num_opens = WPGH()->activity->count( array( 'end' => $time, 'activity_type' => 'email_opened' ) );
        $num_clicks = WPGH()->activity->count( array( 'end' => $time, 'activity_type' => 'email_link_click' ) );

        $stats = [
            'site_key'  => wpgh_get_option( 'gh_site_key', md5( str_replace( 'www.' , '', parse_url( site_url(), PHP_URL_HOST ) ) ) ),
            'contacts'  =>  WPGH()->contacts->count(),
            'funnels'   => WPGH()->funnels->count(),
            'emails'    => WPGH()->emails->count(),
            'sent'      => $num_emails_sent,
            'opens'     => $num_opens,
            'clicks'    => $num_clicks,
        ];

        $response = wp_remote_post( 'https://www.groundhogg.io/wp-json/gh/stats/collect/', array( 'body' => $stats ) );

        /* Success */
        if ( ! is_wp_error( $response ) ){

            $body = wp_remote_retrieve_body( $response );
            $json = json_decode( $body );

            if ( wpgh_is_json_error( $json ) ){

                $error = wpgh_get_json_error( $json );

                /* Optin if not already and optin enabled via settings... */
                if ( $error->get_error_code() === 'site_unregistered' ){

                    $this->optin();

                }

            }

        }

    }

}