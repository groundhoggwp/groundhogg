<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_3 extends WPGH_Guided_Setup_Step
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
        if ( ! wpgh_is_option_enabled( 'gh_email_api_dns_records' ) ):
        ?>
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
        <div class="postbox" style="padding-right: 10px">
            <img src="https://ps.w.org/wp-mail-smtp/assets/banner-772x250.png?rev=1982773" width="300" style="float: left; margin: 10px 20px 0 10px;">
            <p><?php _ex( 'You can send your emails using an <b>SMTP Service</b> using an SMTP plugin like WP Mail SMTP. This is recommended if you do not use our service.', 'guided_setup', 'groundhogg' ); ?></p>
            <p>
                <a target="_blank" class="button button-primary" href="https://wordpress.org/plugins/wp-mail-smtp/"><?php _ex( 'Get WP Mail SMTP', 'guided_setup', 'groundhogg' ); ?></a>
                <a target="_blank" class="button button-secondary" href="https://wordpress.org/plugins/search/smtp/"><?php _ex( 'Browse Others...', 'guided_setup', 'groundhogg' ); ?></a>
            </p>
            <div class="wp-clearfix"></div>
        </div>
        <?php

        /* They have */
        else:
        ?>
        <p><?php _ex( 'Your Groundhogg account has been enabled to send emails & text messages! To finish this configuration, please add the following DNS records to your DNS zone.', 'guided_setup', 'groundhogg' ); ?></p>
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
                <td><?php esc_html_e( $record[ 'name' ] ); ?></td>
                <td><?php esc_html_e( $record[ 'type' ] ); ?></td>
                <td><?php esc_html_e( $record[ 'value' ] ); ?></td>
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
	        set_transient( 'gh_listen_for_connect', true, HOUR_IN_SECONDS );
	        wp_redirect( $redirect_to );
	        die();
        }

        return true;
    }

}