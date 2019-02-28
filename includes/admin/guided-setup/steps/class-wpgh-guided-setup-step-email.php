<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Email extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Sending Email', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'email_info';
    }

    public function get_description()
    {
        return _x( 'There are lots of ways to send email with Groundhogg! Choose one below.', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {
        ob_start();

        /* Will check to see if they've gone through the process */
        if ( ! wpgh_get_option( 'gh_email_api_dns_records', false ) ):
        ?>
        <h3><?php _e( 'Recommended' ); ?></h3>
        <div class="postbox" style="padding-right: 10px">
            <img src="https://www.groundhogg.io/wp-content/uploads/edd/2018/11/email-credits-1024x576.png" width="150" style="float: left; margin: 10px 20px 0 10px;">
            <p><?php _ex( 'You can send your emails & text messages using our Groundhogg Delivery System to get faster delivery times and improved deliverability. Get your <b>first 1000 credits free!</b>', 'guided_setup', 'groundhogg' ); ?></p>
            <p>
                <?php submit_button( _x( 'Activate', 'guided_setup', 'groundhogg' ), 'primary', 'gh_active_email', false ); ?>
                <a target="_blank" class="button button-secondary" href="https://www.groundhogg.io/register/"><?php _ex( 'Create Your Account', 'guided_setup', 'groundhogg' ); ?></a>
                <a target="_blank" class="button button-secondary" href="https://www.groundhogg.io/downloads/email-credits/"><?php _ex( 'Learn more...', 'guided_setup', 'groundhogg' ); ?></a>
            </p>
            <div class="wp-clearfix"></div>
        </div>
        <hr>
        <h3><?php _e( 'Alternatives' ); ?></h3>
        <div class="postbox" style="padding-right: 10px">
            <img src="https://ps.w.org/wp-mail-smtp/assets/banner-772x250.png?rev=1982773" width="300" style="float: left; margin: 10px 20px 0 10px;border: 1px solid #ededed">
            <p><?php _ex( 'You can send your emails using an <b>SMTP Service</b> using an SMTP plugin like WP Mail SMTP. This is recommended if you do not use our service.', 'guided_setup', 'groundhogg' ); ?></p>
            <p>
                <a target="_blank" class="button button-primary" href="https://wordpress.org/plugins/wp-mail-smtp/"><?php _ex( 'Get WP Mail SMTP', 'guided_setup', 'groundhogg' ); ?></a>
                <a target="_blank" class="button button-secondary" href="https://wordpress.org/plugins/search/smtp/"><?php _ex( 'Browse Others...', 'guided_setup', 'groundhogg' ); ?></a>
            </p>
            <div class="wp-clearfix"></div>
        </div><div class="postbox" style="padding-right: 10px">
            <img src="https://ps.w.org/wp-ses/assets/banner-772x250.png?rev=2012130" width="300" style="float: left; margin: 10px 20px 0 10px;">
            <p><?php _ex( 'You can send your emails using <b>Amazon SES</b> which is very cost effective and provides a high deliverability rating, although is more difficult to setup.', 'guided_setup', 'groundhogg' ); ?></p>
            <p>
                <a target="_blank" class="button button-primary" href="https://wordpress.org/plugins/wp-ses/"><?php _ex( 'Get WP SES', 'guided_setup', 'groundhogg' ); ?></a>
                <a target="_blank" class="button button-secondary" href="https://wordpress.org/plugins/search/SES/"><?php _ex( 'Browse Others...', 'guided_setup', 'groundhogg' ); ?></a>
            </p>
            <div class="wp-clearfix"></div>
        </div>
        <?php

        /* They have */
        else:

            WPGH()->service_manager->get_dns_table();

        endif;

        return ob_get_clean();
    }

	/**
     * Listen for the que to redirect to Groundhogg's Oauth Method.
     *
	 * @return bool
	 */
    public function save()
    {
        if ( isset( $_POST[ 'gh_active_email' ] ) ){
	        $redirect_to = sprintf( 'https://www.groundhogg.io/wp-login.php?doing_oauth=true&redirect_to=%s', urlencode( admin_url( 'admin.php?page=gh_guided_setup&action=connect_to_gh&step=' . $this->get_current_step_id() ) ) );
	        set_transient( 'gh_listen_for_connect', 1, HOUR_IN_SECONDS );
	        wp_redirect( $redirect_to );
	        die();
        }

        return true;
    }

}