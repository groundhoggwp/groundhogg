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
        return _x( 'Enable Tracking', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'tracking';
    }

    public function get_description()
    {
        return _x( 'Want a free extension? You can choose to share non sensitive data about how you use Groundhogg with us and in exchange you will receive a premium extension on us!', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {


        echo html()->wrap( html()->checkbox( [
            'label'         => __( 'Yes, send me a discount code to receive a free extension.' ),
            'name'          => 'enable_tracking',
            'id'            => 'enable_tracking',
            'value'         => '1',
            'checked'       => false,
            'required'      => false,
        ] ), 'div', [ 'style' => [
                'text-align' => 'center',
            'font-weight' => '500',
            'padding' => '20px'
        ] ] );

        echo html()->description( sprintf( __( 'A discount code will be emailed to %s upon completion.', 'groundhogg' ), wp_get_current_user()->user_email ) );
    }

    public function save()
    {

        if ( get_request_var( 'enable_tracking' ) ){
            Plugin::$instance->stats_collection->stats_tracking_optin();
        }

        return true;
    }

}