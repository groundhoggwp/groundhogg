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
class Tracking extends Step
{

    public function get_title()
    {
        return _x('Enable Tracking', 'guided_setup', 'groundhogg');
    }

    public function get_slug()
    {
        return 'tracking';
    }

    public function get_description()
    {
        return _x('Want a free extension? You can choose to share non sensitive data about how you use Groundhogg with us and in exchange you will receive one of the following premium extensions on us!', 'guided_setup', 'groundhogg');
    }

    /**
     * - Form Styling (https://www.groundhogg.io/downloads/form-styling/)
    - Content Restriction (https://www.groundhogg.io/downloads/content-restriction/)
    - Email Countdown Timers (https://www.groundhogg.io/downloads/countdown/)
    - SMTP (https://www.groundhogg.io/downloads/smtp/)
     */
    public function get_content()
    {

        echo html()->e( 'style', [], ".radio{display:block;margin:10px;}" );

        echo html()->wrap([
            html()->e( 'label', [ 'class' => 'radio' ], [
                html()->input([
                    'name' => 'extension_choice',
                    'type' => 'radio',
                    'class' => '',
                    'value' => 'smtp',
                    'checked' => false,
                ]),
                " ",
                html()->e( 'b', [],__( 'SMTP Add-On' ) ),
                '. ',
                html()->e( 'a', [ 'href' => 'https://www.groundhogg.io/downloads/smtp/', 'target' => '_blank' ], __( 'More details &rarr;' ) ),
            ] ),
            html()->e( 'label', [ 'class' => 'radio' ], [
                html()->input([
                    'name' => 'extension_choice',
                    'type' => 'radio',
                    'class' => '',
                    'value' => 'styling',
                    'checked' => false,
                ]),
                " ",
                html()->e( 'b', [],__( 'Form Styling Add-On' ) ),
                '. ',
                html()->e( 'a', [ 'href' => 'https://www.groundhogg.io/downloads/form-styling/', 'target' => '_blank' ], __( 'More details &rarr;' ) ),
            ] ),
            html()->e( 'label', [ 'class' => 'radio' ], [
                html()->input([
                    'name' => 'extension_choice',
                    'type' => 'radio',
                    'class' => '',
                    'value' => 'restriction',
                    'checked' => false,
                ]),
                " ",
                html()->e( 'b', [],__( 'Content Restriction Add-On' ) ),
                '. ',
                html()->e( 'a', [ 'href' => 'https://www.groundhogg.io/downloads/content-restriction/', 'target' => '_blank' ], __( 'More details &rarr;' ) ),
            ] ),
            html()->e( 'label', [ 'class' => 'radio' ], [
                html()->input([
                    'name' => 'extension_choice',
                    'type' => 'radio',
                    'class' => '',
                    'value' => 'timers',
                    'checked' => false,
                    'required' => true
                ]),
                " ",
                html()->e( 'b', [],__( 'Email Countdown Timers Add-On' ) ),
                '. ',
                html()->e( 'a', [ 'href' => 'https://www.groundhogg.io/downloads/countdown/', 'target' => '_blank' ], __( 'More details &rarr;' ) ),
            ] ),
        ], 'div', ['style' => [
            'padding' => '10px'
        ]]);

        echo html()->wrap(html()->checkbox([
            'label' => __('Yes, send me a discount code to receive my free extension.'),
            'name' => 'enable_tracking',
            'id' => 'enable_tracking',
            'value' => '1',
            'checked' => false,
            'required' => true,
        ]), 'div', ['style' => [
            'font-weight' => '500',
            'padding' => '20px'
        ]]);

        echo html()->description(sprintf(__('A discount code will be emailed to %s upon completion.', 'groundhogg'), wp_get_current_user()->user_email));
    }

    public function save()
    {

        if (get_request_var('enable_tracking')) {

            set_transient( 'extension_choice', sanitize_text_field( get_request_var( 'extension_choice' ) ), HOUR_IN_SECONDS );

            Plugin::$instance->stats_collection->stats_tracking_optin();
        }

        return true;
    }

}