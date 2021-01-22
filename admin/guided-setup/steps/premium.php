<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\License_Manager;
use function Groundhogg\dashicon_e;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Premium extends Step
{

    public function get_title()
    {
        return _x( 'Go Premium', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'premium';
    }

    public function get_description()
    {
        return _x( 'Unlock functionality, powerups and integrations by upgrading to premium!', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
        $pricing_url = add_query_arg( [
            'utm_source'    => get_bloginfo(),
            'utm_medium'    => 'guided-setup',
            'utm_campaign'  => 'tracking-optin',
            'utm_content'   => 'description',
        ], 'https://www.groundhogg.io/pricing/' );
        
        $discount = get_user_meta( wp_get_current_user()->ID, 'gh_free_extension_discount', true );

        if ( $discount ){
            $pricing_url = add_query_arg( [ 'discount' => $discount ], $pricing_url );
        }

        ?>
        <style>
            #pricing h3{
                font-size: 16px;
                text-align: center;
            }
            #pricing p{
                text-align: justify;
            }

            #pricing-button {
                display: inline-block;
            }
        </style>
        <div id="pricing">
            <h3><?php _e( "Unlock powerful tools and integrations when you go premium!", 'groundhogg' ); ?></h3>
            <p><?php _e( "Get access to over 30 premium extensions and integrations including WooCommerce, Zapier, Amazon Web Services, LifterLMS, Scheduling and more which will help you build the prefect customer journey." ); ?></p>
            <p style="text-align: center">
                <a id="pricing-button" class="button-primary big-button" href="<?php echo esc_url( $pricing_url ); ?>" target="_blank"><?php dashicon_e( 'star-filled' );_e( 'Yes, I Want To Upgrade!' ); ?></a>
            </p>
            <p class="description"><?php _e('If you requested a discount code from the previous step it will automatically be applied at checkout.', 'groundhogg'); ?></p>
        </div>
        <?php
	}

    protected function step_nav(){}

    public function save()
    {
        return true;
    }

}