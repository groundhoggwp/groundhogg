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

    private function connect()
    {


    }

    public function get_settings()
    {
        ob_start();

        if ( ! wpgh_is_option_enabled( 'gh_send_with_gh_api' ) ):
        ?>
        <div class="postbox" style="padding-right: 10px">
            <img src="https://www.groundhogg.io/wp-content/uploads/edd/2018/11/email-credits-1024x576.png" width="150" style="float: left; margin: 10px 20px 0 10px;">
            <p><?php _ex( 'You can send your emails using our Groundhogg Delivery System to get faster delivery times and improved deliverability. Get your <b>first 1000 credits free!</b>', 'guided_setup', 'groundhogg' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php printf( 'https://www.groundhogg.io/wp-login.php?doing_oauth=true&redirect_to=%s', urlencode( admin_url( 'admin.php?page=gh_guided_setup&action=connect_to_gh&step=' . $this->get_current_step_id() ) ) ); ?>"><?php _ex( 'Activate', 'guided_setup', 'groundhogg' ); ?></a>
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

        else:


        endif;


        return ob_get_clean();
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

}