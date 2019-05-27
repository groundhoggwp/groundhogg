<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\floating_phil;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\show_groundhogg_branding;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Start extends Step
{

    public function get_title()
    {
        return _x( 'Get Started!', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'start';
    }

    public function get_settings(){}

    /**
     * @return bool
     */
    public function save()
    {
        return true;
    }

    /**
     * @return void
     */
    public function get_content(){}


    public function get_description(){}

    /**
     * Get default step content.
     *
     * @return void
     */
    public function view()
    {
        ?>
        <div class="big-header" style="text-align: center;margin: 2.5em;">
            <?php if ( show_groundhogg_branding() ): ?>
                <?php floating_phil(); ?>
                <?php groundhogg_logo(); ?>
            <?php else: ?>
                <h1 style="font-size: 40px;"><b><?php _ex( 'Guided Setup', 'guided_setup', 'groundhogg' ); ?></b></h1>
            <?php endif; ?>
        </div>
        <div class="">
            <div class="postbox">
                <div class="inside" style="padding: 30px;">
                    <h2><b><?php _ex( 'Welcome to the Guided Setup', 'guided_setup', 'groundhogg' );?></b></h2>
                    <p><?php _ex( 'Follow these steps to quickly setup Groundhogg for your business. Setup only takes a few minutes. You can always change this information later in the settings page.', 'guided_setup', 'groundhogg' ); ?></p>
                    <?php if ( show_groundhogg_branding() ): ?>
                        <img width="100%" src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-pulling-lever.png'; ?>">
                    <?php endif; ?>
                    <p class="submit" style="text-align: center">
                        <a style="float: right" class="button button-primary" href="<?php printf( admin_url( 'admin.php?page=gh_guided_setup&step=%d' ), 1 ) ?>"><?php _ex( 'Get Started!', 'guided_setup', 'groundhogg' ); ?></a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}