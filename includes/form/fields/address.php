<?php
namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

class Address extends Input
{

    public function get_default_args()
    {
        return [
            'label'         => _x( 'Address *', 'form_default', 'groundhogg' ),
            'class'         => 'gh-address',
            'enabled'       => 'all',
            'name_prefix'   => '',
            'required'      => false,
        ];
    }

    /**
     * Get the name of the shortcode
     *
     * @return string
     */
    public function get_shortcode_name()
    {
        return 'address';
    }

    public function render()
    {



    }
}