<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\SendWp;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */
class Email extends Step
{

    public function get_title()
    {
        return _x('Sending Email', 'guided_setup', 'groundhogg');
    }

    public function get_slug()
    {
        return 'email_info';
    }

    public function scripts()
    {
        wp_enqueue_script( 'groundhogg-sendwp' );
    }

    public function get_description()
    {
        if (!Plugin::$instance->sending_service->has_dns_records()):
            return _x('See below to setup the Groundhogg Sending Service.', 'guided_setup', 'groundhogg');
        else:
            return _x('There are different ways to send email with Groundhogg! Choose one below.', 'guided_setup', 'groundhogg');
        endif;
    }

    public function get_content()
    {

        /* Will check to see if they've gone through the process */
        if (Plugin::$instance->sending_service->has_dns_records()):
            Plugin::$instance->sending_service->get_dns_table();
            return;

        endif;

        SendWp::instance()->output_css();

        ?>
        <style type="text/css">
            #groundhogg-sendwp-connect {
                display: block;
                margin: 20px auto 20px auto;
                padding: 8px 14px;
            }
            #connect-send-wp h3{
                text-align: center;
            }
            #connect-send-wp p{
                font-size: 14px;
            }
            #connect-send-wp{
                margin: 60px auto;
            }
        </style>

        <div id="connect-send-wp">
            <h3 id="connect-send-wp-h3"><?php _e( 'Never worry about email deliverability again!' ); ?></h3>
            <?php

            SendWp::instance()->output_connect_button();
            SendWp::instance()->output_js();
            ?>
            <p id="connect-send-wp-p"><?php _e( '<a href="https://sendwp.com/" target="_blank">SendWP</a> makes WordPress email delivery as simple as a few clicks. Send unlimited email, just <b>$9/month</b>.', 'groundhogg' ); ?></p>
        </div>
        <h3><?php _e( 'Alternatives' ); ?></h3>
        <style>
            .premium-smtp-plugins .postbox {
                width: 49%;
                display: inline-block;
            }
        </style>
        <div class="premium-smtp-plugins">
            <?php

            $smtp_plugins = License_Manager::get_store_products(array(
                'tag' => [150],
            ));

            foreach ($smtp_plugins->products as $plugin) {
                License_Manager::extension_to_html($plugin);
            }

            ?>
        </div>
        <?php
    }

    /**
     * Listen for the que to redirect to Groundhogg's Oauth Method.
     *
     * @return bool
     */
    public function save()
    {
        return true;
    }

}