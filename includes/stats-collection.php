<?php
namespace Groundhogg;

// TODO Remove old cron "wpgh_do_stats_collection"

class Stats_Collection
{

    const ACTION = 'gh_do_stats_collection';

    public function __construct()
    {
        add_action( 'init', [ $this, 'init' ] );
    }

    public function init()
    {
        if ( $this->is_enabled() ){
            add_action( 'admin_init', [ $this, 'init_cron' ] );
            add_action( self::ACTION, [ $this, 'send_stats' ] );
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

        if ( ! current_user_can( 'manage_options' ) ){
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

        $uid = get_current_user_id();
        $checkout_link = get_user_meta( $uid, 'gh_free_extension_checkout_link', true );

        Plugin::$instance->notices->add( 'opted_in', sprintf( _x( 'Thank you! &rarr; <a href="%s" target="_blank">Use my discount code!</a>', 'notice', 'groundhogg' ), esc_url( $checkout_link ) ) );

        $message = sprintf( __( "Hi %s,

Thank you for opting in to our anonymous usage tracking system. Because of you we will be able to create better WordPress products for you in the near and distant future.
As a thank you, you've received a discount code for 25%% off any premium plan.

Upgrade Now >> [%s]

Note, this discount will expire in 7 days.

We appreciate your help, enjoy!

@ the Groundhogg Team", 'groundhogg' ), wp_get_current_user()->display_name, $checkout_link );

        wp_mail( wp_get_current_user()->user_email, __( '[Groundhogg] Download your free extension.', 'groundhogg' ), $message );
    }

    /**
     * Send the initial request to the GH server and get a response.
     *
     * @return false|\WP_Error|Object
     */
    public function optin()
    {

        $stats = [
            'site_key'  => md5( str_replace( 'www.' , '', parse_url( site_url(), PHP_URL_HOST ) ) ),
            'site_email' => base64_encode( wp_get_current_user()->user_email ),
            'display_name' => base64_encode( wp_get_current_user()->display_name ),
            'is_v3' => true,
        ];

        $response = remote_post_json( 'https://www.groundhogg.io/wp-json/gh/stats/optin/', $stats );

        /* Success */
        if ( ! is_wp_error( $response ) ){

            $json = $response;

            update_option( 'gh_site_key', $stats[ 'site_key' ] );
            update_option( 'gh_opted_in_stats_collection', 1 );

            if ( is_user_logged_in() ){
                update_user_meta( wp_get_current_user()->ID, 'gh_free_extension_checkout_link', esc_url_raw( $json->checkout_link ) );
                update_user_meta( wp_get_current_user()->ID, 'gh_free_extension_discount', sanitize_text_field( $json->discount ) );
            }

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
        $events = get_db( 'events' )->get_table_name();
        $steps  = get_db( 'steps' )->get_table_name();
        $time = time();

        $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE e.time <= $time AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );
        $num_opens = get_db( 'activity' )->count( array( 'end' => $time, 'activity_type' => 'email_opened' ) );
        $num_clicks = get_db( 'activity' )->count( array( 'end' => $time, 'activity_type' => 'email_link_click' ) );

        $stats = [
            'site_key'  => get_option( 'gh_site_key', md5( str_replace( 'www.' , '', parse_url( site_url(), PHP_URL_HOST ) ) ) ),
            'contacts'  => get_db( 'contacts' )->count(),
            'funnels'   => get_db( 'funnels' )->count(),
            'emails'    => get_db( 'emails' )->count(),
            'sent'      => $num_emails_sent,
            'opens'     => $num_opens,
            'clicks'    => $num_clicks,
        ];

        $response = wp_remote_post( 'https://www.groundhogg.io/wp-json/gh/stats/collect/', array( 'body' => $stats ) );

        /* Success */
        if ( ! is_wp_error( $response ) ){

            $body = wp_remote_retrieve_body( $response );
            $json = json_decode( $body );

            if ( is_json_error( $json ) ){

                $error = get_json_error( $json );

                /* Optin if not already and optin enabled via settings... */
                if ( $error->get_error_code() === 'site_unregistered' ){

                    $this->optin();

                }

            }

        }

    }

}