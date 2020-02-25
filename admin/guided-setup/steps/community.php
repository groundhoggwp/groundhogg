<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Community extends Step
{

    public function get_title()
    {
        return _x( 'Community', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'community';
    }

    public function get_description()
    {
        return _x( 'Join our online community and be a part of our global movement to democratize digital marketing & sales.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {
        ?>
        <style>
            #socials{
                margin-bottom: 50px;
            }
            #socials .social-button{
                display: inline-block;
                float: right;
                font-size: 16px;
                height: auto;
                margin-left: 20px;
                padding: 8px 14px;
            }
        </style>

        <div id="socials">
        <?php

        echo html()->e( 'style', [], '.button .dashicons { vertical-align: middle;}.title{font-size: 18px;padding: 0;font-weight:600;margin: 1em 0 1em 0;line-height: 1.4;}' );
        echo html()->e( 'div', [ 'class' => 'title'  ], 'Support Group' );
        echo html()->e( 'a', [ 'href' => 'https://www.facebook.com/groups/groundhoggwp/', 'class' => 'button button-secondary social-button', 'target' => '_blank' ], '<span class="dashicons dashicons-facebook"></span> Join the group now!' );
        echo html()->e( 'p', [], 'Our support group is where you can crowd-source support from our awesome user community!' );

        echo html()->e( 'div', [ 'class' => 'title'  ], 'Facebook' );
        echo html()->e( 'a', [ 'href' => 'https://www.facebook.com/groundhoggwp/', 'class' => 'button button-secondary social-button', 'target' => '_blank'], '<span class="dashicons dashicons-facebook-alt"></span> Like us on Facebook!' );
        echo html()->e( 'p', [], 'Get inspiration from our Facebook page as we share podcasts, tutorials, and how to guides.' );

        echo html()->e( 'div', [ 'class' => 'title' ], 'Twitter' );
        echo html()->e( 'a', [ 'href' => 'https://twitter.com/Groundhoggwp', 'class' => 'button button-secondary social-button', 'target' => '_blank' ], '<span class="dashicons dashicons-twitter"></span> Follow us on Twitter!' );
        echo html()->e( 'p', [], 'Get promotions, important news, and general updates by staying up to date with us on Twitter.' );

        echo html()->e( 'div', [ 'class' => 'title' ], 'Youtube' );
        echo html()->e( 'a', [ 'href' => 'https://www.youtube.com/channel/UChHW8I3wPv-KUhQYX-eUp6g', 'class' => 'button button-secondary social-button', 'target' => '_blank' ], '<span class="dashicons dashicons-playlist-video"></span> Subscribe to us on YouTube!' );
        echo html()->e( 'p', [], 'Watch tutorials, how to\'s, guides, and podcasts on our official Youtube channel.' );
        ?></div><?php

    }

    public function save() {
        return true;
    }

}