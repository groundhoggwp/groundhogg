<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */
class Tracking extends Step
{

    public function get_title()
    {
        return _x('Enable Statistics', 'guided_setup', 'groundhogg');
    }

    public function get_slug()
    {
        return 'tracking';
    }

    public function get_description()
    {
        return _x('Help us make Groundhogg better! Enable anonymous statistics collection.', 'guided_setup', 'groundhogg');
    }

    /**
     * - Form Styling (https://www.groundhogg.io/downloads/form-styling/)
    - Content Restriction (https://www.groundhogg.io/downloads/content-restriction/)
    - Email Countdown Timers (https://www.groundhogg.io/downloads/countdown/)
    - SMTP (https://www.groundhogg.io/downloads/smtp/)
     */
    public function get_content()
    {

        $pricing_url = add_query_arg( [
            'utm_source'    => get_bloginfo(),
            'utm_medium'    => 'guided-setup',
            'utm_campaign'  => 'tracking-optin',
            'utm_content'   => 'description',
        ], 'https://www.groundhogg.io/pricing/' );

        ?>
        <style>
            #enable-tracking h3{
                text-align: center;
            }
            #enable-tracking p{
                text-align: justify;
            }

            #enabled-tracking-button {
                display: block;
            }
        </style>
        <div id="enable-tracking">
            <h3><?php _e( 'Want 25% off any premium plan?', 'groundhogg' ); ?></h3>
            <p><?php printf( __( "When you enable anonymous statistics collection, you help us make Groundhogg better. As a thank you we'll send you a 25%% discount code which you can use for any <a href='%s' target='_blank'>Groundhogg premium plan.</a>", 'groundhogg' ), $pricing_url ); ?></p>
            <button id="enabled-tracking-button" type="submit" class="button-primary big-button" name="enable_tracking" value="enable"><?php dashicon_e( 'yes' );_e( 'Yes! I Want <b>25% Off!</b>' ); ?></button>
            <p class="description"><?php echo sprintf(__('A discount code will be emailed to %s upon completion.', 'groundhogg'), wp_get_current_user()->user_email ); ?></p>
        </div>
        <?php
    }

    public function save()
    {
        if ( get_request_var('enable_tracking') === 'enable' ) {
            Plugin::$instance->stats_collection->stats_tracking_optin();
        }

        return true;
    }

}