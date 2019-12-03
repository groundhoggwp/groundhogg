<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\dashicon;
use function Groundhogg\floating_phil;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\html;
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

    protected function step_nav()
    {
        echo html()->wrap( dashicon( 'admin-tools' ) . __( 'Start Guided Setup', 'groundhogg' ), 'button', [
            'class' => 'button button-primary big-button next-button',
            'type'  => 'submit',
        ] );
    }

    /**
     * Get default step content.
     *
     * @return void
     */
    public function view()
    {

        echo html()->input( array(
            'type' => 'hidden',
            'name' => 'guided_setup_step_save',
            'value' => $this->get_slug(),
        )); ?>
        <div class="big-header" style="text-align: center;margin: 2.5em;">
            <?php if ( show_groundhogg_branding() ): ?>
                <?php floating_phil(); ?>
                <?php groundhogg_logo(); ?>
            <?php else: ?>
                <h1 style="font-size: 40px;"><b><?php _ex( 'Guided Setup', 'guided_setup', 'groundhogg' ); ?></b></h1>
            <?php endif; ?>
        </div>
        <h1></h1>
        <div class="setup-wrap">
            <div class="postbox">
                <div class="inside">

                    <h3 style="text-align: center"><?php _e( 'Welcome to Groundhogg!', 'groundhogg' ); ?></h3>
                    <div class="description">
                        <p><?php _ex( 'Please take 5 minutes to complete a few steps so we can properly configure Groundhogg for you.', 'guided_setup', 'groundhogg' ); ?></p>
                    </div>
                    <div class="step-nav">
                        <?php $this->step_nav(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}