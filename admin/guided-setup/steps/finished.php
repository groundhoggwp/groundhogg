<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\Plugin;
use function Groundhogg\show_groundhogg_branding;

class Finished extends Step
{

    public function get_title()
    {
        return _x( 'All Done!', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'finished';
    }

    public function get_description()
    {
        return _x( 'Congratulations! Groundhogg has been successfully setup. Here are your next steps...', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {

        update_option( 'gh_guided_setup_finished', 1 );

        if ( ! Plugin::$instance->stats_collection->is_enabled() ): ?>
        <div class="postbox" style="padding: 0 10px 0 20px">
            <h3><?php _e( 'Get a free extension when you help us make Groundhogg better!', 'Groundhogg' ); ?></h3>
            <p>
                <a class="button button-primary" href="<?php echo wp_nonce_url( $_SERVER[ 'REQUEST_URI' ] . '&action=opt_in_to_stats' , 'opt_in_to_stats' ); ?>" ><?php _e( 'Yes, I want to help make Groundhogg better!' ); ?></a>
                <a href="https://www.groundhogg.io/privacy-policy/#usage-tracking" target="_blank"><?php _e( 'Learn more', 'groundhogg' ); ?></a>
            </p>
            <p><?php _e( "Want a free extension? You can choose to share non sensitive data about how you use Groundhogg with us and in exchange you will receive a premium extension on us!", 'groundhogg' ); ?></p>
        </div>
        <?php endif;

        if ( show_groundhogg_branding() ): ?>
            <iframe style="border: 3px solid #e5e5e5" src="https://player.vimeo.com/video/339379046" width="538" height="303" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        <?php endif;
    }

    protected function step_nav()
    {
        $html = Plugin::$instance->utils->html;

        echo $html->wrap( __( '&larr; Back', 'groundhogg' ), 'a', [
            'class' => 'button button-secondary',
            'style' => [
                'float' => 'left'
            ],
            'href' => $this->prev_step_url()
        ]);

        echo $html->wrap( __( 'Complete Setup!', 'groundhogg' ), 'a', [
            'class' => 'button button-primary',
            'style' => [
                'float' => 'right',
            ],
            'href' => admin_url( 'admin.php?page=groundhogg' )
        ]);
    }

    public function save()
    {
        // TODO: Implement save() method.
    }
}