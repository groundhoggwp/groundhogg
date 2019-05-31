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